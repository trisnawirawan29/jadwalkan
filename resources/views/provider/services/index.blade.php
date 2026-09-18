@extends('layouts.admin')
@section('title', 'Layanan · '.$businessPlace->name)
@section('page-title', 'Layanan / Lapangan')
@section('page-subtitle', $businessPlace->name)
@section('content')
    <div class="content-card business-service-list-card">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        <div class="card-heading"><div><h5>Layanan dan lapangan</h5><p>Tambahkan fasilitas yang tersedia di tempat ini.</p></div><div class="d-flex gap-2"><a href="{{ route('provider.business-places.index') }}" class="btn btn-light">Kembali</a><a href="{{ route('provider.business-places.services.create', $businessPlace) }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah layanan</a></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>LAYANAN</th><th>JENIS LAYANAN</th><th>RINGKASAN JADWAL</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($businessPlace->services as $businessService)
                @php
                    $openSchedules = $businessService->schedules->where('is_active', true)->where('is_closed', false);
                    $weeklyClosures = $businessService->schedules->where('is_closed', true);
                    $scheduleSummary = $openSchedules->take(2)->map(fn ($schedule) => $schedule->day_name.' '.substr($schedule->start_time, 0, 5).'-'.substr($schedule->end_time, 0, 5))->implode(', ');
                    $remainingSchedules = max(0, $openSchedules->count() - 2);
                @endphp
                <tr><td><div class="business-service-list-item">@if ($businessService->cover_image_url)<img class="business-service-list-thumb" src="{{ $businessService->cover_image_url }}" alt="{{ $businessService->name }}">@else<span class="business-service-list-thumb business-service-list-placeholder"><i class="fas fa-image"></i></span>@endif<div><strong>{{ $businessService->name }}</strong><small class="d-block text-muted">{{ $businessService->description ?: 'Tanpa deskripsi' }}</small></div></div></td><td>{{ $businessService->businessCategory?->name ?: $businessService->type ?: '-' }}</td><td><div class="business-service-schedule-summary">@if ($scheduleSummary)<span><i class="fas fa-clock me-1"></i>{{ $scheduleSummary }}@if ($remainingSchedules) <small>+{{ $remainingSchedules }} hari</small>@endif</span>@else<span class="text-muted">Jadwal belum diatur</span>@endif @if ($weeklyClosures->isNotEmpty())<span class="business-service-closed-summary"><i class="fas fa-moon me-1"></i>Tutup: {{ $weeklyClosures->pluck('day_name')->implode(', ') }}</span>@endif @if ($businessService->closures->isNotEmpty())<span class="business-service-closed-summary"><i class="fas fa-calendar-xmark me-1"></i>{{ $businessService->closures->count() }} tanggal tutup mendatang</span>@endif</div></td><td><span class="status {{ $businessService->is_active ? 'status-success' : 'status-warning' }}">{{ $businessService->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="d-flex gap-2"><a href="{{ route('provider.business-places.services.schedules.index', [$businessPlace, $businessService]) }}" class="btn btn-sm btn-primary">Jadwal</a><a href="{{ route('provider.business-places.services.edit', [$businessPlace, $businessService]) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('provider.business-places.services.destroy', [$businessPlace, $businessService]) }}" data-confirm="Hapus layanan beserta semua jadwalnya?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada layanan atau lapangan.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
@endsection
