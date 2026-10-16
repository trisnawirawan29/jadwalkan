@extends('layouts.admin')

@section('title', 'Kelompok olahraga')
@section('page-title', 'Kelompok olahraga')
@section('page-subtitle', 'Pilih kelompok yang paling sesuai dengan olahraga, level, dan jadwal Anda.')

@section('content')
    @php($dayNames = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'])
    @php($levelNames = ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir'])

    <div class="content-card mb-4"><div class="card-heading"><div><h5>Jadwal populer di sekitar Anda</h5><p>Rata-rata jadwal yang digunakan user dalam radius 25 km, berdasarkan olahraga dan level yang sama.</p></div><span class="badge text-bg-info">{{ $popularSchedules->count() }} rekomendasi</span></div>
        <form method="GET" action="{{ route('sport-groups.index') }}" class="row g-2 align-items-end mb-4">
            <div class="col-12 col-md-6 col-lg-4"><label for="sport-filter" class="form-label small fw-semibold">Filter jenis olahraga</label><select id="sport-filter" name="sport" class="form-select"><option value="">Semua olahraga</option>@foreach($sports as $sport)<option value="{{ $sport->id }}" @selected((string) $selectedSportId === (string) $sport->id)>{{ $sport->name }}</option>@endforeach</select></div>
            <div class="col-auto"><button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i>Terapkan</button>@if($selectedSportId)<a href="{{ route('sport-groups.index') }}" class="btn btn-light ms-1">Reset</a>@endif</div>
        </form>
        @if(auth()->user()->latitude === null || auth()->user()->longitude === null)
            <div class="alert alert-light border mb-0"><i class="fas fa-location-dot text-primary me-2"></i>Tambahkan koordinat lokasi di profil pengguna agar kami dapat menghitung jadwal populer di sekitar Anda.</div>
        @elseif($popularSchedules->isEmpty())
            <div class="text-center text-muted py-4"><i class="fas fa-chart-column fs-3 d-block mb-2"></i>Belum ada jadwal olahraga yang cukup di sekitar Anda.</div>
        @else
            @php($popularChartLabels = $popularSchedules->map(fn (array $schedule): string => sprintf('%s · %s | %s %s–%s', $schedule['sport_name'], $levelNames[$schedule['level']] ?? ucfirst($schedule['level']), $dayNames[$schedule['day_of_week']] ?? $schedule['day_of_week'], substr($schedule['start_time'], 0, 5), substr($schedule['end_time'], 0, 5)))->values())
            @php($popularChartDetails = $popularSchedules->map(fn (array $schedule): array => ['average_distance_km' => $schedule['average_distance_km'], 'sport_name' => $schedule['sport_name'], 'level' => $levelNames[$schedule['level']] ?? ucfirst($schedule['level']), 'day' => $dayNames[$schedule['day_of_week']] ?? $schedule['day_of_week'], 'time' => substr($schedule['start_time'], 0, 5).'–'.substr($schedule['end_time'], 0, 5)])->values())
            <div class="border rounded p-3 mb-4"><div style="height: 360px"><canvas id="popularSchedulesChart" aria-label="Grafik jadwal olahraga populer" role="img"></canvas></div><div class="small text-muted mt-2"><i class="fas fa-circle-info me-1"></i>Semakin panjang batang, semakin banyak user di sekitar Anda yang menggunakan jadwal tersebut.</div></div>
            <div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>OLAHRAGA</th><th>LEVEL</th><th>HARI</th><th>JAM</th><th>PEMINAT</th><th>JARAK RATA-RATA</th></tr></thead><tbody>@foreach($popularSchedules as $schedule)<tr><td><strong>{{ $schedule['sport_name'] }}</strong></td><td><span class="badge text-bg-light">{{ $levelNames[$schedule['level']] ?? ucfirst($schedule['level']) }}</span></td><td>{{ $dayNames[$schedule['day_of_week']] ?? $schedule['day_of_week'] }}</td><td>{{ substr($schedule['start_time'], 0, 5) }}–{{ substr($schedule['end_time'], 0, 5) }}</td><td>{{ $schedule['user_count'] }} user</td><td>{{ number_format($schedule['average_distance_km'], 1, ',', '.') }} km</td></tr>@endforeach</tbody></table></div>
        @endif
    </div>

    <div class="content-card mb-4"><div class="card-heading"><div><h5>Saran kelompok untuk Anda</h5><p>Saran dibuat otomatis dari kemiripan preferensi olahraga dan hanya menampilkan kelompok dengan lapangan yang tersedia.</p></div><span class="badge text-bg-primary">{{ $suggestions->count() }} cocok</span></div>
        <div class="row g-3">
            @forelse($suggestions as $suggestion)
                <div class="col-12 col-lg-6"><div class="border rounded p-3 h-100"><div class="d-flex justify-content-between gap-3"><div><span class="badge text-bg-light mb-2">{{ $suggestion->businessCategory->name }} · {{ ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir'][$suggestion->level] ?? ucfirst($suggestion->level) }}</span><h5 class="mb-1">{{ $suggestion->name }}</h5><small class="text-muted">Dibuat oleh {{ $suggestion->creator->name }}</small></div><i class="fas fa-wand-magic-sparkles text-primary fs-4"></i></div><div class="small mt-3"><div><i class="fas fa-calendar-day text-muted me-2"></i>{{ $suggestion->activity_date->format('d M Y') }} · {{ substr($suggestion->start_time, 0, 5) }}–{{ substr($suggestion->end_time, 0, 5) }}</div><div class="mt-1"><i class="fas fa-location-dot text-muted me-2"></i>{{ $suggestion->selectedBusinessService->businessPlace->name }} · {{ $suggestion->selectedBusinessService->name }}</div><div class="mt-1"><i class="fas fa-users text-muted me-2"></i>{{ $suggestion->accepted_members_count }}/{{ $suggestion->max_members }} anggota</div></div><form method="POST" action="{{ route('sport-groups.join', $suggestion) }}" class="mt-3">@csrf<button class="btn btn-primary btn-sm w-100"><i class="fas fa-user-plus me-1"></i>Pilih dan ikut kelompok</button></form></div></div>
            @empty
                <div class="col-12"><div class="text-center text-muted py-4"><i class="fas fa-calendar-xmark fs-3 d-block mb-2"></i>Belum ada saran kelompok yang cocok. Lengkapi lebih banyak olahraga, level, dan jadwal di profil Anda.</div></div>
            @endforelse
        </div>
    </div>

    <div class="content-card"><div class="card-heading"><div><h5>Kelompok saya</h5><p>Kegiatan yang sudah Anda pilih atau ikuti.</p></div></div><div class="table-responsive"><table class="table align-middle"><thead><tr><th>KEGIATAN</th><th>OLAHRAGA & LEVEL</th><th>JADWAL</th><th>LAPANGAN</th><th>ANGGOTA</th><th></th></tr></thead><tbody>@forelse($groups as $group)<tr><td><strong>{{ $group->name }}</strong><small class="d-block text-muted">{{ $group->creator->name }}</small></td><td><span class="badge text-bg-light">{{ $group->businessCategory->name }}</span><small class="d-block text-muted">{{ ['beginner' => 'Pemula', 'intermediate' => 'Menengah', 'advanced' => 'Mahir'][$group->level] ?? ucfirst($group->level) }}</small></td><td>{{ $group->activity_date->format('d M Y') }}<small class="d-block text-muted">{{ substr($group->start_time, 0, 5) }}–{{ substr($group->end_time, 0, 5) }}</small></td><td>{{ $group->selectedBusinessService?->businessPlace?->name ?: 'Tidak tersedia' }}</td><td>{{ $group->accepted_members_count }}/{{ $group->max_members }}</td><td class="text-end"><a href="{{ route('sport-groups.show', $group) }}" class="btn btn-sm btn-light">Detail</a></td></tr>@empty<tr><td colspan="6" class="text-center text-muted py-5">Belum ada kelompok yang Anda ikuti.</td></tr>@endforelse</tbody></table></div>{{ $groups->links() }}</div>
    @if($popularSchedules->isNotEmpty() && auth()->user()->latitude !== null && auth()->user()->longitude !== null)
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                if (typeof Chart === 'undefined') {
                    return;
                }

                const chartElement = document.getElementById('popularSchedulesChart');
                const details = @json($popularChartDetails);
                const styles = getComputedStyle(document.documentElement);
                const muted = styles.getPropertyValue('--muted').trim() || '#8690aa';
                const line = styles.getPropertyValue('--line').trim() || '#edf0f7';

                new Chart(chartElement, {
                    type: 'bar',
                    data: {
                        labels: @json($popularChartLabels),
                        datasets: [{
                            label: 'Jumlah user',
                            data: @json($popularSchedules->pluck('user_count')->values()),
                            backgroundColor: 'rgba(108, 99, 255, .78)',
                            borderRadius: 6,
                            borderSkipped: false,
                            barThickness: 18,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            x: {
                                beginAtZero: true,
                                ticks: { precision: 0, color: muted },
                                grid: { color: line },
                            },
                            y: {
                                ticks: { color: muted, font: { size: 11 } },
                                grid: { display: false },
                            },
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: context => context.raw + ' user',
                                    afterLabel: context => 'Jarak rata-rata: ' + details[context.dataIndex].average_distance_km + ' km',
                                },
                            },
                        },
                    },
                });
            })();
        </script>
    @endif
@endsection
