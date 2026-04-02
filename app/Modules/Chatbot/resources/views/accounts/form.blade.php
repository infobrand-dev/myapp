@extends('layouts.admin')

@section('title', ($account->exists ? 'Edit' : 'Tambah') . ' Chatbot')

@section('content')
@php
    $isEdit = $account->exists;
    $behaviorMode = old('behavior_mode', method_exists($account, 'behaviorMode') ? $account->behaviorMode() : (($account->automation_mode ?? 'ai_first') === 'rule_only' ? 'rule_only' : (($account->operation_mode ?? 'ai_only') === 'ai_then_human' ? 'ai_then_human' : 'ai_only')));
    $accessScope = old('access_scope', method_exists($account, 'accessScope') ? $account->accessScope() : ($account->access_scope ?: 'public'));
    $aiSource = old('ai_source', method_exists($account, 'aiSource') ? $account->aiSource() : ($account->ai_source ?: 'managed'));
    $botConfig = method_exists($account, 'botConfig') ? $account->botConfig() : [
        'auto_reply_enabled' => true,
        'allowed_channels' => ['wa_api', 'wa_web', 'social_dm'],
        'allow_interactive_buttons' => true,
        'human_handoff_ack_enabled' => true,
        'minimum_context_score' => 4,
        'reply_cooldown_seconds' => 30,
        'max_bot_replies_per_conversation' => 0,
    ];
    $providers = [
        'openai' => 'OpenAI (ChatGPT)',
        'anthropic' => 'Anthropic (Claude)',
        'groq' => 'Groq (LLaMA / Mixtral)',
    ];
@endphp

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Chatbot</div>
            <h2 class="page-title">{{ $isEdit ? 'Edit' : 'Tambah' }} Chatbot</h2>
            <p class="text-muted mb-0">Utamakan chatbot ini untuk auto-reply live channel, lalu teruskan ke tim saat bot tidak yakin.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-arrow-left me-1"></i>Kembali
            </a>
        </div>
    </div>
</div>

<form method="POST" id="chatbot-account-form"
      action="{{ $isEdit ? route('chatbot.accounts.update', $account) : route('chatbot.accounts.store') }}">
    @csrf
    @if($isEdit)
        @method('PUT')
    @endif

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Konfigurasi Chatbot</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">

                {{-- Nama & Behavior --}}
                <div class="col-md-6">
                    <label class="form-label">Nama <span class="text-danger">*</span></label>
                    <input type="text" name="name"
                           class="form-control @error('name') is-invalid @enderror"
                           value="{{ old('name', $account->name) }}" required>
                    @error('name')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Label <span class="text-danger">*</span></label>
                    <select name="access_scope" class="form-select @error('access_scope') is-invalid @enderror">
                        <option value="public" {{ $accessScope === 'public' ? 'selected' : '' }}>Public</option>
                        <option value="private" {{ $accessScope === 'private' ? 'selected' : '' }}>Private</option>
                    </select>
                    @error('access_scope')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <label class="form-label">Behavior Mode <span class="text-danger">*</span></label>
                    <select name="behavior_mode" id="behavior_mode"
                            class="form-select @error('behavior_mode') is-invalid @enderror">
                        <option value="rule_only" {{ $behaviorMode === 'rule_only' ? 'selected' : '' }}>Rule Only</option>
                        <option value="ai_only" {{ $behaviorMode === 'ai_only' ? 'selected' : '' }}>AI Only</option>
                        <option value="ai_then_human" {{ $behaviorMode === 'ai_then_human' ? 'selected' : '' }}>AI Then Human</option>
                    </select>
                    @error('behavior_mode')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">
                        "Rule Only" tidak memakai AI. "AI Only" membiarkan bot terus menjawab otomatis.
                        "AI Then Human" mengizinkan bot menjawab lebih dulu lalu handoff ke tim saat kondisi terpenuhi.
                    </div>
                </div>

                {{-- AI Settings (disembunyikan jika Rule Only) --}}
                <div id="ai-settings-section" class="col-12 {{ $behaviorMode === 'rule_only' ? 'd-none' : '' }}">
                    <div class="row g-3">
                        @if($byoEnabled)
                            <div class="col-md-5">
                                <label class="form-label">AI Source</label>
                                <select name="ai_source" id="ai_source" class="form-select">
                                    <option value="managed" {{ $aiSource === 'managed' ? 'selected' : '' }}>Managed AI (pakai AI Credits)</option>
                                    <option value="byo" {{ $aiSource === 'byo' ? 'selected' : '' }}>BYO AI (pakai tagihan provider Anda)</option>
                                </select>
                                <div class="form-hint">Setiap chatbot memilih salah satu sumber AI. Managed AI memakai AI Credits. BYO AI memakai API key provider Anda dan mengikuti limit add-on BYO.</div>
                            </div>
                        @else
                            <input type="hidden" name="ai_source" value="managed">
                        @endif

                        <div class="col-md-{{ $byoEnabled ? '4' : '6' }}">
                            <label class="form-label">Model</label>
                            <input type="text" name="model" id="model_input"
                                   class="form-control @error('model') is-invalid @enderror"
                                   placeholder="gpt-4o-mini"
                                   value="{{ old('model', $account->model) }}">
                            @error('model')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-hint">
                                @if($byoEnabled)
                                    Managed AI default ke model platform. BYO AI dapat memakai model provider Anda.
                                @else
                                    Kosongkan untuk menggunakan model default platform.
                                @endif
                            </div>
                        </div>

                        @if($byoEnabled)
                            <div class="col-md-3" id="provider-field">
                                <label class="form-label">Provider AI</label>
                                <select name="provider" id="provider_select" class="form-select">
                                    @foreach($providers as $val => $label)
                                        <option value="{{ $val }}" {{ old('provider', $account->provider ?? 'openai') === $val ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12" id="managed-ai-notice">
                            <div class="alert alert-azure mb-0">
                                <div class="fw-semibold">Managed AI</div>
                                <div class="small mt-1">Bot memakai kredensial AI platform dan konsumsi dibebankan ke AI Credits tenant. Jika AI Credits habis, auto-reply AI akan berhenti sampai top up atau upgrade plan.</div>
                            </div>
                        </div>

                        @if($byoEnabled)
                            <div class="col-12 d-none" id="byo-api-key-section">
                                <label class="form-label">API Key</label>
                                <div class="input-group">
                                    <input type="password" name="api_key" id="api_key_input"
                                           class="form-control @error('api_key') is-invalid @enderror"
                                           placeholder="{{ $isEdit ? 'Kosongkan jika tidak ingin mengubah' : 'sk-...' }}"
                                           autocomplete="off">
                                    <button type="button" class="btn btn-outline-secondary" id="btn-verify-key">Verifikasi</button>
                                </div>
                                @error('api_key')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <div id="api-key-verify-result" class="mt-1"></div>
                                <div class="form-hint">Hanya dipakai untuk BYO AI. Token provider ditagihkan langsung oleh provider, bukan lewat AI Credits.</div>
                            </div>

                            <div class="col-12 d-none" id="byo-limit-notice">
                                <div class="alert alert-warning mb-0">
                                    <div class="fw-semibold">Batas Add-on BYO AI</div>
                                    <div class="small mt-1">
                                        Chatbot BYO: {{ $byoUsageStates['accounts']['usage'] ?? 0 }} / {{ $byoUsageStates['accounts']['limit'] ?? 'Unlimited' }}
                                        · Request / bulan: {{ $byoUsageStates['requests']['usage'] ?? 0 }} / {{ $byoUsageStates['requests']['limit'] ?? 'Unlimited' }}
                                        · Token / bulan: {{ number_format((int) ($byoUsageStates['tokens']['usage'] ?? 0)) }} / {{ $byoUsageStates['tokens']['limit'] !== null ? number_format((int) $byoUsageStates['tokens']['limit']) : 'Unlimited' }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Gaya, Status, Batas --}}
                <div class="col-md-4">
                    <label class="form-label">Gaya Respons</label>
                    <select name="response_style" class="form-select @error('response_style') is-invalid @enderror">
                        @foreach(['concise' => 'Singkat', 'balanced' => 'Seimbang', 'detailed' => 'Terperinci'] as $val => $label)
                            <option value="{{ $val }}" {{ old('response_style', $account->response_style ?: 'balanced') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('response_style')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select @error('status') is-invalid @enderror">
                        <option value="active" {{ old('status', $account->status) === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="inactive" {{ old('status', $account->status) === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                    @error('status')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batas Balasan Bot / Percakapan</label>
                    <div class="input-group">
                        <input type="number" min="0" max="100" name="max_bot_replies_per_conversation"
                               class="form-control @error('max_bot_replies_per_conversation') is-invalid @enderror"
                               value="{{ old('max_bot_replies_per_conversation', $botConfig['max_bot_replies_per_conversation'] ?? 0) }}">
                        <span class="input-group-text">kali</span>
                    </div>
                    @error('max_bot_replies_per_conversation')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Isi 0 untuk tanpa batas. Cocok pada mode "AI Then Human", misal isi 10 agar setelah 10 balasan percakapan diserahkan ke tim.</div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Auto-Reply Settings --}}
                <div class="col-12">
                    <div class="fw-semibold">Pengaturan Auto-Reply</div>
                    <div class="text-muted small">Atur kapan bot boleh menjawab, kapan harus diam, dan kapan langsung menyerahkan ke tim.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Auto-reply</label>
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="auto_reply_enabled" value="1"
                               {{ old('auto_reply_enabled', ($botConfig['auto_reply_enabled'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Aktif untuk channel live</span>
                    </label>
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Channel aktif</label>
                    @foreach(['wa_api' => 'WhatsApp API', 'wa_web' => 'WhatsApp Web', 'social_dm' => 'Social Inbox'] as $channel => $label)
                        <label class="form-check">
                            <input class="form-check-input" type="checkbox" name="allowed_channels[]" value="{{ $channel }}"
                                   {{ in_array($channel, old('allowed_channels', $botConfig['allowed_channels'] ?? []), true) ? 'checked' : '' }}>
                            <span class="form-check-label">{{ $label }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="col-md-4">
                    <label class="form-label d-block">Perilaku saat perlu tim</label>
                    <label class="form-check form-switch m-0 mb-2">
                        <input class="form-check-input" type="checkbox" name="human_handoff_ack_enabled" value="1"
                               {{ old('human_handoff_ack_enabled', ($botConfig['human_handoff_ack_enabled'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Kirim pesan "tim kami akan membantu"</span>
                    </label>
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" name="allow_interactive_buttons" value="1"
                               {{ old('allow_interactive_buttons', ($botConfig['allow_interactive_buttons'] ?? true) ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Izinkan tombol cepat WhatsApp</span>
                    </label>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Kepercayaan minimum jawaban</label>
                    <input type="number" min="1" max="30" step="0.5" name="minimum_context_score"
                           class="form-control @error('minimum_context_score') is-invalid @enderror"
                           value="{{ old('minimum_context_score', $botConfig['minimum_context_score'] ?? 4) }}">
                    @error('minimum_context_score')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Jika skor di bawah ini, bot menyerahkan ke tim alih-alih menjawab sendiri.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jeda antar auto-reply</label>
                    <div class="input-group">
                        <input type="number" min="0" max="300" name="reply_cooldown_seconds"
                               class="form-control @error('reply_cooldown_seconds') is-invalid @enderror"
                               value="{{ old('reply_cooldown_seconds', $botConfig['reply_cooldown_seconds'] ?? 30) }}">
                        <span class="input-group-text">detik</span>
                    </div>
                    @error('reply_cooldown_seconds')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Instruksi & Scope --}}
                <div class="col-12">
                    <label class="form-label">Instruksi Utama Bot</label>
                    <textarea name="system_prompt" rows="4"
                              class="form-control @error('system_prompt') is-invalid @enderror"
                              placeholder="Contoh: Kamu adalah asisten CS ramah untuk toko X. Jawab hanya soal produk, pengiriman, dan pembayaran.">{{ old('system_prompt', $account->system_prompt) }}</textarea>
                    @error('system_prompt')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Instruksi ini selalu dibaca bot setiap kali menjawab.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">Batasan Topik</label>
                    <textarea name="focus_scope" rows="2"
                              class="form-control @error('focus_scope') is-invalid @enderror"
                              placeholder="Contoh: hanya jawab soal produk A, harga, onboarding.">{{ old('focus_scope', $account->focus_scope) }}</textarea>
                    @error('focus_scope')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-hint">Kosongkan jika bot boleh menjawab semua topik.</div>
                </div>

                <div class="col-12"><hr class="my-1"></div>

                {{-- Pencarian Dokumen AI --}}
                <div class="col-12">
                    <div class="fw-semibold">Pencarian Dokumen AI</div>
                    <div class="text-muted small mb-2">Saat diaktifkan, bot akan mencari referensi dari dokumen yang Anda upload sebelum menjawab.</div>
                    <label class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="rag_enabled" value="1"
                               {{ old('rag_enabled', $account->rag_enabled ? '1' : '0') === '1' ? 'checked' : '' }}>
                        <span class="form-check-label">Aktifkan pencarian dokumen saat menjawab</span>
                    </label>
                </div>

                {{-- Pengaturan Lanjutan (collapsible) --}}
                <div class="col-12">
                    <a class="d-flex align-items-center gap-1 fw-semibold text-body text-decoration-none"
                       data-bs-toggle="collapse" href="#advanced-settings" role="button" aria-expanded="false">
                        <i class="ti ti-chevron-right" style="transition:transform .2s;" id="advanced-chevron"></i>
                        Pengaturan Lanjutan
                        <span class="text-muted small ms-1 fw-normal">jumlah referensi, rekam ke inbox</span>
                    </a>
                    <div class="collapse mt-3" id="advanced-settings">
                        <div class="border rounded p-3">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">Jumlah Referensi Diambil</label>
                                    <input type="number" min="1" max="8" name="rag_top_k"
                                           class="form-control @error('rag_top_k') is-invalid @enderror"
                                           value="{{ old('rag_top_k', $account->rag_top_k ?: 3) }}">
                                    @error('rag_top_k')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-hint">Berapa potongan dokumen yang disertakan sebagai konteks jawaban. Nilai 3–5 umumnya sudah cukup.</div>
                                </div>
                                <div class="col-md-8 d-flex flex-column justify-content-center">
                                    <label class="form-label d-block">Rekam percakapan Playground ke Inbox</label>
                                    <label class="form-check form-switch m-0">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               name="mirror_to_conversations" value="1"
                                               {{ old('mirror_to_conversations', $account->mirror_to_conversations ? '1' : '0') === '1' ? 'checked' : '' }}>
                                        <span class="form-check-label">Saat aktif, percakapan dari Playground juga tercatat di modul Conversations.</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>
</form>

@push('scripts')
<script>
(function () {
    var behaviorSelect = document.getElementById('behavior_mode');
    var aiSection = document.getElementById('ai-settings-section');
    var aiSourceSelect = document.getElementById('ai_source');
    var providerField = document.getElementById('provider-field');
    var managedNotice = document.getElementById('managed-ai-notice');
    var byoApiKeySection = document.getElementById('byo-api-key-section');
    var byoLimitNotice = document.getElementById('byo-limit-notice');

    function toggleAiSection() {
        var hidden = behaviorSelect && behaviorSelect.value === 'rule_only';
        if (aiSection) aiSection.classList.toggle('d-none', hidden);
        toggleAiSource();
    }

    function toggleAiSource() {
        var hidden = behaviorSelect && behaviorSelect.value === 'rule_only';
        var isByo = aiSourceSelect && aiSourceSelect.value === 'byo' && !hidden;
        if (providerField) providerField.classList.toggle('d-none', !isByo);
        if (managedNotice) managedNotice.classList.toggle('d-none', isByo || hidden);
        if (byoApiKeySection) byoApiKeySection.classList.toggle('d-none', !isByo);
        if (byoLimitNotice) byoLimitNotice.classList.toggle('d-none', !isByo);
    }

    if (behaviorSelect) behaviorSelect.addEventListener('change', toggleAiSection);
    if (aiSourceSelect) aiSourceSelect.addEventListener('change', toggleAiSource);
    toggleAiSection();

    // Rotate chevron on collapse toggle
    var collapseEl = document.getElementById('advanced-settings');
    var chevron = document.getElementById('advanced-chevron');
    if (collapseEl && chevron) {
        collapseEl.addEventListener('show.bs.collapse', function () {
            chevron.style.transform = 'rotate(90deg)';
        });
        collapseEl.addEventListener('hide.bs.collapse', function () {
            chevron.style.transform = 'rotate(0deg)';
        });
    }

    // API Key verification
    var btnVerify = document.getElementById('btn-verify-key');
    var resultDiv = document.getElementById('api-key-verify-result');

    if (btnVerify) {
        btnVerify.addEventListener('click', function () {
            var apiKey = document.getElementById('api_key_input').value.trim();
            var provider = document.getElementById('provider_select').value;

            if (!apiKey) {
                resultDiv.innerHTML = '<span class="badge bg-orange-lt text-orange">Masukkan API key terlebih dahulu.</span>';
                return;
            }

            btnVerify.disabled = true;
            btnVerify.textContent = 'Memeriksa...';
            resultDiv.innerHTML = '';

            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';

            fetch('{{ route('chatbot.accounts.test-api-key') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ api_key: apiKey, provider: provider }),
            })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.ok) {
                    resultDiv.innerHTML = '<span class="badge bg-green-lt text-green"><i class="ti ti-check me-1"></i>' + data.message + '</span>';
                } else {
                    resultDiv.innerHTML = '<span class="badge bg-red-lt text-red"><i class="ti ti-x me-1"></i>' + data.message + '</span>';
                }
            })
            .catch(function () {
                resultDiv.innerHTML = '<span class="badge bg-red-lt text-red">Gagal menghubungi server.</span>';
            })
            .finally(function () {
                btnVerify.disabled = false;
                btnVerify.textContent = 'Verifikasi';
            });
        });
    }
})();
</script>
@endpush

@endsection
