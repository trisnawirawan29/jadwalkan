@extends('layouts.admin')

@section('title', $businessService->exists ? 'Edit Layanan' : 'Tambah Layanan')
@section('page-title', $businessService->exists ? 'Edit Layanan' : 'Tambah Layanan')
@section('page-subtitle', $businessPlace->name)

@section('content')
    @php
        $isActive = (string) old('is_active', $businessService->exists ? (int) $businessService->is_active : 1) === '1';
        $storedHourlyPrices = $businessService->relationLoaded('hourlyPrices') ? $businessService->hourly_price_map : [];
        $hourlyPrices = old('hourly_prices', $storedHourlyPrices !== [] ? $storedHourlyPrices : ($businessService->hourly_prices ?? []));
    @endphp
    @php
        $hourlyScheduleGroups = collect($businessService->schedules ?? [])->map(function ($schedule): array {
            $start = (int) substr($schedule->start_time, 0, 2) * 60 + (int) substr($schedule->start_time, 3, 2);
            $end = (int) substr($schedule->end_time, 0, 2) * 60 + (int) substr($schedule->end_time, 3, 2);
            $slots = [];

            for ($minutes = $start; $minutes + 60 <= $end; $minutes += 60) {
                $slots[] = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            }

            return [
                'day' => $schedule->day_of_week,
                'day_name' => $schedule->day_name,
                'start' => substr($schedule->start_time, 0, 5),
                'end' => substr($schedule->end_time, 0, 5),
                'slots' => $slots,
            ];
        })
            ->unique(fn (array $group): string => $group['day'].'-'.$group['start'].'-'.$group['end'])
            ->values();
    @endphp

    <div class="business-service-editor">
        <div class="business-service-hero">
            <div>
                <p class="eyebrow">{{ $businessService->exists ? 'PERBARUI LAYANAN' : 'TAMBAHKAN LAYANAN BARU' }}</p>
                <h2>{{ $businessService->exists ? 'Sempurnakan detail layanan Anda.' : 'Hadirkan layanan terbaik di tempat Anda.' }}</h2>
                <p>Lengkapi detail, harga per jam, dan status layanan agar pelanggan dapat melakukan booking dengan jelas.</p>
            </div>
            <div class="business-service-hero-icon"><i class="fas fa-calendar-check"></i></div>
        </div>

        @if ($errors->any())
            <div class="alert alert-danger mt-3 mb-0"><i class="fas fa-circle-exclamation me-2"></i>{{ $errors->first() }}</div>
        @endif

        <div class="business-service-form-grid">
            <div class="business-service-card">
                <div class="business-service-card-heading"><span class="number">01</span><div><h5>Informasi layanan</h5><p>Data yang akan dilihat pelanggan saat memilih layanan.</p></div></div>
                <form method="POST" action="{{ $businessService->exists ? route('provider.business-places.services.update', [$businessPlace, $businessService]) : route('provider.business-places.services.store', $businessPlace) }}" enctype="multipart/form-data">
                    @csrf
                    @if ($businessService->exists)
                        @method('PUT')
                    @endif

                    <div class="business-service-form-grid-fields">
                        <div><label class="form-label" for="name">Nama layanan / lapangan</label><input id="name" name="name" class="form-control form-control-lg" value="{{ old('name', $businessService->name) }}" placeholder="Contoh: Lapangan Tenis 1" required></div>
                        <div><label class="form-label" for="business_category_id">Jenis layanan</label><select id="business_category_id" name="business_category_id" class="form-select form-select-lg"><option value="">Pilih jenis layanan</option>@foreach ($categories as $category)<option value="{{ $category->id }}" @selected((string) old('business_category_id', $businessService->business_category_id) === (string) $category->id)>{{ $category->name }}</option>@foreach ($category->children as $child)<option value="{{ $child->id }}" @selected((string) old('business_category_id', $businessService->business_category_id) === (string) $child->id)>{{ $category->name }} · {{ $child->name }}</option>@endforeach @endforeach</select></div>
                    </div>

                    <div class="row g-3 mt-1"><div class="col-md-6"><label class="form-label" for="type">Tipe layanan</label><input id="type" name="type" class="form-control" value="{{ old('type', $businessService->type) }}" placeholder="Contoh: Futsal"></div><div class="col-md-6"><label class="form-label" for="price_per_hour">Harga umum per jam</label><div class="input-group"><span class="input-group-text">Rp</span><input id="price_per_hour" name="price_per_hour" type="number" min="0" step="1000" class="form-control" value="{{ old('price_per_hour', $businessService->price_per_hour ?? 0) }}" required></div><div class="business-service-field-hint">Harga ini digunakan untuk slot yang tidak memiliki aturan khusus.</div></div></div>

                    @include('provider.services._hourly-pricing')
                    {{--
                    <div class="service-hourly-pricing mt-4">
                        <div class="d-flex justify-content-between align-items-start gap-3"><div><label class="form-label mb-1">{{ __('Harga berbeda per slot jam') }} <span class="text-muted">({{ __('opsional') }})</span></label><p class="business-service-field-hint mb-0">{{ __('Pilih beberapa jam sekaligus, masukkan satu harga, lalu tambahkan sebagai aturan. Jam tanpa aturan memakai harga umum.') }}</p></div><span class="service-price-badge"><i class="fas fa-wand-magic-sparkles me-1"></i> {{ __('Fleksibel') }}</span></div>
                        @if ($hourlyScheduleGroups->isNotEmpty())
                            <div class="service-hourly-price-days mt-3">
                                @foreach ($hourlyScheduleGroups as $group)
                                    @php($dayPrices = is_array($hourlyPrices[$group['day']] ?? null) ? $hourlyPrices[$group['day']] : [])
                                    <div class="service-hourly-day-group" data-price-day="{{ $group['day'] }}">
                                        <div class="service-hourly-day-heading"><strong>{{ $group['day_name'] }}</strong><span>{{ $group['start'] }}–{{ $group['end'] }}</span></div>
                                        <div class="service-price-rule-builder">
                                            <select class="form-select service-hourly-multi-select" multiple size="{{ min(max(count($group['slots']), 3), 6) }}" data-slot-select aria-label="{{ __('Pilih jam khusus') }}">
                                                @foreach ($group['slots'] as $slot)
                                                    @php($slotPrice = old('hourly_prices.'.$group['day'].'.'.$slot, $dayPrices[$slot] ?? $hourlyPrices[$slot] ?? ''))
                                                    <option value="{{ $slot }}" @selected($slotPrice !== '')>{{ $slot }}</option>
                                                @endforeach
                                            </select>
                                            <div class="service-price-rule-input"><div class="input-group"><span class="input-group-text">Rp</span><input type="number" min="0" step="1000" class="form-control" data-price-input placeholder="{{ __('Masukkan harga') }}"></div><button type="button" class="btn btn-primary" data-add-price-rule><i class="fas fa-plus me-1"></i>{{ __('Tambahkan aturan') }}</button></div>
                                            <div class="service-price-rules" data-price-rules>
                                                @foreach ($group['slots'] as $slot)
                                                    @php($slotPrice = old('hourly_prices.'.$group['day'].'.'.$slot, $dayPrices[$slot] ?? $hourlyPrices[$slot] ?? ''))
                                                    @if ($slotPrice !== '')
                                                        <div class="service-price-rule" data-price-rule="{{ $slot }}"><span><i class="fas fa-clock me-1"></i>{{ $slot }}</span><strong>Rp {{ number_format((float) $slotPrice, 0, ',', '.') }}</strong><input type="hidden" name="hourly_prices[{{ $group['day'] }}][{{ $slot }}]" value="{{ $slotPrice }}"><button type="button" class="btn btn-sm text-danger p-0" data-remove-price-rule aria-label="{{ __('Hapus aturan') }}"><i class="fas fa-xmark"></i></button></div>
                                                    @endif
                                                @endforeach
                                            </div>
                                            <small class="service-price-rule-help">{{ __('Gunakan Ctrl/Cmd untuk memilih beberapa jam.') }}</small>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="service-hourly-empty mt-3"><i class="fas fa-info-circle me-2"></i>{{ __('Simpan layanan dan tambahkan jadwal operasional terlebih dahulu. Harga per slot dapat diatur saat mengedit layanan.') }}</div>
                        @endif
                    </div>
                    --}}

                    <div class="mt-4"><label class="form-label" for="description">Deskripsi layanan</label><textarea id="description" name="description" class="form-control" rows="5" maxlength="2000" placeholder="Ceritakan fasilitas atau keunggulan layanan...">{{ old('description', $businessService->description) }}</textarea></div>
                    <div class="business-service-cover-upload mt-4"><div class="business-service-cover-preview" id="service-cover-preview">@if ($businessService->cover_image_url)<img src="{{ $businessService->cover_image_url }}" alt="{{ $businessService->name }}">@else<i class="fas fa-image"></i><span>{{ __('Belum ada gambar layanan') }}</span>@endif</div><div class="business-service-cover-copy"><label class="form-label" for="cover_image">Gambar layanan <span class="text-muted">({{ __('opsional') }})</span></label><p>{{ __('Tambahkan gambar agar layanan lebih mudah dikenali pelanggan.') }}</p><label class="btn btn-light btn-sm business-service-upload-button" for="cover_image"><i class="fas fa-camera me-1"></i>{{ __('Pilih gambar') }}</label><input id="cover_image" type="file" name="cover_image" class="d-none" accept="image/jpeg,image/png,image/webp"><div class="business-service-field-hint">JPG, PNG, atau WEBP · Maksimal 5 MB</div></div></div>
                    <div class="business-service-status mt-4"><div class="business-service-status-copy"><strong>Status layanan</strong><span>{{ $isActive ? 'Layanan aktif dan dapat diatur jadwalnya.' : 'Layanan nonaktif tidak ditampilkan sebagai pilihan aktif.' }}</span></div><div class="business-service-status-control"><span class="business-service-status-label {{ $isActive ? 'is-active' : '' }}">{{ $isActive ? 'Aktif' : 'Nonaktif' }}</span><label class="business-service-switch" aria-label="Status layanan"><input type="hidden" name="is_active" value="0"><input id="is_active" type="checkbox" name="is_active" value="1" @checked($isActive)><span></span></label></div></div>
                    <div class="business-service-actions"><a href="{{ route('provider.business-places.services.index', $businessPlace) }}" class="btn btn-light">Batal</a><button class="btn btn-primary"><i class="fas fa-check me-2"></i>{{ $businessService->exists ? 'Simpan perubahan' : 'Simpan layanan' }}</button></div>
                </form>
            </div>
            <aside class="business-service-card"><div class="business-service-card-heading"><span class="number">02</span><div><h5>{{ __('Booking per jam') }}</h5><p>{{ __('Harga dan jadwal menjadi acuan pelanggan.') }}</p></div></div><div class="business-service-tips"><div class="business-service-tip"><i class="fas fa-money-bill-wave"></i><div><strong>{{ __('Atur harga tiap slot') }}</strong><p>{{ __('Jam ramai dan jam reguler dapat memiliki harga yang berbeda.') }}</p></div></div><div class="business-service-tip"><i class="fas fa-clock"></i><div><strong>{{ __('Slot mengikuti jadwal') }}</strong><p>{{ __('Pelanggan hanya melihat jam yang tersedia pada tanggal pilihannya.') }}</p></div></div><div class="business-service-tip"><i class="fas fa-calculator"></i><div><strong>{{ __('Total dijumlah otomatis') }}</strong><p>{{ __('Total booking mengikuti harga dari setiap slot jam yang dipilih.') }}</p></div></div></div></aside>
        </div>
    </div>

    <script>
        document.querySelectorAll('[data-price-day]').forEach(group => {
            const day = group.dataset.priceDay;
            const select = group.querySelector('[data-slot-select]');
            const priceInput = group.querySelector('[data-price-input]');
            const addButton = group.querySelector('[data-add-price-rule]');
            const rules = group.querySelector('[data-price-rules]');
            const formatPrice = value => new Intl.NumberFormat('{{ app()->getLocale() === 'en' ? 'en-US' : 'id-ID' }}').format(value);

            addButton.addEventListener('click', () => {
                const price = Number(priceInput.value);
                const selectedSlots = [...select.selectedOptions].map(option => option.value);
                if (!selectedSlots.length || !Number.isFinite(price) || price < 0) {
                    group.classList.add('has-price-error');
                    return;
                }

                group.classList.remove('has-price-error');
                selectedSlots.forEach(slot => {
                    let rule = rules.querySelector(`[data-price-rule="${slot}"]`);
                    if (!rule) {
                        rule = document.createElement('div');
                        rule.className = 'service-price-rule';
                        rule.dataset.priceRule = slot;
                        rule.innerHTML = `<span><i class="fas fa-clock me-1"></i>${slot}</span><strong></strong><input type="hidden" name="hourly_prices[${day}][${slot}]"><button type="button" class="btn btn-sm text-danger p-0" data-remove-price-rule aria-label="{{ __('Hapus aturan') }}"><i class="fas fa-xmark"></i></button>`;
                        rules.appendChild(rule);
                        rule.querySelector('[data-remove-price-rule]').addEventListener('click', () => {
                            rule.remove();
                            const option = select.querySelector(`option[value="${slot}"]`);
                            if (option) option.selected = false;
                        });
                    }

                    rule.querySelector('strong').textContent = `Rp ${formatPrice(price)}`;
                    rule.querySelector('input[type="hidden"]').value = price;
                });

                select.selectedIndex = -1;
                priceInput.value = '';
            });

            group.querySelectorAll('[data-remove-price-rule]').forEach(button => button.addEventListener('click', () => {
                const rule = button.closest('[data-price-rule]');
                const option = select.querySelector(`option[value="${rule.dataset.priceRule}"]`);
                if (option) option.selected = false;
                rule.remove();
            }));
        });
    </script>
    <script>
        document.getElementById('cover_image')?.addEventListener('change', event => {
            const file = event.target.files?.[0];
            const preview = document.getElementById('service-cover-preview');
            if (!file || !preview) return;

            const image = document.createElement('img');
            image.alt = '{{ __('Preview gambar layanan') }}';
            image.src = URL.createObjectURL(file);
            preview.replaceChildren(image);
        });
    </script>
@endsection
