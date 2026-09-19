@extends('layouts.admin')

@section('title', __('Laporan keuangan'))
@section('page-title', __('Laporan keuangan'))
@section('page-subtitle', __('Pantau omzet dan transaksi bisnis Anda secara terukur.'))

@section('content')
    <div class="financial-report-shell">
        <div class="financial-report-hero mb-4">
            <div>
                <span class="eyebrow">{{ __('PERFORMA BISNIS') }}</span>
                <h2>{{ __('Ringkasan keuangan') }}</h2>
                <p>{{ __('Lihat pendapatan dari booking yang sudah dikonfirmasi dan telusuri transaksi berdasarkan hari.') }}</p>
            </div>
            <div class="financial-report-hero-icon"><i class="fas fa-chart-line"></i></div>
        </div>

        <div class="content-card mb-4">
            <div class="card-heading"><div><h5>{{ __('Filter laporan') }}</h5><p>{{ __('Gunakan filter untuk mempersempit periode dan transaksi yang ingin dianalisis.') }}</p></div><a href="{{ route('provider.reports.financial') }}" class="btn btn-light btn-sm"><i class="fas fa-rotate-left me-1"></i>{{ __('Reset') }}</a></div>
            <form method="GET" class="financial-report-filters">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-md-3"><label class="form-label" for="report-month">{{ __('Bulan') }}</label><input id="report-month" type="month" name="month" class="form-control" value="{{ $month->format('Y-m') }}"></div>
                    <div class="col-12 col-md-3"><label class="form-label" for="report-date">{{ __('Rinci tanggal') }}</label><input id="report-date" type="date" name="booking_date" class="form-control" value="{{ $filters['booking_date'] ?? '' }}"></div>
                    <div class="col-12 col-md-3"><label class="form-label" for="report-place">{{ __('Tempat bisnis') }}</label><select id="report-place" name="business_place_id" class="form-select"><option value="">{{ __('Semua tempat') }}</option>@foreach ($places as $place)<option value="{{ $place->id }}" @selected((string) ($filters['business_place_id'] ?? '') === (string) $place->id)>{{ $place->name }}</option>@endforeach</select></div>
                    <div class="col-12 col-md-3"><label class="form-label" for="report-status">{{ __('Status transaksi') }}</label><select id="report-status" name="status" class="form-select"><option value="confirmed" @selected(($filters['status'] ?? 'confirmed') === 'confirmed')>{{ __('Confirmed / sudah dibayar') }}</option><option value="all" @selected(($filters['status'] ?? '') === 'all')>{{ __('Semua status') }}</option><option value="payment_submitted" @selected(($filters['status'] ?? '') === 'payment_submitted')>{{ __('Menunggu verifikasi') }}</option><option value="held" @selected(($filters['status'] ?? '') === 'held')>{{ __('Hold') }}</option><option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>{{ __('Ditolak') }}</option></select></div>
                    <div class="col-12 col-md-9"><label class="form-label" for="report-search">{{ __('Cari transaksi') }}</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input id="report-search" type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="{{ __('Kode booking, pelanggan, tempat, atau layanan') }}"></div></div>
                    <div class="col-12 col-md-3"><button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>{{ __('Terapkan filter') }}</button></div>
                </div>
            </form>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4"><div class="financial-stat-card financial-stat-revenue"><div class="financial-stat-icon"><i class="fas fa-wallet"></i></div><small>{{ __('Total pendapatan') }}</small><strong>Rp {{ number_format((float) $summary->total_revenue, 0, ',', '.') }}</strong><span>{{ $month->translatedFormat('F Y') }}</span></div></div>
            <div class="col-12 col-md-4"><div class="financial-stat-card financial-stat-transactions"><div class="financial-stat-icon"><i class="fas fa-receipt"></i></div><small>{{ __('Transaksi berhasil') }}</small><strong>{{ number_format((int) $summary->transaction_count, 0, ',', '.') }}</strong><span>{{ __('booking terkonfirmasi') }}</span></div></div>
            <div class="col-12 col-md-4"><div class="financial-stat-card financial-stat-average"><div class="financial-stat-icon"><i class="fas fa-arrow-trend-up"></i></div><small>{{ __('Rata-rata transaksi') }}</small><strong>Rp {{ number_format((float) $summary->average_value, 0, ',', '.') }}</strong><span>{{ __('nilai per booking') }}</span></div></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-5">
                <div class="content-card h-100">
                    <div class="card-heading"><div><h5>{{ __('Rincian per hari') }}</h5><p>{{ __('Distribusi pendapatan pada periode terpilih.') }}</p></div><span class="report-period-badge">{{ $month->format('M Y') }}</span></div>
                    @if ($dailySummary->isNotEmpty())
                        <div class="financial-daily-list">
                            @foreach ($dailySummary as $daily)
                                @php($dailyWidth = min(100, ((float) $daily->total_revenue / $maxDailyRevenue) * 100))
                                <div class="financial-daily-row"><div class="financial-daily-heading"><span>{{ \Carbon\Carbon::parse($daily->booking_date)->translatedFormat('d M Y') }}</span><strong>Rp {{ number_format((float) $daily->total_revenue, 0, ',', '.') }}</strong></div><div class="financial-daily-track"><span style="width: {{ $dailyWidth }}%"></span></div><small>{{ $daily->transaction_count }} {{ __('transaksi') }}</small></div>
                            @endforeach
                        </div>
                    @else
                        <div class="report-empty-state"><i class="fas fa-chart-column"></i><p>{{ __('Belum ada transaksi pada periode ini.') }}</p></div>
                    @endif
                </div>
            </div>
            <div class="col-12 col-xl-7">
                <div class="content-card h-100">
                    <div class="card-heading"><div><h5>{{ __('Ikhtisar transaksi') }}</h5><p>{{ __('Detail transaksi sesuai filter yang dipilih.') }}</p></div><span class="status status-success">{{ $transactions->total() }} {{ __('data') }}</span></div>
                    <div class="financial-table-wrap"><table class="table financial-table align-middle"><thead><tr><th>{{ __('Tanggal') }}</th><th>{{ __('Booking') }}</th><th>{{ __('Pelanggan') }}</th><th>{{ __('Tempat / layanan') }}</th><th class="text-end">{{ __('Nominal') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
                        @forelse ($transactions as $booking)
                            @php($statusClass = $booking->status === 'confirmed' ? 'status-success' : ($booking->status === 'rejected' ? 'status-danger' : 'status-warning'))
                            <tr><td><strong>{{ $booking->booking_date->format('d M Y') }}</strong><small class="d-block text-muted">{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</small></td><td><a href="{{ route('provider.bookings.index', ['search' => $booking->booking_code]) }}" class="report-booking-code">{{ $booking->booking_code }}</a></td><td><strong>{{ $booking->user->name }}</strong><small class="d-block text-muted">{{ $booking->user->email }}</small></td><td><strong>{{ $booking->businessService->businessPlace->name }}</strong><small class="d-block text-muted">{{ $booking->businessService->name }}</small></td><td class="text-end"><strong class="text-success">Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}</strong></td><td><span class="status {{ $statusClass }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span></td></tr>
                        @empty
                            <tr><td colspan="6"><div class="report-empty-state"><i class="fas fa-receipt"></i><p>{{ __('Tidak ada transaksi yang sesuai filter.') }}</p></div></td></tr>
                        @endforelse
                    </tbody></table></div>
                    <div class="mt-3">{{ $transactions->links() }}</div>
                </div>
            </div>
        </div>
    </div>
@endsection
