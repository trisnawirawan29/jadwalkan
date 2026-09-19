@php($activePage = $activePage ?? null)
<header class="landing-nav fixed inset-x-0 top-0 z-50">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-6 px-5 py-4 lg:px-8">
        <a href="{{ route('landing') }}" class="flex items-center gap-3 no-underline">
            <span class="landing-logo"><i class="fa-solid fa-compass"></i></span>
            <span class="text-lg font-extrabold tracking-tight text-white">{{ $appName }}</span>
        </a>
        <nav class="hidden items-center gap-8 text-sm font-semibold text-slate-300 md:flex">
            <a href="{{ route('landing') }}" class="transition hover:text-white {{ $activePage === 'home' ? 'text-white' : '' }}">{{ __('Beranda') }}</a>
            <a href="{{ route('places.index') }}" class="transition hover:text-white {{ $activePage === 'places' ? 'text-white' : '' }}">{{ __('Jelajahi') }}</a>
            <a href="{{ route('landing') }}#sebaran" class="transition hover:text-white">{{ __('Sebaran tempat') }}</a>
            <a href="{{ route('user-manual') }}" class="transition hover:text-white {{ $activePage === 'manual' ? 'text-white' : '' }}">{{ __('Panduan') }}</a>
        </nav>
        <div class="flex items-center gap-2">
            <div class="relative group">
                <button type="button" class="landing-language-button" aria-label="{{ __('Bahasa') }}"><i class="fa-solid fa-language"></i><span>{{ strtoupper(app()->getLocale()) }}</span><i class="fa-solid fa-chevron-down text-[10px]"></i></button>
                <div class="landing-language-menu absolute right-0 top-full mt-2 hidden min-w-36 rounded-2xl p-1.5 shadow-xl group-focus-within:block group-hover:block">
                    @foreach (['id' => 'Bahasa Indonesia', 'en' => 'English'] as $locale => $label)
                        <form method="POST" action="{{ route('language.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="flex w-full items-center justify-between rounded-xl px-3 py-2 text-left text-sm {{ app()->getLocale() === $locale ? 'active' : '' }}">{{ $label }} @if(app()->getLocale() === $locale)<i class="fa-solid fa-check"></i>@endif</button></form>
                    @endforeach
                </div>
            </div>
            @auth
                <a href="{{ route('dashboard') }}" class="landing-nav-cta">{{ __('Dashboard') }}</a>
            @else
                <a href="{{ route('login') }}" class="landing-login hidden sm:inline">{{ __('Masuk') }}</a>
                @if(Route::has('register'))<a href="{{ route('register') }}" class="landing-nav-cta">{{ __('Mulai sekarang') }}</a>@endif
            @endauth
        </div>
    </div>
</header>
