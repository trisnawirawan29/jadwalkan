@extends('layouts.admin')
@section('title', 'Jadwal · '.$businessService->name)
@section('page-title', 'Jadwal Berulang')
@section('page-subtitle', $businessPlace->name.' · '.$businessService->name)
@section('content')
    <div class="content-card">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        <div class="card-heading"><div><h5>Jadwal mingguan</h5><p>Jadwal ini berulang otomatis setiap minggu.</p></div><div class="d-flex gap-2"><a href="{{ route('provider.business-places.services.index', $businessPlace) }}" class="btn btn-light">Kembali</a><a href="{{ route('provider.business-places.services.schedules.create', [$businessPlace, $businessService]) }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah jadwal</a></div></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>HARI</th><th>JAM</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($businessService->schedules as $schedule)
                <tr><td class="fw-semibold">{{ $schedule->day_name }}</td><td>@if ($schedule->is_closed)<span class="status status-warning"><i class="fas fa-moon me-1"></i>Tutup / hari libur</span>@else{{ substr($schedule->start_time, 0, 5) }} - {{ substr($schedule->end_time, 0, 5) }}@endif</td><td><span class="status {{ $schedule->is_closed ? 'status-warning' : ($schedule->is_active ? 'status-success' : 'status-warning') }}">{{ $schedule->is_closed ? 'Tutup' : ($schedule->is_active ? 'Aktif' : 'Nonaktif') }}</span></td><td><div class="d-flex gap-2"><a href="{{ route('provider.business-places.services.schedules.edit', [$businessPlace, $businessService, $schedule]) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('provider.business-places.services.schedules.destroy', [$businessPlace, $businessService, $schedule]) }}" data-confirm="Hapus jadwal ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada jadwal berulang.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
    <div class="content-card mt-4">
        <div class="card-heading"><div><h5>Penutupan tanggal tertentu</h5><p>Tambahkan hari libur atau tanggal tutup di luar jadwal mingguan.</p></div><span class="badge text-bg-light"><i class="fas fa-calendar-xmark me-1"></i>{{ $businessService->closures->count() }} tanggal aktif</span></div>
        <form method="POST" action="{{ route('provider.business-places.services.closures.store', [$businessPlace, $businessService]) }}" class="row g-3 align-items-end mb-4">
            @csrf
            <div class="col-md-4"><label class="form-label" for="closure_date">Tanggal tutup</label><input id="closure_date" type="date" name="closure_date" class="form-control" min="{{ now()->toDateString() }}" value="{{ old('closure_date') }}" required></div>
            <div class="col-md-5"><label class="form-label" for="note">Keterangan (opsional)</label><input id="note" type="text" name="note" class="form-control" maxlength="255" value="{{ old('note') }}" placeholder="Contoh: Libur hari raya atau perawatan lapangan"></div>
            <div class="col-md-3"><button class="btn btn-primary w-100"><i class="fas fa-calendar-plus me-1"></i>Tambah tanggal tutup</button></div>
        </form>
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>TANGGAL</th><th>KETERANGAN</th><th></th></tr></thead><tbody>
            @forelse ($businessService->closures as $closure)
                <tr><td class="fw-semibold"><i class="fas fa-calendar-day me-2 text-primary"></i>{{ $closure->formatted_date }}</td><td class="text-muted">{{ $closure->note ?: 'Tempat tutup pada tanggal ini.' }}</td><td class="text-end"><form method="POST" action="{{ route('provider.business-places.services.closures.destroy', [$businessPlace, $businessService, $closure]) }}" data-confirm="Hapus tanggal penutupan ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></td></tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">Belum ada penutupan tanggal tertentu.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
@endsection
