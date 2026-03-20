<button
    type="button"
    class="btn btn-icon btn-outline-success js-wa-contact-action"
    title="Kirim WhatsApp"
    data-bs-toggle="modal"
    data-bs-target="#wa-contact-action-modal"
    data-contact-id="{{ $contact->id }}"
    data-contact-name="{{ $contact->name }}"
    data-contact-phone="{{ $contact->mobile ?: $contact->phone }}"
    data-contact-email="{{ $contact->email }}"
    data-contact-company="{{ $contact->parentContact?->name }}"
    data-contact-job-title="{{ $contact->job_title }}"
    data-contact-website="{{ $contact->website }}"
    data-contact-industry="{{ $contact->industry }}"
    data-contact-city="{{ $contact->city }}"
    data-contact-state="{{ $contact->state }}"
    data-contact-country="{{ $contact->country }}"
    data-return-to="{{ url()->full() }}"
>
    <i class="ti ti-brand-whatsapp"></i>
</button>
