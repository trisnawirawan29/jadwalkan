@extends('layouts.admin')

@section('title', $sportGroup->name)
@section('page-title', $sportGroup->name)
@section('page-subtitle', $sportGroup->businessCategory->name.' · '.ucfirst($sportGroup->level).' · '.$sportGroup->activity_date->format('d M Y').' · '.substr($sportGroup->start_time, 0, 5).' - '.substr($sportGroup->end_time, 0, 5))

@section('content')
    <div class="row g-4">
        <div class="col-12 col-lg-5">
            <div class="content-card h-100">
                <div class="card-heading"><div><h5>Anggota kelompok</h5><p>{{ $sportGroup->members->where('pivot.status', 'accepted')->count() }}/{{ $sportGroup->max_members }} anggota sudah konfirmasi</p></div></div>
                @foreach ($sportGroup->members as $member)
                    <div class="d-flex align-items-center justify-content-between border-bottom py-2">
                        <span><i class="fas fa-user-circle text-muted me-2"></i>{{ $member->name }} @if ($member->id === $sportGroup->creator_id)<small class="text-muted">(pembuat)</small>@endif</span>
                        <span class="badge {{ $member->pivot->status === 'accepted' ? 'text-bg-success' : ($member->pivot->status === 'declined' ? 'text-bg-secondary' : 'text-bg-warning') }}">{{ ['accepted' => 'ikut', 'declined' => 'tidak ikut', 'invited' => 'menunggu'][$member->pivot->status] }}</span>
                    </div>
                @endforeach
                @php($currentMember = $sportGroup->members->firstWhere('id', auth()->id()))
                @if ($currentMember?->pivot->status === 'invited')
                    <div class="d-flex gap-2 mt-4">
                        <form method="POST" action="{{ route('sport-groups.respond', $sportGroup) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="accepted"><button class="btn btn-success">Saya ikut</button></form>
                        <form method="POST" action="{{ route('sport-groups.respond', $sportGroup) }}">@csrf @method('PATCH')<input type="hidden" name="status" value="declined"><button class="btn btn-outline-secondary">Tidak ikut</button></form>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="content-card">
                <div class="card-heading"><div><h5>Rekomendasi lapangan</h5><p>Diurutkan dari rata-rata jarak anggota, harga, dan ketersediaan.</p></div></div>
                @forelse ($recommendations as $service)
                    <div class="border rounded p-3 mb-3 d-flex justify-content-between gap-3">
                        <div><strong>{{ $service->businessPlace->name }}</strong><div>{{ $service->name }}</div><small class="text-muted">{{ $service->businessPlace->address ?: 'Alamat belum tersedia' }}</small><div class="mt-2 small"><span class="badge text-bg-success me-1">Tersedia</span><span class="text-muted">{{ $recommendationDistances[$service->id] !== null ? $recommendationDistances[$service->id].' km rata-rata' : 'Jarak belum dapat dihitung' }}</span><span class="text-muted ms-2">Rp {{ number_format((float) $service->price_per_hour, 0, ',', '.') }}/jam</span></div></div>
                        <form method="POST" action="{{ route('sport-groups.select-place', $sportGroup) }}" class="align-self-center">@csrf @method('PATCH')<input type="hidden" name="business_service_id" value="{{ $service->id }}"><button class="btn btn-sm {{ $sportGroup->selected_business_service_id === $service->id ? 'btn-success' : 'btn-outline-primary' }}">{{ $sportGroup->selected_business_service_id === $service->id ? 'Terpilih' : 'Pilih' }}</button></form>
                    </div>
                @empty
                    <div class="text-muted py-4">Belum ada lapangan yang tersedia pada waktu ini. Coba ubah jadwal kegiatan.</div>
                @endforelse
            </div>
        </div>
    </div>
@endsection
