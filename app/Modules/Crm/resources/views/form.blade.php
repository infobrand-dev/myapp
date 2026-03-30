@extends('layouts.admin')

@section('content')
@php
    $defaultCurrency = app(\App\Support\CurrencySettingsResolver::class)->defaultCurrency();
@endphp
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center gap-3">
        <div>
            <div class="page-pretitle">CRM</div>
            <h2 class="page-title">{{ $pageTitle }}</h2>
        </div>
        <a href="{{ $lead->exists ? route('crm.show', $lead) : route('crm.index') }}"
           class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
    </div>
</div>

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="ti ti-id-badge me-2 text-muted"></i>Lead Profile
                    </h3>
                </div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label required">Judul Lead</label>
                        <input type="text" name="title"
                               class="form-control @error('title') is-invalid @enderror"
                               value="{{ old('title', $lead->title) }}"
                               placeholder="Contoh: Renewal omnichannel PT ABC">
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact</label>
                        <select name="contact_id" class="form-select">
                            <option value="">Tanpa contact terhubung</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}"
                                        @selected((int) old('contact_id', $lead->contact_id) === (int) $contact->id)>
                                    {{ $contact->name }}{{ $contact->email ? ' · ' . $contact->email : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Pilih contact agar riwayat pelanggan tetap konsisten.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner</label>
                        <select name="owner_user_id" class="form-select">
                            <option value="">Belum ada owner</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}"
                                        @selected((int) old('owner_user_id', $lead->owner_user_id) === (int) $owner->id)>
                                    {{ $owner->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Stage</label>
                        <select name="stage" class="form-select @error('stage') is-invalid @enderror">
                            @foreach($stageOptions as $stageKey => $stageLabel)
                                <option value="{{ $stageKey }}"
                                        @selected(old('stage', $lead->stage) === $stageKey)>{{ $stageLabel }}</option>
                            @endforeach
                        </select>
                        @error('stage')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            @foreach($priorityOptions as $priorityKey => $priorityLabel)
                                <option value="{{ $priorityKey }}"
                                        @selected(old('priority', $lead->priority ?: 'medium') === $priorityKey)>
                                    {{ $priorityLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lead Source</label>
                        <input type="text" name="lead_source" class="form-control"
                               value="{{ old('lead_source', $lead->lead_source) }}"
                               placeholder="Instagram DM, referral, renewal, dll">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Estimated Value</label>
                        <div class="input-group">
                            <span class="input-group-text">{{ old('currency', $lead->currency ?: $defaultCurrency) }}</span>
                            <input type="number" step="1" min="0" name="estimated_value"
                                   class="form-control"
                                   value="{{ old('estimated_value', $lead->estimated_value ? (int) $lead->estimated_value : '') }}">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Probability</label>
                        <div class="input-group">
                            <input type="number" min="0" max="100" name="probability"
                                   class="form-control"
                                   value="{{ old('probability', $lead->probability) }}"
                                   placeholder="0–100">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control"
                               value="{{ old('currency', $lead->currency ?: $defaultCurrency) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Next Follow Up</label>
                        <input type="datetime-local" name="next_follow_up_at"
                               class="form-control"
                               value="{{ old('next_follow_up_at', optional($lead->next_follow_up_at)->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Contacted</label>
                        <input type="datetime-local" name="last_contacted_at"
                               class="form-control"
                               value="{{ old('last_contacted_at', optional($lead->last_contacted_at)->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Labels</label>
                        <input type="text" name="labels" class="form-control"
                               value="{{ old('labels', collect($lead->labels ?? [])->implode(', ')) }}"
                               placeholder="vip, renewal, enterprise">
                        <div class="form-hint">Pisahkan dengan koma. Contoh: <code>vip, enterprise</code></div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="6" class="form-control"
                                  placeholder="Kebutuhan, keberatan, timeline, next action...">{{ old('notes', $lead->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title mb-0">
                        <i class="ti ti-settings me-2 text-muted"></i>Status
                    </h3>
                </div>
                <div class="card-body">
                    <label class="form-check">
                        <input type="checkbox" name="is_archived" value="1"
                               class="form-check-input"
                               @checked(old('is_archived', $lead->is_archived))>
                        <span class="form-check-label fw-medium">Arsipkan lead ini</span>
                    </label>
                    <div class="form-hint mt-1">
                        Lead yang diarsipkan tersimpan untuk histori, tapi tersembunyi dari view utama.
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button class="btn btn-primary btn-lg" type="submit" data-loading="Menyimpan...">
                    <i class="ti ti-device-floppy me-1"></i>
                    {{ $lead->exists ? 'Simpan Perubahan' : 'Simpan Lead CRM' }}
                </button>
                <a href="{{ $lead->exists ? route('crm.show', $lead) : route('crm.index') }}"
                   class="btn btn-outline-secondary">Batal</a>
            </div>

            @if($lead->exists)
            <div class="card mt-3">
                <div class="card-body">
                    <div class="text-muted small fw-semibold text-uppercase mb-2">Quick Info</div>
                    <div class="small d-flex flex-column gap-1">
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Dibuat</span>
                            <span>{{ $lead->created_at?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Diperbarui</span>
                            <span>{{ $lead->updated_at?->translatedFormat('d M Y') ?? '-' }}</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Stage saat ini</span>
                            <span class="badge {{ \App\Modules\Crm\Support\CrmStageCatalog::badgeClass($lead->stage) }}">
                                {{ $stageOptions[$lead->stage] ?? $lead->stage }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</form>
@endsection
