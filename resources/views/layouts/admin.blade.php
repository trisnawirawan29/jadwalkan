<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @php($appSettings = \App\Models\Setting::pluck('value', 'key'))
    <title>@yield('title', 'Dashboard') · {{ $appSettings['app_name'] ?? 'NexaAdmin' }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    <link rel="stylesheet" href="{{ asset('css/profile.css') }}">
    <link rel="stylesheet" href="{{ asset('css/user-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/business-place-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/business-category-picker.css') }}">
    <link rel="stylesheet" href="{{ asset('css/business-service-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/business-service-schedule-summary.css') }}">
    <link rel="stylesheet" href="{{ asset('css/business-schedule-form.css') }}">
    <link rel="stylesheet" href="{{ asset('css/provider-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('css/provider-map.css') }}">
    <link rel="stylesheet" href="{{ asset('css/notifications.css') }}">
    <link rel="stylesheet" href="{{ asset('css/flash.css') }}">
    <link rel="stylesheet" href="{{ asset('css/alerts.css') }}">
    <link rel="stylesheet" href="{{ asset('css/settings.css') }}">
    <link rel="stylesheet" href="{{ asset('css/layout-colors.css') }}">
    <link rel="stylesheet" href="{{ asset('css/audit.css') }}">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="{{ asset('css/datatables.css') }}">
    <style>:root{--brand-color:{{ $appSettings['primary_color'] ?? '#6c63ff' }};--sidebar-color:{{ $appSettings['sidebar_color'] ?? '#1f2440' }};--navbar-color:{{ $appSettings['navbar_color'] ?? '#ffffff' }};--footer-color:{{ $appSettings['footer_color'] ?? '#ffffff' }};--brand-color-rgb:108,99,255}.avatar img{width:100%;height:100%;object-fit:cover}.profile-avatar{width:104px;height:104px;border-radius:50%;overflow:hidden;background:#ddd9ff;color:#5a50d8;display:flex;align-items:center;justify-content:center;font-size:38px;font-weight:800}.profile-avatar img{width:100%;height:100%;object-fit:cover}.brand-mark,.btn-primary{background-color:var(--brand-color)!important;border-color:var(--brand-color)!important}.text-primary{color:var(--brand-color)!important}.sidebar-menu .nav-link.active{background:var(--brand-color)!important}.profile-tabs .nav-link.active{background:var(--brand-color)!important}.app-sidebar{background:var(--sidebar-color)!important}.app-header{background-color:var(--navbar-color)!important}.app-footer{background-color:var(--footer-color)!important}</style>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
@if (session('success') || session('error') || session('warning') || session('info') || $errors->any())
    <div class="flash-stack" aria-live="polite" aria-atomic="true">
        @foreach (['success' => 'check-circle', 'error' => 'circle-xmark', 'warning' => 'triangle-exclamation', 'info' => 'circle-info'] as $flashType => $flashIcon)
            @if (session($flashType))
                <div class="flash-toast flash-{{ $flashType }}"><i class="fas fa-{{ $flashIcon }}"></i><span>{{ session($flashType) }}</span><button type="button" class="flash-close" aria-label="Tutup">&times;</button></div>
            @endif
        @endforeach
        @if ($errors->any())<div class="flash-toast flash-error"><i class="fas fa-circle-xmark"></i><span>{{ $errors->first() }}</span><button type="button" class="flash-close" aria-label="Tutup">&times;</button></div>@endif
    </div>
@endif
<div class="app-wrapper">
    @php($notificationItems = auth()->user()->notifications()->latest()->limit(5)->get())
    @php($notificationUnread = auth()->user()->unreadNotifications()->count())
    <nav class="app-header navbar navbar-expand bg-white border-bottom"><div class="container-fluid"><ul class="navbar-nav"><li class="nav-item"><a class="nav-link navbar-menu-toggle" data-lte-toggle="sidebar" href="#" aria-label="Buka menu"><i class="fas fa-bars"></i></a></li></ul><ul class="navbar-nav ms-auto align-items-center navbar-actions"><li class="nav-item dropdown"><a class="nav-link navbar-action navbar-notification-action position-relative" data-bs-toggle="dropdown" href="#" aria-label="Notifikasi"><i class="far fa-bell"></i>@if($notificationUnread)<span class="notification-count">{{ $notificationUnread > 9 ? '9+' : $notificationUnread }}</span>@endif</a><div class="dropdown-menu dropdown-menu-end notification-menu shadow-sm"><div class="notification-head"><strong>Notifikasi</strong><a href="{{ route('notifications') }}">Lihat semua</a></div>@forelse($notificationItems as $notification)<a class="notification-item {{ $notification->read_at ? '' : 'unread' }}" href="{{ route('notifications.read', $notification) }}" onclick="event.preventDefault();document.getElementById('read-{{ $notification->id }}').submit();"><span class="notification-icon"><i class="fas fa-{{ data_get($notification->data, 'type') === 'success' ? 'check' : 'bell' }}"></i></span><span><strong>{{ data_get($notification->data, 'title') }}</strong><small>{{ data_get($notification->data, 'message') }}</small></span></a><form id="read-{{ $notification->id }}" method="POST" action="{{ route('notifications.read', $notification) }}" class="d-none">@csrf @method('PATCH')</form>@empty<div class="empty-notification">Belum ada notifikasi.</div>@endforelse</div></li><li class="nav-item dropdown"><a class="nav-link navbar-action language-switcher" data-bs-toggle="dropdown" href="#" aria-label="{{ __('Language') }}"><i class="fas fa-language"></i><span>{{ strtoupper(app()->getLocale()) }}</span></a><div class="dropdown-menu dropdown-menu-end shadow-sm language-menu"><div class="dropdown-header">{{ __('Language') }}</div>@foreach (['id' => 'Bahasa Indonesia', 'en' => 'English'] as $locale => $label)<form method="POST" action="{{ route('language.update') }}">@csrf<input type="hidden" name="locale" value="{{ $locale }}"><button type="submit" class="dropdown-item d-flex align-items-center {{ app()->getLocale() === $locale ? 'active' : '' }}"><span>{{ $label }}</span>@if(app()->getLocale() === $locale)<i class="fas fa-check ms-auto"></i>@endif</button></form>@endforeach</div></li><li class="nav-item"><button type="button" class="theme-toggle nav-link navbar-action" data-theme-toggle aria-label="Ubah tema"><i class="fas fa-moon"></i></button></li><li class="nav-item dropdown"><a class="nav-link navbar-profile" data-bs-toggle="dropdown" href="#"><span class="avatar">@if(auth()->user()->avatar)<img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="Avatar">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</span><span class="d-none d-sm-inline text-dark fw-semibold">{{ auth()->user()->name }}</span><i class="fas fa-chevron-down small text-muted"></i></a><div class="dropdown-menu dropdown-menu-end shadow-sm"><a class="dropdown-item" href="{{ route('profile') }}"><i class="fas fa-user me-2"></i>Profil pengguna</a><div class="dropdown-divider"></div><button class="dropdown-item" form="logout-form"><i class="fas fa-sign-out-alt me-2"></i>Keluar</button></div></li></ul></div></nav>
    <aside class="app-sidebar bg-dark shadow" data-bs-theme="dark">
        <div class="sidebar-brand"><a href="{{ route('dashboard') }}" class="brand-link"><span class="brand-mark"><i class="fas fa-layer-group"></i></span><span class="brand-text fw-bold">{{ $appSettings['app_name'] ?? 'NexaAdmin' }}</span></a></div>
        <div class="sidebar-wrapper"><nav>
            <div class="user-panel d-flex align-items-center"><span class="avatar avatar-lg">@if(auth()->user()->avatar)<img src="{{ asset('storage/'.auth()->user()->avatar) }}" alt="Avatar">@else{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}@endif</span><div class="info"><a href="{{ route('profile') }}" class="d-block text-white">{{ auth()->user()->name }}</a><small>{{ ucfirst(auth()->user()->role) }}</small></div></div>
            <div class="nav-header">{{ __('MAIN MENU') }}</div>
            <ul class="nav sidebar-menu flex-column" role="menu">
                <li class="nav-item"><a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"><i class="nav-icon fas fa-chart-pie"></i><p>{{ __('Dashboard') }}</p></a></li>
                <li class="nav-item"><a href="{{ route('landing') }}" class="nav-link" target="_blank" rel="noopener"><i class="nav-icon fas fa-compass"></i><p>{{ __('Landing page') }}</p></a></li>
                @if (auth()->user()->isAdmin())
                    <li class="nav-item"><a href="{{ route('admin.users') }}" class="nav-link {{ request()->routeIs('admin.users*') ? 'active' : '' }}"><i class="nav-icon fas fa-users-cog"></i><p>{{ __('User management') }}</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.provider-applications.index') }}" class="nav-link {{ request()->routeIs('admin.provider-applications*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-check"></i><p>Pengajuan provider</p></a></li>
                    <li class="nav-item"><a href="{{ route('admin.business-categories.index') }}" class="nav-link {{ request()->routeIs('admin.business-categories*') ? 'active' : '' }}"><i class="nav-icon fas fa-sitemap"></i><p>{{ __('Business categories') }}</p></a></li>
                @endif
                @if (auth()->user()->isProvider())
                    <li class="nav-item"><a href="{{ route('provider.business-places.index') }}" class="nav-link {{ request()->routeIs('provider.business-places*') ? 'active' : '' }}"><i class="nav-icon fas fa-building"></i><p>{{ __('Business Places') }}</p></a></li>
                    <li class="nav-item"><a href="{{ route('provider.staff.index') }}" class="nav-link {{ request()->routeIs('provider.staff*') ? 'active' : '' }}"><i class="nav-icon fas fa-user-shield"></i><p>Pegawai provider</p></a></li>
                @endif
                @if (auth()->user()->isProvider() || auth()->user()->isProviderStaff())
                    <li class="nav-item"><a href="{{ route('provider.bookings.index') }}" class="nav-link {{ request()->routeIs('provider.bookings*') ? 'active' : '' }}"><i class="nav-icon fas fa-receipt"></i><p>{{ __('Booking masuk') }}</p></a></li>
                @endif
                @if (auth()->user()->hasRole('user'))
                    <li class="nav-item"><a href="{{ route('provider-application.create') }}" class="nav-link {{ request()->routeIs('provider-application.*') ? 'active' : '' }}"><i class="nav-icon fas fa-store-alt"></i><p>Pengajuan provider</p></a></li>
                @endif
                @if (auth()->user()->hasRole('user') || auth()->user()->isProvider())
                    <li class="nav-item"><a href="{{ route('bookings.index') }}" class="nav-link {{ request()->routeIs('bookings.*') ? 'active' : '' }}"><i class="nav-icon fas fa-calendar-check"></i><p>{{ __('Booking saya') }}</p></a></li>
                @endif
            </ul>
            <div class="nav-header mt-3">{{ __('MY ACCOUNT') }}</div>
            <ul class="nav sidebar-menu flex-column" role="menu">
                <li class="nav-item"><a href="{{ route('profile') }}" class="nav-link {{ request()->routeIs('profile*') ? 'active' : '' }}"><i class="nav-icon fas fa-user"></i><p>{{ __('Profile') }}</p></a></li>
            </ul>
            <div class="nav-header mt-3">{{ __('OTHER') }}</div>
            <ul class="nav sidebar-menu flex-column" role="menu">
                @if (auth()->user()->isAdmin())<li class="nav-item"><a href="{{ route('admin.settings') }}" class="nav-link {{ request()->routeIs('admin.settings*') ? 'active' : '' }}"><i class="nav-icon fas fa-sliders"></i><p>{{ __('Application settings') }}</p></a></li>@endif
                @if (auth()->user()->isAdmin())<li class="nav-item"><a href="{{ route('admin.audit-logs') }}" class="nav-link {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}"><i class="nav-icon fas fa-clock-rotate-left"></i><p>{{ __('Audit Log') }}</p></a></li>@endif
                <li class="nav-item"><form method="POST" action="{{ route('logout') }}" data-confirm="Apakah Anda yakin ingin keluar dari aplikasi?">@csrf<button type="submit" class="nav-link border-0 w-100 text-start bg-transparent"><i class="nav-icon fas fa-sign-out-alt"></i><p>{{ __('Logout') }}</p></button></form></li>
            </ul>
        </nav></div>
    </aside>

    <main class="app-main"><div class="app-content-header"><div class="container-fluid"><div class="d-flex justify-content-between align-items-center"><div><h1 class="page-title">@yield('page-title', 'Dashboard')</h1><p class="text-muted mb-0">@yield('page-subtitle', 'Ringkasan aktivitas bisnis Anda hari ini.')</p></div><div class="text-muted small d-none d-md-block"><i class="far fa-calendar-alt me-2"></i>{{ now()->translatedFormat('l, d F Y') }} <span class="timezone-badge" data-indonesia-timezone>WITA</span></div></div></div></div><div class="app-content"><div class="container-fluid">@yield('content')</div></div></main>
    <footer class="app-footer"><strong>© {{ date('Y') }} {{ $appSettings['app_name'] ?? 'NexaAdmin' }}.</strong><span class="text-muted"> {{ $appSettings['footer_text'] ?? 'Semua hak dilindungi.' }}</span><span class="float-end text-muted">v{{ $appSettings['app_version'] ?? '1.0.0' }}</span></footer>
</div>
<form id="logout-form" method="POST" action="{{ route('logout') }}" class="d-none" data-confirm="Apakah Anda yakin ingin keluar dari aplikasi?">@csrf</form>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script><script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script><script>(function(){const key='nexa-theme';const root=document.documentElement;const apply=theme=>{root.setAttribute('data-bs-theme',theme);document.querySelector('[data-theme-toggle] i')?.classList.toggle('fa-sun',theme==='dark');document.querySelector('[data-theme-toggle] i')?.classList.toggle('fa-moon',theme!=='dark')};apply(localStorage.getItem(key)||'{{ $appSettings['default_theme'] ?? 'light' }}');document.querySelector('[data-theme-toggle]')?.addEventListener('click',()=>{const next=root.getAttribute('data-bs-theme')==='dark'?'light':'dark';localStorage.setItem(key,next);apply(next)})})();document.querySelectorAll('.flash-close').forEach(button=>button.addEventListener('click',()=>button.parentElement.remove()));document.querySelectorAll('.flash-toast').forEach(toast=>setTimeout(()=>toast.remove(),5000));document.addEventListener('submit',event=>{const form=event.target;if(form.dataset.confirm&&!window.confirm(form.dataset.confirm)){event.preventDefault()}});document.querySelectorAll('[data-confirm-trigger]').forEach(button=>button.addEventListener('click',event=>{if(!window.confirm(button.dataset.confirmTrigger))event.preventDefault()}));</script>
<script>
    (() => {
        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const offset = -new Date().getTimezoneOffset() / 60;
        const zoneMap = {'Asia/Jakarta': 'WIB', 'Asia/Pontianak': 'WIB', 'Asia/Makassar': 'WITA', 'Asia/Ujung_Pandang': 'WITA', 'Asia/Jayapura': 'WIT'};
        const label = zoneMap[timezone] || ({7: 'WIB', 8: 'WITA', 9: 'WIT'}[offset] || 'WITA');
        document.querySelectorAll('[data-indonesia-timezone]').forEach(element => { element.textContent = label; });
        document.querySelectorAll('[data-local-datetime]').forEach(element => {
            const date = new Date(element.dataset.localDatetime);
            if (!Number.isNaN(date.getTime())) element.textContent = `${new Intl.DateTimeFormat(undefined, {dateStyle: 'medium', timeStyle: 'short'}).format(date)} ${label}`;
        });
    })();
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script><script>(function(){const stack=document.querySelector('.flash-stack');if(!stack||typeof Swal==='undefined')return;const types={success:{icon:'success',title:'Berhasil'},error:{icon:'error',title:'Terjadi kesalahan'},warning:{icon:'warning',title:'Perhatian'},info:{icon:'info',title:'Informasi'}};stack.querySelectorAll('.flash-toast').forEach(item=>{const type=Object.keys(types).find(name=>item.classList.contains('flash-'+name))||'info';const config=types[type];Swal.fire({toast:true,position:'top-end',icon:config.icon,title:config.title,html:item.querySelector('span')?.textContent||'',showConfirmButton:false,timer:4500,timerProgressBar:true,customClass:{popup:'nexa-swal-toast'}})});stack.remove()})();</script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script><script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script><script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap5.min.js"></script><script>$(function(){$('[data-data-table]').DataTable({pageLength:10,lengthMenu:[[5,10,25,50,-1],[5,10,25,50,'Semua']],language:{lengthMenu:'Tampilkan _MENU_ data',search:'Cari:',info:'Menampilkan _START_ sampai _END_ dari _TOTAL_ data',infoEmpty:'Tidak ada data',zeroRecords:'Data tidak ditemukan',paginate:{first:'Awal',last:'Akhir',next:'›',previous:'‹'}}});});</script>
@if (app()->getLocale() === 'en')
    <script>
        (() => {
            const translations = @json(json_decode(file_get_contents(lang_path('en.json')), true));
            const translate = value => translations[value?.trim()] ?? null;
            const walker = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
            const textNodes = [];
            while (walker.nextNode()) textNodes.push(walker.currentNode);
            textNodes.forEach(node => {
                if (node.parentElement?.closest('script, style, textarea, input')) return;
                const translated = translate(node.nodeValue);
                if (translated) node.nodeValue = node.nodeValue.replace(node.nodeValue.trim(), translated);
            });
            document.querySelectorAll('[placeholder], [title], [aria-label], [data-confirm]').forEach(element => {
                ['placeholder', 'title', 'aria-label', 'data-confirm'].forEach(attribute => {
                    const translated = translate(element.getAttribute(attribute));
                    if (translated) element.setAttribute(attribute, translated);
                });
            });
            const titleParts = document.title.split(' · ');
            const translatedTitle = translate(titleParts[0]);
            if (translatedTitle) document.title = [translatedTitle, ...titleParts.slice(1)].join(' · ');
        })();
    </script>
@endif
</body>
</html>
