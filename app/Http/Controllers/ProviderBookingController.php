<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Notifications\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProviderBookingController extends Controller
{
    public function index(Request $request): View
    {
        Booking::cleanupExpiredHolds();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:held,payment_submitted,confirmed,rejected'],
            'booking_date' => ['nullable', 'date', 'date_format:Y-m-d'],
        ]);
        $bookingQuery = Booking::query()
            ->with(['user', 'businessService.businessPlace', 'serviceSchedule'])
            ->whereHas('businessService.businessPlace', fn ($query) => $query->where('provider_id', $request->user()->providerOwnerId()))
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('booking_code', 'like', "%{$search}%")
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('businessService', function ($serviceQuery) use ($search): void {
                            $serviceQuery->where('name', 'like', "%{$search}%")
                                ->orWhereHas('businessPlace', fn ($placeQuery) => $placeQuery->where('name', 'like', "%{$search}%"));
                        });
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['booking_date'] ?? null, fn ($query, string $date) => $query->whereDate('booking_date', $date));

        if (empty($filters['booking_date'] ?? null)) {
            $bookingQuery->where(function ($query): void {
                $query->whereDate('booking_date', '>', today())
                    ->orWhere(function ($todayQuery): void {
                        $todayQuery->whereDate('booking_date', today())
                            ->where('end_time', '>', now()->format('H:i:s'));
                    });
            });
        }

        $bookings = $bookingQuery
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->paginate(15)
            ->withQueryString();

        return view('provider.bookings.index', compact('bookings', 'filters'));
    }

    public function scan(): View
    {
        return view('provider.bookings.scan');
    }

    public function approve(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureProviderOwnsBooking($request, $booking);

        if ($booking->status !== 'payment_submitted' || ! $booking->payment_proof) {
            return back()->withErrors(['booking' => 'Booking ini belum memiliki bukti pembayaran yang menunggu verifikasi.']);
        }

        $booking->update(['status' => 'confirmed', 'paid_at' => now(), 'verified_at' => now(), 'verification_note' => null]);
        $booking->user->notify(new SystemNotification(
            'Booking dikonfirmasi provider',
            "Pembayaran untuk booking {$booking->booking_code} telah disetujui provider.",
            'success',
            'provider',
            $booking->id,
        ));

        return back()->with('success', 'Bukti pembayaran disetujui. Booking sekarang berstatus confirmed.');
    }

    public function reject(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureProviderOwnsBooking($request, $booking);

        if ($booking->status !== 'payment_submitted') {
            return back()->withErrors(['booking' => 'Booking ini tidak sedang menunggu verifikasi.']);
        }

        $data = $request->validate(['verification_note' => ['nullable', 'string', 'max:500']]);
        $booking->update(['status' => 'rejected', 'verification_note' => $data['verification_note'] ?? 'Bukti pembayaran perlu diperiksa kembali.']);
        $booking->user->notify(new SystemNotification(
            'Bukti pembayaran ditolak provider',
            "Bukti pembayaran untuk booking {$booking->booking_code} perlu diperiksa kembali.",
            'info',
            'provider',
            $booking->id,
        ));

        return back()->with('warning', 'Bukti pembayaran ditolak. Pengguna dapat mengirim bukti baru.');
    }

    public function releaseRejected(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureProviderOwnsBooking($request, $booking);

        if ($booking->status !== 'rejected') {
            return back()->withErrors(['booking' => 'Hanya booking yang ditolak yang dapat dilepas hold-nya.']);
        }

        $booking->update(['status' => 'expired', 'expires_at' => now()]);
        $booking->user->notify(new SystemNotification(
            'Hold booking dilepas provider',
            "Hold untuk booking {$booking->booking_code} telah dilepas. Silakan buat booking baru jika masih membutuhkan jadwal tersebut.",
            'info',
            'provider',
            $booking->id,
        ));

        return back()->with('success', 'Hold booking berhasil dilepas. Jadwal sekarang tersedia kembali.');
    }

    public function checkIn(Request $request, Booking $booking): View
    {
        $this->ensureProviderOwnsBooking($request, $booking);
        $booking->load(['user', 'businessService.businessPlace', 'serviceSchedule']);

        return view('provider.bookings.check-in', compact('booking'));
    }

    public function confirmAttendance(Request $request, Booking $booking): RedirectResponse
    {
        $this->ensureProviderOwnsBooking($request, $booking);

        if ($booking->status !== 'confirmed') {
            return back()->withErrors(['booking' => 'Booking harus berstatus confirmed sebelum kehadiran dapat dicatat.']);
        }

        if ($booking->checked_in_at) {
            return back()->with('info', 'Kehadiran booking ini sudah dicatat sebelumnya.');
        }

        $booking->update(['checked_in_at' => now(), 'checked_in_by' => $request->user()->id]);

        return redirect()->route('provider.bookings.check-in', $booking)->with('success', 'Kehadiran pelanggan berhasil dikonfirmasi.');
    }

    private function ensureProviderOwnsBooking(Request $request, Booking $booking): void
    {
        abort_unless($booking->businessService()->whereHas('businessPlace', fn ($query) => $query->where('provider_id', $request->user()->providerOwnerId()))->exists(), 403);
    }
}
