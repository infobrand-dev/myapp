@extends('layouts.admin')

@section('content')
@php $isEdit = $account->exists; @endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit' : 'Tambah' }} Social Account</h2>
        <div class="text-muted small">Isi token page/IG untuk DM.</div>
    </div>
    <a href="{{ route('social-media.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('social-media.accounts.update', $account) : route('social-media.accounts.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Platform</label>
                    <select name="platform" class="form-select">
                        @foreach(['facebook','instagram'] as $p)
                            <option value="{{ $p }}" {{ old('platform', $account->platform) === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Page ID (FB)</label>
                    <input type="text" name="page_id" class="form-control" value="{{ old('page_id', $account->page_id) }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">IG Business ID</label>
                    <input type="text" name="ig_business_id" class="form-control" value="{{ old('ig_business_id', $account->ig_business_id) }}">
                </div>
                <div class="col-12">
                    <label class="form-label">Access Token</label>
                    <input type="text" name="access_token" class="form-control" value="{{ old('access_token', $account->access_token) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $account->name) }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-select">
                        @foreach(['active','inactive'] as $st)
                            <option value="{{ $st }}" {{ old('status', $account->status) === $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-center">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" name="auto_reply" value="1" id="auto_reply" {{ old('auto_reply', $account->auto_reply ?? false) ? 'checked' : '' }}>
                        <label class="form-check-label" for="auto_reply">Auto-reply AI</label>
                    </div>
                </div>
                <div class="col-md-9">
                    <label class="form-label">Chatbot Account</label>
                    <select name="chatbot_account_id" class="form-select">
                        <option value="">-- Pilih AI --</option>
                        @foreach(($chatbotAccounts ?? []) as $ai)
                            <option value="{{ $ai->id }}" {{ (string) old('chatbot_account_id', $account->chatbot_account_id) === (string) $ai->id ? 'selected' : '' }}>{{ $ai->name }} ({{ $ai->model ?? 'default' }})</option>
                        @endforeach
                    </select>
                    <div class="text-muted small">Aktifkan auto-reply hanya bila token aman.</div>
                </div>
            </div>
            <div class="mt-4 d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
