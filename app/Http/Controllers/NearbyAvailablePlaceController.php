<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\ServiceSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NearbyAvailablePlaceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $coordinates = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ]);

        $now = now();
        $today = $now->toDateString();
        $dayOfWeek = $now->isoWeekday();
        $activeBookingStatuses = ['held', 'payment_submitted', 'rejected', 'confirmed'];

        $places = BusinessPlace::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereHas('services', static function (Builder $serviceQuery) use ($today, $dayOfWeek): void {
                $serviceQuery->where('is_active', true)
                    ->whereDoesntHave('closures', static function (Builder $closureQuery) use ($today): void {
                        $closureQuery->whereDate('closure_date', $today)->where('is_active', true);
                    })
                    ->whereHas('schedules', static function (Builder $scheduleQuery) use ($dayOfWeek): void {
                        $scheduleQuery->where('is_active', true)
                            ->where('is_closed', false)
                            ->where('day_of_week', $dayOfWeek);
                    });
            })
            ->with([
                'services' => static function ($query) use ($today, $dayOfWeek, $activeBookingStatuses): void {
                    $query->where('is_active', true)
                        ->whereDoesntHave('closures', static function (Builder $closureQuery) use ($today): void {
                            $closureQuery->whereDate('closure_date', $today)->where('is_active', true);
                        })
                        ->with([
                            'schedules' => static function ($scheduleQuery) use ($dayOfWeek, $today, $activeBookingStatuses): void {
                                $scheduleQuery->where('is_active', true)
                                    ->where('is_closed', false)
                                    ->where('day_of_week', $dayOfWeek)
                                    ->with(['bookings' => static function ($bookingQuery) use ($today, $activeBookingStatuses): void {
                                        $bookingQuery->whereDate('booking_date', $today)
                                            ->whereIn('status', $activeBookingStatuses)
                                            ->where(static function (Builder $query): void {
                                                $query->where('status', '!=', 'held')->orWhere('expires_at', '>', now());
                                            });
                                    }]);
                            },
                        ])
                        ->orderBy('name');
                },
            ])
            ->get();

        $nearbyPlaces = $places
            ->map(function (BusinessPlace $place) use ($coordinates, $now): array {
                $distance = $this->distanceInKilometers(
                    (float) $coordinates['latitude'],
                    (float) $coordinates['longitude'],
                    (float) $place->latitude,
                    (float) $place->longitude,
                );

                return [
                    'id' => $place->id,
                    'url' => route('places.show', $place),
                    'name' => $place->name,
                    'address' => $place->address,
                    'distance' => round($distance, 1),
                    'mapsUrl' => $place->google_maps_url,
                    'services' => $place->services
                        ->filter(fn ($service): bool => $service->schedules->contains(fn (ServiceSchedule $schedule): bool => $this->hasAvailableSlotToday($schedule, $now)))
                        ->pluck('name')
                        ->values(),
                ];
            })
            ->filter(static fn (array $place): bool => $place['services']->isNotEmpty())
            ->sortBy('distance')
            ->take(6)
            ->values()
            ->map(static function (array $place): array {
                $place['services'] = $place['services']->all();

                return $place;
            });

        return response()->json([
            'places' => $nearbyPlaces,
            'checkedAt' => $now->toIso8601String(),
        ]);
    }

    private function hasAvailableSlotToday(ServiceSchedule $schedule, Carbon $now): bool
    {
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);

        for ($slotStart = $scheduleStart->copy(); $slotStart->copy()->addHour()->lte($scheduleEnd); $slotStart->addHour()) {
            if ($slotStart->lte($now)) {
                continue;
            }

            $slotEnd = $slotStart->copy()->addHour();
            $isBooked = $schedule->bookings->contains(static function (Booking $booking) use ($slotStart, $slotEnd): bool {
                return Carbon::parse($booking->start_time)->lt($slotEnd)
                    && Carbon::parse($booking->end_time)->gt($slotStart);
            });

            if (! $isBooked) {
                return true;
            }
        }

        return false;
    }

    private function distanceInKilometers(float $latitude, float $longitude, float $placeLatitude, float $placeLongitude): float
    {
        $earthRadius = 6371;
        $latitudeDelta = deg2rad($placeLatitude - $latitude);
        $longitudeDelta = deg2rad($placeLongitude - $longitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($placeLatitude)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
