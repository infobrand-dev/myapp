@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WA Templates</h2>
        <div class="text-muted small">Nama internal untuk aplikasi. Meta Name dipakai khusus saat submit/send ke WhatsApp.</div>
    </div>
    <div class="d-flex gap-2">
        <form method="POST" action="{{ route('whatsapp-api.templates.refresh-statuses') }}">
            @csrf
            <button type="submit" class="btn btn-outline-primary">Refresh Status</button>
        </form>
        <a href="{{ route('whatsapp-api.blast-campaigns.index') }}" class="btn btn-outline-secondary">Blast Campaigns</a>
        <a href="{{ route('whatsapp-api.templates.create') }}" class="btn btn-primary">Tambah Template</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Meta Name</th>
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
                        <td><code>{{ $tpl->meta_name ?: '-' }}</code></td>
                        <td>{{ $tpl->language }}</td>
                        <td>{{ $tpl->category ?? '-' }}</td>
                        <td>
                            <span class="badge {{
                                $tpl->status === 'approved' ? 'bg-green-lt text-green'
                                : ($tpl->status === 'pending' ? 'bg-yellow-lt text-yellow'
                                : ($tpl->status === 'rejected' ? 'bg-red-lt text-red' : 'bg-secondary-lt text-secondary'))
                            }}">
                                {{ $tpl->status }}
                            </span>
                            @if($tpl->status === 'rejected' && $tpl->last_submit_error)
                                <details class="mt-2">
                                    <summary class="text-danger small" style="cursor:pointer;">Lihat error lengkap</summary>
                                    <pre class="small text-danger bg-light border rounded p-2 mt-2 mb-0" style="white-space: pre-wrap;">{{ $tpl->last_submit_error }}</pre>
                                </details>
                            @endif
                        </td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="View" aria-label="View">
                                    <i class="ti ti-eye icon" aria-hidden="true"></i>
                                </a>
                                <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-secondary btn-icon" title="Edit" aria-label="Edit">
                                    <i class="ti ti-pencil icon" aria-hidden="true"></i>
                                </a>
                                @if(!$tpl->meta_template_id && $tpl->status !== 'pending' && $tpl->status !== 'approved')
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.submit', $tpl) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-outline-azure btn-icon" type="submit" title="Submit Approval" aria-label="Submit Approval">
                                            <i class="ti ti-check icon" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                @endif
                                <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.destroy', $tpl) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Delete" aria-label="Delete" data-confirm="Hapus template ini?">
                                        <i class="ti ti-trash icon" aria-hidden="true"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada template.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $templates->links() }}</div>
@endsection
