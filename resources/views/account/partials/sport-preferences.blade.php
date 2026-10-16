@php
    $dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];
    $levelNames = ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir'];
    $agenda = $sportPreferences->groupBy('day_of_week');
@endphp

<div class="content-card mb-4">
    <div class="card-heading"><div><h5>Ketertarikan olahraga</h5><p>Pilih olahraga yang Anda minati, lalu tambahkan level dan waktu yang biasanya tersedia. Anda tidak perlu mengisi semua olahraga.</p></div></div>
    <form method="POST" action="{{ route('sport-preferences.store') }}">@csrf
        <div class="row g-3 align-items-end">
            <div class="col-12 col-md-3"><label class="form-label">Jenis olahraga</label><select name="business_category_id" class="form-select" required><option value="">Pilih olahraga</option>@foreach($sports as $sport)<option value="{{ $sport->id }}">{{ $sport->name }}</option>@endforeach</select></div>
            <div class="col-12 col-md-3"><label class="form-label">Level bermain</label><select name="level" class="form-select" required><option value="">Pilih level</option>@foreach($levelNames as $level => $label)<option value="{{ $level }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-12 col-md-2"><label class="form-label">Hari</label><select name="day_of_week" class="form-select" required><option value="">Pilih hari</option>@foreach($dayNames as $day => $label)<option value="{{ $day }}">{{ $label }}</option>@endforeach</select></div>
            <div class="col-6 col-md-2"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="18:00" required></div>
            <div class="col-6 col-md-2"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="20:00" required></div>
        </div>
        <div class="d-flex align-items-center justify-content-between mt-3"><small class="text-muted"><i class="fas fa-circle-info me-1"></i>Tambahkan beberapa slot jika jadwal Anda berbeda-beda.</small><button class="btn btn-primary btn-sm"><i class="fas fa-plus me-1"></i>Tambah ke agenda</button></div>
    </form>
</div>

<div class="content-card mb-4">
    <div class="card-heading"><div><h5>Agenda olahraga saya</h5><p>Slot di bawah ini dipakai sistem untuk menemukan partner dengan waktu dan level yang sama.</p></div></div>
    <div class="row g-3">
        @foreach($dayNames as $day => $dayName)
            <div class="col-12 col-md-6 col-xl-4"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between align-items-center mb-2"><strong>{{ $dayName }}</strong><span class="badge text-bg-light">{{ $agenda->get($day, collect())->count() }} slot</span></div>@forelse($agenda->get($day, collect()) as $preference)<div class="bg-body-tertiary rounded p-2 mb-2"><div class="d-flex justify-content-between gap-2"><div><strong>{{ $preference->businessCategory->name }}</strong><small class="d-block text-muted">{{ $levelNames[$preference->level] ?? ucfirst($preference->level) }} · {{ substr($preference->start_time, 0, 5) }}–{{ substr($preference->end_time, 0, 5) }}</small></div><form method="POST" action="{{ route('sport-preferences.destroy', $preference) }}" data-confirm="Hapus jadwal ini dari agenda?"><input type="hidden" name="_token" value="{{ csrf_token() }}"><input type="hidden" name="_method" value="DELETE"><button class="btn btn-sm btn-link text-danger p-0" aria-label="Hapus jadwal"><i class="fas fa-trash"></i></button></form></div></div>@empty<span class="small text-muted">Belum ada jadwal.</span>@endforelse</div></div>
        @endforeach
    </div>
</div>
