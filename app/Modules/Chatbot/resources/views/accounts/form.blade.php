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
                <div class="col-12">
                    <label class="form-label">API Key</label>
                    <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $account->api_key) }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active','inactive'] as $st)
                            <option value="{{ $st }}" {{ old('status', $account->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
