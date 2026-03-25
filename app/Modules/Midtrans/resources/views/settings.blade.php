@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Midtrans Settings</h2>
        <div class="text-muted small">Konfigurasi payment gateway Midtrans (Snap).</div>
    </div>
    <a href="{{ route('midtrans.transactions.index') }}" class="btn btn-outline-secondary">Lihat Transaksi</a>
</div>

@if(session('status'))
    <div class="alert alert-success">{{ session('status') }}</div>
@endif

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Kredensial API</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('midtrans.settings.update') }}">
                    @csrf
                    @method('PUT')

                    <div class="mb-3">
                        <label class="form-label">Environment</label>
                        <select name="environment" class="form-select">
                            <option value="sandbox" {{ ($setting->environment ?? 'sandbox') === 'sandbox' ? 'selected' : '' }}>Sandbox (Testing)</option>
                            <option value="production" {{ ($setting->environment ?? '') === 'production' ? 'selected' : '' }}>Production (Live)</option>
                        </select>
                        <div class="form-text">Gunakan <strong>Sandbox</strong> untuk testing, <strong>Production</strong> untuk live.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Server Key</label>
                        <input type="password" name="server_key" class="form-control font-monospace"
                               placeholder="{{ $setting->server_key ? '••••••••••••••••••• (tersimpan, kosongkan untuk tidak mengubah)' : 'SB-Mid-server-xxxx atau Mid-server-xxxx' }}"
                               autocomplete="new-password">
                        <div class="form-text">Digunakan untuk request ke API Midtrans. Jangan share ke siapapun.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Client Key</label>
                        <input type="text" name="client_key" class="form-control font-monospace"
                               placeholder="{{ $setting->client_key ? '••••••••••••••••••• (tersimpan)' : 'SB-Mid-client-xxxx atau Mid-client-xxxx' }}"
                               autocomplete="off">
                        <div class="form-text">Digunakan di frontend (Snap.js). Aman ditampilkan ke browser.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Merchant ID <span class="text-muted">(opsional)</span></label>
                        <input type="text" name="merchant_id" class="form-control" value="{{ old('merchant_id', $setting->merchant_id) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input type="hidden" name="is_active" value="0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1"
                                {{ $setting->is_active ? 'checked' : '' }}>
                            <span class="form-check-label">Aktifkan Midtrans</span>
                        </label>
                        <div class="form-text">Jika tidak aktif, tombol bayar via Midtrans tidak akan muncul.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Metode Pembayaran yang Diaktifkan</label>
                        <div class="form-text mb-2">
                            Pilih metode yang sudah diaktifkan di <strong>Midtrans Dashboard</strong>.
                            Kosongkan untuk menampilkan semua metode yang tersedia.
                        </div>
                        @php $enabledPayments = $setting->enabled_payments ?? []; @endphp
                        <div class="row g-2">
                            @foreach(\App\Modules\Midtrans\Models\MidtransSetting::AVAILABLE_PAYMENT_METHODS as $code => $label)
                                <div class="col-sm-6">
                                    <label class="form-check">
                                        <input class="form-check-input" type="checkbox"
                                               name="enabled_payments[]" value="{{ $code }}"
                                               {{ in_array($code, $enabledPayments) ? 'checked' : '' }}>
                                        <span class="form-check-label">{{ $label }}</span>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">Simpan</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Status Koneksi</h3></div>
            <div class="card-body">
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Environment</span>
                    <span class="badge {{ ($setting->environment ?? 'sandbox') === 'production' ? 'bg-green-lt text-green' : 'bg-yellow-lt text-yellow' }}">
                        {{ strtoupper($setting->environment ?? 'sandbox') }}
                    </span>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Server Key</span>
                    <span>{{ $setting->server_key ? '✓ Tersimpan' : '✗ Belum diisi' }}</span>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Client Key</span>
                    <span>{{ $setting->client_key ? '✓ Tersimpan' : '✗ Belum diisi' }}</span>
                </div>
                <div class="mb-2 d-flex justify-content-between">
                    <span class="text-muted">Status</span>
                    <span class="badge {{ $setting->is_active ? 'text-bg-green' : 'text-bg-secondary' }}">
                        {{ $setting->is_active ? 'Aktif' : 'Nonaktif' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title mb-0">Webhook URL</h3></div>
            <div class="card-body">
                <p class="text-muted small mb-2">Daftarkan URL ini di <strong>Midtrans Dashboard → Settings → Configuration → Payment Notification URL</strong>:</p>
                <div class="input-group">
                    <input type="text" class="form-control font-monospace small" readonly
                           value="{{ route('midtrans.webhook.notification') }}"
                           id="webhookUrl">
                    <button class="btn btn-outline-secondary" type="button"
                            onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value); this.textContent='Copied!'">
                        Copy
                    </button>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Cara Integrasi Snap</h3></div>
            <div class="card-body small text-muted">
                <ol class="mb-0 ps-3">
                    <li>Simpan Server Key & Client Key dari Midtrans Dashboard.</li>
                    <li>Dari frontend, POST ke <code>{{ route('midtrans.snap-token') }}</code> dengan <code>payable_type</code>, <code>payable_id</code>, dan <code>amount</code>.</li>
                    <li>Gunakan <code>snap_token</code> yang dikembalikan untuk membuka Snap popup.</li>
                    <li>Setelah pembayaran, Midtrans akan kirim notifikasi ke Webhook URL di atas.</li>
                    <li>Sistem otomatis membuat record Payment setelah settlement.</li>
                </ol>
            </div>
        </div>
    </div>
</div>
@endsection
