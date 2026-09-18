@extends('layouts.admin')

@section('title', 'Notifikasi')
@section('page-title', 'Notifikasi')
@section('page-subtitle', 'Ikuti pembaruan terbaru dari akun Anda.')

@section('content')
    <div class="content-card">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        <div class="card-heading"><div><h5>Semua notifikasi</h5><p>Notifikasi yang belum dibaca ditandai dengan latar lembut.</p></div><form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button class="btn btn-light btn-sm">Tandai semua dibaca</button></form></div>
        <div class="notification-page-list">
            @forelse ($notifications as $notification)
                <div class="notification-page-item {{ $notification->read_at ? '' : 'unread' }}"><span class="notification-icon"><i class="fas fa-{{ data_get($notification->data, 'type') === 'success' ? 'check' : 'bell' }}"></i></span><div class="flex-grow-1"><strong>{{ data_get($notification->data, 'title') }}</strong><p>{{ data_get($notification->data, 'message') }}</p><small>{{ $notification->created_at->diffForHumans() }}</small></div>@if(!$notification->read_at)<form method="POST" action="{{ route('notifications.read', $notification) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light">Tandai dibaca</button></form>@endif</div>
            @empty
                <div class="empty-notification py-5">Belum ada notifikasi.</div>
            @endforelse
        </div>{{ $notifications->links() }}
    </div>
@endsection
