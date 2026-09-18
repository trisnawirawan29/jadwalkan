@extends('layouts.admin')
@section('title', 'Audit Log')
@section('page-title', 'Audit Log')
@section('page-subtitle', 'Riwayat aktivitas dan perubahan data dalam aplikasi.')
@section('content')
<div class="content-card"><form method="GET" class="row g-2 mb-4"><div class="col-md-5"><select name="action" class="form-select"><option value="">Semua aktivitas</option>@foreach($actions as $action)<option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>@endforeach</select></div><div class="col-md-2"><button class="btn btn-light">Filter</button></div></form><div class="table-responsive"><table class="table align-middle data-table" data-data-table><thead><tr><th>WAKTU</th><th>AKTOR</th><th>AKSI</th><th>DESKRIPSI</th><th>IP</th></tr></thead><tbody>@forelse($logs as $log)<tr><td class="text-muted text-nowrap">{{ $log->created_at->format('d M Y H:i') }}</td><td><strong>{{ $log->user?->name ?? 'Sistem / Guest' }}</strong><small class="d-block text-muted">{{ $log->user?->email ?? '-' }}</small></td><td><span class="audit-action">{{ $log->action }}</span></td><td>{{ $log->description }}</td><td class="text-muted">{{ $log->ip_address ?? '-' }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted py-5">Belum ada aktivitas tercatat.</td></tr>@endforelse</tbody></table></div></div>
@endsection
