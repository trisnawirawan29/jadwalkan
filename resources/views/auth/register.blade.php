<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrasi · NexaAdmin</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.6.0/css/all.min.css">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="login-page">
    <div class="login-panel">
        <div class="brand-lockup"><span class="brand-mark"><i class="fas fa-layer-group"></i></span><span>Nexa<span>Admin</span></span></div>
        <div class="login-heading"><p class="eyebrow">MULAI SEKARANG</p><h1>Buat akun baru</h1><p>Daftar sebagai pengguna atau penyedia tempat olahraga.</p></div>
        @if ($errors->any())<div class="alert alert-danger py-2 small"><i class="fas fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ route('register.store') }}">
            @csrf
            <div class="mb-3"><label class="form-label" for="name">Nama lengkap</label><input id="name" type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Nama lengkap Anda" required autofocus></div>
            <div class="mb-3"><label class="form-label" for="email">Alamat email</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="nama@perusahaan.com" required></div>
            <div class="mb-3"><label class="form-label" for="role">Daftar sebagai</label><select id="role" name="role" class="form-select" required><option value="user" @selected(old('role', 'user') === 'user')>Pengguna</option><option value="provider" @selected(old('role') === 'provider')>Penyedia / Provider</option></select><div class="form-text">Provider dapat membuat tempat bisnis, layanan, dan jadwal lapangan.</div></div>
            <div class="mb-3"><label class="form-label" for="password">Password</label><input id="password" type="password" name="password" class="form-control" placeholder="Minimal 8 karakter" minlength="8" required></div>
            <div class="mb-4"><label class="form-label" for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password" required></div>
            <button class="btn btn-primary w-100 py-2 fw-semibold" type="submit">Buat akun <i class="fas fa-arrow-right ms-2"></i></button>
        </form>
        <p class="text-center text-muted small mt-4 mb-0">Sudah punya akun? <a href="{{ route('login') }}" class="text-primary text-decoration-none fw-semibold">Masuk di sini</a></p>
    </div>
    <div class="login-art"><div class="art-content"><span class="badge rounded-pill">PLATFORM ADMINISTRASI MODERN</span><h2>Bangun lebih cepat.<br><em>Kelola lebih baik.</em></h2><p>Semua tools yang Anda butuhkan untuk menjalankan bisnis dalam satu dashboard.</p><div class="art-orb orb-one"></div><div class="art-orb orb-two"></div></div></div>
</body>
</html>
