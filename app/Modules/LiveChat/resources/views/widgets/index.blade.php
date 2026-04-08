@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Live Chat Widgets</h2>
        <div class="text-muted small">Daftar widget live chat untuk website.</div>
    </div>
    <a href="{{ route('live-chat.widgets.create') }}" class="btn btn-primary">Tambah Widget</a>
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
                    <th>Website</th>
                    <th>Brand</th>
                    <th>Status</th>
                    <th>Allowed Domains</th>
                    <th>Embed</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($widgets as $widget)
                    <tr>
                        <td>{{ $widget->name }}</td>
                        <td>{{ $widget->website_name ?: '-' }}</td>
                        <td>
                            <div>{{ $widget->launcher_label ?: 'Chat' }} / {{ ucfirst($widget->position ?: 'right') }}</div>
                            <div class="text-muted small">{{ $widget->header_bg_color ?: ($widget->theme_color ?: '#206bc4') }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $widget->is_active ? 'green' : 'secondary' }}-lt text-{{ $widget->is_active ? 'green' : 'secondary' }}">
                                {{ $widget->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td>
                            @php
                                $domains = $widget->allowed_domains ?? [];
                            @endphp
                            {{ empty($domains) ? 'Belum dikonfigurasi' : implode(', ', $domains) }}
                        </td>
                        <td><code>{{ $widget->embedCode() }}</code></td>
                        <td class="text-end">
                            <a href="{{ route('live-chat.widgets.edit', $widget) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">Belum ada widget live chat.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $widgets->links() }}
    </div>
</div>
@endsection
