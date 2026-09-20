@extends('layouts.admin')

@section('title', 'Booking Masuk')
@section('page-title', 'Booking Masuk')
@section('page-subtitle', 'Cari, periksa, dan kelola booking pelanggan dari satu daftar.')

@section('content')
    <div class="content-card">
        <div class="card-heading"><div><h5>{{ __('Booking masuk') }}</h5><p>{{ empty($filters['booking_date'] ?? null) ? __('Menampilkan booking mendatang yang paling dekat.') : __('Menampilkan booking pada tanggal yang dipilih.') }}</p></div><div class="d-flex align-items-center gap-2"><span class="status status-warning">{{ $bookings->total() }} {{ __('booking') }}</span><a href="{{ route('provider.bookings.scan') }}" class="btn btn-primary btn-sm"><i class="fas fa-qrcode me-1"></i>{{ __('Scan QR') }}</a></div></div>
        <form method="GET" class="provider-booking-filters mb-4">
            <div class="row g-2 align-items-end"><div class="col-12 col-lg-5"><label class="form-label small fw-semibold" for="booking-search">{{ __('Cari booking') }}</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input id="booking-search" type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Kode, pelanggan, tempat, atau layanan') }}"></div></div><div class="col-12 col-md-5 col-lg-3"><label class="form-label small fw-semibold" for="booking-status">{{ __('Status') }}</label><select id="booking-status" name="status" class="form-select"><option value="">{{ __('Semua status') }}</option><option value="held" @selected(($filters['status'] ?? '') === 'held')>{{ __('Sedang hold') }}</option><option value="payment_submitted" @selected(($filters['status'] ?? '') === 'payment_submitted')>{{ __('Menunggu verifikasi') }}</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>{{ __('Confirmed') }}</option><option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>{{ __('Ditolak') }}</option></select></div><div class="col-12 col-md-5 col-lg-2"><label class="form-label small fw-semibold" for="booking-date">{{ __('Tanggal') }}</label><input id="booking-date" type="date" name="booking_date" class="form-control" value="{{ $filters['booking_date'] ?? '' }}"></div><div class="col-12 col-md-2 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i>{{ __('Filter') }}</button><a href="{{ route('provider.bookings.index') }}" class="btn btn-light" title="{{ __('Reset filter') }}"><i class="fas fa-rotate-left"></i></a></div></div>
        </form>

        <div class="provider-booking-list-wrap"><div class="table-responsive"><table class="table provider-booking-list align-middle mb-0"><thead><tr><th>{{ __('Booking') }}</th><th>{{ __('Pelanggan') }}</th><th>{{ __('Tempat & layanan') }}</th><th>{{ __('Jadwal') }}</th><th>{{ __('Total') }}</th><th>{{ __('Status') }}</th><th class="text-end">{{ __('Aksi') }}</th></tr></thead><tbody>
            @forelse ($bookings as $booking)
                <tr class="provider-booking-row {{ $booking->status === 'held' ? 'is-held' : ($booking->status === 'payment_submitted' ? 'is-payment-submitted' : ($booking->status === 'rejected' ? 'is-rejected' : '')) }}"><td><strong class="provider-booking-code">{{ $booking->booking_code }}</strong><small class="d-block text-muted mt-1">{{ $booking->created_at->format('d M Y H:i') }}</small></td><td><strong>{{ $booking->user->name }}</strong><small class="d-block text-muted">{{ $booking->user->email }}</small></td><td><strong>{{ $booking->businessService->businessPlace->name }}</strong><small class="d-block text-muted">{{ $booking->businessService->name }}</small></td><td><strong>{{ $booking->booking_date->format('d M Y') }}</strong><small class="d-block text-muted"><i class="fas fa-clock me-1"></i>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} <span class="timezone-badge" data-indonesia-timezone>WITA</span></small></td><td><strong class="text-success">Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}</strong></td><td><span class="status {{ $booking->status === 'confirmed' ? 'status-success' : ($booking->status === 'rejected' || $booking->status === 'expired' ? 'status-danger' : 'status-warning') }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span>@if ($booking->status === 'held' && $booking->expires_at)<small class="provider-booking-countdown" data-provider-booking-countdown data-expires="{{ $booking->expires_at->toIso8601String() }}"><i class="fas fa-hourglass-half me-1"></i><span>{{ __('Sisa waktu') }} --:--</span></small>@endif @if($booking->checked_in_at)<small class="d-block text-success mt-1"><i class="fas fa-user-check me-1"></i>{{ __('Hadir') }}</small>@endif</td><td class="text-end"><div class="provider-booking-list-actions">@if ($booking->payment_proof)<a href="{{ asset('storage/'.$booking->payment_proof) }}" target="_blank" rel="noopener" class="btn btn-sm btn-light" title="{{ __('Lihat bukti pembayaran') }}"><i class="fas fa-file-invoice-dollar"></i></a>@endif @if ($booking->status === 'payment_submitted')<form method="POST" action="{{ route('provider.bookings.approve', $booking) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" title="{{ __('Setujui') }}"><i class="fas fa-check"></i></button></form><form method="POST" action="{{ route('provider.bookings.reject', $booking) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-danger" title="{{ __('Tolak') }}"><i class="fas fa-xmark"></i></button></form>@endif @if ($booking->status === 'rejected')<form method="POST" action="{{ route('provider.bookings.release', $booking) }}" data-confirm="{{ __('Lepas hold booking ini agar jadwal tersedia kembali?') }}">@csrf @method('PATCH')<button class="btn btn-sm btn-outline-warning" title="{{ __('Lepas hold') }}"><i class="fas fa-unlock"></i></button></form>@endif @if ($booking->status === 'confirmed')<a href="{{ URL::signedRoute('provider.bookings.check-in', ['booking' => $booking]) }}" class="btn btn-sm btn-outline-primary" title="{{ __('Check-in') }}"><i class="fas fa-user-check"></i></a>@endif</div></td></tr>
            @empty
                <tr><td colspan="7"><div class="provider-empty-state"><i class="fas fa-receipt"></i><p>{{ __('Tidak ada booking yang sesuai filter.') }}</p></div></td></tr>
            @endforelse
        </tbody></table></div></div>
        <div class="mt-4">{{ $bookings->links() }}</div>
    </div>
    <script>
        document.querySelectorAll('[data-provider-booking-countdown]').forEach((element) => {
            const expiresAt = new Date(element.dataset.expires).getTime();
            const label = element.querySelector('span');

            const updateCountdown = () => {
                const remaining = Math.max(0, expiresAt - Date.now());
                const minutes = Math.floor(remaining / 60000).toString().padStart(2, '0');
                const seconds = Math.floor((remaining % 60000) / 1000).toString().padStart(2, '0');

                label.textContent = `{{ __('Sisa waktu') }} ${minutes}:${seconds}`;

                if (remaining === 0) {
                    label.textContent = '{{ __('Hold berakhir') }}';
                    element.classList.add('is-expired');
                }
            };

            updateCountdown();
            window.setInterval(updateCountdown, 1000);
        });
    </script>
@endsection
