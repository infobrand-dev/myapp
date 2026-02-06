@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Lampiran Dinamis</h2>
        <div class="text-muted small">Template PDF dengan placeholder kontak.</div>
    </div>
    <a href="{{ route('email-marketing.templates.create') }}" class="btn btn-primary">Buat Template</a>
    <a href="{{ route('email-marketing.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table card-table table-vcenter">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Filename</th>
                    <th>Deskripsi</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($templates as $tpl)
                    <tr>
                        <td>{{ $tpl->name }}</td>
                        <td>{{ $tpl->filename }}</td>
                        <td class="text-muted">{{ $tpl->description }}</td>
                        <td class="text-end">
                            <a href="{{ route('email-marketing.templates.edit', $tpl) }}" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="{{ route('email-marketing.templates.destroy', $tpl) }}" class="d-inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus template?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Belum ada template.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
