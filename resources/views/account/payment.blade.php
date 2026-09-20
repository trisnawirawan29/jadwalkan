@extends('layouts.admin')

@section('title', __('Pembayaran'))
@section('page-title', __('Pembayaran'))
@section('page-subtitle', __('Atur rekening bank dan QRIS yang dapat digunakan pelanggan saat melakukan pembayaran.'))

@section('content')
    @if (session('success'))<div class="alert alert-success small">{{ session('success') }}</div>@endif
    @if ($errors->any())<div class="alert alert-danger small">{{ $errors->first() }}</div>@endif
    <div class="row g-4">
        <div class="col-12 col-xl-7"><div class="content-card"><div class="card-heading"><div><h5>{{ __('Metode pembayaran aktif') }}</h5><p>{{ __('Pilih metode tertentu saat mengatur setiap bisnis.') }}</p></div><span class="status status-info"><i class="fas fa-shield-halved me-1"></i>{{ __('Data aman') }}</span></div>
            @forelse ($paymentMethods as $method)
                <div class="payment-method-card mb-3 {{ $method->isQris() ? 'payment-method-card-qris' : 'payment-method-card-bank' }} {{ $method->is_active ? '' : 'opacity-75' }}"><div class="d-flex justify-content-between align-items-start gap-3"><div class="payment-method-card-heading"><span class="payment-method-card-icon"><i class="fas {{ $method->isQris() ? 'fa-qrcode' : 'fa-building-columns' }}"></i></span><div><strong>{{ $method->isQris() ? __('QRIS') : __('Transfer bank') }}</strong><small>{{ $method->isQris() ? __('Scan kode untuk membayar') : $method->bank_name }}</small></div></div><div class="d-flex align-items-center gap-2"><form method="POST" action="{{ route('provider.payment-methods.toggle', $method) }}">@csrf @method('PATCH')<div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" role="switch" id="payment-method-{{ $method->id }}" onchange="this.form.submit()" @checked($method->is_active)><label class="form-check-label small" for="payment-method-{{ $method->id }}">{{ $method->is_active ? __('Aktif') : __('Nonaktif') }}</label></div></form><form method="POST" action="{{ route('provider.payment-methods.destroy', $method) }}" onsubmit="return confirm('{{ __('Hapus metode pembayaran ini?') }}')">@csrf @method('DELETE')<button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button></form></div></div>@if ($method->isQris()) @if ($method->qris_image_url)<img src="{{ $method->qris_image_url }}" alt="QRIS" class="payment-qris-preview mt-3">@endif @else<div class="payment-account-number mt-3">{{ $method->account_number }}</div><small class="payment-account-name">{{ __('a.n.') }} {{ $method->account_name }}</small>@endif</div>
            @empty
                <div class="alert alert-warning mb-0"><i class="fas fa-triangle-exclamation me-2"></i>{{ __('Belum ada metode pembayaran. Tambahkan minimal satu metode agar pelanggan dapat membayar booking.') }}</div>
            @endforelse
        </div></div>
        <div class="col-12 col-xl-5"><div class="content-card"><div class="card-heading"><div><h5>{{ __('Tambah metode pembayaran') }}</h5><p>{{ __('Satu provider dapat menambahkan beberapa rekening bank dan QRIS.') }}</p></div></div>
            <form method="POST" action="{{ route('provider.payment-methods.store') }}" enctype="multipart/form-data">@csrf
                <label class="form-label" for="payment_type">{{ __('Jenis pembayaran') }}</label><select id="payment_type" name="type" class="form-select mb-3" required><option value="bank_transfer">{{ __('Transfer bank') }}</option><option value="qris" @selected(old('type') === 'qris')>{{ __('QRIS') }}</option></select>
                <div data-payment-type="bank_transfer"><label class="form-label" for="bank_name">{{ __('Nama bank') }}</label><select id="bank_name" name="bank_name" class="form-select mb-3"><option value="">{{ __('Pilih bank') }}</option>@foreach ($commonBanks as $bank)<option value="{{ $bank }}" @selected(old('bank_name') === $bank)>{{ $bank }}</option>@endforeach</select><label class="form-label" for="account_name">{{ __('Nama pemilik rekening') }}</label><input id="account_name" name="account_name" class="form-control mb-3" value="{{ old('account_name') }}" placeholder="{{ __('Sesuai buku rekening') }}"><label class="form-label" for="account_number">{{ __('Nomor rekening') }}</label><input id="account_number" name="account_number" class="form-control" value="{{ old('account_number') }}" placeholder="{{ __('Masukkan nomor rekening') }}"></div>
                <div data-payment-type="qris" hidden><label class="form-label" for="qris_image">{{ __('Gambar QRIS') }}</label><input id="qris_image" type="file" name="qris_image" class="form-control" accept="image/jpeg,image/png,image/webp"><small class="text-muted">{{ __('JPG, PNG, atau WEBP. Maksimal 5 MB.') }}</small></div>
                <button class="btn btn-primary w-100 mt-4"><i class="fas fa-plus me-2"></i>{{ __('Tambah metode') }}</button>
            </form>
        </div></div>
    </div>
    <script>const paymentType = document.getElementById('payment_type'); const paymentPanels = document.querySelectorAll('[data-payment-type]'); const syncPaymentType = () => paymentPanels.forEach(panel => { panel.hidden = panel.dataset.paymentType !== paymentType.value; }); paymentType?.addEventListener('change', syncPaymentType); syncPaymentType();</script>
@endsection
