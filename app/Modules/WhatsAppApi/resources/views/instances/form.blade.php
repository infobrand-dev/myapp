@extends('layouts.admin')

@section('content')
@php
    $isEdit = $instance->exists;
    $provider = old('provider', $instance->provider ?? 'cloud');
    $autoWebhookUrl = route('whatsapp-api.webhook');
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} WA Instance</h2>
        <div class="text-muted small">Pisahkan pengaturan koneksi, receive webhook, dan automation agar lebih mudah dikelola.</div>
    </div>
    <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<form method="POST" action="{{ $isEdit ? route('whatsapp-api.instances.update', $instance) : route('whatsapp-api.instances.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
        <input type="hidden" name="instance_id" value="{{ $instance->id }}">
    @endif

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Identity</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama Instance</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $instance->name) }}" required>
                            @error('name') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Provider</label>
                            <select name="provider" id="provider" class="form-select" required>
                                <option value="cloud" {{ $provider === 'cloud' ? 'selected' : '' }}>Cloud</option>
                                <option value="wwebjs" {{ $provider === 'wwebjs' ? 'selected' : '' }}>wwebjs / Gateway</option>
                                <option value="third_party" {{ $provider === 'third_party' ? 'selected' : '' }}>Third Party</option>
                            </select>
                            @error('provider') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Nomor WA (opsional)</label>
                            <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number', $instance->phone_number) }}" placeholder="628xxxx">
                            <div class="text-muted small">Hanya label internal admin. Bukan field wajib untuk WA Cloud.</div>
                            @error('phone_number') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-3 d-flex align-items-center">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', $instance->is_active ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="is_active">Aktif</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Sending Credentials</h3>
                </div>
                <div class="card-body">
                    <div class="gateway-fields row g-3">
                        <div class="col-md-6">
                            <label class="form-label">API Base URL</label>
                            <input type="url" name="api_base_url" class="form-control" value="{{ old('api_base_url', $instance->api_base_url) }}" placeholder="https://wa-gateway.test">
                            @error('api_base_url') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">API Token</label>
                            <input type="password" name="api_token" class="form-control" placeholder="{{ $isEdit && $instance->api_token ? 'Kosongkan jika tidak diubah' : 'Isi API token' }}" autocomplete="off">
                            <div class="text-muted small">Token tidak ditampilkan ulang untuk keamanan.</div>
                            @error('api_token') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="cloud-fields row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Phone Number ID</label>
                            <input type="text" name="phone_number_id" class="form-control" value="{{ old('phone_number_id', $instance->phone_number_id) }}" placeholder="1xxxxxxxxxx">
                            @error('phone_number_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cloud Business Account ID</label>
                            <input type="text" name="cloud_business_account_id" class="form-control" value="{{ old('cloud_business_account_id', $instance->cloud_business_account_id) }}">
                            @error('cloud_business_account_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Cloud Access Token (Bearer)</label>
                            <input type="password" name="cloud_token" class="form-control" placeholder="{{ $isEdit && $instance->cloud_token ? 'Kosongkan jika tidak diubah' : 'Isi cloud token' }}" autocomplete="off">
                            <div class="text-muted small">Token tidak ditampilkan ulang untuk keamanan.</div>
                            @error('cloud_token') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Receive Message (Webhook)</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Webhook URL</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="webhook_url_display" value="{{ $autoWebhookUrl }}" readonly>
                                <button type="button" class="btn btn-outline-secondary btn-icon" id="copy-webhook-url" title="Copy Webhook URL" aria-label="Copy Webhook URL">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                        </div>
                        <div class="cloud-fields col-md-6">
                            <label class="form-label">Verify Token Webhook</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    name="wa_cloud_verify_token"
                                    id="wa_cloud_verify_token"
                                    class="form-control"
                                    value="{{ old('wa_cloud_verify_token', data_get($instance->settings, 'wa_cloud_verify_token', '')) }}"
                                    placeholder="verify-token-meta"
                                >
                                <button type="button" class="btn btn-outline-secondary btn-icon" id="copy-verify-token" title="Copy Verify Token" aria-label="Copy Verify Token">
                                    <i class="ti ti-copy"></i>
                                </button>
                            </div>
                            <div class="text-muted small">Wajib untuk provider Cloud.</div>
                            @error('wa_cloud_verify_token') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                        <div class="cloud-fields col-md-6">
                            <label class="form-label">App Secret</label>
                            <input type="password" name="wa_cloud_app_secret" class="form-control" placeholder="Kosongkan jika tidak diubah">
                            <div class="text-muted small">Wajib untuk validasi signature X-Hub-Signature-256 webhook Cloud.</div>
                            @error('wa_cloud_app_secret') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">Automation / Sending</h3>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4 d-flex align-items-center">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" name="auto_reply" value="1" id="auto_reply" {{ old('auto_reply', $instance->auto_reply ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_reply">Auto-reply AI</label>
                            </div>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Chatbot Account</label>
                            <select name="chatbot_account_id" id="chatbot_account_id" class="form-select">
                                <option value="">-- Pilih AI --</option>
                                @foreach(($chatbotAccounts ?? []) as $acc)
                                    <option value="{{ $acc->id }}" {{ (string) old('chatbot_account_id', $instance->chatbot_account_id) === (string) $acc->id ? 'selected' : '' }}>{{ $acc->name }} ({{ $acc->model ?? 'default' }})</option>
                                @endforeach
                            </select>
                            <div class="text-muted small">Dipakai hanya jika Auto-reply AI aktif.</div>
                            @error('chatbot_account_id') <div class="text-danger small">{{ $message }}</div> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Advanced</h3>
                </div>
                <div class="card-body">
                    <label class="form-label">Settings JSON (opsional)</label>
                    <textarea name="settings" class="form-control" rows="3" placeholder='{"extra_key":"value"}'>{{ old('settings', $instance->settings ? json_encode($instance->settings) : '') }}</textarea>
                    <div class="text-muted small">Gunakan hanya untuk properti tambahan di luar field utama.</div>
                    @error('settings') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div style="position: sticky; top: 1rem;">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Connection Test</h3>
                    </div>
                    <div class="card-body">
                        @if($isEdit)
                            <button
                                type="submit"
                                class="btn btn-outline-azure w-100"
                                formaction="{{ route('whatsapp-api.instances.save-and-test', $instance) }}"
                                formmethod="POST"
                            >
                                Test Credentials
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-azure w-100" disabled>Simpan dulu untuk Test Credentials</button>
                        @endif
                        @if($isEdit)
                            <button
                                type="submit"
                                class="btn btn-outline-primary w-100 mt-2"
                                formaction="{{ route('whatsapp-api.instances.save-and-sync-templates', $instance) }}"
                                formmethod="POST"
                            >
                                Sync Templates
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-primary w-100 mt-2" disabled>Simpan dulu untuk Sync Templates</button>
                        @endif
                        <div class="text-muted small mt-2">Aksi ini memakai data instance yang sudah tersimpan.</div>
                        <hr>
                        <div class="text-muted small">
                            <div class="fw-semibold mb-1">Wajib untuk Cloud:</div>
                            <div>Phone Number ID, WABA ID, Cloud Token, Verify Token, App Secret.</div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title mb-0">Actions</h3>
                    </div>
                    <div class="card-body d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Simpan Instance</button>
                        <a href="{{ route('whatsapp-api.instances.index') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('form');
    const providerSelect = document.getElementById('provider');
    const cloudFields = document.querySelectorAll('.cloud-fields');
    const gatewayFields = document.querySelectorAll('.gateway-fields');
    const autoReplyInput = document.getElementById('auto_reply');
    const chatbotAccountInput = document.getElementById('chatbot_account_id');
    const verifyTokenInput = document.getElementById('wa_cloud_verify_token');
    const copyVerifyTokenBtn = document.getElementById('copy-verify-token');
    const webhookUrlInput = document.getElementById('webhook_url_display');
    const copyWebhookUrlBtn = document.getElementById('copy-webhook-url');

    const syncProviderSections = () => {
        const provider = (providerSelect?.value || '').toLowerCase();
        const isCloud = provider === 'cloud';

        cloudFields.forEach((el) => {
            el.style.display = isCloud ? '' : 'none';
        });

        gatewayFields.forEach((el) => {
            el.style.display = isCloud ? 'none' : '';
        });

    };

    providerSelect?.addEventListener('change', syncProviderSections);
    syncProviderSections();

    const syncAutomationFields = () => {
        const enabled = !!autoReplyInput?.checked;
        if (!chatbotAccountInput) return;
        chatbotAccountInput.disabled = !enabled;
    };

    autoReplyInput?.addEventListener('change', syncAutomationFields);
    syncAutomationFields();

    const copyText = async (button, value, originalLabel) => {
        if (!button || !value) return;
        const originalTitle = button.getAttribute('title') || originalLabel;
        try {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(value);
            } else {
                const area = document.createElement('textarea');
                area.value = value;
                area.style.position = 'fixed';
                area.style.left = '-9999px';
                document.body.appendChild(area);
                area.focus();
                area.select();
                document.execCommand('copy');
                document.body.removeChild(area);
            }
            button.setAttribute('title', 'Copied');
            button.classList.add('btn-success');
            button.classList.remove('btn-outline-secondary');
            setTimeout(() => {
                button.setAttribute('title', originalTitle);
                button.classList.remove('btn-success');
                button.classList.add('btn-outline-secondary');
            }, 900);
        } catch (e) {
            button.setAttribute('title', 'Copy failed');
        }
    };

    copyVerifyTokenBtn?.addEventListener('click', async () => {
        if (!verifyTokenInput) return;
        const value = verifyTokenInput.value.trim();
        if (!value) return;
        await copyText(copyVerifyTokenBtn, value, 'Copy Verify Token');
    });

    copyWebhookUrlBtn?.addEventListener('click', async () => {
        const value = webhookUrlInput?.value?.trim() || '';
        if (!value) return;
        await copyText(copyWebhookUrlBtn, value, 'Copy Webhook URL');
    });

});
</script>
@endpush
@endsection
