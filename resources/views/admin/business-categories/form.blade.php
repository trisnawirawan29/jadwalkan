@extends('layouts.admin')

@php($editing = $businessCategory->exists)
@section('title', $editing ? 'Edit Kategori Bisnis' : 'Tambah Kategori Bisnis')
@section('page-title', $editing ? 'Edit Kategori Bisnis' : 'Tambah Kategori Bisnis')
@section('page-subtitle', 'Susun kategori utama dan subkategori untuk penyedia.')

@section('content')
    <div class="content-card col-12 col-xl-8">
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <form method="POST" action="{{ $editing ? route('admin.business-categories.update', $businessCategory) : route('admin.business-categories.store') }}">
            @csrf @if ($editing) @method('PUT') @endif
            <div class="mb-3"><label class="form-label" for="name">Nama kategori</label><input id="name" name="name" class="form-control" value="{{ old('name', $businessCategory->name) }}" placeholder="Contoh: Olahraga atau Bulutangkis" required></div>
            <div class="mb-3"><label class="form-label" for="parent_id">Kategori utama</label><select id="parent_id" name="parent_id" class="form-select"><option value="">Tidak ada — jadikan kategori utama</option>@foreach ($parents as $parent)<option value="{{ $parent->id }}" @selected((string) old('parent_id', $businessCategory->parent_id) === (string) $parent->id)>{{ $parent->name }}</option>@endforeach</select><div class="form-text">Pilih kategori utama jika data ini ingin menjadi subkategori.</div></div>
            <div class="row g-3"><div class="col-md-6"><label class="form-label" for="sort_order">Urutan tampil</label><input id="sort_order" type="number" name="sort_order" class="form-control" min="0" value="{{ old('sort_order', $businessCategory->sort_order ?? 0) }}"></div><div class="col-md-6 d-flex align-items-end"><div class="form-check mb-2"><input type="hidden" name="is_active" value="0"><input id="is_active" type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $businessCategory->exists ? $businessCategory->is_active : true))><label class="form-check-label" for="is_active">Kategori aktif dan tersedia untuk penyedia</label></div></div></div>
            <div class="d-flex justify-content-end gap-2 mt-4"><a href="{{ route('admin.business-categories.index') }}" class="btn btn-light">Batal</a><button class="btn btn-primary">Simpan kategori</button></div>
        </form>
    </div>
@endsection
