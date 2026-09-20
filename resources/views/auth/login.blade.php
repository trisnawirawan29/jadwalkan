<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Login') }} · {{ $settings['app_name'] ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}"><link rel="stylesheet" href="{{ asset('css/oauth.css') }}">
</head>
<body class="login-page" data-bs-theme="{{ $settings['default_theme'] ?? 'light' }}">
    <div class="login-panel">
        <div class="brand-lockup"><span class="brand-mark"><i class="fas fa-layer-group"></i></span><span>{{ $settings['app_name'] ?? config('app.name') }}</span></div>
        <div class="login-heading">
            @if(session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
            @if(request()->string('booking')->toString() === 'required')<div class="alert alert-info small"><i class="fas fa-lock me-2"></i>{{ __('Login diperlukan untuk booking. Setelah masuk, Anda dapat melihat jadwal dan memilih jam yang tersedia.') }}</div>@endif
            <p class="eyebrow">{{ strtoupper($settings['app_tagline'] ?? 'Selamat datang kembali') }}</p>
            <h1>{{ __('Masuk ke akun Anda') }}</h1>
            <p>{{ $settings['app_tagline'] ?? 'Kelola semua aktivitas dari satu tempat.' }}</p>
        </div>
        @if($errors->any())<div class="alert alert-danger py-2 small"><i class="fas fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="email">{{ __('Alamat email') }}</label><div class="input-group"><span class="input-group-text"><i class="far fa-envelope"></i></span><input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="nama@perusahaan.com" required autofocus></div></div>
            <div class="mb-3"><div class="d-flex justify-content-between"><label class="form-label" for="password">{{ __('Password') }}</label><a href="{{ route('password.request') }}" class="small text-decoration-none">{{ __('Lupa password?') }}</a></div><div class="input-group"><span class="input-group-text"><i class="fas fa-lock"></i></span><input id="password" type="password" name="password" class="form-control" placeholder="{{ __('Masukkan password') }}" required></div></div>
            <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small text-muted" for="remember">{{ __('Ingat saya') }}</label></div>
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">{{ __('Masuk ke Dashboard') }} <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
        <div class="login-divider"><span>{{ __('atau lanjutkan dengan') }}</span></div>
        <a href="{{ route('auth.google.redirect') }}" class="btn btn-google w-100 py-2 fw-semibold"><i class="fab fa-google me-2"></i>{{ __('Masuk / daftar dengan Google') }}</a>
        <p class="text-center text-muted small mt-4 mb-0">{{ __('Belum punya akun?') }} <a href="{{ route('register') }}" class="text-primary text-decoration-none fw-semibold">{{ __('Buat akun baru') }}</a></p>
        <p class="text-center text-muted small mt-2 mb-0">Demo: <strong>admin@example.com</strong> · <strong>password</strong></p>
    </div>
    <div class="login-art"><div class="art-content"><span class="badge rounded-pill">{{ strtoupper($settings['app_name'] ?? config('app.name')) }}</span><h2>Semua insight.<br><em>Satu dashboard.</em></h2><p>{{ $settings['app_tagline'] ?? 'Pantau performa, kelola tim, dan ambil keputusan lebih cepat.' }}</p><div class="art-orb orb-one"></div><div class="art-orb orb-two"></div></div></div>
    <style>
        :root { --brand-color: {{ $settings['primary_color'] ?? '#6c63ff' }}; }
        .brand-mark, .btn-primary { background-color: var(--brand-color) !important; border-color: var(--brand-color) !important; }
        .text-primary { color: var(--brand-color) !important; }
        .eyebrow { color: var(--brand-color) !important; }
        body { background-color: {{ ($settings['default_theme'] ?? 'light') === 'dark' ? '#171a2b' : '#f7f8fc' }}; }
        [data-bs-theme="dark"] .login-panel { background-color: #24283b; color: #f4f6ff; }
        [data-bs-theme="dark"] .login-heading p:not(.eyebrow), [data-bs-theme="dark"] .form-check-label, [data-bs-theme="dark"] .login-footer { color: #b9c1d5 !important; }
        [data-bs-theme="dark"] .input-group-text, [data-bs-theme="dark"] .form-control { background-color: #30364d; border-color: #4a526d; color: #f4f6ff; }
        [data-bs-theme="dark"] .btn-google { background-color: #30364d; border-color: #4a526d; color: #f4f6ff; }
    </style>
    <footer class="login-footer">{{ $settings['footer_text'] ?? 'Semua hak dilindungi.' }} · v{{ $settings['app_version'] ?? '1.0.0' }}</footer>
</body>
</html>
