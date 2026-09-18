@extends('layouts.admin')

@section('title', 'Change Password')
@section('page-title', 'Change Password')
@section('page-subtitle', 'Pastikan password Anda tetap kuat dan rahasia.')

@section('content')
    <div class="content-card col-12 col-lg-7">
        @if (session('success'))
            <div class="alert alert-success small">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger small">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.change.update') }}">
            @csrf
            @method('PUT')
            <label class="form-label" for="current-password">Password saat ini</label>
            <input id="current-password" type="password" name="current_password" class="form-control mb-3" required>
            <label class="form-label" for="new-password">Password baru</label>
            <input id="new-password" type="password" name="password" class="form-control mb-3" required minlength="8">
            <label class="form-label" for="password-confirmation">Konfirmasi password baru</label>
            <input id="password-confirmation" type="password" name="password_confirmation" class="form-control mb-4" required>
            <button class="btn btn-primary">Ubah password</button>
        </form>
    </div>
@endsection
