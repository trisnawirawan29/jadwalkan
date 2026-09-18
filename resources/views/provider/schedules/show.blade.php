@extends('layouts.admin')
@section('title', 'Detail Jadwal')
@section('page-title', 'Detail Jadwal')
@section('page-subtitle', $businessPlace->name.' · '.$businessService->name)
@section('content')<div class="content-card"><h5>{{ $schedule->day_name }}</h5><p>{{ $schedule->is_closed ? 'Tutup / hari libur' : substr($schedule->start_time, 0, 5).' - '.substr($schedule->end_time, 0, 5) }}</p></div>@endsection
