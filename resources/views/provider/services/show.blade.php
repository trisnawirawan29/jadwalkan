@extends('layouts.admin')
@section('title', $businessService->name)
@section('page-title', $businessService->name)
@section('page-subtitle', $businessPlace->name)
@section('content')<div class="content-card"><h5>{{ $businessService->name }}</h5><p>{{ $businessService->description }}</p><a href="{{ route('provider.business-places.services.schedules.index', [$businessPlace, $businessService]) }}" class="btn btn-primary">Kelola jadwal</a></div>@endsection
