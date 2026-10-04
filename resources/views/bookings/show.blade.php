@extends('layouts.admin')

@section('title', 'Detail Booking')
@section('page-title', 'Detail Booking')
@section('page-subtitle', $booking->businessService->businessPlace->name)

@section('content')
    <style>
        .booking-held-alert { position: relative; overflow: hidden; padding: 22px; border: 0; border-radius: 20px; color: #fff; background: linear-gradient(125deg, #172554, #3730a3 58%, #6366f1); box-shadow: 0 14px 30px rgba(55, 48, 163, .2); }
        .booking-held-alert::after { content: ''; position: absolute; width: 180px; height: 180px; right: -70px; top: -100px; border: 1px solid rgba(255, 255, 255, .16); border-radius: 50%; }
        .booking-held-alert-heading { position: relative; z-index: 1; display: flex; align-items: flex-start; gap: 13px; }
        .booking-held-alert-icon { display: grid; place-items: center; flex: 0 0 auto; width: 42px; height: 42px; border: 1px solid rgba(255, 255, 255, .2); border-radius: 13px; color: #fff; background: rgba(255, 255, 255, .14); font-size: 18px; }
        .booking-held-alert small { display: block; color: #c9c5ff; font-size: 10px; font-weight: 800; letter-spacing: .1em; text-transform: uppercase; }
        .booking-held-alert h3 { margin: 3px 0 0; color: #fff; font-size: 18px; font-weight: 800; }
        .booking-held-alert p { margin: 5px 0 0; color: #dedcff; font-size: 11px; line-height: 1.55; }
        .booking-held-alert-timer { position: relative; z-index: 1; margin-left: auto; min-width: 78px; padding: 9px 10px; border: 1px solid rgba(255, 255, 255, .2); border-radius: 12px; color: #fff; background: rgba(15, 23, 42, .25); text-align: center; }
        .booking-held-alert-timer strong { display: block; font-size: 19px; line-height: 1; letter-spacing: .04em; }
        .booking-held-alert-timer span { display: block; margin-top: 5px; color: #c9c5ff; font-size: 8px; font-weight: 800; text-transform: uppercase; }
        .booking-held-summary { position: relative; z-index: 1; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-top: 18px; }
        .booking-held-summary div { padding: 10px 11px; border: 1px solid rgba(255, 255, 255, .14); border-radius: 11px; background: rgba(255, 255, 255, .08); }
        .booking-held-summary span, .booking-held-summary strong { display: block; }
        .booking-held-summary span { color: #c9c5ff; font-size: 9px; font-weight: 700; }
        .booking-held-summary strong { margin-top: 4px; color: #fff; font-size: 11px; }
        @media (max-width: 575px) { .booking-held-alert-heading { flex-wrap: wrap; } .booking-held-alert-timer { margin-left: 55px; } }
    </style>
    @if ($booking->status === 'held')
        <div class="booking-held-alert mb-4" role="alert">
            <div class="booking-held-alert-heading">
                <span class="booking-held-alert-icon"><i class="fas fa-lock"></i></span>
                <div><small>{{ __('Booking berhasil ditahan') }}</small><h3>{{ __('Jadwal Anda sudah diamankan') }}</h3><p>{{ __('Jadwal ditahan selama :minutes menit. Selesaikan pembayaran sebelum waktu hold berakhir agar jadwal ini tetap menjadi milik Anda.', ['minutes' => $booking->businessService->businessPlace->hold_duration_minutes]) }}</p></div>
                <div class="booking-held-alert-timer"><strong data-booking-alert-countdown>--:--</strong><span>{{ __('tersisa') }}</span></div>
            </div>
            <div class="booking-held-summary">
                <div><span>{{ __('Kode booking') }}</span><strong>{{ $booking->booking_code }}</strong></div>
                <div><span>{{ __('Tempat · layanan') }}</span><strong>{{ $booking->businessService->businessPlace->name }} · {{ $booking->businessService->name }}</strong></div>
                <div><span>{{ __('Tanggal · jam') }}</span><strong>{{ $booking->booking_date->translatedFormat('d M Y') }} · {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</strong></div>
                <div><span>{{ __('Total estimasi') }}</span><strong>Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}</strong></div>
            </div>
        </div>
    @elseif (session('success'))
        <div class="alert alert-success small">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger small">{{ $errors->first() }}</div>
    @endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="content-card booking-detail-card">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <p class="eyebrow mb-2">{{ __('KODE BOOKING') }}</p>
                        <h2 class="mb-1">{{ $booking->booking_code }}</h2>
                        <p class="text-muted mb-0">{{ $booking->businessService->businessPlace->name }}</p>
                    </div>
                    <span class="status {{ $booking->status === 'confirmed' ? 'status-success' : ($booking->status === 'expired' ? 'status-danger' : 'status-warning') }}">{{ ucfirst($booking->status) }}</span>
                </div>

                <div class="booking-detail-grid mt-4">
                    <div><small>{{ __('LAYANAN') }}</small><strong>{{ $booking->businessService->name }}</strong></div>
                    <div><small>{{ __('TANGGAL') }}</small><strong>{{ $booking->booking_date->format('d M Y') }}</strong></div>
                    <div><small>{{ __('JAM') }}</small><strong>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} <span class="timezone-badge" data-indonesia-timezone>WITA</span></strong></div>
                    <div><small>{{ __('TOTAL BIAYA') }}</small><strong>Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}</strong></div>
                    <div><small>{{ __('ALAMAT') }}</small><strong>{{ $booking->businessService->businessPlace->address ?: '-' }}</strong></div>
                </div>

                @if ($booking->status === 'held' || $booking->status === 'rejected')
                    @php
                        $paymentPlace = $booking->businessService->businessPlace;
                        $paymentMethods = $paymentPlace->paymentMethods->where('is_active', true);
                    @endphp
                    @if ($paymentMethods->isNotEmpty())
                        <div class="payment-instruction-card mt-4"><div class="d-flex align-items-start gap-3"><span class="payment-instruction-icon"><i class="fas fa-wallet"></i></span><div><strong>{{ __('Informasi pembayaran') }}</strong><p>{{ __('Gunakan salah satu metode pembayaran berikut, lalu unggah bukti pembayaran Anda.') }}</p></div></div><div class="payment-method-cards mt-3">@foreach ($paymentMethods as $paymentMethod)<div class="payment-method-card {{ $paymentMethod->isQris() ? 'payment-method-card-qris' : 'payment-method-card-bank' }}"><div class="payment-method-card-heading"><span class="payment-method-card-icon"><i class="fas {{ $paymentMethod->isQris() ? 'fa-qrcode' : 'fa-building-columns' }}"></i></span><div><strong>{{ $paymentMethod->isQris() ? __('Bayar dengan QRIS') : __('Transfer bank') }}</strong><small>{{ $paymentMethod->isQris() ? __('Scan kode untuk membayar') : $paymentMethod->bank_name }}</small></div></div>@if ($paymentMethod->isQris()) @if ($paymentMethod->qris_image_url)<img src="{{ $paymentMethod->qris_image_url }}" alt="QRIS {{ $paymentPlace->name }}">@endif @else<div class="payment-account-number">{{ $paymentMethod->account_number }}</div><small class="payment-account-name">{{ __('a.n.') }} {{ $paymentMethod->account_name ?: '-' }}</small>@endif</div>@endforeach</div><div class="payment-proof-tip mt-3"><i class="fas fa-camera"></i><span>{{ __('Setelah transfer, screenshot atau foto bukti pembayaran Anda. Kirim melalui tombol konfirmasi pembayaran di bawah.') }}</span></div></div>
                    @endif
                    @if ($booking->status === 'held')
                        <div class="booking-countdown mt-4"><i class="fas fa-hourglass-half"></i><div><strong>{{ __('Jadwal ditahan sementara') }}</strong><p id="booking-countdown" data-expires="{{ $booking->expires_at?->toIso8601String() }}">{{ __('Sisa waktu :minutes:00', ['minutes' => $booking->businessService->businessPlace->hold_duration_minutes]) }}</p></div></div>
                    @elseif ($booking->verification_note)
                        <div class="alert alert-warning mt-4 mb-0"><i class="fas fa-circle-info me-2"></i>{{ $booking->verification_note }}</div>
                    @endif
                    <form method="POST" action="{{ route('bookings.payment-proof', $booking) }}" enctype="multipart/form-data" class="mt-4 booking-proof-form">
                        @csrf
                        <label class="form-label small fw-semibold" for="payment_proof">{{ __('Bukti pembayaran') }}</label>
                        <input id="payment_proof" type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" capture="environment" required>
                        <small class="text-muted">{{ __('Format JPG, PNG, WEBP, atau PDF. Maksimal 5 MB.') }}</small>
                        <button class="btn btn-primary w-100 py-2 mt-3"><i class="fas fa-check-to-slot me-2"></i>{{ __('Konfirmasi pembayaran') }}</button>
                    </form>
                    <p class="small text-muted text-center mt-2 mb-0">{{ __('Booking akan dikonfirmasi setelah penyedia memverifikasi bukti pembayaran.') }}</p>
                    @if ($extensionOptions)
                        <div class="booking-extension-card mt-4"><div><strong><i class="fas fa-clock-rotate-left me-2"></i>{{ __('Tambah jam booking') }}</strong><small>{{ __('Pilihan hanya sampai jam yang masih kosong, sebelum booking berikutnya.') }}</small></div><form method="POST" action="{{ route('bookings.extend', $booking) }}" class="row g-2 align-items-end mt-2">@csrf @method('PATCH')<div class="col-7"><label class="form-label small mb-1" for="additional_hours">{{ __('Tambahan durasi') }}</label><select id="additional_hours" name="additional_hours" class="form-select">@foreach ($extensionOptions as $option)<option value="{{ $option['hours'] }}">+{{ $option['hours'] }} {{ __('jam') }} ({{ $option['end_time'] }})</option>@endforeach</select></div><div class="col-5"><button class="btn btn-outline-primary w-100"><i class="fas fa-plus me-1"></i>{{ __('Tambah jam') }}</button></div></form></div>
                    @endif
                    <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="mt-3" data-confirm="{{ __('Batalkan booking ini? Jadwal akan tersedia kembali.') }}">@csrf @method('DELETE')<button class="btn btn-outline-danger w-100"><i class="fas fa-ban me-2"></i>{{ __('Batalkan booking') }}</button></form>
                @elseif ($booking->status === 'payment_submitted')
                    <div class="alert alert-info mt-4 mb-0"><i class="fas fa-hourglass-half me-2"></i>{{ __('Bukti pembayaran sudah dikirim dan sedang menunggu verifikasi penyedia.') }}</div>
                    @if ($booking->payment_proof)<a href="{{ asset('storage/'.$booking->payment_proof) }}" target="_blank" rel="noopener" class="btn btn-light w-100 mt-3">{{ __('Lihat bukti pembayaran') }}</a>@endif
                @elseif ($booking->status === 'confirmed')
                    <div class="alert alert-success mt-4 mb-0"><i class="fas fa-circle-check me-2"></i>{{ __('Booking sudah dikonfirmasi dan pembayaran tercatat.') }}</div>
                    <div class="booking-qr-card mt-4">
                        <div><span class="booking-qr-icon"><i class="fas fa-qrcode"></i></span><div><strong>{{ __('QR kehadiran') }}</strong><p>{{ __('Tunjukkan QR ini kepada provider saat Anda tiba di lokasi.') }}</p></div></div>
                        <div id="booking-qrcode" class="booking-qrcode" data-url="{{ URL::signedRoute('provider.bookings.check-in', ['booking' => $booking]) }}"></div>
                        @if ($booking->checked_in_at)<span class="badge text-bg-success"><i class="fas fa-check me-1"></i>{{ __('Kehadiran sudah dikonfirmasi') }}</span>@endif
                    </div>
                @else
                    <div class="alert alert-danger mt-4 mb-0"><i class="fas fa-clock me-2"></i>{{ __('Hold booking sudah berakhir. Silakan buat booking baru.') }}</div>
                @endif
            </div>
        </div>

        <div class="col-lg-5">
            <div class="content-card h-100">
                <div class="card-heading"><div><h5>{{ __('Langkah berikutnya') }}</h5><p>{{ __('Selesaikan booking dengan mudah.') }}</p></div></div>
                <div class="booking-step-list">
                    <div><span>1</span><p><strong>{{ __('Pilih jadwal') }}</strong><small>{{ __('Pilih layanan dan tanggal yang sesuai.') }}</small></p></div>
                    <div><span>2</span><p><strong>{{ __('Hold :minutes menit', ['minutes' => $booking->businessService->businessPlace->hold_duration_minutes]) }}</strong><small>{{ __('Jadwal diamankan sementara untuk Anda.') }}</small></p></div>
                    <div><span>3</span><p><strong>{{ __('Kirim bukti pembayaran') }}</strong><small>{{ __('Penyedia memverifikasi bukti sebelum booking dikonfirmasi.') }}</small></p></div>
                </div>
                <a href="{{ route('bookings.index') }}" class="btn btn-light w-100 mt-3">{{ __('Lihat booking saya') }}</a>
            </div>
        </div>
    </div>

    @if ($booking->status === 'held')
        <script>
            const countdown = document.getElementById('booking-countdown');
            const alertCountdown = document.querySelector('[data-booking-alert-countdown]');
            const expiresAt = new Date(countdown.dataset.expires).getTime();
            const updateCountdown = () => {
                const remaining = Math.max(0, expiresAt - Date.now());
                const minutes = Math.floor(remaining / 60000).toString().padStart(2, '0');
                const seconds = Math.floor((remaining % 60000) / 1000).toString().padStart(2, '0');
                countdown.textContent = `{{ __('Sisa waktu') }} ${minutes}:${seconds}`;
                if (alertCountdown) { alertCountdown.textContent = `${minutes}:${seconds}`; }
                if (remaining === 0) { window.location.reload(); }
            };
            updateCountdown();
            setInterval(updateCountdown, 1000);
        </script>
    @endif

    @if ($booking->status === 'confirmed')
        <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
        <script>
            const qrElement = document.getElementById('booking-qrcode');
            if (qrElement && typeof QRCode !== 'undefined') {
                new QRCode(qrElement, {text: qrElement.dataset.url, width: 190, height: 190, colorDark: '#1f2440', colorLight: '#ffffff'});
            }
        </script>
    @endif
@endsection
