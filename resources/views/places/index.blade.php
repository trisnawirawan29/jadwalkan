<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="{{ __('Temukan tempat dan layanan terbaik di sekitar Anda.') }}">
    <title>{{ __('Jelajahi tempat') }} · {{ $appName }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="landing-page bg-slate-950 text-slate-100 antialiased">
    @include('partials.public-navbar', ['activePage' => 'places'])

    <main>
        <section class="directory-hero"><div class="mx-auto max-w-7xl px-5 pb-12 pt-32 lg:px-8 lg:pt-36"><p class="landing-eyebrow">{{ __('Direktori tempat') }}</p><div class="mt-3 flex flex-wrap items-end justify-between gap-6"><div><h1 class="landing-section-title">{{ __('Temukan tempat yang tepat untuk Anda.') }}</h1><p class="mt-4 max-w-2xl leading-7 text-slate-400">{{ __('Jelajahi tempat aktif berdasarkan wilayah dan kategori, lalu temukan rekomendasi terdekat dari lokasi Anda.') }}</p></div><div class="directory-total"><strong>{{ $places->total() }}</strong><span>{{ __('tempat tersedia') }}</span></div></div></div></section>

        <section class="mx-auto -mt-5 max-w-7xl px-5 lg:px-8"><form method="GET" action="{{ route('places.index') }}" class="directory-filter"><div class="directory-filter-heading"><span class="directory-filter-icon"><i class="fa-solid fa-sliders"></i></span><div><h2>{{ __('Filter pencarian') }}</h2><p>{{ __('Persempit hasil berdasarkan area dan kategori.') }}</p></div></div><div class="grid gap-3 md:grid-cols-2 lg:grid-cols-4"><label><span>{{ __('Provinsi') }}</span><select name="province" class="directory-select"><option value="">{{ __('Semua provinsi') }}</option>@foreach($regionOptions->unique('province_code')->filter(fn($region) => $region->province_code) as $region)<option value="{{ $region->province_code }}" @selected(request('province') === $region->province_code)>{{ $region->province_name }}</option>@endforeach</select></label><label><span>{{ __('Kabupaten / kota') }}</span><select name="regency" class="directory-select"><option value="">{{ __('Semua kabupaten / kota') }}</option>@foreach($regionOptions->unique('regency_code')->filter(fn($region) => $region->regency_code) as $region)<option value="{{ $region->regency_code }}" @selected(request('regency') === $region->regency_code)>{{ $region->regency_name }}</option>@endforeach</select></label><label><span>{{ __('Kecamatan') }}</span><select name="district" class="directory-select"><option value="">{{ __('Semua kecamatan') }}</option>@foreach($regionOptions->unique('district_code')->filter(fn($region) => $region->district_code) as $region)<option value="{{ $region->district_code }}" @selected(request('district') === $region->district_code)>{{ $region->district_name }}</option>@endforeach</select></label><label><span>{{ __('Kategori') }}</span><select name="category" class="directory-select"><option value="">{{ __('Semua kategori') }}</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected((string) request('category') === (string) $category->id)>{{ $category->name }} ({{ $category->places_count }})</option>@endforeach</select></label></div><div class="mt-4 flex flex-wrap justify-end gap-2"><a href="{{ route('places.index') }}" class="directory-reset">{{ __('Reset filter') }}</a><button class="landing-primary-button" type="submit"><i class="fa-solid fa-magnifying-glass"></i>{{ __('Terapkan filter') }}</button></div></form></section>

        <section class="mx-auto max-w-7xl px-5 pb-20 pt-10 lg:px-8"><div class="directory-nearest-banner"><div class="flex items-center gap-3"><span class="directory-nearest-icon"><i class="fa-solid fa-location-crosshairs"></i></span><div><h2>{{ __('Rekomendasi terdekat') }}</h2><p id="location-status">{{ __('Aktifkan lokasi untuk mengurutkan tempat terdekat dari Anda.') }}</p></div></div><button id="use-location" class="directory-location-button" type="button">{{ __('Gunakan lokasi saya') }} <i class="fa-solid fa-arrow-right"></i></button></div><div class="mt-9 flex flex-wrap items-end justify-between gap-4"><div><p class="landing-eyebrow">{{ __('Daftar tempat') }}</p><h2 class="landing-section-title">{{ __('Pilihan tempat untuk Anda') }}</h2></div><span class="landing-result-count">{{ $places->count() }} {{ __('ditampilkan') }}</span></div><div id="places-grid" class="mt-8 grid gap-5 md:grid-cols-2 lg:grid-cols-3">@forelse($places as $place)<article class="directory-place-card" data-place-card data-latitude="{{ $place->latitude }}" data-longitude="{{ $place->longitude }}"><div class="directory-place-cover @if($place->cover_image_url) has-image @endif" @if($place->cover_image_url) style="background-image:url('{{ $place->cover_image_url }}')" @endif><span class="landing-place-badge"><i class="fa-solid fa-circle-check"></i> {{ __('Aktif') }}</span><span class="directory-distance" data-distance></span><span class="landing-place-mark"><i class="fa-solid fa-building"></i></span></div><div class="p-5"><h3 class="text-lg font-bold text-white">{{ $place->name }}</h3><p class="mt-2 line-clamp-2 text-sm leading-6 text-slate-400"><i class="fa-solid fa-location-dot mr-1 text-indigo-300"></i>{{ $place->address ?: __('Alamat belum tersedia') }}</p><div class="mt-4 flex flex-wrap gap-2">@foreach($place->businessCategories->take(2) as $category)<span class="landing-tag">{{ $category->name }}</span>@endforeach<span class="landing-tag">{{ $place->active_services_count }} {{ __('layanan') }}</span></div><div class="mt-5 flex flex-wrap items-center justify-between gap-3 border-t border-white/10 pt-4"><div class="flex items-center gap-3">@if($place->google_maps_url)<a href="{{ $place->google_maps_url }}" target="_blank" rel="noopener" class="landing-card-link">{{ __('Buka Maps') }} <i class="fa-solid fa-arrow-up-right-from-square"></i></a>@else<span class="text-xs text-slate-500">{{ __('Lokasi segera tersedia') }}</span>@endif<span class="text-xs text-slate-500">{{ $place->province_name ?: __('Indonesia') }}</span></div>@auth<a href="{{ route('places.show', $place) }}" class="landing-book-now"><i class="fa-solid fa-calendar-check"></i>{{ __('Book now') }}</a>@else<a href="{{ route('login', ['booking' => 'required']) }}" class="landing-book-now"><i class="fa-solid fa-lock"></i>{{ __('Book now') }}</a>@endauth</div></div></article>@empty<div class="landing-empty md:col-span-2 lg:col-span-3">{{ __('Belum ada tempat yang sesuai dengan filter Anda.') }}</div>@endforelse</div><div class="mt-8">{{ $places->links() }}</div></section>
    </main>

    <footer class="border-t border-white/10"><div class="mx-auto flex max-w-7xl flex-col gap-3 px-5 py-7 text-sm text-slate-500 sm:flex-row sm:items-center sm:justify-between lg:px-8"><span>© {{ date('Y') }} {{ $appName }}</span><span>{{ __('Temukan lebih banyak, rencanakan lebih mudah.') }}</span></div></footer>
    <script>
        const useLocationButton = document.getElementById('use-location');
        const locationStatus = document.getElementById('location-status');
        const placesGrid = document.getElementById('places-grid');
        const placeCards = [...document.querySelectorAll('[data-place-card]')];
        const placeUrls = @json($places->mapWithKeys(fn ($place) => [$place->name => route('places.show', $place)]));
        placeCards.forEach(card => card.addEventListener('click', event => { if (event.target.closest('a,button')) return; const name = card.querySelector('h3, a')?.textContent.trim(); if (placeUrls[name]) window.location.href = placeUrls[name]; }));
        const distanceBetween = (latitudeOne, longitudeOne, latitudeTwo, longitudeTwo) => {
            const radians = value => value * Math.PI / 180;
            const latitudeDelta = radians(latitudeTwo - latitudeOne);
            const longitudeDelta = radians(longitudeTwo - longitudeOne);
            const a = Math.sin(latitudeDelta / 2) ** 2 + Math.cos(radians(latitudeOne)) * Math.cos(radians(latitudeTwo)) * Math.sin(longitudeDelta / 2) ** 2;
            return 6371 * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
        };
        useLocationButton?.addEventListener('click', () => {
            if (!navigator.geolocation) { locationStatus.textContent = '{{ __('Browser Anda tidak mendukung fitur lokasi.') }}'; return; }
            useLocationButton.disabled = true; locationStatus.textContent = '{{ __('Meminta izin lokasi...') }}';
            navigator.geolocation.getCurrentPosition(position => {
                const { latitude, longitude } = position.coords;
                const ranked = placeCards.map(card => ({ card, distance: card.dataset.latitude && card.dataset.longitude ? distanceBetween(latitude, longitude, Number(card.dataset.latitude), Number(card.dataset.longitude)) : Number.POSITIVE_INFINITY })).sort((first, second) => first.distance - second.distance);
                ranked.forEach(({ card, distance }) => { card.dataset.distanceValue = distance; const label = card.querySelector('[data-distance]'); if (Number.isFinite(distance)) { label.textContent = `${distance.toFixed(1)} km`; label.classList.add('is-visible'); } placesGrid.appendChild(card); });
                const nearest = ranked.find(item => Number.isFinite(item.distance));
                locationStatus.textContent = nearest ? `{{ __('Tempat terdekat berjarak') }} ${nearest.distance.toFixed(1)} km {{ __('dari lokasi Anda.') }}` : '{{ __('Belum ada tempat dengan koordinat yang tersedia.') }}';
                useLocationButton.innerHTML = '{{ __('Lokasi aktif') }} <i class="fa-solid fa-check"></i>'; useLocationButton.disabled = false;
            }, () => { locationStatus.textContent = '{{ __('Lokasi tidak dapat diakses. Periksa izin browser Anda.') }}'; useLocationButton.disabled = false; });
        });
    </script>
</body>
</html>
