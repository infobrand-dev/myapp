@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WA Templates</h2>
        <div class="text-muted small">Template WABA (nama & bahasa harus sesuai di Meta).</div>
    </div>
    <a href="{{ route('whatsapp-api.templates.create') }}" class="btn btn-primary">Tambah Template</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Language</th>
                    <th>Category</th>
                    <th>Status</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                    <tr>
                        <td class="fw-bold">{{ $tpl->name }}</td>
                        <td>{{ $tpl->language }}</td>
                        <td>{{ $tpl->category ?? '-' }}</td>
                        <td>
                            <span class="badge {{ $tpl->status === 'active' ? 'bg-green-lt text-green' : ($tpl->status === 'pending' ? 'bg-yellow-lt text-yellow' : 'bg-secondary-lt text-secondary') }}">
                                {{ $tpl->status }}
                            </span>
                        </td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="View" aria-label="View">
                                    <i class="ti ti-eye icon" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit" aria-label="Edit">
                                    <i class="ti ti-pencil icon" aria-hidden="true"></i>
                                </a>
                                @if($tpl->status !== 'pending' && $tpl->status !== 'active')
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.submit', $tpl) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-azure btn-icon" type="submit" title="Submit Approval" aria-label="Submit Approval">
                                            <i class="ti ti-check icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.destroy', $tpl) }}" onsubmit="return confirm('Hapus template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Delete" aria-label="Delete">
                                        <i class="ti ti-trash icon" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-muted">Belum ada template.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $templates->links() }}</div>
@endsection
