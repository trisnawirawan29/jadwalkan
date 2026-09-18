@extends('layouts.admin')
@section('title', $businessPlace->name)
@section('page-title', $businessPlace->name)
@section('page-subtitle', 'Detail tempat bisnis penyedia.')
@section('content')
    <div class="content-card"><h5>{{ $businessPlace->name }}</h5><p>@forelse ($businessPlace->businessCategories as $category)<span class="badge text-bg-light me-1"><i class="fas fa-sitemap me-1"></i>{{ $category->parent?->name ? $category->parent->name.' · ' : '' }}{{ $category->name }}</span>@empty<span class="badge text-bg-light">Kategori belum dipilih</span>@endforelse</p><p>{{ $businessPlace->description }}</p><p class="text-muted">{{ $businessPlace->address }} · {{ $businessPlace->district_name }}, {{ $businessPlace->regency_name }}, {{ $businessPlace->province_name }} · {{ $businessPlace->phone }}</p><div class="d-flex gap-2"><a href="{{ route('provider.business-places.services.index', $businessPlace) }}" class="btn btn-primary">Kelola layanan</a>@if ($businessPlace->google_maps_url)<a href="{{ $businessPlace->google_maps_url }}" target="_blank" rel="noopener noreferrer" class="btn btn-light"><i class="fas fa-map-location-dot me-1"></i>Buka Google Maps</a>@endif</div></div>
@endsection
