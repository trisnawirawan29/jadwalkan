<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('Temukan tempat dan layanan terbaik di sekitar Anda.') }}">
    <title>{{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page bg-slate-950 text-slate-100 antialiased">
    @include('partials.public-navbar', ['activePage' => 'home'])

    <main>
        <section class="landing-hero overflow-hidden">
            <div class="mx-auto grid max-w-7xl items-center gap-12 px-5 pb-20 pt-36 lg:grid-cols-[1.05fr_.95fr] lg:px-8 lg:pb-28 lg:pt-44">
                <div class="relative z-10">
                    <div class="landing-kicker"><span class="landing-pulse"></span>{{ __('Platform pencarian tempat lokal') }}</div>
                    <h1 class="mt-6 max-w-3xl text-4xl font-extrabold leading-[1.08] tracking-[-.04em] text-white sm:text-6xl">{{ __('Temukan tempat terbaik untuk aktivitas Anda.') }}</h1>
                    <p class="mt-6 max-w-xl text-base leading-8 text-slate-300 sm:text-lg">{{ __('Temukan tempat, pilih jam yang paling nyaman, dan booking dalam beberapa langkah. Semua kebutuhan aktivitas Anda dimulai dari sini.') }}</p>
                    <div class="mt-9 flex flex-wrap gap-3">
                        @auth
                            <a href="{{ route('dashboard') }}" class="landing-primary-button"><i class="fa-solid fa-gauge-high"></i><span>{{ __('Dashboard') }}</span><i class="fa-solid fa-arrow-right"></i></a>
                        @else
                            <a href="{{ route('login') }}" class="landing-primary-button"><i class="fa-solid fa-right-to-bracket"></i><span>{{ __('Login') }}</span><i class="fa-solid fa-arrow-right"></i></a>
                        @endauth
                        <a href="{{ route('places.index') }}" class="landing-secondary-button"><i class="fa-solid fa-compass"></i><span>{{ __('Jelajahi tempat') }}</span></a>
                    </div>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold"><span class="landing-audience-pill"><i class="fa-solid fa-calendar-check"></i>{{ __('Untuk booking') }}</span><span class="landing-audience-pill"><i class="fa-solid fa-store"></i>{{ __('Untuk pemilik bisnis') }}</span></div>
                    <div class="mt-12 flex items-center gap-4 text-sm text-slate-400"><div class="flex -space-x-2"><span class="landing-avatar bg-orange-300">A</span><span class="landing-avatar bg-cyan-300">B</span><span class="landing-avatar bg-violet-300">C</span></div><span>{{ __('Tempat baru terus bertambah setiap hari') }}</span></div>
                </div>
                <div class="landing-hero-art relative mx-auto w-full max-w-xl">
                    <div class="landing-glow"></div>
                    <div class="landing-orbit landing-orbit-one"></div><div class="landing-orbit landing-orbit-two"></div>
                    <div class="landing-map-card">
                        <div class="flex items-center justify-between"><div><p class="text-xs font-semibold uppercase tracking-[.2em] text-slate-400">{{ __('Eksplorasi') }}</p><p class="mt-1 text-lg font-bold text-slate-900">{{ __('Tempat di sekitar Anda') }}</p></div><span class="landing-map-icon"><i class="fa-solid fa-location-dot"></i></span></div>
                        <div class="landing-mini-map"><span class="mini-road road-a"></span><span class="mini-road road-b"></span><span class="mini-road road-c"></span><span class="mini-pin pin-a"><i class="fa-solid fa-basketball"></i></span><span class="mini-pin pin-b"><i class="fa-solid fa-music"></i></span><span class="mini-pin pin-c"><i class="fa-solid fa-microphone"></i></span><span class="mini-pin pin-d"><i class="fa-solid fa-star"></i></span></div>
                        <div class="mt-4 flex items-center justify-between rounded-2xl bg-slate-50 px-4 py-3"><span class="text-xs font-semibold text-slate-500">{{ __('Lokasi populer') }}</span><span class="text-xs font-bold text-indigo-600">{{ $activePlaces->count() }} {{ __('tempat aktif') }}</span></div>
                    </div>
                </div>
            </div>
        </section>

        <section class="relative z-10 mx-auto -mt-2 max-w-7xl px-5 lg:px-8">
            <div class="landing-stats-grid">
                <div class="landing-stat-card"><span class="landing-stat-icon bg-indigo-500/15 text-indigo-300"><i class="fa-solid fa-building"></i></span><div><p class="landing-stat-value">{{ number_format($activePlaces->count()) }}</p><p class="landing-stat-label">{{ __('Bisnis aktif') }}</p></div></div>
                <div class="landing-stat-card"><span class="landing-stat-icon bg-cyan-500/15 text-cyan-300"><i class="fa-solid fa-layer-group"></i></span><div><p class="landing-stat-value">{{ number_format($activeServiceCount) }}</p><p class="landing-stat-label">{{ __('Layanan tersedia') }}</p></div></div>
                <div class="landing-stat-card"><span class="landing-stat-icon bg-amber-500/15 text-amber-300"><i class="fa-solid fa-shapes"></i></span><div><p class="landing-stat-value">{{ number_format($categories->count()) }}</p><p class="landing-stat-label">{{ __('Kategori pilihan') }}</p></div></div>
                <div class="landing-stat-card"><span class="landing-stat-icon bg-emerald-500/15 text-emerald-300"><i class="fa-solid fa-map-location-dot"></i></span><div><p class="landing-stat-value">{{ number_format($mapPlaces->count()) }}</p><p class="landing-stat-label">{{ __('Lokasi di peta') }}</p></div></div>
            </div>
        </section>

        <section id="booking" class="landing-section mx-auto max-w-7xl px-5 lg:px-8">
            <div class="grid items-center gap-8 lg:grid-cols-[.85fr_1.15fr]">
                <div>
                    <p class="landing-eyebrow">{{ __('Booking lebih praktis') }}</p>
                    <h2 class="landing-section-title">{{ __('Pesan tempat favorit Anda dengan mudah.') }}</h2>
                    <p class="mt-4 max-w-lg leading-7 text-slate-400">{{ __('Tidak perlu chat atau datang langsung. Pilih tempat, tentukan layanan dan jadwal yang tersedia, lalu selesaikan booking melalui aplikasi.') }}</p>
                    <a href="#jelajahi" class="landing-primary-button mt-7">{{ __('Mulai cari tempat') }} <i class="fa-solid fa-arrow-right"></i></a>
                </div>
                <div class="landing-booking-flow">
                    <div class="landing-booking-card"><span class="landing-booking-number">01</span><span class="landing-booking-icon bg-indigo-500/15 text-indigo-300"><i class="fa-solid fa-location-dot"></i></span><h3>{{ __('Pilih tempat') }}</h3><p>{{ __('Temukan lokasi yang sesuai di sekitar Anda.') }}</p></div>
                    <span class="landing-booking-connector"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="landing-booking-card"><span class="landing-booking-number">02</span><span class="landing-booking-icon bg-cyan-500/15 text-cyan-300"><i class="fa-solid fa-calendar-check"></i></span><h3>{{ __('Tentukan jadwal') }}</h3><p>{{ __('Lihat layanan dan waktu yang tersedia secara real-time.') }}</p></div>
                    <span class="landing-booking-connector"><i class="fa-solid fa-arrow-right"></i></span>
                    <div class="landing-booking-card"><span class="landing-booking-number">03</span><span class="landing-booking-icon bg-emerald-500/15 text-emerald-300"><i class="fa-solid fa-circle-check"></i></span><h3>{{ __('Konfirmasi booking') }}</h3><p>{{ __('Booking lebih cepat, rapi, dan mudah dipantau.') }}</p></div>
                </div>
            </div>
            <div class="landing-register-banner"><span class="landing-register-icon"><i class="fa-solid fa-user-plus"></i></span><div><h3>{{ __('Belum punya akun?') }}</h3><p>{{ __('Daftar gratis untuk menyimpan booking dan melihat aktivitas Anda dengan lebih mudah.') }}</p></div><a href="{{ route('register') }}" class="landing-register-button">{{ __('Daftar sekarang') }} <i class="fa-solid fa-arrow-right"></i></a></div>
        </section>

        <section id="kategori" class="landing-section mx-auto max-w-7xl px-5 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-5"><div><p class="landing-eyebrow">{{ __('Temukan sesuai minat') }}</p><h2 class="landing-section-title">{{ __('Jelajahi berdasarkan kategori') }}</h2></div><a href="#jelajahi" data-category-reset class="landing-text-link">{{ __('Lihat semua tempat') }} <i class="fa-solid fa-arrow-right"></i></a></div>
            <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($categories as $category)
                    <a href="#jelajahi" data-category-filter="{{ $category->name }}" class="landing-category-card"><span class="landing-category-icon"><i class="fa-solid {{ ['fa-basketball', 'fa-microphone-lines', 'fa-music', 'fa-table-tennis-paddle-ball'][$loop->index % 4] }}"></i></span><span class="min-w-0"><strong>{{ $category->name }}</strong><small>{{ $category->places_count }} {{ __('tempat') }}</small></span><i class="fa-solid fa-arrow-up-right-from-square ml-auto text-xs text-slate-500"></i></a>
                @empty
                    <div class="landing-empty sm:col-span-2 lg:col-span-4">{{ __('Belum ada kategori dengan tempat aktif.') }}</div>
                @endforelse
            </div>
        </section>

        <section id="sebaran" class="landing-section mx-auto max-w-7xl px-5 lg:px-8">
            <div class="landing-map-section"><div class="max-w-md"><p class="landing-eyebrow">{{ __('Jangkauan lokasi') }}</p><h2 class="landing-section-title">{{ __('Lihat sebaran tempat dalam satu peta.') }}</h2><p class="mt-4 leading-7 text-slate-400">{{ __('Gunakan peta interaktif untuk menemukan tempat terdekat dan buka petunjuk arah menuju lokasi pilihan Anda.') }}</p><div class="mt-7 flex items-center gap-3 text-sm text-slate-300"><span class="landing-check"><i class="fa-solid fa-check"></i></span>{{ __('Data lokasi diperbarui oleh pemilik bisnis') }}</div></div><div id="landing-map" class="landing-leaflet-map" data-empty="{{ __('Belum ada lokasi dengan koordinat.') }}"></div></div>
        </section>

        <section id="jelajahi" class="landing-section mx-auto max-w-7xl px-5 lg:px-8">
            <div class="flex flex-wrap items-end justify-between gap-5"><div><p class="landing-eyebrow">{{ __('Pilihan terbaru') }}</p><h2 class="landing-section-title">{{ __('Tempat yang siap Anda kunjungi') }}</h2></div><span class="landing-result-count">{{ $activePlaces->count() }} {{ __('tempat aktif') }}</span></div>
            <div id="landing-places-grid" class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">
                @forelse ($activePlaces->take(6) as $place)
                    <article data-place-categories="{{ $place->businessCategories->pluck('name')->implode('|') }}" class="landing-place-card"><div class="landing-place-cover @if ($place->cover_image_url) has-image @endif" @if ($place->cover_image_url) style="background-image:url('{{ $place->cover_image_url }}')" @endif><span class="landing-place-badge"><i class="fa-solid fa-circle-check"></i> {{ __('Aktif') }}</span><span class="landing-place-mark"><i class="fa-solid fa-building"></i></span></div><div class="p-5"><div class="flex items-start justify-between gap-3"><h3 class="text-lg font-bold text-white">{{ $place->name }}</h3><span class="text-xs font-bold text-amber-300"><i class="fa-solid fa-star"></i> {{ __('Pilihan') }}</span></div><p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-400"><i class="fa-solid fa-location-dot mr-1 text-indigo-300"></i>{{ $place->address ?: __('Alamat belum tersedia') }}</p><div class="mt-4 flex flex-wrap gap-2">@foreach ($place->businessCategories->take(2) as $category)<span class="landing-tag">{{ $category->name }}</span>@endforeach<span class="landing-tag">{{ $place->active_services_count }} {{ __('layanan') }}</span></div><div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4"><div class="flex items-center gap-3">@if ($place->google_maps_url)<a href="{{ $place->google_maps_url }}" target="_blank" rel="noopener" class="landing-card-link">{{ __('Buka Maps') }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@else<span class="text-xs text-slate-500">{{ __('Lokasi segera tersedia') }}</span>@endif<span class="text-xs font-semibold text-slate-500">{{ $place->province_name ?: __('Indonesia') }}</span></div>@auth<a href="{{ route('places.show', $place) }}" class="landing-book-now"><i class="fa-solid fa-calendar-check"></i>{{ __('Book now') }}</a>@else<a href="{{ route('login', ['booking' => 'required']) }}" class="landing-book-now"><i class="fa-solid fa-lock"></i>{{ __('Book now') }}</a>@endauth</div></div></article>
                @empty
                    <div class="landing-empty md:col-span-2 lg:col-span-3">{{ __('Belum ada tempat aktif yang dapat ditampilkan.') }}</div>
                @endforelse
            </div>
        </section>

        <section class="mx-auto max-w-7xl px-5 pt-16 pb-20 lg:px-8"><div class="landing-cta"><div><p class="landing-eyebrow text-indigo-200">{{ __('Untuk pemilik bisnis') }}</p><h2 class="mt-2 text-2xl font-extrabold text-white sm:text-3xl">{{ __('Bawa bisnis Anda lebih dekat dengan pelanggan.') }}</h2><p class="mt-3 max-w-xl text-sm leading-6 text-indigo-100/75">{{ __('Daftarkan tempat, layanan, dan jadwal Anda agar lebih mudah ditemukan.') }}</p></div><a href="{{ route('register') }}" class="landing-cta-button">{{ __('Daftarkan bisnis') }} <i class="fa-solid fa-arrow-right"></i></a></div></section>
    </main>

    <footer class="border-t border-white/10"><div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8"><span>© {{ date('Y') }} {{ $appName }}</span><span>{{ __('Temukan lebih banyak, rencanakan lebih mudah.') }}</span></div></footer>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const mapPlaces = {{ Illuminate\Support\Js::from($mapPlaces) }};
        const mapElement = document.getElementById('landing-map');
        if (mapElement && window.L) {
            const escapeHtml = value => String(value ?? '').replace(/[&<>"']/g, character => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[character]);
            const map = L.map(mapElement, { scrollWheelZoom: false }).setView([-2.5489, 118.0149], mapPlaces.length ? 5 : 4);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '&copy; OpenStreetMap contributors' }).addTo(map);
            const bounds = [];
            mapPlaces.forEach(place => {
                const coordinates = [place.latitude, place.longitude]; bounds.push(coordinates);
                const link = place.mapsUrl ? `<a href="${escapeHtml(place.mapsUrl)}" target="_blank" rel="noopener">{{ __('Buka petunjuk arah') }}</a>` : '';
                L.marker(coordinates).addTo(map).bindPopup(`<strong>${escapeHtml(place.name)}</strong><br><span>${escapeHtml(place.address)}</span><br>${link}`);
            });
            if (bounds.length > 1) map.fitBounds(bounds, { padding: [32, 32] });
            if (!mapPlaces.length) mapElement.innerHTML += `<div class="landing-map-empty">${mapElement.dataset.empty}</div>`;
        }
        document.querySelectorAll('[data-category-filter]').forEach(filter => filter.addEventListener('click', () => {
            const category = filter.dataset.categoryFilter;
            document.querySelectorAll('[data-place-categories]').forEach(place => {
                place.hidden = !place.dataset.placeCategories.split('|').includes(category);
            });
        }));
        document.querySelector('[data-category-reset]')?.addEventListener('click', () => {
            document.querySelectorAll('[data-place-categories]').forEach(place => { place.hidden = false; });
        });
    </script>
</body>
</html>
