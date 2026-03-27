@if(!empty($relatedContact))
    <div class="detail-row">
        <span class="detail-key">Contact CRM</span>
        <span class="detail-value">
            <span class="detail-action-group">
                <a href="{{ route('contacts.edit', $relatedContact) }}" class="btn btn-sm btn-outline-primary detail-action-btn" title="Open Contact" aria-label="Open Contact">
                    <i class="ti ti-address-book" aria-hidden="true"></i>
                </a>
                <button
                    type="button"
                    class="btn btn-sm btn-outline-secondary detail-action-btn"
                    data-bs-toggle="collapse"
                    data-bs-target="#contact-note-panel"
                    aria-expanded="false"
                    aria-controls="contact-note-panel"
                    title="Open Note"
                    aria-label="Open Note"
                >
                    <i class="ti ti-notebook" aria-hidden="true"></i>
                </button>
            </span>
        </span>
    </div>
    <div class="detail-row detail-row-stack">
        <span class="detail-key">Contact Notes</span>
        <div class="detail-value detail-value-detail">
            <div class="collapse" id="contact-note-panel">
                <div class="detail-collapse-panel">
                    <form method="POST" action="{{ route('contacts.notes-from-conversation', $relatedContact) }}" class="detail-inline-form">
                        @csrf
                        <textarea name="notes" class="form-control form-control-sm mb-2" {{ $canReply ? '' : 'disabled' }}>{{ old('notes', $relatedContact->notes ?? '') }}</textarea>
                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-sm btn-primary" {{ $canReply ? '' : 'disabled' }}>Save Note</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@elseif(Route::has('contacts.create'))
    <div class="detail-row">
        <span class="detail-key">Contact CRM</span>
        <span class="detail-value">
            <a
                href="{{ route('contacts.create', [
                    'type' => 'individual',
                    'name' => $conversation->contact_name,
                    'mobile' => $conversation->contact_external_id,
                    'phone' => $conversation->contact_external_id,
                    'notes' => 'Created from conversation #' . $conversation->id,
                ]) }}"
                class="btn btn-sm btn-outline-success detail-action-btn"
                title="Add Contact"
                aria-label="Add Contact"
            >
                <i class="ti ti-user-plus" aria-hidden="true"></i>
            </a>
        </span>
    </div>
@else
    <div class="detail-row">
        <span class="detail-key">Contact CRM</span>
        <span class="detail-value text-muted">Contacts module not available.</span>
    </div>
@endif
