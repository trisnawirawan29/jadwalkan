@extends('layouts.admin')

@section('title', 'Pengajuan Provider')
@section('page-title', 'Pengajuan Provider')
@section('page-subtitle', 'Validasi permohonan pengguna untuk mendapatkan akses provider.')

@section('content')
    @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
    <div class="content-card">
        <form method="GET" class="row g-2 mb-4"><div class="col-md-4"><select name="status" class="form-select"><option value="">Semua status</option><option value="pending" @selected(request('status') === 'pending')>Menunggu</option><option value="approved" @selected(request('status') === 'approved')>Disetujui</option><option value="rejected" @selected(request('status') === 'rejected')>Ditolak</option></select></div><div class="col-md-3"><button class="btn btn-light">Filter</button></div></form>
        <div class="card-heading"><div><h5>Daftar pengajuan</h5><p>{{ $applications->count() }} pengajuan ditemukan.</p></div></div>
        <div class="table-responsive"><table class="table align-middle data-table" data-data-table><thead><tr><th>PENGGUNA</th><th>BISNIS</th><th>ALAMAT</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($applications as $application)
                <tr><td><div class="fw-semibold">{{ $application->user->name }}</div><small class="text-muted">{{ $application->user->email }}</small></td><td><div class="fw-semibold">{{ $application->business_name }}</div><small class="text-muted">{{ $application->phone ?: 'Telepon tidak dicantumkan' }}</small></td><td class="text-muted">{{ $application->business_address ?: '-' }}</td><td><span class="status {{ $application->status === 'approved' ? 'status-success' : ($application->status === 'rejected' ? 'status-danger' : 'status-warning') }}">{{ ucfirst($application->status) }}</span>@if($application->rejection_reason)<small class="d-block text-danger mt-1">{{ $application->rejection_reason }}</small>@endif</td><td>@if($application->status === 'pending')<div class="d-flex gap-2"><form method="POST" action="{{ route('admin.provider-applications.approve', $application) }}">@csrf @method('PATCH')<button class="btn btn-sm btn-success" data-confirm-trigger="Setujui pengajuan ini?"><i class="fas fa-check me-1"></i>Setujui</button></form><button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $application->id }}"><i class="fas fa-xmark me-1"></i>Tolak</button></div><div class="modal fade" id="rejectModal{{ $application->id }}" tabindex="-1"><div class="modal-dialog"><form method="POST" action="{{ route('admin.provider-applications.reject', $application) }}" class="modal-content">@csrf @method('PATCH')<div class="modal-header"><h5 class="modal-title">Tolak pengajuan</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><label class="form-label">Alasan penolakan</label><textarea name="rejection_reason" class="form-control" rows="4" required maxlength="1000"></textarea></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button class="btn btn-danger">Tolak pengajuan</button></div></form></div></div>@else<span class="text-muted small">{{ $application->reviewed_at?->format('d M Y H:i') }}</span>@endif</td></tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada pengajuan provider.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
@endsection
