@extends('layouts.admin')

@section('title', 'Pegawai provider')
@section('page-title', 'Pegawai provider')
@section('page-subtitle', 'Berikan akses operasional untuk memantau booking bisnis Anda.')

@section('content')
    <div class="row g-4">
        <div class="col-12 col-xl-5">
            <div class="content-card h-100">
                <div class="card-heading mb-4">
                    <div>
                        <h5 class="mb-1">Tambah pegawai</h5>
                        <p class="text-muted mb-0">Pegawai hanya dapat melihat booking masuk, memverifikasi pembayaran, dan mencatat kehadiran.</p>
                    </div>
                    <span class="icon-badge"><i class="fas fa-user-plus"></i></span>
                </div>
                <form method="POST" action="{{ route('provider.staff.store') }}" class="row g-3">
                    @csrf
                    <div class="col-12"><label class="form-label" for="staff-name">Nama lengkap</label><input id="staff-name" name="name" class="form-control" value="{{ old('name') }}" required></div>
                    <div class="col-12"><label class="form-label" for="staff-email">Email login</label><input id="staff-email" type="email" name="email" class="form-control" value="{{ old('email') }}" required></div>
                    <div class="col-md-6"><label class="form-label" for="staff-password">Password</label><input id="staff-password" type="password" name="password" class="form-control" minlength="8" required></div>
                    <div class="col-md-6"><label class="form-label" for="staff-password-confirmation">Ulangi password</label><input id="staff-password-confirmation" type="password" name="password_confirmation" class="form-control" minlength="8" required></div>
                    <div class="col-12"><button class="btn btn-primary"><i class="fas fa-user-plus me-2"></i>Daftarkan pegawai</button></div>
                </form>
            </div>
        </div>
        <div class="col-12 col-xl-7">
            <div class="content-card h-100">
                <div class="card-heading mb-3"><div><h5 class="mb-1">Pegawai aktif</h5><p class="text-muted mb-0">{{ $staffMembers->count() }} pegawai terhubung ke provider ini.</p></div><span class="status status-success"><i class="fas fa-shield-halved me-1"></i>Akses terbatas</span></div>
                @forelse ($staffMembers as $staff)
                    <div class="staff-member-row">
                        <div class="staff-member-avatar">{{ strtoupper(substr($staff->name, 0, 1)) }}</div>
                        <div class="flex-grow-1"><strong>{{ $staff->name }}</strong><small class="d-block text-muted">{{ $staff->email }}</small></div>
                        <form method="POST" action="{{ route('provider.staff.destroy', $staff) }}" data-confirm="Cabut akses pegawai ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-user-minus me-1"></i>Cabut akses</button></form>
                    </div>
                @empty
                    <div class="empty-state py-5 text-center"><i class="fas fa-user-shield fa-2x mb-3 text-muted"></i><p class="mb-0 text-muted">Belum ada pegawai provider.</p></div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
