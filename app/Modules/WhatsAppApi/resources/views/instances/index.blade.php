@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">WhatsApp API Instances</h2>
        <div class="text-muted small">Kelola koneksi WA API (hanya Super-admin).</div>
    </div>
    <a href="{{ route('whatsapp-api.instances.create') }}" class="btn btn-primary">Tambah Instance</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Nomor</th>
                    <th>Status</th>
                    <th>Aktif</th>
                    <th>Last Health</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($instances as $instance)
                    <tr>
                        <td class="fw-bold">{{ $instance->name }}</td>
                        <td>{{ $instance->phone_number ?? '—' }}</td>
                        <td><span class="badge bg-{{ $instance->status === 'connected' ? 'success' : 'secondary' }}">{{ $instance->status }}</span></td>
                        <td>{{ $instance->is_active ? 'Ya' : 'Tidak' }}</td>
                        <td>{{ optional($instance->last_health_check_at)->format('d M Y H:i') ?? '—' }}</td>
                        <td class="text-end">
                            <div class="btn-list flex-nowrap">
                                <a href="{{ route('whatsapp-api.instances.edit', $instance) }}" class="btn btn-outline-secondary btn-sm">Edit</a>
                                <form method="POST" action="{{ route('whatsapp-api.instances.destroy', $instance) }}" onsubmit="return confirm('Hapus instance?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-outline-danger btn-sm" type="submit">Hapus</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-muted">Belum ada instance.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3">{{ $instances->links() }}</div>
@endsection
