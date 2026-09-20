@extends('layouts.admin')

@section('title', __('Kategori Bisnis'))
@section('page-title', __('Kategori Bisnis'))
@section('page-subtitle', __('Kelola kategori utama dan subkategori tempat bisnis.'))

@section('content')
    <div class="content-card">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        <div class="card-heading"><div><h5>Master kategori bisnis</h5><p>Kategori yang aktif akan tersedia untuk dipilih oleh penyedia.</p></div><a href="{{ route('admin.business-categories.create') }}" class="btn btn-primary"><i class="fas fa-plus me-1"></i>Tambah kategori</a></div>
        <div class="table-responsive"><table class="table align-middle"><thead><tr><th>KATEGORI</th><th>TIPE</th><th>TEMPAT BISNIS</th><th>STATUS</th><th>AKSI</th></tr></thead><tbody>
            @forelse ($categories as $category)
                <tr><td><strong>{{ $category->name }}</strong></td><td><span class="status status-primary">Kategori utama</span></td><td>{{ $category->places_count }}</td><td><span class="status {{ $category->is_active ? 'status-success' : 'status-warning' }}">{{ $category->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="d-flex gap-2"><a href="{{ route('admin.business-categories.edit', $category) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('admin.business-categories.destroy', $category) }}" data-confirm="Hapus kategori ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
                @foreach ($category->children as $child)
                    <tr><td><span class="ms-4 text-muted">↳</span> <strong>{{ $child->name }}</strong><small class="d-block text-muted ms-5">{{ $category->name }}</small></td><td><span class="status status-light">Subkategori</span></td><td>{{ $child->places_count }}</td><td><span class="status {{ $child->is_active ? 'status-success' : 'status-warning' }}">{{ $child->is_active ? 'Aktif' : 'Nonaktif' }}</span></td><td><div class="d-flex gap-2"><a href="{{ route('admin.business-categories.edit', $child) }}" class="btn btn-sm btn-light"><i class="fas fa-pen"></i></a><form method="POST" action="{{ route('admin.business-categories.destroy', $child) }}" data-confirm="Hapus subkategori ini?">@csrf @method('DELETE')<button class="btn btn-sm btn-light text-danger"><i class="fas fa-trash"></i></button></form></div></td></tr>
                @endforeach
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">Belum ada kategori bisnis.</td></tr>
            @endforelse
        </tbody></table></div>
    </div>
@endsection
