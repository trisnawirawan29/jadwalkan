<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login · NexaAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}"><link rel="stylesheet" href="{{ asset('css/oauth.css') }}">
</head>
<body class="login-page">
    <div class="login-panel">
        <div class="brand-lockup"><span class="brand-mark"><i class="fas fa-layer-group"></i></span><span>Nexa<span>Admin</span></span></div>
        <div class="login-heading">
            @if(session('status'))<div class="alert alert-success small">{{ session('status') }}</div>@endif
            <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
            <h1>Masuk ke akun Anda</h1>
            <p>Kelola semua aktivitas dari satu tempat.</p>
        </div>
        @if($errors->any())<div class="alert alert-danger py-2 small"><i class="fas fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="email">Alamat email</label><div class="input-group"><span class="input-group-text"><i class="far fa-envelope"></i></span><input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="nama@perusahaan.com" required autofocus></div></div>
            <div class="mb-3"><div class="d-flex justify-content-between"><label class="form-label" for="password">Password</label><a href="{{ route('password.request') }}" class="small text-decoration-none">Lupa password?</a></div><div class="input-group"><span class="input-group-text"><i class="fas fa-lock"></i></span><input id="password" type="password" name="password" class="form-control" placeholder="Masukkan password" required></div></div>
            <div class="form-check mb-4"><input class="form-check-input" type="checkbox" name="remember" id="remember"><label class="form-check-label small text-muted" for="remember">Ingat saya</label></div>
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">Masuk ke Dashboard <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
        <div class="login-divider"><span>atau lanjutkan dengan</span></div>
        <a href="{{ route('auth.google.redirect') }}" class="btn btn-google w-100 py-2 fw-semibold"><i class="fab fa-google me-2"></i>Masuk / daftar dengan Google</a>
        <p class="text-center text-muted small mt-4 mb-0">Belum punya akun? <a href="{{ route('register') }}" class="text-primary text-decoration-none fw-semibold">Buat akun baru</a></p>
        <p class="text-center text-muted small mt-2 mb-0">Demo: <strong>admin@example.com</strong> · <strong>password</strong></p>
    </div>
    <div class="login-art"><div class="art-content"><span class="badge rounded-pill">PLATFORM ADMINISTRASI MODERN</span><h2>Semua insight.<br><em>Satu dashboard.</em></h2><p>Pantau performa, kelola tim, dan ambil keputusan lebih cepat.</p><div class="art-orb orb-one"></div><div class="art-orb orb-two"></div></div></div>
</body>
</html>
