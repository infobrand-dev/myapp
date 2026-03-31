@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Knowledge Base</h2>
        <div class="text-muted small">{{ $account->name }} — dokumen referensi yang digunakan bot saat menjawab pertanyaan.</div>
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
                    <th>Status</th>
                    <th>Kategori</th>
                    <th>Potongan Teks</th>
                    <th>Update</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($documents as $doc)
                    <tr>
                        <td>{{ $doc->title }}</td>
                        <td>{{ data_get($doc->metadata, 'status', 'active') }}</td>
                        <td>{{ data_get($doc->metadata, 'category', '-') }}</td>
                        <td>{{ $doc->chunks_count }}</td>
                        <td>{{ optional($doc->updated_at)->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-secondary" href="{{ route('chatbot.knowledge.edit', [$account, $doc]) }}">Edit</a>
                            <form class="d-inline-block m-0" method="POST" action="{{ route('chatbot.knowledge.destroy', [$account, $doc]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" data-confirm="Hapus dokumen ini?">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="text-muted">
                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                                    </svg>
                                </div>
                                <div class="fw-semibold mb-1">Belum ada dokumen referensi</div>
                                <div class="text-muted small mb-3">Tambahkan dokumen seperti FAQ, katalog produk, atau SOP agar bot bisa menjawab berdasarkan informasi yang tepat.</div>
                                <a href="{{ route('chatbot.knowledge.create', $account) }}" class="btn btn-primary btn-sm">+ Upload Dokumen Pertama</a>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $documents->links() }}</div>

<div class="card mt-3">
    <div class="card-header"><h3 class="card-title">Dokumen yang Paling Sering Dipakai Bot</h3></div>
    <div class="list-group list-group-flush">
        @forelse($topKnowledgeDocuments as $document)
            <div class="list-group-item">
                <div class="fw-semibold">{{ $document->title }}</div>
                <div class="text-muted small">{{ data_get($document->metadata, 'category', 'Umum') }} · {{ data_get($document->metadata, 'status', 'active') === 'active' ? 'Aktif' : ucfirst(data_get($document->metadata, 'status', 'active')) }}</div>
            </div>
        @empty
            <div class="list-group-item text-muted">Belum ada data penggunaan knowledge oleh bot.</div>
        @endforelse
    </div>
</div>
@endsection
