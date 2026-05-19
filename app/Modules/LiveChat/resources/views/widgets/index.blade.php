@extends('layouts.admin')

@section('title', 'Live Chat Widgets')

@section('content')

<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Live Chat</div>
            <h2 class="page-title">Widgets</h2>
            <p class="text-muted mb-0">Daftar widget live chat untuk website.</p>
        </div>
        <div class="col-auto">
            <a href="{{ route('live-chat.widgets.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i>Tambah Widget
            </a>
        </div>
    </div>
</div>

@if(session('status'))
    <div class="alert alert-azure">
        <div class="d-flex align-items-center gap-2">
            <i class="ti ti-info-circle" style="font-size:1.1rem; color:var(--tblr-azure);"></i>
            {{ session('status') }}
        </div>
    </div>
@endif

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Website</th>
                        <th>Branding</th>
                        <th>Status</th>
                        <th>Allowed Domains</th>
                        <th>Embed Code</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($widgets as $widget)
                        <tr>
                            <td class="fw-semibold">{{ $widget->name }}</td>
                            <td>{{ $widget->website_name ?: '—' }}</td>
                            <td>
                                <div>{{ $widget->launcher_label ?: 'Chat' }} · {{ ucfirst($widget->position ?: 'right') }}</div>
                                <div class="text-muted small">
                                    <span class="d-inline-block rounded"
                                          style="width:12px; height:12px; background:{{ $widget->header_bg_color ?: ($widget->theme_color ?: '#206bc4') }}; vertical-align:middle; margin-right:4px;"></span>
                                    {{ $widget->header_bg_color ?: ($widget->theme_color ?: '#206bc4') }}
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $widget->is_active ? 'green' : 'secondary' }}-lt text-{{ $widget->is_active ? 'green' : 'secondary' }}">
                                    {{ $widget->is_active ? 'Aktif' : 'Nonaktif' }}
                                </span>
                            </td>
                            <td>
                                @php $domains = $widget->allowed_domains ?? []; @endphp
                                @if(empty($domains))
                                    <span class="text-muted small">Belum dikonfigurasi</span>
                                @else
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($domains as $domain)
                                            <span class="badge bg-secondary-lt text-secondary">{{ $domain }}</span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="max-width:260px;">
                                    <code class="form-control form-control-sm text-truncate bg-light" id="embed-{{ $widget->id }}">{{ $widget->embedCode() }}</code>
                                    <button class="btn btn-outline-secondary btn-sm"
                                            type="button"
                                            title="Salin"
                                            onclick="navigator.clipboard.writeText(document.getElementById('embed-{{ $widget->id }}').textContent).then(()=>window.AppToast?.success('Embed code disalin!'))">
                                        <i class="ti ti-copy"></i>
                                    </button>
                                </div>
                            </td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('live-chat.widgets.edit', $widget) }}"
                                       class="btn btn-icon btn-sm btn-outline-primary"
                                       title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="ti ti-message-chatbot text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada widget live chat.</div>
                                <a href="{{ route('live-chat.widgets.create') }}" class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>Tambah Widget Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $widgets->links() }}
    </div>
</div>

@endsection
