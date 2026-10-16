@extends('layouts.admin')

@section('title', 'Komunitas olahraga')
@section('page-title', 'Komunitas olahraga')
@section('page-subtitle', 'Bergabung dengan komunitas sesuai olahraga yang Anda sukai.')

@section('content')
    <div class="alert alert-info small"><i class="fas fa-calendar-days me-2"></i>Atur jenis olahraga, level, dan jadwal Anda dari <a href="{{ route('profile') }}" class="alert-link">Profil Pengguna</a>. Data tersebut dipakai untuk mencocokkan partner olahraga.</div>
    <div class="row g-4">
        @forelse($sports as $sport)
            @php($community = $sport->sportCommunity)
            @php($joined = auth()->user()->sportInterests->contains($sport->id))
            <div class="col-12 col-md-6 col-xl-4"><div class="content-card h-100 d-flex flex-column"><div class="d-flex justify-content-between align-items-start mb-3"><span class="rounded-circle bg-primary-subtle text-primary p-3"><i class="fas fa-volleyball"></i></span><span class="badge text-bg-light">{{ $sport->interested_users_count }} peminat</span></div><h5>{{ $community?->name ?: 'Komunitas '.$sport->name }}</h5><p class="text-muted small flex-grow-1">{{ $community?->description ?: 'Bergabung dengan orang-orang yang menyukai '.$sport->name.'.' }}</p><div class="small text-muted mb-3"><i class="fas fa-users me-1"></i>{{ $community?->members_count ?? 0 }} anggota</div><form method="POST" action="{{ route('communities.join', $sport) }}">@csrf<button class="btn {{ $joined ? 'btn-outline-danger' : 'btn-primary' }} w-100">{{ $joined ? 'Keluar komunitas' : 'Bergabung' }}</button></form></div></div>
        @empty
            <div class="col-12"><div class="content-card text-center py-5 text-muted">Belum ada jenis olahraga yang tersedia.</div></div>
        @endforelse
    </div>
@endsection
