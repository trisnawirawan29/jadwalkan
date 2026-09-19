@extends('layouts.admin')

@section('title', 'Pengajuan Provider')
@section('page-title', 'Ajukan Akses Provider')
@section('page-subtitle', 'Ajukan profil bisnis Anda untuk divalidasi oleh superadmin.')

@section('content')
    @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="content-card">
                <div class="card-heading"><div><h5>Form pengajuan provider</h5><p>Lengkapi informasi awal bisnis yang akan Anda kelola.</p></div><span class="status status-warning">Menunggu validasi</span></div>
                @if ($application?->status === 'pending')
                    <div class="provider-empty-state py-5"><i class="fas fa-hourglass-half"></i><h5>Pengajuan sedang ditinjau</h5><p class="mb-0">Superadmin akan memvalidasi pengajuan Anda. Anda dapat mengirim pengajuan baru setelah pengajuan ini selesai diproses.</p></div>
                @else
                    @if ($application?->status === 'rejected')<div class="alert alert-warning small">Pengajuan sebelumnya ditolak. Alasan: {{ $application->rejection_reason }}</div>@endif
                    <form method="POST" action="{{ route('provider-application.store') }}">
                        @csrf
                        <div class="mb-3"><label class="form-label" for="business_name">Nama bisnis</label><input id="business_name" name="business_name" class="form-control" value="{{ old('business_name', $application?->business_name) }}" required maxlength="150"></div>
                        <div class="mb-3"><label class="form-label" for="business_address">Alamat bisnis</label><input id="business_address" name="business_address" class="form-control" value="{{ old('business_address', $application?->business_address) }}" required maxlength="255"></div>
                        <div class="mb-3"><label class="form-label" for="phone">Nomor telepon <span class="text-muted">(opsional)</span></label><input id="phone" name="phone" class="form-control" value="{{ old('phone', $application?->phone) }}" maxlength="30"></div>
                        <div class="mb-4"><label class="form-label" for="business_description">Deskripsi bisnis <span class="text-muted">(opsional)</span></label><textarea id="business_description" name="business_description" class="form-control" rows="5" maxlength="2000">{{ old('business_description', $application?->business_description) }}</textarea></div>
                        <button class="btn btn-primary" type="submit"><i class="fas fa-paper-plane me-2"></i>Kirim pengajuan</button>
                    </form>
                @endif
            </div>
        </div>
        <div class="col-lg-5">
            <div class="content-card h-100"><div class="card-heading"><div><h5>Alur validasi</h5><p>Akses provider diberikan setelah disetujui.</p></div></div><div class="d-flex gap-3 mb-4"><span class="landing-step-icon">1</span><div><strong>Ajukan data bisnis</strong><p class="small text-muted mb-0 mt-1">Isi informasi bisnis yang benar dan mudah diverifikasi.</p></div></div><div class="d-flex gap-3 mb-4"><span class="landing-step-icon">2</span><div><strong>Superadmin meninjau</strong><p class="small text-muted mb-0 mt-1">Tim akan memeriksa kelayakan pengajuan Anda.</p></div></div><div class="d-flex gap-3"><span class="landing-step-icon">3</span><div><strong>Kelola bisnis</strong><p class="small text-muted mb-0 mt-1">Setelah disetujui, menu provider akan terbuka otomatis.</p></div></div></div>
        </div>
    </div>
@endsection
