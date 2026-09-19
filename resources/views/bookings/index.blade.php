@extends('layouts.admin')

@section('title', 'Booking Saya')
@section('page-title', 'Booking Saya')
@section('page-subtitle', $display === 'calendar' ? 'Lihat riwayat dan booking mendatang dalam satu kalender.' : ($view === 'history' ? 'Lihat kembali booking yang sudah selesai atau terlewat.' : 'Pantau booking mendatang Anda dari yang paling dekat.'))

@section('content')
    <div class="content-card booking-list-card">
        <div class="card-heading booking-list-heading">
            <div><h5 class="mb-1">{{ $display === 'calendar' ? 'Kalender booking' : ($view === 'history' ? 'Riwayat booking' : 'Booking mendatang') }}</h5><p class="mb-0">{{ $display === 'calendar' ? 'Semua booking, baik yang sudah lewat maupun yang akan datang, ditampilkan berdasarkan tanggal.' : ($view === 'history' ? 'Booking ditampilkan berdasarkan bulan dan waktu terbaru.' : 'Booking terdekat tampil paling atas agar mudah dipersiapkan.') }}</p></div>
            <a href="{{ route('places.index') }}" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Cari tempat</a>
        </div>

        <div class="booking-list-toolbar">
            @if ($display === 'list')<div class="booking-list-tabs">
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => 'upcoming', 'month' => $calendarMonth->format('Y-m'), 'display' => $display])) }}" class="{{ $view === 'upcoming' ? 'active' : '' }}"><i class="fas fa-calendar-day me-2"></i>Booking mendatang</a>
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => 'history', 'month' => $calendarMonth->format('Y-m'), 'display' => $display])) }}" class="{{ $view === 'history' ? 'active' : '' }}"><i class="fas fa-clock-rotate-left me-2"></i>Riwayat booking</a>
            </div>@endif
            <div class="booking-display-switch" role="group" aria-label="Pilih tampilan booking">
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'display', 'search', 'status', 'booking_date']), ['display' => 'calendar'])) }}" class="{{ $display === 'calendar' ? 'active' : '' }}"><i class="fas fa-calendar-days me-1"></i>Kalender</a>
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'display']), ['display' => 'list'])) }}" class="{{ $display === 'list' ? 'active' : '' }}"><i class="fas fa-list me-1"></i>List</a>
            </div>
        </div>

        @if ($display === 'list')
        <form method="GET" action="{{ route('bookings.index') }}" class="booking-list-filters">
            <input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="month" value="{{ $calendarMonth->format('Y-m') }}"><input type="hidden" name="display" value="list">
            <div class="row g-2 align-items-end">
                <div class="col-12 col-lg-5"><label class="form-label small fw-semibold" for="booking-search">Cari booking</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input id="booking-search" type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode, tempat, atau layanan"></div></div>
                <div class="col-12 col-md-5 col-lg-3"><label class="form-label small fw-semibold" for="booking-status">Status</label><select id="booking-status" name="status" class="form-select"><option value="">Semua status</option><option value="held" @selected(($filters['status'] ?? '') === 'held')>Sedang hold</option><option value="payment_submitted" @selected(($filters['status'] ?? '') === 'payment_submitted')>Menunggu verifikasi</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmed</option><option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Ditolak</option></select></div>
                <div class="col-12 col-md-5 col-lg-2"><label class="form-label small fw-semibold" for="booking-date">Tanggal</label><input id="booking-date" type="date" name="booking_date" class="form-control" value="{{ $filters['booking_date'] ?? '' }}"></div>
                <div class="col-12 col-md-2 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i>Filter</button><a href="{{ route('bookings.index', ['view' => $view, 'month' => $calendarMonth->format('Y-m')]) }}" class="btn btn-light" title="Reset filter"><i class="fas fa-rotate-left"></i></a></div>
            </div>
        </form>

        <div class="booking-list-summary"><span><strong>{{ $bookings->total() }}</strong> booking ditemukan</span><span><i class="fas fa-arrow-{{ $view === 'history' ? 'down' : 'up' }}-wide-short me-1"></i>{{ $view === 'history' ? 'Terbaru terlebih dahulu' : 'Terdekat terlebih dahulu' }}</span></div>
        <div class="booking-list-items">
            @forelse($bookings as $booking)
                <a href="{{ route('bookings.show', $booking) }}" class="booking-list-item">
                    <span class="booking-list-date" aria-label="{{ $booking->booking_date->translatedFormat('l, d M Y') }}"><strong>{{ $booking->booking_date->format('d') }}</strong><small>{{ $booking->booking_date->translatedFormat('M Y') }}</small></span>
                    <span class="booking-list-main"><strong class="booking-list-place"><i class="fas fa-location-dot me-1"></i>{{ $booking->businessService->businessPlace->name }}</strong><span class="booking-list-primary"><span><i class="fas fa-clock me-1"></i>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }} <span class="timezone-badge" data-indonesia-timezone>WITA</span></span></span><span class="booking-list-secondary"><span><i class="fas fa-layer-group me-1"></i>{{ $booking->businessService->name }}</span><span><i class="fas fa-hashtag me-1"></i>{{ $booking->booking_code }}</span></span></span>
                    <span class="booking-list-side"><span class="status {{ $booking->status === 'confirmed' ? 'status-success' : ($booking->status === 'rejected' ? 'status-danger' : 'status-warning') }}">{{ ucfirst(str_replace('_', ' ', $booking->status)) }}</span><strong>Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}</strong><i class="fas fa-chevron-right"></i></span>
                </a>
            @empty
                <div class="provider-empty-state"><i class="fas fa-calendar-plus"></i><p>{{ $view === 'history' ? 'Belum ada riwayat booking yang sesuai.' : 'Belum ada booking mendatang.' }}</p><a href="{{ route('places.index') }}" class="btn btn-primary">Jelajahi tempat</a></div>
            @endforelse
        </div>
        @if ($bookings->hasPages())<div class="mt-4">{{ $bookings->links() }}</div>@endif
        @else

        <div class="booking-calendar-toolbar">
            <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => $view, 'month' => $calendarMonth->copy()->subMonth()->format('Y-m')])) }}" class="btn btn-light btn-sm" aria-label="Bulan sebelumnya"><i class="fas fa-chevron-left"></i></a>
            <div><strong>{{ $calendarMonth->translatedFormat('F Y') }}</strong><small>{{ $calendarBookings->count() }} booking pada kalender</small></div>
            <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => $view, 'month' => $calendarMonth->copy()->addMonth()->format('Y-m')])) }}" class="btn btn-light btn-sm" aria-label="Bulan berikutnya"><i class="fas fa-chevron-right"></i></a>
        </div>

        <div class="booking-calendar-scroll">
            <div class="booking-calendar-grid">
                @foreach (['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $weekday)<div class="booking-calendar-weekday">{{ $weekday }}</div>@endforeach
                @foreach ($calendarDays as $day)
                    @php($dayBookings = $calendarBookingsByDate->get($day->toDateString(), collect()))
                    <div class="booking-calendar-day {{ $day->month !== $calendarMonth->month ? 'is-outside' : '' }} {{ $day->isToday() ? 'is-today' : '' }}">
                        <div class="booking-calendar-day-number">{{ $day->day }}</div>
                        <div class="booking-calendar-events">
                            @foreach ($dayBookings as $booking)
                                <a href="{{ route('bookings.show', $booking) }}" class="booking-calendar-event {{ $booking->status === 'confirmed' ? 'is-confirmed' : ($booking->status === 'rejected' ? 'is-rejected' : 'is-held') }}" title="{{ $booking->businessService->businessPlace->name }} · {{ $booking->businessService->name }}">
                                    <strong>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</strong><span>{{ $booking->businessService->businessPlace->name }}</span><small>{{ $booking->businessService->name }} · {{ $booking->booking_code }}</small>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="booking-calendar-legend"><span><i class="booking-legend-dot is-confirmed"></i>Confirmed</span><span><i class="booking-legend-dot is-held"></i>Menunggu pembayaran</span><span><i class="booking-legend-dot is-rejected"></i>Ditolak</span></div>
        @endif
    </div>
@endsection
