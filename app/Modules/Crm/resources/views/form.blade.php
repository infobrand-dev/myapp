@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">{{ $pageTitle }}</h2>
        <div class="text-muted small">Kelola lead pipeline berbasis contact agar follow up tim lebih rapi.</div>
    </div>
    <a href="{{ $lead->exists ? route('crm.show', $lead) : route('crm.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

@if ($errors->any())
    <div class="alert alert-danger">
        <div class="fw-bold mb-1">Periksa input CRM Anda.</div>
        <ul class="mb-0 ps-3">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $formAction }}">
    @csrf
    @if($method !== 'POST')
        @method($method)
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Lead Profile</h3></div>
                <div class="card-body row g-3">
                    <div class="col-12">
                        <label class="form-label">Judul Lead</label>
                        <input type="text" name="title" class="form-control" value="{{ old('title', $lead->title) }}" placeholder="Contoh: Renewal omnichannel PT ABC">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Contact</label>
                        <select name="contact_id" class="form-select">
                            <option value="">Tanpa contact terhubung</option>
                            @foreach($contacts as $contact)
                                <option value="{{ $contact->id }}" @selected((int) old('contact_id', $lead->contact_id) === (int) $contact->id)>
                                    {{ $contact->name }}{{ $contact->email ? ' | ' . $contact->email : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div class="form-hint">Pilih contact yang sudah ada agar riwayat pelanggan tetap konsisten.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Owner</label>
                        <select name="owner_user_id" class="form-select">
                            <option value="">Belum ada owner</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}" @selected((int) old('owner_user_id', $lead->owner_user_id) === (int) $owner->id)>{{ $owner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Stage</label>
                        <select name="stage" class="form-select">
                            @foreach($stageOptions as $stageKey => $stageLabel)
                                <option value="{{ $stageKey }}" @selected(old('stage', $lead->stage) === $stageKey)>{{ $stageLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            @foreach($priorityOptions as $priorityKey => $priorityLabel)
                                <option value="{{ $priorityKey }}" @selected(old('priority', $lead->priority ?: 'medium') === $priorityKey)>{{ $priorityLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lead Source</label>
                        <input type="text" name="lead_source" class="form-control" value="{{ old('lead_source', $lead->lead_source) }}" placeholder="Instagram DM, referral, renewal, dll">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Estimated Value</label>
                        <input type="number" step="0.01" min="0" name="estimated_value" class="form-control" value="{{ old('estimated_value', $lead->estimated_value) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Currency</label>
                        <input type="text" name="currency" class="form-control" value="{{ old('currency', $lead->currency ?: 'IDR') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Probability (%)</label>
                        <input type="number" min="0" max="100" name="probability" class="form-control" value="{{ old('probability', $lead->probability) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Next Follow Up</label>
                        <input type="datetime-local" name="next_follow_up_at" class="form-control" value="{{ old('next_follow_up_at', optional($lead->next_follow_up_at)->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last Contacted</label>
                        <input type="datetime-local" name="last_contacted_at" class="form-control" value="{{ old('last_contacted_at', optional($lead->last_contacted_at)->format('Y-m-d\\TH:i')) }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Labels</label>
                        <input type="text" name="labels" class="form-control" value="{{ old('labels', collect($lead->labels ?? [])->implode(', ')) }}" placeholder="vip, renewal, enterprise">
                        <div class="form-hint">Pisahkan dengan koma.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" rows="6" class="form-control" placeholder="Catatan lead, kebutuhan, keberatan, timeline, next action">{{ old('notes', $lead->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Status</h3></div>
                <div class="card-body">
                    <label class="form-check">
                        <input type="checkbox" name="is_archived" value="1" class="form-check-input" @checked(old('is_archived', $lead->is_archived))>
                        <span class="form-check-label">Arsipkan lead ini</span>
                    </label>
                    <div class="form-hint mt-2">Lead yang diarsipkan tetap tersimpan untuk histori, tapi bisa disembunyikan dari view utama.</div>
                </div>
            </div>
            <div class="d-grid gap-2 mt-3">
                <button class="btn btn-primary">{{ $lead->exists ? 'Simpan Perubahan' : 'Simpan Lead CRM' }}</button>
                <a href="{{ route('crm.index') }}" class="btn btn-outline-secondary">Batal</a>
            </div>
        </div>
    </div>
</form>
@endsection
