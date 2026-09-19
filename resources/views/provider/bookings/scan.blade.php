@extends('layouts.admin')

@section('title', 'Scan QR Booking')
@section('page-title', 'Scan QR Booking')
@section('page-subtitle', 'Pindai QR pelanggan untuk melihat informasi booking dan konfirmasi kehadiran.')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="content-card provider-scanner-card">
                <div class="text-center mb-4">
                    <span class="provider-check-in-icon"><i class="fas fa-camera"></i></span>
                    <p class="eyebrow mt-3 mb-2">{{ __('SCAN QR BOOKING') }}</p>
                    <h2 class="mb-2">{{ __('Pindai QR pelanggan') }}</h2>
                    <p class="text-muted mb-0">{{ __('Arahkan kamera ke QR pada detail booking pelanggan.') }}</p>
                </div>
                <div id="booking-qr-reader" class="booking-qr-reader"></div>
                <div id="booking-qr-status" class="alert alert-info small mt-3 mb-0"><i class="fas fa-camera me-2"></i>{{ __('Izinkan akses kamera untuk mulai memindai.') }}</div>
                <a href="{{ route('provider.bookings.index') }}" class="btn btn-light w-100 mt-3">{{ __('Kembali ke booking masuk') }}</a>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
    <script>
        (() => {
            const readerId = 'booking-qr-reader';
            const status = document.getElementById('booking-qr-status');
            const allowedPath = @json(url('/provider/bookings/'));
            let hasScanned = false;

            const showStatus = (message, type = 'info') => {
                status.className = `alert alert-${type} small mt-3 mb-0`;
                status.innerHTML = `<i class="fas fa-${type === 'danger' ? 'triangle-exclamation' : 'camera'} me-2"></i>${message}`;
            };

            const handleScan = (decodedText, decodedResult) => {
                if (hasScanned) return;
                try {
                    const scannedUrl = new URL(decodedText);
                    if (scannedUrl.origin !== window.location.origin || !scannedUrl.href.startsWith(allowedPath) || !scannedUrl.pathname.endsWith('/check-in')) {
                        showStatus(@json(__('QR tidak dikenali. Gunakan QR dari detail booking aplikasi ini.')), 'danger');
                        return;
                    }

                    hasScanned = true;
                    showStatus(@json(__('QR berhasil dipindai. Membuka informasi booking...')), 'success');
                    window.setTimeout(() => window.location.assign(scannedUrl.href), 400);
                } catch (error) {
                    showStatus(@json(__('Format QR tidak valid. Silakan coba lagi.')), 'danger');
                }
            };

            const scanner = new Html5QrcodeScanner(readerId, {fps: 10, qrbox: {width: 250, height: 250}, rememberLastUsedCamera: true}, false);
            scanner.render(handleScan, () => {});
        })();
    </script>
@endsection
