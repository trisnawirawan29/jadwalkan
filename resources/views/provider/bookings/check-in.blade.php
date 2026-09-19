@extends('layouts.admin')

@section('title', 'Konfirmasi Kehadiran')
@section('page-title', 'Konfirmasi Kehadiran')
@section('page-subtitle', 'Periksa detail booking sebelum mencatat pelanggan hadir.')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-7">
            <div class="content-card provider-check-in-card">
                <div class="text-center mb-4">
                    <span class="provider-check-in-icon"><i class="fas fa-qrcode"></i></span>
                    <p class="eyebrow mt-3 mb-2">{{ __('QR KEHADIRAN') }}</p>
                    <h2 class="mb-1">{{ $booking->booking_code }}</h2>
                    <p class="text-muted mb-0">{{ __('QR berhasil dipindai. Pastikan detailnya sesuai.') }}</p>
                </div>
                <div class="booking-detail-grid">
                    <div><small>{{ __('PELANGGAN') }}</small><strong>{{ $booking->user->name }}</strong></div>
                    <div><small>{{ __('TEMPAT') }}</small><strong>{{ $booking->businessService->businessPlace->name }}</strong></div>
                    <div><small>{{ __('LAYANAN') }}</small><strong>{{ $booking->businessService->name }}</strong></div>
                    <div><small>{{ __('JADWAL') }}</small><strong>{{ $booking->booking_date->format('d M Y') }} · {{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} <span class="timezone-badge" data-indonesia-timezone>WITA</span></strong></div>
                </div>
                @if ($booking->checked_in_at)
                    <div class="alert alert-success mt-4 mb-0"><i class="fas fa-circle-check me-2"></i>{{ __('Kehadiran sudah dikonfirmasi pada') }} <span data-local-datetime="{{ $booking->checked_in_at->toIso8601String() }}">{{ $booking->checked_in_at->format('d M Y H:i') }}</span>.</div>
                @elseif ($booking->status !== 'confirmed')
                    <div class="alert alert-warning mt-4 mb-0"><i class="fas fa-triangle-exclamation me-2"></i>{{ __('Booking belum confirmed sehingga belum dapat dicatat hadir.') }}</div>
                @else
                    <form method="POST" action="{{ route('provider.bookings.check-in.confirm', $booking) }}" class="mt-4">
                        @csrf
                        @method('PATCH')
                        <button class="btn btn-success w-100 py-2"><i class="fas fa-user-check me-2"></i>{{ __('Konfirmasi pelanggan hadir') }}</button>
                    </form>
                @endif
                <a href="{{ route('provider.bookings.index') }}" class="btn btn-light w-100 mt-3">{{ __('Kembali ke booking masuk') }}</a>
            </div>
        </div>
    </div>
@endsection
