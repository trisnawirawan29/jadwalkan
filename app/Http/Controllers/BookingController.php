<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BusinessPlace;
use App\Models\BusinessService;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Services\ProviderPlanLimitService;
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
            'status' => ['nullable', 'in:held,payment_submitted,confirmed,rejected,cancelled'],
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

        if (empty($filters['booking_date'] ?? null)) {
            $applyBookingPeriod($bookingQuery);
        }

        $bookings = $bookingQuery
            ->when($view === 'history', fn ($query) => $query->orderByDesc('booking_date')->orderByDesc('start_time'), fn ($query) => $query->orderBy('booking_date')->orderBy('start_time'))
            ->paginate(10)
            ->withQueryString();

        $calendarMonth = Carbon::createFromFormat('Y-m', $filters['month'] ?? now()->format('Y-m'))->startOfMonth();
        $calendarBookingsQuery = $applyBookingFilters($request->user()->bookings()->with(['businessService.businessPlace', 'serviceSchedule']))
            ->whereBetween('booking_date', [$calendarMonth->toDateString(), $calendarMonth->copy()->endOfMonth()->toDateString()])
            ->whereIn('status', ['held', 'confirmed']);

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

    public function store(Request $request, BusinessPlace $businessPlace, ProviderPlanLimitService $planLimits): RedirectResponse
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
        $planLimits->ensureCanAcceptBooking($businessPlace, $bookingDate);
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

        $holdDurationMinutes = (int) ($businessPlace->hold_duration_minutes ?: 10);
        $booking = DB::transaction(function () use ($data, $request, $schedule, $bookingDate, $startTime, $endTime, $totalCost, $holdDurationMinutes): Booking {
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
                'expires_at' => now()->addMinutes($holdDurationMinutes),
                'notes' => $data['notes'] ?? null,
            ]);
        });

        return redirect()->route('bookings.show', $booking)->with('success', "Jadwal berhasil ditahan selama {$holdDurationMinutes} menit. Selesaikan pembayaran untuk mengonfirmasi booking.");
    }

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);
        $this->markProviderNotificationsAsRead($request->user(), $booking);

        if ($booking->status === 'held' && $booking->expires_at?->isPast()) {
            $booking->delete();

            return redirect()->route('bookings.index')->withErrors(['booking' => 'Waktu hold telah berakhir. Booking dihapus.']);
        }

        $booking->load(['businessService.businessPlace.paymentMethods', 'serviceSchedule']);
        $extensionOptions = $this->getExtensionOptions($booking);

        return view('bookings.show', compact('booking', 'extensionOptions'));
    }

    private function markProviderNotificationsAsRead(User $user, Booking $booking): void
    {
        $user->unreadNotifications()
            ->whereJsonContains('data->source', 'provider')
            ->where(function ($query) use ($booking): void {
                $query->whereJsonContains('data->booking_id', $booking->id)
                    ->orWhere('data->message', 'like', "%{$booking->booking_code}%");
            })
            ->get()
            ->markAsRead();
    }

    public function submitPaymentProof(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['held', 'rejected'], true)) {
            return back()->withErrors(['booking' => 'Booking ini tidak dapat menerima bukti pembayaran baru.']);
        }

        if ($booking->status === 'held' && ! $booking->expires_at?->isFuture()) {
            $booking->delete();

            return back()->withErrors(['booking' => 'Waktu hold telah berakhir. Silakan buat booking baru.']);
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

    public function extend(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['held', 'rejected'], true)) {
            return back()->withErrors(['booking' => 'Booking ini tidak dapat ditambah jamnya.']);
        }

        if ($booking->status === 'held' && ! $booking->expires_at?->isFuture()) {
            $booking->delete();

            return redirect()->route('bookings.index')->withErrors(['booking' => 'Waktu hold telah berakhir. Booking dihapus.']);
        }

        $data = $request->validate([
            'additional_hours' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        DB::transaction(function () use ($booking, $data): void {
            $lockedBooking = Booking::query()
                ->with(['businessService', 'serviceSchedule'])
                ->whereKey($booking->id)
                ->lockForUpdate()
                ->firstOrFail();
            if (! $lockedBooking->serviceSchedule) {
                throw ValidationException::withMessages(['additional_hours' => 'Jadwal layanan tidak ditemukan.']);
            }
            $additionalHours = (int) $data['additional_hours'];
            $currentEnd = Carbon::parse($lockedBooking->end_time);
            $newEnd = $currentEnd->copy()->addHours($additionalHours);
            $scheduleEnd = Carbon::parse($lockedBooking->serviceSchedule->end_time);

            if ($newEnd->gt($scheduleEnd)) {
                throw ValidationException::withMessages(['additional_hours' => 'Penambahan jam melewati jam selesai layanan.']);
            }

            $existingBookings = Booking::query()
                ->where('id', '<>', $lockedBooking->id)
                ->where('business_service_id', $lockedBooking->business_service_id)
                ->whereDate('booking_date', $lockedBooking->booking_date)
                ->whereIn('status', ['held', 'payment_submitted', 'rejected', 'confirmed'])
                ->lockForUpdate()
                ->get();

            foreach ($existingBookings as $existingBooking) {
                if ($existingBooking->status === 'held' && $existingBooking->expires_at?->isPast()) {
                    $existingBooking->delete();

                    continue;
                }

                if ($existingBooking->service_schedule_id === $lockedBooking->service_schedule_id
                    && $existingBooking->start_time < $newEnd->format('H:i:s')
                    && $existingBooking->end_time > $lockedBooking->start_time) {
                    throw ValidationException::withMessages(['additional_hours' => 'Jam tambahan bertabrakan dengan booking lain.']);
                }
            }

            $additionalCost = $this->calculateBookingCost(
                $lockedBooking->businessService,
                $lockedBooking->booking_date,
                $currentEnd,
                $newEnd,
            );

            $lockedBooking->update([
                'end_time' => $newEnd->format('H:i:s'),
                'total_cost' => round((float) $lockedBooking->total_cost + $additionalCost, 2),
            ]);
        });

        return back()->with('success', 'Jam booking berhasil ditambahkan.');
    }

    public function cancel(Request $request, Booking $booking): RedirectResponse
    {
        abort_unless($booking->user_id === $request->user()->id, 403);

        if (! in_array($booking->status, ['held', 'rejected'], true)) {
            return back()->withErrors(['booking' => 'Booking ini tidak dapat dibatalkan.']);
        }

        $booking->update(['status' => 'cancelled', 'expires_at' => null]);

        return redirect()->route('bookings.index')->with('success', 'Booking berhasil dibatalkan.');
    }

    /**
     * @return array<int, array{hours: int, end_time: string}>
     */
    private function getExtensionOptions(Booking $booking): array
    {
        if (! in_array($booking->status, ['held', 'rejected'], true) || ! $booking->serviceSchedule) {
            return [];
        }

        $currentEnd = Carbon::parse($booking->end_time);
        $scheduleEnd = Carbon::parse($booking->serviceSchedule->end_time);
        $options = [];

        for ($hours = 1; $currentEnd->copy()->addHours($hours)->lte($scheduleEnd); $hours++) {
            $options[] = ['hours' => $hours, 'end_time' => $currentEnd->copy()->addHours($hours)->format('H:i')];
        }

        return $options;
    }

    private function calculateBookingCost(BusinessService $businessService, Carbon $bookingDate, Carbon $startTime, Carbon $endTime): float
    {
        $businessService->loadMissing('hourlyPrices');
        $hourlyPrices = $businessService->hourly_price_map;
        $dayPrices = $hourlyPrices[(string) $bookingDate->isoWeekday()] ?? [];
        $totalCost = 0;
        $priceCursor = $startTime->copy();

        while ($priceCursor->lt($endTime)) {
            $time = $priceCursor->format('H:i');
            $totalCost += (float) ($dayPrices[$time] ?? $hourlyPrices[$time] ?? $businessService->price_per_hour);
            $priceCursor->addHour();
        }

        return $totalCost;
    }
}
