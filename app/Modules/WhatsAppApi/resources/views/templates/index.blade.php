@extends('layouts.admin')

@section('title', 'WA Templates')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">WhatsApp API</div>
            <h2 class="page-title">WA Templates</h2>
            <p class="text-muted mb-0">Nama internal untuk aplikasi. Meta Name dipakai khusus saat submit/send ke WhatsApp.</p>
        </div>
        <div class="col-auto d-flex gap-2 flex-wrap">
            <form method="POST" action="{{ route('whatsapp-api.templates.refresh-statuses') }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary">
                    <i class="ti ti-refresh me-1"></i>Refresh Status
                </button>
            </form>
            <a href="{{ route('whatsapp-api.blast-campaigns.index') }}" class="btn btn-outline-secondary">
                <i class="ti ti-speakerphone me-1"></i>Blast Campaigns
            </a>
            <a href="{{ route('whatsapp-api.templates.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Tambah Template
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
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
                            <td class="fw-semibold">{{ $tpl->name }}</td>
                            <td><code class="text-muted">{{ $tpl->meta_name ?: '—' }}</code></td>
                            <td class="text-muted small">{{ $tpl->language }}</td>
                            <td class="text-muted small">{{ $tpl->category ?? '—' }}</td>
                            <td>
                                <span class="badge {{
                                    $tpl->status === 'approved' ? 'bg-green-lt text-green'
                                    : ($tpl->status === 'pending' ? 'bg-orange-lt text-orange'
                                    : ($tpl->status === 'rejected' ? 'bg-red-lt text-red' : 'bg-secondary-lt text-secondary'))
                                }}">
                                    {{ ucfirst($tpl->status) }}
                                </span>
                                @if($tpl->status === 'rejected' && $tpl->last_submit_error)
                                    <details class="mt-2">
                                        <summary class="text-danger small" style="cursor:pointer;">Lihat error</summary>
                                        <pre class="small text-danger bg-light border rounded p-2 mt-2 mb-0" style="white-space:pre-wrap;">{{ $tpl->last_submit_error }}</pre>
                                    </details>
                                @endif
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}"
                                       class="btn btn-icon btn-sm btn-outline-primary"
                                       title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    @if(!$tpl->meta_template_id && $tpl->status !== 'pending' && $tpl->status !== 'approved')
                                        <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.submit', $tpl) }}">
                                            @csrf
                                            <button class="btn btn-icon btn-sm btn-outline-secondary"
                                                    type="submit"
                                                    title="Submit Approval">
                                                <i class="ti ti-check"></i>
                                            </button>
                                        </form>
                                    @endif
                                    <form class="d-inline-block m-0" method="POST" action="{{ route('whatsapp-api.templates.destroy', $tpl) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-icon btn-sm btn-outline-danger"
                                                type="submit"
                                                title="Hapus"
                                                data-confirm="Hapus template {{ $tpl->name }}?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-layout-cards text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada template.</div>
                                <a href="{{ route('whatsapp-api.templates.create') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Tambah Template Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $templates->links() }}
    </div>
</div>

@endsection
