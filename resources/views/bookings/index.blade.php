@extends('layouts.admin')

@section('title', 'Booking Saya')
@section('page-title', 'Booking Saya')
@section('page-subtitle', $view === 'history' ? 'Lihat kembali booking yang sudah selesai atau terlewat.' : 'Pantau booking mendatang Anda dari yang paling dekat.')

@section('content')
    <section class="booking-explore-section" aria-labelledby="booking-explore-title">
        <div class="booking-explore-content">
            <span class="booking-explore-kicker"><i class="fas fa-sparkles me-1"></i>Temukan pengalaman baru</span>
            <h2 id="booking-explore-title">Siap menemukan jadwal yang cocok?</h2>
            <p>Jelajahi tempat dan layanan pilihan, lalu amankan jadwal sesuai kebutuhan Anda.</p>
            <div class="booking-explore-benefits"><span><i class="fas fa-location-dot"></i>Pilihan tempat</span><span><i class="fas fa-clock"></i>Jadwal fleksibel</span><span><i class="fas fa-bolt"></i>Booking mudah</span></div>
        </div>
        <a href="{{ route('places.index') }}" class="booking-explore-action"><span class="booking-explore-action-icon"><i class="fas fa-compass"></i></span><span><strong>Jelajahi tempat</strong><small>Mulai booking baru</small></span><i class="fas fa-arrow-right"></i></a>
    </section>

    @if ($display === 'list')
        <section class="content-card booking-all-status-panel">
            <div class="card-heading mb-3"><div><span class="booking-section-kicker"><i class="fas fa-list-check me-1"></i>Semua status booking</span><h5 class="mb-1">{{ $view === 'history' ? 'Riwayat booking' : 'Booking mendatang' }}</h5><p class="mb-0">Lihat seluruh status booking yang pernah Anda lakukan secara lengkap.</p></div><a href="{{ route('bookings.index', ['month' => $calendarMonth->format('Y-m')]) }}" class="btn btn-light btn-sm"><i class="fas fa-calendar-days me-1"></i>Kembali ke kalender</a></div>
            <form method="GET" action="{{ route('bookings.index') }}" class="booking-list-filters">
                <input type="hidden" name="view" value="{{ $view }}"><input type="hidden" name="month" value="{{ $calendarMonth->format('Y-m') }}"><input type="hidden" name="display" value="list">
                <div class="row g-2 align-items-end">
                    <div class="col-12 col-lg-5"><label class="form-label small fw-semibold" for="booking-search">Cari booking</label><div class="input-group"><span class="input-group-text"><i class="fas fa-search"></i></span><input id="booking-search" type="search" name="search" class="form-control" value="{{ $filters['search'] ?? '' }}" placeholder="Kode, tempat, atau layanan"></div></div>
                    <div class="col-12 col-md-5 col-lg-3"><label class="form-label small fw-semibold" for="booking-status">Status</label><select id="booking-status" name="status" class="form-select"><option value="">Semua status</option><option value="held" @selected(($filters['status'] ?? '') === 'held')>Sedang hold</option><option value="payment_submitted" @selected(($filters['status'] ?? '') === 'payment_submitted')>Menunggu verifikasi</option><option value="confirmed" @selected(($filters['status'] ?? '') === 'confirmed')>Confirmed</option><option value="rejected" @selected(($filters['status'] ?? '') === 'rejected')>Ditolak</option><option value="cancelled" @selected(($filters['status'] ?? '') === 'cancelled')>Dibatalkan</option></select></div>
                    <div class="col-12 col-md-5 col-lg-2"><label class="form-label small fw-semibold" for="booking-date">Tanggal</label><input id="booking-date" type="date" name="booking_date" class="form-control" value="{{ $filters['booking_date'] ?? '' }}"></div>
                    <div class="col-12 col-md-2 col-lg-2 d-flex gap-2"><button class="btn btn-primary flex-grow-1"><i class="fas fa-filter me-1"></i>Filter</button><a href="{{ route('bookings.index', ['view' => $view, 'display' => 'list', 'month' => $calendarMonth->format('Y-m')]) }}" class="btn btn-light" title="Reset filter"><i class="fas fa-rotate-left"></i></a></div>
                </div>
            </form>
            <nav class="booking-list-tabs" aria-label="Pilih kelompok booking">
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => 'upcoming', 'month' => $calendarMonth->format('Y-m'), 'display' => 'list'])) }}" class="{{ $view === 'upcoming' ? 'active' : '' }}"><span class="booking-list-tab-icon"><i class="fas fa-calendar-day"></i></span><span><strong>Booking mendatang</strong><small>Jadwal yang akan datang</small></span></a>
                <a href="{{ route('bookings.index', array_merge(request()->except(['page', 'month']), ['view' => 'history', 'month' => $calendarMonth->format('Y-m'), 'display' => 'list'])) }}" class="{{ $view === 'history' ? 'active' : '' }}"><span class="booking-list-tab-icon"><i class="fas fa-clock-rotate-left"></i></span><span><strong>Riwayat booking</strong><small>Booking yang sudah lewat</small></span></a>
            </nav>
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
        </section>
    @else
        <div class="booking-calendar-booking-layout">
            <section class="content-card booking-calendar-panel">
                <div class="card-heading mb-3"><div><span class="booking-section-kicker"><i class="fas fa-calendar-days me-1"></i>Jadwal visual</span><h5 class="mb-1">Kalender booking</h5><p class="mb-0">Pilih booking pada kalender untuk melihat informasinya.</p></div><span class="booking-panel-icon is-calendar"><i class="fas fa-calendar-check"></i></span></div>
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
                                        <a href="{{ route('bookings.show', $booking) }}" class="booking-calendar-event {{ $booking->status === 'confirmed' ? 'is-confirmed' : 'is-held' }}" data-calendar-booking data-booking-code="{{ $booking->booking_code }}" data-booking-place="{{ $booking->businessService->businessPlace->name }}" data-booking-service="{{ $booking->businessService->name }}" data-booking-date="{{ $booking->booking_date->translatedFormat('d M Y') }}" data-booking-time="{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}" data-booking-status="{{ ucfirst(str_replace('_', ' ', $booking->status)) }}" data-booking-total="Rp {{ number_format((float) $booking->total_cost, 0, ',', '.') }}" data-booking-url="{{ route('bookings.show', $booking) }}" title="{{ $booking->businessService->businessPlace->name }} · {{ $booking->businessService->name }}">
                                            <strong>{{ substr($booking->start_time, 0, 5) }}–{{ substr($booking->end_time, 0, 5) }}</strong><span>{{ $booking->businessService->businessPlace->name }}</span><small>{{ $booking->businessService->name }} · {{ $booking->booking_code }}</small>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="booking-calendar-legend"><span><i class="booking-legend-dot is-confirmed"></i>Confirmed</span><span><i class="booking-legend-dot is-held"></i>Menunggu pembayaran</span></div>
            </section>
            <aside class="content-card booking-selected-detail" aria-live="polite">
                <div class="card-heading mb-3"><div><span class="booking-section-kicker"><i class="fas fa-circle-info me-1"></i>Informasi booking</span><h5 class="mb-1">Detail pilihan Anda</h5><p class="mb-0">Klik salah satu booking di kalender.</p></div><span class="booking-panel-icon"><i class="fas fa-receipt"></i></span></div>
                <div class="booking-detail-empty" data-booking-empty><i class="fas fa-hand-pointer"></i><strong>Pilih booking pada kalender</strong><p>Informasi tempat, jadwal, status, dan total biaya akan tampil di sini.</p></div>
                <div class="booking-detail-content d-none" data-booking-detail>
                    <div class="booking-detail-status" data-booking-status></div>
                    <div class="booking-selected-place"><span class="booking-selected-place-icon"><i class="fas fa-location-dot"></i></span><div><strong data-booking-place></strong><small data-booking-service></small></div></div>
                    <div class="booking-selected-info"><div><small>TANGGAL</small><strong data-booking-date></strong></div><div><small>JAM</small><strong data-booking-time></strong></div><div><small>TOTAL</small><strong data-booking-total></strong><small class="booking-selected-code" data-booking-code></small></div></div>
                    <a href="#" class="btn btn-primary w-100 mt-4" data-booking-url><i class="fas fa-arrow-right me-2"></i>Buka detail booking</a>
                </div>
            </aside>
        </div>
        <a href="{{ route('bookings.index', ['display' => 'list', 'view' => 'history', 'month' => $calendarMonth->format('Y-m')]) }}" class="booking-all-status-notice"><span class="booking-all-status-icon"><i class="fas fa-list-check"></i></span><span><strong>Ingin melihat semua status booking?</strong><small>Buka riwayat lengkap untuk melihat booking hold, menunggu verifikasi, confirmed, rejected, dan dibatalkan.</small></span><i class="fas fa-arrow-right"></i></a>
    @endif

    @if ($display !== 'list')
        <script>
            document.querySelectorAll('[data-calendar-booking]').forEach((bookingElement) => {
                bookingElement.addEventListener('click', (event) => {
                    event.preventDefault();
                    const detailPanel = document.querySelector('[data-booking-detail]');
                    const emptyPanel = document.querySelector('[data-booking-empty]');

                    emptyPanel?.classList.add('d-none');
                    detailPanel?.classList.remove('d-none');
                    document.querySelectorAll('[data-calendar-booking]').forEach((item) => item.classList.remove('is-selected'));
                    bookingElement.classList.add('is-selected');

                    detailPanel.querySelector('[data-booking-status]').textContent = bookingElement.dataset.bookingStatus;
                    detailPanel.querySelector('[data-booking-place]').textContent = bookingElement.dataset.bookingPlace;
                    detailPanel.querySelector('[data-booking-service]').textContent = bookingElement.dataset.bookingService;
                    detailPanel.querySelector('[data-booking-date]').textContent = bookingElement.dataset.bookingDate;
                    detailPanel.querySelector('[data-booking-time]').textContent = bookingElement.dataset.bookingTime;
                    detailPanel.querySelector('[data-booking-total]').textContent = bookingElement.dataset.bookingTotal;
                    detailPanel.querySelector('[data-booking-code]').textContent = bookingElement.dataset.bookingCode;
                    detailPanel.querySelector('[data-booking-url]').href = bookingElement.dataset.bookingUrl;
                });
            });
        </script>
    @endif
@endsection
