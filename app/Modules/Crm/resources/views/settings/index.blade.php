@extends('layouts.admin')

@section('content')
<div class="page-header mb-4">
    <div>
        <div class="page-pretitle">CRM</div>
        <h2 class="page-title">Settings & Entitlements</h2>
    </div>
</div>

@include('crm::partials.nav')

<div class="row g-3 mb-4">
    @foreach($limits as $label => $state)
        <div class="col-md-6 col-xl-4">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small text-uppercase">{{ \Illuminate\Support\Str::headline(str_replace('_', ' ', $label)) }}</div>
                    <div class="fs-3 fw-bold">{{ $state['usage'] }}</div>
                    <div class="small text-muted">Limit: {{ $state['limit'] ?? 'Unlimited' }} • Status: {{ \Illuminate\Support\Str::headline($state['status']) }}</div>
                </div>
            </div>
        </div>
    @endforeach
</div>

<div class="row g-3">
    <div class="col-lg-4"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Exports</div><div class="fw-semibold">{{ $capabilities['export'] ? 'Aktif' : 'Belum termasuk plan' }}</div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Manager Visibility</div><div class="fw-semibold">{{ $capabilities['manager_visibility'] ? 'Aktif' : 'Belum termasuk plan' }}</div></div></div></div>
    <div class="col-lg-4"><div class="card"><div class="card-body"><div class="text-muted small text-uppercase">Automation Access</div><div class="fw-semibold">{{ $capabilities['automation'] ? 'Ready for bridge' : 'Placeholder only' }}</div></div></div></div>
</div>

<div class="row g-3 mt-1">
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Lead Capture API</h3></div>
            <div class="card-body">
                <div class="small text-muted mb-2">Gunakan endpoint ini untuk sistem internal atau partner yang memakai token Sanctum tenant user.</div>
                <label class="form-label">Authenticated API URL</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $leadCaptureApiUrl }}">
                <div class="form-hint mt-2">Payload minimal: <code>title</code>, lalu optional <code>name/email/mobile/external_reference/provider</code>.</div>

                <label class="form-label mt-3">Public Webhook URL</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $leadCaptureWebhookUrl }}">
                <label class="form-label mt-3">Meta Lead Ads Webhook URL</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $metaLeadWebhookUrl }}">
                <label class="form-label mt-3">Lead Capture Token</label>
                <input type="text" class="form-control font-monospace" readonly value="{{ $integrationSettings['lead_capture_token'] ?? '' }}">
                <div class="form-hint mt-2">Cocok untuk Meta Ads, form builder, atau webhook eksternal yang tidak login ke tenant.</div>
                <div class="form-hint mt-2">Untuk Meta Lead Ads, kirim token yang sama lewat header <code>X-Lead-Capture-Token</code>.</div>
                <div class="form-hint mt-2">Rule routing owner mendukung prefix: <code>source:</code>, <code>provider:</code>, <code>campaign:</code>, <code>adset:</code>, <code>form:</code>, <code>title:</code>.</div>
            </div>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title mb-0">Won Automation</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('crm.settings.update') }}">
                    @csrf
                    @php($onWon = (array) ($integrationSettings['on_won'] ?? []))
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="on_won_enabled" value="1" @checked(!empty($onWon['enabled']))>
                        <span class="form-check-label">Aktifkan automation saat deal pindah ke stage Won</span>
                    </label>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="create_sales_quotation" value="1" @checked(!empty($onWon['create_sales_quotation']))>
                        <span class="form-check-label">Buat draft sales quotation bila module Sales + Accounting aktif</span>
                    </label>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="create_draft_sale" value="1" @checked(!empty($onWon['create_draft_sale']))>
                        <span class="form-check-label">Buat draft sale / invoice basis bila module Sales + Accounting aktif</span>
                    </label>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="finalize_draft_sale" value="1" @checked(!empty($onWon['finalize_draft_sale']))>
                        <span class="form-check-label">Langsung finalize draft sale menjadi invoice bila Accounting aktif</span>
                    </label>
                    <div class="mb-3">
                        <label class="form-label">Default Product untuk automation</label>
                        <select name="default_product_id" class="form-select">
                            <option value="">Pilih produk default</option>
                            @foreach($productOptions as $product)
                                <option value="{{ $product->id }}" @selected((int) ($onWon['default_product_id'] ?? 0) === (int) $product->id)>
                                    {{ $product->name }} @if($product->sell_price) - {{ number_format((float) $product->sell_price, 0, ',', '.') }} @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Fail-closed: tanpa product default, CRM hanya log activity bahwa automation dilewati.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner Routing Rules</label>
                        <textarea name="owner_routing_rules_text" rows="6" class="form-control" placeholder="source:meta_ads|12&#10;campaign:jakarta|8&#10;form:renewal|5">{{ collect((array) ($integrationSettings['owner_routing_rules'] ?? []))->map(fn ($rule) => (($rule['field'] ?? null) ? ($rule['field'] . ':') : '') . ($rule['keyword'] ?? '') . '|' . ($rule['owner_user_id'] ?? ''))->implode("\n") }}</textarea>
                        <div class="form-hint">Format satu baris per rule: <code>keyword|owner_user_id</code> atau lebih spesifik <code>campaign:keyword|owner_user_id</code>.</div>
                        @if($owners->isNotEmpty())
                            <div class="small text-muted mt-2">
                                Owner IDs:
                                @foreach($owners as $owner)
                                    <span class="badge bg-secondary-lt text-secondary me-1 mb-1">{{ $owner->id }} - {{ $owner->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    <label class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="rotate_lead_capture_token" value="1">
                        <span class="form-check-label">Rotate lead capture token saat simpan</span>
                    </label>
                    <button class="btn btn-primary">Simpan Pengaturan Integrasi</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header"><h3 class="card-title mb-0">Recent CRM Webhook Receipts</h3></div>
    <div class="table-responsive">
        <table class="table table-sm table-vcenter mb-0">
            <thead>
                <tr>
                    <th>Endpoint</th>
                    <th>Status</th>
                    <th>Signature</th>
                    <th>Processed</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($recentWebhookReceipts as $receipt)
                    <tr>
                        <td class="small">{{ $receipt->endpoint }}</td>
                        <td><span class="badge bg-secondary-lt text-secondary">{{ $receipt->status }}</span></td>
                        <td class="small">{{ $receipt->signature_valid === null ? 'N/A' : ($receipt->signature_valid ? 'valid' : 'invalid') }}</td>
                        <td class="small">{{ optional($receipt->processed_at)->format('d M Y H:i') ?? '-' }}</td>
                        <td>
                            <form method="POST" action="{{ route('crm.settings.webhook-replay', $receipt->id) }}">
                                @csrf
                                <button class="btn btn-sm btn-outline-secondary">Replay</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada receipt webhook CRM.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
