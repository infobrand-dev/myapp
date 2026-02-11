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
                        <td><span class="badge bg-{{ $tpl->status === 'active' ? 'success' : 'secondary' }}">{{ $tpl->status }}</span></td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                <a href="{{ route('whatsapp-api.templates.edit', $tpl) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('whatsapp-api.templates.destroy', $tpl) }}" onsubmit="return confirm('Hapus template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Hapus</button>
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
