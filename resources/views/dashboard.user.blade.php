@extends('layouts.admin')
@section('title', 'Dashboard Pengguna')
@section('page-title', 'Dashboard Pengguna')
@section('page-subtitle', 'Temukan layanan dan kelola aktivitas akun Anda.')
@section('content')
    <div class="provider-dashboard-hero"><div><p class="eyebrow">RUANG PENGGUNA</p><h2>Selamat datang, {{ auth()->user()->name }}.</h2><p>Semua aktivitas dan layanan pilihan Anda akan tampil di sini.</p></div><a href="{{ route('profile') }}" class="btn btn-light"><i class="fas fa-user me-2"></i>Kelola profil</a></div>
    <div class="row g-4 mt-1"><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Reservasi aktif</p><h2>0</h2><div class="stat-foot"><span class="text-muted">Belum ada reservasi</span></div></div></div><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Tempat tersimpan</p><h2>0</h2><div class="stat-foot"><span class="text-muted">Belum ada tempat tersimpan</span></div></div></div><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Notifikasi</p><h2>{{ auth()->user()->unreadNotifications()->count() }}</h2><div class="stat-foot"><a href="{{ route('notifications') }}" class="text-primary text-decoration-none">Lihat notifikasi</a></div></div></div></div>
    <div class="content-card mt-4"><div class="card-heading"><div><h5>Temukan tempat olahraga</h5><p>Daftar tempat dan layanan publik akan tersedia di sini.</p></div></div><div class="provider-empty-state"><i class="fas fa-compass"></i><p>Belum ada katalog tempat yang tersedia.</p></div></div>
@endsection
