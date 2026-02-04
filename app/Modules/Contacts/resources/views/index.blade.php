@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Contacts</h2>
        <div class="text-muted small">Database perusahaan dan individu.</div>
    </div>
    <a href="{{ route('contacts.create') }}" class="btn btn-primary">Tambah Contact</a>
</div>

@if(session('status'))
    <div class="alert alert-info">{{ session('status') }}</div>
@endif

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Tipe</th>
                    <th>Company</th>
                    <th>Email</th>
                    <th>Telepon</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($contacts as $contact)
                <tr>
                    <td>
                        <a href="{{ route('contacts.show', $contact) }}" class="text-decoration-none">
                            {{ $contact->name }}
                        </a>
                    </td>
                    <td>
                        <span class="badge bg-{{ $contact->type === 'company' ? 'primary' : 'azure' }}-lt text-{{ $contact->type === 'company' ? 'primary' : 'azure' }}">
                            {{ $contact->type === 'company' ? 'Company' : 'Individual' }}
                        </span>
                    </td>
                    <td>{{ $contact->company?->name ?? '-' }}</td>
                    <td>{{ $contact->email ?? '-' }}</td>
                    <td>{{ $contact->phone ?? $contact->mobile ?? '-' }}</td>
                    <td class="text-end">
                        <div class="btn-list flex-nowrap mb-0">
                            <a class="btn btn-icon btn-outline-secondary" href="{{ route('contacts.edit', $contact) }}" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4z" />
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('contacts.destroy', $contact) }}" onsubmit="return confirm('Hapus contact ini?')" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-icon btn-outline-danger" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7h16" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                        <path d="M9 7v-3h6v3" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="6" class="text-center text-muted">Belum ada contact.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $contacts->links() }}
    </div>
</div>
@endsection
