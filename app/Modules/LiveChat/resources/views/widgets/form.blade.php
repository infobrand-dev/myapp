@extends('layouts.admin')

@section('content')
@php
    $isEdit = (bool) $widget->exists;
@endphp
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">{{ $isEdit ? 'Edit Live Chat Widget' : 'Tambah Live Chat Widget' }}</h2>
        <div class="text-muted small">Widget live chat untuk website.</div>
    </div>
    <a href="{{ route('live-chat.widgets.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ $isEdit ? route('live-chat.widgets.update', $widget) : route('live-chat.widgets.store') }}">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label">Nama Widget</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $widget->name) }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Nama Website</label>
                    <input type="text" name="website_name" class="form-control" value="{{ old('website_name', $widget->website_name) }}">
                </div>
                <div class="col-md-8">
                    <label class="form-label">Welcome Text</label>
                    <textarea name="welcome_text" class="form-control" rows="3">{{ old('welcome_text', $widget->welcome_text) }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Theme Color</label>
                    <input type="text" name="theme_color" class="form-control" value="{{ old('theme_color', $widget->theme_color ?: '#206bc4') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Launcher Label</label>
                    <input type="text" name="launcher_label" class="form-control" value="{{ old('launcher_label', $widget->launcher_label) }}" placeholder="Default: Chat">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Widget Position</label>
                    <select name="position" class="form-select">
                        <option value="">Default: Right</option>
                        <option value="right" @selected(old('position', $widget->position) === 'right')>Right</option>
                        <option value="left" @selected(old('position', $widget->position) === 'left')>Left</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Logo URL</label>
                    <input type="text" name="logo_url" class="form-control" value="{{ old('logo_url', $widget->logo_url) }}" placeholder="Optional custom logo">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Header Background</label>
                    <input type="text" name="header_bg_color" class="form-control" value="{{ old('header_bg_color', $widget->header_bg_color) }}" placeholder="Default: theme color">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Visitor Bubble</label>
                    <input type="text" name="visitor_bubble_color" class="form-control" value="{{ old('visitor_bubble_color', $widget->visitor_bubble_color) }}" placeholder="Default: theme color">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Agent Bubble</label>
                    <input type="text" name="agent_bubble_color" class="form-control" value="{{ old('agent_bubble_color', $widget->agent_bubble_color) }}" placeholder="Default: #ffffff">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Allowed Domains</label>
                    <textarea name="allowed_domains" class="form-control" rows="4" placeholder="example.com&#10;app.example.com&#10;*.example.org">{{ old('allowed_domains', implode(PHP_EOL, $widget->allowed_domains ?? [])) }}</textarea>
                    <div class="form-hint">Kosong berarti browser request dengan header <code>Origin</code> akan ditolak. Isi satu domain per baris tanpa protokol, atau pakai <code>*</code> bila memang ingin mengizinkan semua origin.</div>
                </div>
                <div class="col-md-12">
                    <label class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" @checked(old('is_active', $widget->is_active))>
                        <span class="form-check-label">Widget aktif</span>
                    </label>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">{{ $isEdit ? 'Simpan Perubahan' : 'Buat Widget' }}</button>
            </div>
        </form>
    </div>
</div>

@if($isEdit)
    <div class="card mt-3">
        <div class="card-body">
            <h3 class="card-title">Embed Script</h3>
            <p class="text-muted small mb-2">Pasang snippet ini di website Anda.</p>
            <textarea class="form-control" rows="3" readonly>{{ $widget->embedCode() }}</textarea>
            <div class="form-hint mt-2">Token widget: <code>{{ $widget->widget_token }}</code></div>
        </div>
    </div>
@endif
@endsection
