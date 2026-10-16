@extends('layouts.admin')

@section('title', 'Buat kelompok olahraga')
@section('page-title', 'Buat kelompok olahraga')
@section('page-subtitle', 'Sistem akan mencocokkan user berdasarkan olahraga, level, hari, dan jam yang sama.')

@section('content')
    <div class="content-card col-12 col-xl-8">
        <form method="POST" action="{{ route('sport-groups.store') }}">@csrf
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">Nama kegiatan</label><input name="name" class="form-control" value="{{ old('name') }}" placeholder="Contoh: Futsal Jumat sore" required></div>
                <div class="col-md-4"><label class="form-label">Jenis olahraga</label><select name="business_category_id" class="form-select" required><option value="">Pilih olahraga</option>@foreach($sports as $sport)<option value="{{ $sport->id }}" @selected(old('business_category_id') == $sport->id)>{{ $sport->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><label class="form-label">Level</label><select name="level" class="form-select"><option value="beginner" @selected(old('level', 'beginner') === 'beginner')>Pemula</option><option value="intermediate" @selected(old('level') === 'intermediate')>Menengah</option><option value="advanced" @selected(old('level') === 'advanced')>Mahir</option></select></div>
                <div class="col-md-4"><label class="form-label">Tanggal</label><input type="date" name="activity_date" class="form-control" value="{{ old('activity_date', today()->addWeek()->toDateString()) }}" required></div>
                <div class="col-md-4"><label class="form-label">Maksimal anggota</label><input type="number" name="max_members" class="form-control" min="2" max="20" value="{{ old('max_members', 4) }}" required></div>
                <div class="col-md-6"><label class="form-label">Mulai</label><input type="time" name="start_time" class="form-control" value="{{ old('start_time', '18:00') }}" required></div>
                <div class="col-md-6"><label class="form-label">Selesai</label><input type="time" name="end_time" class="form-control" value="{{ old('end_time', '20:00') }}" required></div>
                <div class="col-12"><label class="form-label">Catatan kegiatan</label><textarea name="description" class="form-control" rows="4" maxlength="1000" placeholder="Perlengkapan atau catatan lain">{{ old('description') }}</textarea></div>
            </div>
            <div class="alert alert-info small mt-4 mb-0"><i class="fas fa-lightbulb me-2"></i>Undangan hanya dikirim kepada user yang memiliki preferensi olahraga, level, hari, dan waktu yang cocok.</div>
            <button class="btn btn-primary mt-4">Buat kelompok</button><a href="{{ route('sport-groups.index') }}" class="btn btn-light mt-4 ms-2">Batal</a>
        </form>
    </div>
@endsection
