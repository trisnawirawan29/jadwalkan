<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\ServiceSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        Booking::cleanupExpiredHolds();
        $filters = $request->validate([
            'view' => ['nullable', 'in:upcoming,history'],
            'status' => ['nullable', 'in:held,payment_submitted,confirmed,rejected'],
            'booking_date' => ['nullable', 'date', 'date_format:Y-m-d'],
            'search' => ['nullable', 'string', 'max:100'],
            'month' => ['nullable', 'date_format:Y-m'],
            'display' => ['nullable', 'in:list,calendar'],
        ]);
        $view = $filters['view'] ?? 'upcoming';
        $display = $filters['display'] ?? 'calendar';
        $applyBookingFilters = function ($query) use ($filters) {
            return $query
                ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
                ->when($filters['booking_date'] ?? null, fn ($query, string $date) => $query->whereDate('booking_date', $date))
                ->when($filters['search'] ?? null, function ($query, string $search): void {
                    $query->where(function ($searchQuery) use ($search): void {
                        $searchQuery->where('booking_code', 'like', "%{$search}%")
                            ->orWhereHas('businessService', function ($serviceQuery) use ($search): void {
                                $serviceQuery->where('name', 'like', "%{$search}%")
                                    ->orWhereHas('businessPlace', fn ($placeQuery) => $placeQuery->where('name', 'like', "%{$search}%"));
                            });
                    });
                });
        };
        $applyBookingPeriod = function ($query) use ($view) {
            return $query->where(function ($query) use ($view): void {
                if ($view === 'history') {
                    $query->whereDate('booking_date', '<', today())
                        ->orWhere(function ($todayQuery): void {
                            $todayQuery->whereDate('booking_date', today())
                                ->where('end_time', '<=', now()->format('H:i:s'));
                        });

                    return;
                }

                $query->whereDate('booking_date', '>', today())
                    ->orWhere(function ($todayQuery): void {
                        $todayQuery->whereDate('booking_date', today())
                            ->where('end_time', '>', now()->format('H:i:s'));
                    });
            });
        };
        $bookingQuery = $applyBookingFilters($request->user()->bookings()->with(['businessService.businessPlace', 'serviceSchedule']));

        if ($display === 'list' && empty($filters['booking_date'] ?? null)) {
            $applyBookingPeriod($bookingQuery);
        }

        $bookings = $bookingQuery
            ->when($view === 'history', fn ($query) => $query->orderByDesc('booking_date')->orderByDesc('start_time'), fn ($query) => $query->orderBy('booking_date')->orderBy('start_time'))
            ->paginate(10)
            ->withQueryString();

        $calendarMonth = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $calendarBookingsQuery = $applyBookingFilters($request->user()->bookings()->with(['businessService.businessPlace', 'serviceSchedule']))
            ->whereBetween('booking_date', [$calendarMonth->toDateString(), $calendarMonth->copy()->endOfMonth()->toDateString()]);

        $calendarBookings = $calendarBookingsQuery->orderBy('start_time')->get();
        $calendarBookingsByDate = $calendarBookings->groupBy(fn (Booking $booking): string => $booking->booking_date->toDateString());
        $calendarDays = [];
        $calendarDay = $calendarMonth->copy()->startOfWeek(Carbon::MONDAY);
        $calendarEnd = $calendarMonth->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        while ($calendarDay->lte($calendarEnd)) {
            $calendarDays[] = $calendarDay->copy();
            $calendarDay->addDay();
        }

        return view('bookings.index', compact('bookings', 'filters', 'view', 'display', 'calendarMonth', 'calendarDays', 'calendarBookings', 'calendarBookingsByDate'));
    }

    public function store(Request $request, BusinessPlace $businessPlace): RedirectResponse
    {
        abort_unless($businessPlace->is_active, 404);

        $data = $request->validate([
            'service_schedule_id' => ['required', 'integer'],
            'booking_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $schedule = ServiceSchedule::query()
            ->whereKey($data['service_schedule_id'])
            ->where('is_active', true)
            ->where('is_closed', false)
            ->whereHas('businessService', static function (Builder $query) use ($businessPlace): void {
                $query->where('business_place_id', $businessPlace->id)->where('is_active', true);
            })
            ->with('businessService')
            ->firstOrFail();

        $bookingDate = Carbon::createFromFormat('Y-m-d', $data['booking_date']);
        if ((int) $bookingDate->isoWeekday() !== $schedule->day_of_week) {
            throw ValidationException::withMessages(['booking_date' => 'Tanggal tidak sesuai dengan hari pada jadwal yang dipilih.']);
        }

        $startTime = Carbon::createFromFormat('H:i', $data['start_time']);
        $endTime = Carbon::createFromFormat('H:i', $data['end_time']);
        $scheduleStart = Carbon::parse($schedule->start_time);
        $scheduleEnd = Carbon::parse($schedule->end_time);
        $durationMinutes = $startTime->diffInMinutes($endTime, false);

        if ($durationMinutes <= 0 || $durationMinutes % 60 !== 0) {
            throw ValidationException::withMessages(['end_time' => 'Durasi booking harus berupa kelipatan satu jam.']);
        }

        if ($startTime->lt($scheduleStart) || $endTime->gt($scheduleEnd)) {
            throw ValidationException::withMessages(['start_time' => 'Jam booking harus berada di dalam jam operasional layanan.']);
        }

        if ($schedule->businessService->closures()->whereDate('closure_date', $bookingDate)->where('is_active', true)->exists()) {
            throw ValidationException::withMessages(['booking_date' => 'Layanan tutup pada tanggal tersebut. Silakan pilih tanggal lain.']);
        }

        $totalCost = 0;
        $schedule->businessService->loadMissing('hourlyPrices');
        $hourlyPrices = $schedule->businessService->hourly_price_map;
        $dayPrices = $hourlyPrices[(string) $bookingDate->isoWeekday()] ?? [];
        $priceCursor = $startTime->copy();
        while ($priceCursor->lt($endTime)) {
            $time = $priceCursor->format('H:i');
            $totalCost += (float) ($dayPrices[$time] ?? $hourlyPrices[$time] ?? $schedule->businessService->price_per_hour);
            $priceCursor->addHour();
        }

        $booking = DB::transaction(function () use ($data, $request, $schedule, $bookingDate, $startTime, $endTime, $totalCost): Booking {
            $existingBookings = Booking::query()
                ->where('business_service_id', $schedule->business_service_id)
                ->whereDate('booking_date', $bookingDate)
                ->whereIn('status', ['held', 'payment_submitted', 'rejected', 'confirmed'])
                ->lockForUpdate()
                ->get();

            foreach ($existingBookings as $existingBooking) {
                if ($existingBooking->status === 'held' && $existingBooking->expires_at?->isPast()) {
                    $existingBooking->delete();

                    continue;
                }

                if ($existingBooking->service_schedule_id === $schedule->id
                    && $existingBooking->start_time < $endTime->format('H:i:s')
                    && $existingBooking->end_time > $startTime->format('H:i:s')) {
                    throw ValidationException::withMessages(['service_schedule_id' => 'Jadwal tersebut baru saja dipesan. Silakan pilih jadwal lain.']);
                }
            }

            return Booking::create([
                'user_id' => $request->user()->id,
                'business_service_id' => $schedule->business_service_id,
                'service_schedule_id' => $schedule->id,
                'booking_date' => $bookingDate->toDateString(),
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'total_cost' => round($totalCost, 2),
                'booking_code' => 'BK-'.Str::upper(Str::random(10)),
                'status' => 'held',
                'expires_at' => now()->addMinutes(10),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('bookings.show', $booking)->with('success', 'Jadwal berhasil ditahan selama 10 menit. Selesaikan pembayaran untuk mengonfirmasi booking.');
    }

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if ($booking->status === 'held' && $booking->expires_at?->isPast()) {
            $booking->delete();

            return redirect()->route('bookings.index')->withErrors(['booking' => 'Waktu hold 10 menit telah berakhir. Booking dihapus.']);
        }

        $booking->load(['businessService.businessPlace.provider', 'serviceSchedule']);

        return view('bookings.show', compact('booking'));
    }

    public function submitPaymentProof(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['held', 'rejected'], true)) {
            return back()->withErrors(['booking' => 'Booking ini tidak dapat menerima bukti pembayaran baru.']);
        }

        if ($booking->status === 'held' && ! $booking->expires_at?->isFuture()) {
            $booking->delete();

            return back()->withErrors(['booking' => 'Waktu hold 10 menit telah berakhir. Silakan buat booking baru.']);
        }

        $data = $request->validate([
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ]);
        $proofPath = $data['payment_proof']->store('booking-proofs', 'public');

        if ($booking->payment_proof) {
            Storage::disk('public')->delete($booking->payment_proof);
        }

        $booking->update([
            'status' => 'payment_submitted',
            'payment_proof' => $proofPath,
            'payment_submitted_at' => now(),
            'verification_note' => null,
            'expires_at' => null,
        ]);

        return back()->with('success', 'Bukti pembayaran berhasil dikirim. Menunggu verifikasi penyedia.');
    }
}
