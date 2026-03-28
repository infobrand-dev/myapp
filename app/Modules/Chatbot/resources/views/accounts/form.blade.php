@extends('layouts.admin')

@section('content')
@php
    $isEdit = $account->exists;
    $automationMode = old('automation_mode', $account->automation_mode ?: 'ai_first');
    $requiresAiKey = $automationMode !== 'rule_only';
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Chatbot Account</h2>
        <div class="text-muted small">Pisahkan mode automation bot dari cara AI dipakai. Rule-only tidak mengonsumsi AI Credits.</div>
    </div>
    <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('chatbot.accounts.update', $account) : route('chatbot.accounts.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Provider</label>
                    <select name="provider" class="form-select">
                        <option value="openai" {{ old('provider', $account->provider) === 'openai' ? 'selected' : '' }}>OpenAI</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Model</label>
                    <input type="text" name="model" class="form-control" placeholder="gpt-4o-mini" value="{{ old('model', $account->model) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Automation Mode</label>
                    <select name="automation_mode" class="form-select">
                        <option value="rule_only" {{ $automationMode === 'rule_only' ? 'selected' : '' }}>Rule-only</option>
                        <option value="ai_assisted" {{ $automationMode === 'ai_assisted' ? 'selected' : '' }}>AI-assisted</option>
                        <option value="ai_first" {{ $automationMode === 'ai_first' ? 'selected' : '' }}>AI-first</option>
                    </select>
                    <div class="form-hint">Rule-only disiapkan untuk automations/rules dan tidak memakai AI Credits. AI-assisted dan AI-first memakai AI Credits saat model dipanggil.</div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Response Style</label>
                    <select name="response_style" class="form-select">
                        @foreach(['concise' => 'Concise', 'balanced' => 'Balanced', 'detailed' => 'Detailed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('response_style', $account->response_style ?: 'balanced') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Operation Mode</label>
                    <select name="operation_mode" class="form-select">
                        <option value="ai_only" {{ old('operation_mode', $account->operation_mode ?: 'ai_only') === 'ai_only' ? 'selected' : '' }}>AI Only</option>
                        <option value="ai_then_human" {{ old('operation_mode', $account->operation_mode ?: 'ai_only') === 'ai_then_human' ? 'selected' : '' }}>AI then Human</option>
                    </select>
                    <div class="form-hint">Mode ini hanya berlaku saat automation mode memakai AI. AI then Human akan pause bot saat user minta agent/manusia.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">API Key</label>
                    <input
                        type="password"
                        name="api_key"
                        class="form-control"
                        placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : 'sk-...' }}"
                        autocomplete="off"
                        {{ $requiresAiKey && !$isEdit ? 'required' : '' }}
                    >
                    <div class="form-hint">Wajib untuk AI-assisted dan AI-first. Rule-only boleh kosong karena tidak memanggil model AI.</div>
                </div>
                <div class="col-12">
                    <label class="form-label">System Prompt</label>
                    <textarea name="system_prompt" rows="4" class="form-control" placeholder="Instruksi inti untuk chatbot ini...">{{ old('system_prompt', $account->system_prompt) }}</textarea>
                </div>
                <div class="col-12">
                    <label class="form-label">Focus Scope</label>
                    <textarea name="focus_scope" rows="3" class="form-control" placeholder="Contoh: hanya jawab soal produk A, harga, onboarding.">{{ old('focus_scope', $account->focus_scope) }}</textarea>
                    <div class="form-hint">Gunakan untuk membatasi domain jawaban chatbot ini.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active','inactive'] as $st)
                            <option value="{{ $st }}" {{ old('status', $account->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label d-block">Integrasi</label>
                    <label class="form-check form-switch m-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            name="mirror_to_conversations"
                            value="1"
                            {{ old('mirror_to_conversations', $account->mirror_to_conversations ? '1' : '0') === '1' ? 'checked' : '' }}
                        >
                        <span class="form-check-label">Mirror chat Playground ke Conversations</span>
                    </label>
                    <div class="form-hint">Saat aktif, pesan masuk/keluar dari Playground dicatat juga ke module Conversations lewat queue.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label d-block">RAG</label>
                    <label class="form-check form-switch m-0">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            role="switch"
                            name="rag_enabled"
                            value="1"
                            {{ old('rag_enabled', $account->rag_enabled ? '1' : '0') === '1' ? 'checked' : '' }}
                        >
                        <span class="form-check-label">Aktifkan RAG</span>
                    </label>
                    <div class="form-hint">Gunakan knowledge base account ini sebagai konteks jawaban.</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">RAG Top K</label>
                    <input type="number" min="1" max="8" name="rag_top_k" class="form-control" value="{{ old('rag_top_k', $account->rag_top_k ?: 3) }}">
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
