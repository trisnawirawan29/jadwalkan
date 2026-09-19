<div class="service-hourly-pricing mt-4" data-price-rule-builder>
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <label class="form-label mb-1">{{ __('Harga berbeda per slot jam') }} <span class="text-muted">({{ __('opsional') }})</span></label>
            <p class="business-service-field-hint mb-0">{{ __('Pilih hari, pilih slot, lalu masukkan harga khusus. Slot lain memakai harga umum.') }}</p>
        </div>
        <span class="service-price-badge"><i class="fas fa-wand-magic-sparkles me-1"></i> {{ __('Fleksibel') }}</span>
    </div>

    @if ($hourlyScheduleGroups->isNotEmpty())
        <div class="service-price-compact-builder mt-3">
            <div class="service-price-compact-fields">
                <div><label class="form-label" for="special-price-day">{{ __('Pilih hari') }}</label><select id="special-price-day" class="form-select" data-price-day-select><option value="">{{ __('Pilih hari') }}</option>@foreach ($hourlyScheduleGroups->groupBy('day') as $day => $groups)<option value="{{ $day }}" data-slots="{{ $groups->pluck('slots')->flatten()->unique()->sort()->implode('|') }}">{{ $groups->first()['day_name'] }}</option>@endforeach</select></div>
                <div><label class="form-label">{{ __('Pilih slot jam') }}</label><div class="service-slot-checkbox-grid" data-price-slot-options><span class="service-slot-checkbox-empty">{{ __('Pilih hari terlebih dahulu') }}</span></div></div>
                <div><label class="form-label" for="special-price-value">{{ __('Harga khusus') }}</label><div class="input-group"><span class="input-group-text">Rp</span><input id="special-price-value" type="number" min="0" step="1000" class="form-control" data-price-input placeholder="{{ __('Masukkan harga') }}"></div></div>
            </div>
            <button type="button" class="btn btn-primary service-price-add-button mt-3" data-add-price-rule><i class="fas fa-plus me-1"></i>{{ __('Tambahkan aturan') }}</button>
            <small class="service-price-rule-help d-block mt-2">{{ __('Gunakan Ctrl/Cmd untuk memilih beberapa jam.') }}</small>
        </div>

        <div class="service-price-rules mt-3" data-price-rules>
            @foreach ($hourlyPrices as $day => $prices)
                @if (is_array($prices))
                    @foreach ($prices as $slot => $slotPrice)
                        @if ($slotPrice !== '')
                            <div class="service-price-rule" data-price-rule="{{ $day }}-{{ $slot }}"><span><i class="fas fa-calendar-day me-1"></i>{{ $day }} · {{ $slot }}</span><strong>Rp {{ number_format((float) $slotPrice, 0, ',', '.') }}</strong><input type="hidden" name="hourly_prices[{{ $day }}][{{ $slot }}]" value="{{ $slotPrice }}"><button type="button" class="btn btn-sm text-danger p-0" data-remove-price-rule aria-label="{{ __('Hapus aturan') }}"><i class="fas fa-xmark"></i></button></div>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </div>
    @else
        <div class="service-hourly-empty mt-3"><i class="fas fa-info-circle me-2"></i>{{ __('Simpan layanan dan tambahkan jadwal operasional terlebih dahulu. Harga per slot dapat diatur saat mengedit layanan.') }}</div>
    @endif
</div>

<script>
    const priceBuilder = document.querySelector('[data-price-rule-builder]');
    if (priceBuilder) {
        const daySelect = priceBuilder.querySelector('[data-price-day-select]');
        const slotOptions = priceBuilder.querySelector('[data-price-slot-options]');
        const priceInput = priceBuilder.querySelector('[data-price-input]');
        const addButton = priceBuilder.querySelector('[data-add-price-rule]');
        const rules = priceBuilder.querySelector('[data-price-rules]');
        const formatPrice = value => new Intl.NumberFormat('{{ app()->getLocale() === 'en' ? 'en-US' : 'id-ID' }}').format(value);

        daySelect?.addEventListener('change', () => {
            slotOptions.innerHTML = '';
            if (!daySelect.value) { slotOptions.innerHTML = '<span class="service-slot-checkbox-empty">{{ __('Pilih hari terlebih dahulu') }}</span>'; return; }
            daySelect.selectedOptions[0].dataset.slots.split('|').filter(Boolean).forEach(slot => { const label = document.createElement('label'); label.className = 'service-slot-checkbox'; label.innerHTML = `<input type="checkbox" value="${slot}" data-price-slot><span>${slot}</span>`; slotOptions.appendChild(label); });
        });

        addButton?.addEventListener('click', () => {
            const day = daySelect.value;
            const price = Number(priceInput.value);
            const slots = [...slotOptions.querySelectorAll('[data-price-slot]:checked')].map(option => option.value);
            if (!day || !slots.length || !Number.isFinite(price) || price < 0) { priceBuilder.classList.add('has-price-error'); return; }
            priceBuilder.classList.remove('has-price-error');
            slots.forEach(slot => {
                const key = `${day}-${slot}`;
                let rule = rules.querySelector(`[data-price-rule="${key}"]`);
                if (!rule) { rule = document.createElement('div'); rule.className = 'service-price-rule'; rule.dataset.priceRule = key; rule.innerHTML = `<span><i class="fas fa-calendar-day me-1"></i>${day} · ${slot}</span><strong></strong><input type="hidden" name="hourly_prices[${day}][${slot}]"><button type="button" class="btn btn-sm text-danger p-0" data-remove-price-rule aria-label="{{ __('Hapus aturan') }}"><i class="fas fa-xmark"></i></button>`; rules.appendChild(rule); rule.querySelector('[data-remove-price-rule]').addEventListener('click', () => rule.remove()); }
                rule.querySelector('strong').textContent = `Rp ${formatPrice(price)}`;
                rule.querySelector('input').value = price;
            });
            slotOptions.querySelectorAll('[data-price-slot]').forEach(option => { option.checked = false; });
            priceInput.value = '';
        });
    }
</script>
