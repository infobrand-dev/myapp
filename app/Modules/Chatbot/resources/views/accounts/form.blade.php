@extends('layouts.admin')

@section('content')
@php $isEdit = $account->exists; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Chatbot Account</h2>
        <div class="text-muted small">Set credentials OpenAI untuk auto-reply Conversations, WA API, Social DM.</div>
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
                <div class="col-md-3">
                    <label class="form-label">Response Style</label>
                    <select name="response_style" class="form-select">
                        @foreach(['concise' => 'Concise', 'balanced' => 'Balanced', 'detailed' => 'Detailed'] as $val => $label)
                            <option value="{{ $val }}" {{ old('response_style', $account->response_style ?: 'balanced') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label">API Key</label>
                    <input
                        type="password"
                        name="api_key"
                        class="form-control"
                        placeholder="{{ $isEdit ? 'Kosongkan jika tidak diubah' : 'sk-...' }}"
                        autocomplete="off"
                        {{ $isEdit ? '' : 'required' }}
                    >
                    <div class="form-hint">Nilai API key disimpan aman dan tidak ditampilkan kembali.</div>
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
