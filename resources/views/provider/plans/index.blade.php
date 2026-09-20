@extends('layouts.admin')

@section('title', __('Upgrade paket'))
@section('page-title', __('Upgrade paket'))
@section('page-subtitle', __('Pilih paket yang sesuai dengan pertumbuhan bisnis Anda.'))

@section('content')
    <div class="provider-plan-upgrade-shell">
        @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
        @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
        @if (auth()->user()->providerPlan && (auth()->user()->providerPlan->isFree() || auth()->user()->provider_plan_expires_at?->isFuture()))
            <div class="current-plan-banner"><span class="current-plan-icon"><i class="fas fa-gem"></i></span><div><small>{{ __('PAKET AKTIF SAAT INI') }}</small><strong>{{ auth()->user()->providerPlan->name }}</strong><p>Rp {{ number_format((float) auth()->user()->providerPlan->monthly_price, 0, ',', '.') }}/{{ __('bulan') }} · {{ auth()->user()->providerPlan->max_business_places }} {{ __('bisnis') }} · {{ auth()->user()->providerPlan->max_bookings_per_month }} {{ __('booking per bulan') }}</p>@if(auth()->user()->provider_plan_expires_at)<small class="text-muted">{{ __('Berlaku sampai') }} {{ auth()->user()->provider_plan_expires_at->format('d M Y') }}</small>@endif</div></div>
        @elseif (auth()->user()->providerPlan)
            <div class="alert alert-warning small"><i class="fas fa-triangle-exclamation me-2"></i>{{ __('Masa aktif paket Anda telah berakhir. Bisnis dinonaktifkan sementara, silakan ajukan upgrade kembali.') }}</div>
        @else
            <div class="alert alert-info small"><i class="fas fa-circle-info me-2"></i>{{ __('Anda belum memiliki paket provider. Pilih paket untuk mendapatkan kapasitas yang sesuai.') }}</div>
        @endif
        @if ($pendingUpgrade)<div class="alert alert-warning small"><i class="fas fa-hourglass-half me-2"></i>{{ __('Pengajuan upgrade ke paket :plan sedang menunggu validasi superadmin.', ['plan' => $pendingUpgrade->providerPlan->name]) }}</div>@endif
        <div class="row g-4">
            @foreach ($plans as $plan)
                @php($isCurrentPlan = auth()->user()->provider_plan_id === $plan->id && ($plan->isFree() || auth()->user()->provider_plan_expires_at?->isFuture()))
                @php($quote = $upgradeQuotes[$plan->id])
                <div class="col-12 col-md-6 col-xl-4"><div class="provider-plan-upgrade-card {{ $isCurrentPlan ? 'is-current' : '' }}"><div class="d-flex justify-content-between align-items-start"><div><span class="provider-plan-upgrade-tag">{{ $isCurrentPlan ? __('AKTIF') : __('PREMIUM') }}</span><h3>{{ $plan->name }}</h3></div><div class="provider-plan-upgrade-price">Rp {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}<small>/{{ __('bulan') }}</small></div></div><p class="provider-plan-upgrade-description">{{ $plan->description ?: __('Paket untuk provider bisnis.') }}</p><ul class="provider-plan-feature-list"><li><i class="fas fa-building"></i>{{ __('Maksimal :count bisnis', ['count' => $plan->max_business_places]) }}</li><li><i class="fas fa-layer-group"></i>{{ __(':count layanan per bisnis', ['count' => $plan->max_services_per_place]) }}</li><li><i class="fas fa-calendar-check"></i>{{ __(':count booking per bulan', ['count' => $plan->max_bookings_per_month]) }}</li></ul>
                    @if ($isCurrentPlan)
                        <button class="btn btn-light w-100" disabled><i class="fas fa-check me-1"></i>{{ __('Paket sedang digunakan') }}</button>
                    @elseif ($pendingUpgrade)
                        <button class="btn btn-light w-100" disabled>{{ __('Menunggu validasi') }}</button>
                    @else
                        <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#upgradeModal{{ $plan->id }}"><i class="fas fa-arrow-up me-1"></i>{{ __('Ajukan upgrade') }}</button>
                        @if ($quote['is_prorated'])<template id="prorationTemplate{{ $plan->id }}"><div class="upgrade-proration-box"><div class="upgrade-proration-heading"><i class="fas fa-calculator"></i><strong>{{ __('Harga upgrade dihitung prorata') }}</strong></div><p>{{ __('Karena paket :plan masih aktif, Anda hanya membayar selisih harga untuk sisa masa aktif.', ['plan' => $quote['current_plan_name']]) }}</p><div class="upgrade-proration-grid"><span>{{ __('Selisih harga per bulan') }}<strong>Rp {{ number_format($quote['price_difference'], 0, ',', '.') }}</strong></span><span>{{ __('Sisa masa aktif') }}<strong>{{ $quote['remaining_days'] }} / {{ $quote['total_days'] }} {{ __('hari') }}</strong></span></div><small>{{ __('Rumus: selisih harga × sisa hari ÷ total hari.') }}</small></div></template>@endif
                        @php($plan->payment_instruction = __('Lakukan pembayaran melalui metode yang tersedia di bawah ini, lalu unggah bukti pembayaran untuk divalidasi.'))
                        <div class="modal fade" id="upgradeModal{{ $plan->id }}" data-upgrade-modal tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form method="POST" action="{{ route('provider.plans.upgrade', $plan) }}" enctype="multipart/form-data" class="modal-content">@csrf<div class="modal-header"><h5 class="modal-title">{{ __('Ajukan paket :plan', ['plan' => $plan->name]) }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><div class="upgrade-payment-summary"><span>{{ __('Total pembayaran') }}</span><strong>Rp {{ number_format((float) $plan->monthly_price, 0, ',', '.') }}</strong></div><div class="upgrade-service-period"><div><small>{{ __('Mulai layanan') }}</small><strong>{{ $estimatedStartsAt->format('d M Y') }}</strong></div><i class="fas fa-arrow-right"></i><div><small>{{ __('Berakhir layanan') }}</small><strong>{{ $estimatedExpiresAt->format('d M Y') }}</strong></div></div>@if($plan->payment_instruction)<div class="upgrade-payment-instruction"><strong><i class="fas fa-circle-info me-1"></i>{{ __('Instruksi pembayaran') }}</strong><p>{{ $plan->payment_instruction }}</p></div>@endif<p class="small text-muted">{{ __('Lakukan pembayaran sesuai instruksi admin, lalu unggah bukti pembayaran untuk divalidasi superadmin.') }}</p><label class="form-label">{{ __('Metode pembayaran') }}</label><select name="payment_method" data-payment-method class="form-select mb-3" required><option value="bank_transfer">{{ __('Transfer bank') }}</option><option value="qris">{{ __('QRIS') }}</option></select><div class="upgrade-payment-method-panel" data-payment-panel="bank_transfer"><div class="upgrade-method-heading"><span class="upgrade-method-icon"><i class="fas fa-building-columns"></i></span><div><strong>{{ __('Transfer bank') }}</strong><small>{{ $paymentSettings['bank_name'] ?: __('Belum diatur') }}</small></div></div>@if($paymentSettings['bank_account_number'])<div class="upgrade-bank-account">{{ $paymentSettings['bank_account_number'] }}</div><small class="text-muted">{{ __('a.n.') }} {{ $paymentSettings['bank_account_name'] ?: __('Belum diatur') }}</small>@else<p class="small text-muted mb-0">{{ __('Informasi bank belum diatur oleh superadmin.') }}</p>@endif</div><div class="upgrade-payment-method-panel" data-payment-panel="qris" hidden><div class="upgrade-method-heading"><span class="upgrade-method-icon"><i class="fas fa-qrcode"></i></span><div><strong>{{ __('QRIS') }}</strong><small>{{ __('Scan untuk membayar') }}</small></div></div>@if($paymentSettings['qris_url'])<img src="{{ $paymentSettings['qris_url'] }}" class="upgrade-qris-image" alt="QRIS">@else<p class="small text-muted mb-0">{{ __('QRIS belum diatur oleh superadmin.') }}</p>@endif</div><label class="form-label mt-3">{{ __('Bukti pembayaran') }}</label><input type="file" name="payment_proof" class="form-control" accept="image/jpeg,image/png,image/webp,application/pdf" {{ $plan->monthly_price > 0 ? 'required' : '' }}><small class="text-muted">{{ __('JPG, PNG, WEBP, atau PDF. Maksimal 5 MB.') }}</small></div><div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('Batal') }}</button><button class="btn btn-primary"><i class="fas fa-paper-plane me-1"></i>{{ __('Kirim pengajuan') }}</button></div></form></div></div>
                    @endif
                </div></div>
            @endforeach
        </div>
        @if ($upgradeRequests->isNotEmpty())<div class="content-card mt-4"><div class="card-heading"><div><h5>{{ __('Riwayat upgrade') }}</h5><p>{{ __('Pantau status pengajuan paket Anda.') }}</p></div></div><div class="table-responsive"><table class="table align-middle mb-0"><thead><tr><th>{{ __('Tanggal') }}</th><th>{{ __('Paket') }}</th><th>{{ __('Periode layanan') }}</th><th>{{ __('Nominal') }}</th><th>{{ __('Status') }}</th><th>{{ __('Catatan') }}</th></tr></thead><tbody>@foreach ($upgradeRequests as $upgrade)<tr><td>{{ $upgrade->created_at->format('d M Y') }}</td><td class="fw-semibold">{{ $upgrade->providerPlan->name }}</td><td class="small">{{ $upgrade->service_started_at?->format('d M Y') ?: '-' }}<br>{{ $upgrade->service_expires_at?->format('d M Y') ?: '-' }}</td><td>Rp {{ number_format((float) $upgrade->amount, 0, ',', '.') }}</td><td><span class="status {{ $upgrade->status === 'approved' ? 'status-success' : ($upgrade->status === 'rejected' ? 'status-danger' : 'status-warning') }}">{{ ucfirst($upgrade->status) }}</span></td><td class="small text-muted">{{ $upgrade->rejection_reason ?: '-' }}</td></tr>@endforeach</tbody></table></div></div>@endif
<script>
    document.querySelectorAll('[data-upgrade-modal]').forEach(modal => {
        const select = modal.querySelector('[data-payment-method]');
        const panels = modal.querySelectorAll('[data-payment-panel]');
        const planId = modal.id.replace('upgradeModal', '');
        const quote = @json($upgradeQuotes)[planId];
        const paymentSummary = modal.querySelector('.upgrade-payment-summary strong');
        const prorationTemplate = document.getElementById(`prorationTemplate${planId}`);
        if (quote && paymentSummary) paymentSummary.textContent = `Rp ${new Intl.NumberFormat('id-ID').format(quote.amount)}`;
        if (quote?.is_prorated && prorationTemplate) modal.querySelector('.upgrade-payment-summary')?.after(prorationTemplate.content.cloneNode(true));
        if (quote?.is_prorated && quote.current_expires_at) {
            const endDate = new Date(quote.current_expires_at);
            const endLabel = modal.querySelector('.upgrade-service-period div:last-child strong');
            if (endLabel && !Number.isNaN(endDate.getTime())) endLabel.textContent = new Intl.DateTimeFormat('id-ID', {day: '2-digit', month: 'short', year: 'numeric'}).format(endDate);
        }
        const render = () => panels.forEach(panel => { panel.hidden = panel.dataset.paymentPanel !== select.value; });
        select?.addEventListener('change', render);
        render();
    });
</script>
</div>
@endsection
