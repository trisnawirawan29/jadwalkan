@extends('layouts.admin')

@section('title', 'Manajemen Pengguna')
@section('page-title', 'Manajemen Pengguna')
@section('page-subtitle', 'Kelola data, role, dan akses pengguna aplikasi.')

@section('content')
    <div class="content-card">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <form method="GET" class="row g-2 mb-4">
            <div class="col-md-6"><input name="search" class="form-control" value="{{ request('search') }}" placeholder="Cari nama atau email..."></div>
            <div class="col-md-3"><select name="role" class="form-select"><option value="">Semua role</option><option value="admin" @selected(request('role') === 'admin')>Admin</option><option value="manager" @selected(request('role') === 'manager')>Manager</option><option value="user" @selected(request('role') === 'user')>User</option></select></div>
            <div class="col-md-3 d-flex gap-2"><button class="btn btn-light flex-grow-1">Filter</button><a href="{{ route('admin.users.create') }}" class="btn btn-primary text-nowrap"><i class="fas fa-plus me-1"></i>Tambah</a></div>
        </form>
        <div class="card-heading"><div><h5>Daftar pengguna</h5><p>{{ $users->count() }} pengguna terdaftar.</p></div></div>
        <div class="table-responsive"><table class="table align-middle data-table" data-data-table><thead><tr><th>NAMA</th><th>EMAIL</th><th>ROLE</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($users as $user)
                <tr><td class="fw-semibold">{{ $user->name }}</td><td class="text-muted">{{ $user->email }}</td><td><span class="status {{ $user->role === 'admin' ? 'status-danger' : ($user->role === 'manager' ? 'status-warning' : 'status-success') }}">{{ ucfirst($user->role) }}</span></td><td><div class="d-flex gap-2"><a href="{{ route('admin.users.edit', $user) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('admin.users.destroy', $user) }}" data-confirm="Hapus pengguna ini? Data yang dihapus tidak dapat dikembalikan.">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Pengguna tidak ditemukan.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
@endsection
