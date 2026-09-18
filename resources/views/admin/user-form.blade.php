@extends('layouts.admin')

@section('title', $pageTitle)
@section('page-title', $pageTitle)
@section('page-subtitle', 'Kelola identitas, kredensial, dan hak akses pengguna.')

@section('content')
    @php($currentRole = old('role', $user->role ?: 'user'))
    <div class="content-card col-12 col-xl-8">
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}">
            @csrf @if ($user->exists) @method('PUT') @endif
            <div class="mb-3"><label class="form-label" for="name">Nama lengkap</label><input id="name" name="name" class="form-control" value="{{ old('name', $user->name) }}" required></div>
            <div class="mb-3"><label class="form-label" for="email">Alamat email</label><input id="email" type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required></div>
            <div class="mb-3"><label class="form-label">Role pengguna</label><div class="row g-2"><div class="col-md-3"><label class="border rounded p-3 w-100"><input type="radio" name="role" value="admin" @checked($currentRole === 'admin')> <strong>Admin</strong><small class="d-block text-muted">Akses penuh</small></label></div><div class="col-md-3"><label class="border rounded p-3 w-100"><input type="radio" name="role" value="manager" @checked($currentRole === 'manager')> <strong>Manager</strong><small class="d-block text-muted">Operasional</small></label></div><div class="col-md-3"><label class="border rounded p-3 w-100"><input type="radio" name="role" value="provider" @checked($currentRole === 'provider')> <strong>Provider</strong><small class="d-block text-muted">Tempat bisnis</small></label></div><div class="col-md-3"><label class="border rounded p-3 w-100"><input type="radio" name="role" value="user" @checked($currentRole === 'user')> <strong>User</strong><small class="d-block text-muted">Akses dasar</small></label></div></div></div>
            <div class="row g-3"><div class="col-md-6"><label class="form-label" for="password">Password {{ $user->exists ? '(opsional)' : '' }}</label><input id="password" type="password" name="password" class="form-control" minlength="8" {{ $user->exists ? '' : 'required' }}></div><div class="col-md-6"><label class="form-label" for="password_confirmation">Konfirmasi password</label><input id="password_confirmation" type="password" name="password_confirmation" class="form-control" {{ $user->exists ? '' : 'required' }}></div></div>
            <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.users') }}" class="btn btn-light">Batal</a><button class="btn btn-primary">Simpan pengguna</button></div>
        </form>
    </div>
@endsection
