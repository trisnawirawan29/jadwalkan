@extends('layouts.admin')
@section('title', 'Dashboard Manager')
@section('page-title', 'Dashboard Manager')
@section('page-subtitle', 'Pantau pekerjaan operasional dan aktivitas tim.')
@section('content')
    <div class="provider-dashboard-hero"><div><p class="eyebrow">RUANG KERJA MANAGER</p><h2>Selamat datang, {{ auth()->user()->name }}.</h2><p>Gunakan dashboard ini untuk memantau pekerjaan operasional Anda.</p></div><a href="{{ route('profile') }}" class="btn btn-light"><i class="fas fa-user me-2"></i>Lihat profil</a></div>
    <div class="row g-4 mt-1"><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Tugas hari ini</p><h2>0</h2><div class="stat-foot"><span class="text-muted">Belum ada tugas tercatat</span></div></div></div><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Aktivitas tim</p><h2>0</h2><div class="stat-foot"><span class="text-muted">Belum ada aktivitas baru</span></div></div></div><div class="col-12 col-md-4"><div class="stat-card"><p class="stat-label">Notifikasi</p><h2>{{ auth()->user()->unreadNotifications()->count() }}</h2><div class="stat-foot"><a href="{{ route('notifications') }}" class="text-primary text-decoration-none">Lihat notifikasi</a></div></div></div></div>
    <div class="content-card mt-4"><div class="card-heading"><div><h5>Ruang kerja operasional</h5><p>Modul operasional akan tampil di sini saat tersedia.</p></div></div><div class="provider-empty-state"><i class="fas fa-briefcase"></i><p>Belum ada data operasional untuk ditampilkan.</p></div></div>
@endsection
