@extends('layouts.admin')

@section('content')
@php
    $isEdit = $account->exists;
    $automationMode = old('automation_mode', $account->automation_mode ?: 'ai_first');
    $botConfig = method_exists($account, 'botConfig') ? $account->botConfig() : [
        'auto_reply_enabled' => true,
        'allowed_channels' => ['wa_api', 'social_dm'],
        'allow_interactive_buttons' => true,
        'human_handoff_ack_enabled' => true,
        'minimum_context_score' => 4,
        'reply_cooldown_seconds' => 30,
    ];
    $providers = [
        'openai'    => 'OpenAI (ChatGPT)',
        'anthropic' => 'Anthropic (Claude)',
        'groq'      => 'Groq (LLaMA / Mixtral)',
    ];
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Chatbot</h2>
        <div class="text-muted small">Utamakan chatbot ini untuk auto-reply live channel, lalu teruskan ke tim saat bot tidak yakin.</div>
    </div>
    <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" id="chatbot-account-form" action="{{ $isEdit ? route('chatbot.accounts.update', $account) : route('chatbot.accounts.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="row g-3">

                {{-- Nama --}}
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                </div>

                {{-- Automation Mode --}}
                <div class="col-md-6">
                    <label class="form-label">Mode Otomasi</label>
                    <select name="automation_mode" id="automation_mode" class="form-select">
                        <option value="rule_only"   {{ $automationMode === 'rule_only'   ? 'selected' : '' }}>Berbasis Aturan saja (tanpa AI)</option>
                        <option value="ai_assisted" {{ $automationMode === 'ai_assisted' ? 'selected' : '' }}>AI Pendukung — AI membantu, aturan yang memutuskan</option>
                        <option value="ai_first"    {{ $automationMode === 'ai_first'    ? 'selected' : '' }}>AI Utama — AI menjawab langsung, aturan sebagai fallback</option>
                    </select>
                    <div class="form-hint">Mode Berbasis Aturan tidak menggunakan AI Credits. Mode AI Pendukung dan AI Utama menggunakan AI Credits saat menjawab.</div>
                </div>

                {{-- AI Settings Section (hidden when rule_only) --}}
                <div id="ai-settings-section" class="col-12 {{ $automationMode === 'rule_only' ? 'd-none' : '' }}">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label class="form-label">Provider AI</label>
                            <select name="provider" id="provider_select" class="form-select">
                                @foreach($providers as $val => $label)
                                    <option value="{{ $val }}" {{ old('provider', $account->provider ?? 'openai') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="model_input" class="form-control" placeholder="gpt-4o-mini" value="{{ old('model', $account->model) }}">
                            <div class="form-hint">Contoh: gpt-4o-mini, claude-haiku-4-5-20251001, llama3-8b-8192</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label">API Key</label>
                            <div class="input-group">
                                <input
                                    type="password"
                                    name="api_key"
                                    id="api_key_input"
                                    class="form-control"
                                    placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengubah' : 'sk-...' }}"
                                    autocomplete="off"
                                >
                                <button type="button" class="btn btn-outline-secondary" id="btn-verify-key">Verifikasi</button>
                            </div>
                            <div id="api-key-verify-result" class="mt-1"></div>
                            <div class="form-hint">Wajib diisi untuk mode AI. Klik Verifikasi untuk memastikan key berfungsi sebelum menyimpan.</div>
                        </div>
                    </div>
                </div>

                {{-- Response Style & Operation Mode --}}
                <div class="col-md-4">
                    <label class="form-label">Gaya Respons</label>
                    <select name="response_style" class="form-select">
                        @foreach(['concise' => 'Singkat', 'balanced' => 'Seimbang', 'detailed' => 'Terperinci'] as $val => $label)
                            <option value="{{ $val }}" {{ old('response_style', $account->response_style ?: 'balanced') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Setelah Bot Menjawab</label>
                    <select name="operation_mode" class="form-select">
                        <option value="ai_only"       {{ old('operation_mode', $account->operation_mode ?: 'ai_only') === 'ai_only'       ? 'selected' : '' }}>Bot terus menjawab otomatis</option>
                        <option value="ai_then_human" {{ old('operation_mode', $account->operation_mode ?: 'ai_only') === 'ai_then_human' ? 'selected' : '' }}>Serahkan ke tim saat pelanggan minta agen</option>
                    </select>
                    <div class="form-hint">Pilih "Serahkan ke tim" agar bot berhenti saat pelanggan meminta berbicara dengan manusia.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        <option value="active"   {{ old('status', $account->status) === 'active'   ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Live Auto-reply Settings --}}
                <div class="col-12">
                    <div class="fw-semibold">Pengaturan Auto-Reply</div>
                    <div class="text-muted small">Atur kapan bot boleh menjawab, kapan harus diam, dan kapan langsung menyerahkan ke tim.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Auto-reply</label>
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="auto_reply_enabled" value="1" {{ old('auto_reply_enabled', ($botConfig['auto_reply_enabled'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Aktif untuk channel live</span>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Channel aktif</label>
                    @foreach(['wa_api' => 'WhatsApp API', 'wa_web' => 'WhatsApp Web', 'social_dm' => 'Social Inbox'] as $channel => $label)
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="allowed_channels[]" value="{{ $channel }}" {{ in_array($channel, old('allowed_channels', $botConfig['allowed_channels'] ?? []), true) ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Perilaku saat perlu tim</label>
                    <label class="form-check form-switch m-0 mb-2">
                        <input class="form-check-input" type="checkbox" name="human_handoff_ack_enabled" value="1" {{ old('human_handoff_ack_enabled', ($botConfig['human_handoff_ack_enabled'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Kirim pesan "tim kami akan membantu"</span>
                    </label>
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="allow_interactive_buttons" value="1" {{ old('allow_interactive_buttons', ($botConfig['allow_interactive_buttons'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Izinkan tombol cepat WhatsApp</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kepercayaan minimum jawaban</label>
                    <input type="number" min="1" max="30" step="0.5" name="minimum_context_score" class="form-control" value="{{ old('minimum_context_score', $botConfig['minimum_context_score'] ?? 4) }}">
                    <div class="form-hint">Jika skor di bawah ini, bot menyerahkan ke tim alih-alih menjawab sendiri.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jeda antar auto-reply</label>
                    <div class="input-group">
                        <input type="number" min="0" max="300" name="reply_cooldown_seconds" class="form-control" value="{{ old('reply_cooldown_seconds', $botConfig['reply_cooldown_seconds'] ?? 30) }}">
                        <span class="input-group-text">detik</span>
                    </div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- System Prompt & Focus Scope --}}
                <div class="col-12">
                    <label class="form-label">Instruksi Utama Bot</label>
                    <textarea name="system_prompt" rows="4" class="form-control" placeholder="Contoh: Kamu adalah asisten CS ramah untuk toko X. Jawab hanya soal produk, pengiriman, dan pembayaran.">{{ old('system_prompt', $account->system_prompt) }}</textarea>
                    <div class="form-hint">Instruksi ini selalu dibaca bot setiap kali menjawab.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Batasan Topik</label>
                    <textarea name="focus_scope" rows="2" class="form-control" placeholder="Contoh: hanya jawab soal produk A, harga, onboarding.">{{ old('focus_scope', $account->focus_scope) }}</textarea>
                    <div class="form-hint">Kosongkan jika bot boleh menjawab semua topik.</div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Pencarian Dokumen AI (RAG) --}}
                <div class="col-12">
                    <div class="fw-semibold">Pencarian Dokumen AI</div>
                    <div class="text-muted small mb-2">Saat diaktifkan, bot akan mencari referensi dari dokumen yang Anda upload sebelum menjawab.</div>
                    <label class="form-check form-switch m-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            name="rag_enabled"
                            value="1"
                            {{ old('rag_enabled', $account->rag_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        >
                        <span class="form-check-label">Aktifkan pencarian dokumen saat menjawab</span>
                    </label>
                </div>

                {{-- Advanced Settings (collapsible) --}}
                <div class="col-12">
                    <details class="border rounded p-3">
                        <summary class="fw-semibold" style="cursor:pointer;">
                            Pengaturan Lanjutan
                            <span class="text-muted small ms-2 fw-normal">jumlah referensi, rekam ke inbox</span>
                        </summary>
                        <div class="row g-3 mt-2">
                            <div class="col-md-4">
                                <label class="form-label">Jumlah Referensi Diambil</label>
                                <input type="number" min="1" max="8" name="rag_top_k" class="form-control" value="{{ old('rag_top_k', $account->rag_top_k ?: 3) }}">
                                <div class="form-hint">Berapa potongan dokumen yang disertakan sebagai konteks jawaban. Nilai 3–5 umumnya sudah cukup.</div>
                            </div>
                            <div class="col-md-8 d-flex flex-column justify-content-center">
                                <label class="form-label d-block">Rekam percakapan Playground ke Inbox</label>
                                <label class="form-check form-switch m-0">
                                    <input
                                        class="form-check-input"
                                        type="checkbox"
                                        role="switch"
                                        name="mirror_to_conversations"
                                        value="1"
                                        {{ old('mirror_to_conversations', $account->mirror_to_conversations ? '1' : '0') === '1' ? 'checked' : '' }}
                                    >
                                    <span class="form-check-label">Saat aktif, percakapan dari Playground juga tercatat di module Conversations.</span>
                                </label>
                            </div>
                        </div>
                    </details>
                </div>

            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
(function () {
    var automationSelect = document.getElementById('automation_mode');
    var aiSection = document.getElementById('ai-settings-section');

    function toggleAiSection() {
        if (automationSelect.value === 'rule_only') {
            aiSection.classList.add('d-none');
        } else {
            aiSection.classList.remove('d-none');
        }
    }

    if (automationSelect) {
        automationSelect.addEventListener('change', toggleAiSection);
    }

    var btnVerify = document.getElementById('btn-verify-key');
    var resultDiv = document.getElementById('api-key-verify-result');

    if (btnVerify) {
        btnVerify.addEventListener('click', function () {
            var apiKey = document.getElementById('api_key_input').value.trim();
            var provider = document.getElementById('provider_select').value;

            if (!apiKey) {
                resultDiv.innerHTML = '<span class="badge text-bg-warning">Masukkan API key terlebih dahulu.</span>';
                return;
            }

            btnVerify.disabled = true;
            btnVerify.textContent = 'Memeriksa...';
            resultDiv.innerHTML = '';

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            fetch('{{ route('chatbot.accounts.test-api-key') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
                body: JSON.stringify({ api_key: apiKey, provider: provider }),
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.ok) {
                    resultDiv.innerHTML = '<span class="badge text-bg-success">\u2713 ' + data.message + '</span>';
                } else {
                    resultDiv.innerHTML = '<span class="badge text-bg-danger">\u2717 ' + data.message + '</span>';
                }
            })
            .catch(function() {
                resultDiv.innerHTML = '<span class="badge text-bg-danger">Gagal menghubungi server.</span>';
            })
            .finally(function() {
                btnVerify.disabled = false;
                btnVerify.textContent = 'Verifikasi';
            });
        });
    }
})();
</script>
@endpush
@endsection
