@if($lead)
    <a href="{{ route('crm.show', $lead) }}" class="btn btn-sm btn-outline-primary">Open CRM</a>
@else
    <a href="{{ route('crm.create', ['contact_id' => $contact->id, 'title' => 'Follow up ' . $contact->name]) }}" class="btn btn-sm btn-outline-primary">Tambah ke CRM</a>
@endif
