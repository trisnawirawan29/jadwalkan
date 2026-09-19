@extends('layouts.admin')

@section('title', 'Paket Provider')
@section('page-title', 'Paket Provider')
@section('page-subtitle', 'Atur kapasitas dan harga paket untuk akun provider.')

@section('content')
    <div class="content-card provider-plan-admin-shell">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <div class="card-heading"><div><h5>{{ __('Daftar paket provider') }}</h5><p>{{ __('Paket menentukan kapasitas bisnis, layanan, dan booking bulanan.') }}</p></div><a href="{{ route('admin.provider-plans.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>{{ __('Tambah paket') }}</a></div>
        <div class="row g-3">
            @forelse ($plans as $plan)
                <div class="col-12 col-md-6 col-xl-4"><div class="provider-plan-card {{ $plan->is_active ? '' : 'is-inactive' }}"><div class="d-flex justify-content-between align-items-start gap-2"><div><span class="provider-plan-label">{{ $plan->is_active ? __('AKTIF') : __('NONAKTIF') }}</span><h3>{{ $plan->name }}</h3></div><span class="provider-plan-price">Rp {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}<small>/bulan</small></span></div><p class="provider-plan-description">{{ $plan->description ?: __('Paket untuk provider bisnis.') }}</p><div class="provider-plan-limits"><div><i class="fas fa-building"></i><span>{{ __('Bisnis') }}<strong>{{ number_format($plan->max_business_places, 0, ',', '.') }}</strong></span></div><div><i class="fas fa-layer-group"></i><span>{{ __('Layanan / bisnis') }}<strong>{{ number_format($plan->max_services_per_place, 0, ',', '.') }}</strong></span></div><div><i class="fas fa-calendar-check"></i><span>{{ __('Booking / bulan') }}<strong>{{ number_format($plan->max_bookings_per_month, 0, ',', '.') }}</strong></span></div></div><div class="provider-plan-footer"><small><i class="fas fa-users me-1"></i>{{ $plan->providers_count }} {{ __('provider menggunakan paket') }}</small><div class="d-flex gap-2"><form method="POST" action="{{ route('admin.provider-plans.toggle-status', $plan) }}" data-confirm="{{ $plan->is_active ? 'Nonaktifkan paket ini?' : 'Aktifkan paket ini?' }}">@csrf @method('PATCH')<button class="btn btn-sm btn-light {{ $plan->is_active ? 'text-warning' : 'text-success' }}" title="{{ $plan->is_active ? 'Nonaktifkan paket' : 'Aktifkan paket' }}"><i class="fas {{ $plan->is_active ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i></button></form><a href="{{ route('admin.provider-plans.edit', $plan) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('admin.provider-plans.destroy', $plan) }}" data-confirm="Hapus paket ini?"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></div></div></div>
            @empty
                <div class="col-12"><div class="provider-plan-empty"><i class="fas fa-box-open"></i><p>{{ __('Belum ada paket provider.') }}</p><a href="{{ route('admin.provider-plans.create') }}" class="btn btn-primary btn-sm">{{ __('Buat paket pertama') }}</a></div></div>
            @endforelse
        </div>
    </div>
@endsection
