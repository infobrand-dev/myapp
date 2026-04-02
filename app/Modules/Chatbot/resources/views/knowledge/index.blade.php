@extends('layouts.admin')

@section('title', 'Knowledge Base — ' . $account->name)

@section('content')

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Chatbot · {{ $account->name }}</div>
        <h2 class="page-title">Knowledge Base</h2>
        <div class="text-muted small mt-1">Dokumen referensi yang digunakan bot saat menjawab pertanyaan.</div>
    </div>
    <div class="d-flex gap-2 flex-shrink-0">
        <a href="{{ route('chatbot.accounts.index') }}" class="btn btn-outline-secondary">
            <i class="ti ti-arrow-left me-1"></i>Kembali
        </a>
        <a href="{{ route('chatbot.knowledge.create', $account) }}" class="btn btn-primary">
            <i class="ti ti-plus me-1"></i>Tambah Dokumen
        </a>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Judul</th>
                        <th>Status</th>
                        <th>Kategori</th>
                        <th>Potongan Teks</th>
                        <th>Diperbarui</th>
                        <th class="w-1"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td class="fw-semibold">{{ $doc->title }}</td>
                            <td>
                                @php $docStatus = data_get($doc->metadata, 'status', 'active'); @endphp
                                @if($docStatus === 'active')
                                    <span class="badge bg-green-lt text-green">Aktif</span>
                                @elseif($docStatus === 'draft')
                                    <span class="badge bg-orange-lt text-orange">Draft</span>
                                @else
                                    <span class="badge bg-secondary-lt text-secondary">Arsip</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ data_get($doc->metadata, 'category', '-') }}</td>
                            <td class="text-muted small">{{ $doc->chunks_count }}</td>
                            <td class="text-muted small text-nowrap">{{ optional($doc->updated_at)->format('d M Y H:i') }}</td>
                            <td class="text-end align-middle">
                                <div class="table-actions">
                                    <a href="{{ route('chatbot.knowledge.edit', [$account, $doc]) }}"
                                       class="btn btn-icon btn-sm btn-outline-secondary"
                                       title="Edit">
                                        <i class="ti ti-pencil"></i>
                                    </a>
                                    <form class="d-inline-block m-0" method="POST"
                                          action="{{ route('chatbot.knowledge.destroy', [$account, $doc]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-icon btn-sm btn-outline-danger"
                                                title="Hapus"
                                                data-confirm="Hapus dokumen {{ $doc->title }}?">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="ti ti-file-text text-muted d-block mb-2" style="font-size:2rem;"></i>
                                <div class="text-muted mb-2">Belum ada dokumen referensi.</div>
                                <a href="{{ route('chatbot.knowledge.create', $account) }}" class="btn btn-sm btn-primary">Upload Dokumen Pertama</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $documents->links() }}
    </div>
</div>

<div class="card mt-3">
    <div class="card-header">
        <h3 class="card-title">Dokumen Paling Sering Dipakai Bot</h3>
    </div>
    <div class="list-group list-group-flush">
        @forelse($topKnowledgeDocuments as $document)
            <div class="list-group-item">
                <div class="fw-semibold">{{ $document->title }}</div>
                <div class="text-muted small">
                    {{ data_get($document->metadata, 'category', 'Umum') }}
                    ·
                    @php $s = data_get($document->metadata, 'status', 'active'); @endphp
                    {{ $s === 'active' ? 'Aktif' : ucfirst($s) }}
                </div>
            </div>
        @empty
            <div class="list-group-item text-center py-3">
                <i class="ti ti-books text-muted d-block mb-1" style="font-size:1.3rem;"></i>
                <span class="text-muted small">Belum ada data penggunaan knowledge oleh bot.</span>
            </div>
        @endforelse
    </div>
</div>

@endsection
