@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Knowledge Base</h2>
        <div class="text-muted small">{{ $account->name }} - dokumen referensi untuk RAG.</div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">Kembali</a>
        <a href="{{ route('chatbot.knowledge.create', $account) }}" class="btn btn-primary">Tambah Dokumen</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Chunks</th>
                    <th>Update</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->title }}</td>
                        <td>{{ $doc->chunks()->count() }}</td>
                        <td>{{ optional($doc->updated_at)->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('chatbot.knowledge.edit', [$account, $doc]) }}">Edit</a>
                            <form class="d-inline-block m-0" method="POST" action="{{ route('chatbot.knowledge.destroy', [$account, $doc]) }}" onsubmit="return confirm('Hapus dokumen ini?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="text-muted">Belum ada dokumen knowledge.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $documents->links() }}</div>
@endsection

