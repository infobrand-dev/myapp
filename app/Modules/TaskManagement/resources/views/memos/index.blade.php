@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Internal Memos</h2>
        <div class="text-muted">Kelola pekerjaan per klien/brand, lengkap dengan task & progress.</div>
    </div>
    <a href="{{ route('memos.create') }}" class="btn btn-primary">Buat Memo</a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter">
            <thead>
                <tr>
                    <th>Judul</th>
                    <th>Perusahaan</th>
                    <th>Deadline</th>
                    <th>Progress</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($memos as $memo)
                @php
                    $progress = $memo->tasks_count ? intval(($memo->done_tasks_count / $memo->tasks_count) * 100) : 0;
                @endphp
                <tr>
                    <td class="fw-bold">{{ $memo->title }}</td>
                    <td>{{ $memo->company_name }}</td>
                    <td>{{ $memo->deadline?->format('d M Y') ?? '—' }}</td>
                    <td class="w-25">
                        <div class="progress progress-xs">
                            <div class="progress-bar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}">{{ $progress }}%</div>
                        </div>
                    </td>
                    <td class="text-end">
                        <div class="btn-list flex-nowrap mb-0">
                            <a class="btn btn-icon btn-outline-secondary" href="{{ route('memos.show', $memo) }}" title="View">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M3 12s3 -5 9 -5s9 5 9 5s-3 5 -9 5s-9 -5 -9 -5" />
                                    <path d="M12 9a3 3 0 1 0 0 6a3 3 0 0 0 0 -6" />
                                </svg>
                            </a>
                            <a class="btn btn-icon btn-outline-secondary" href="{{ route('memos.edit', $memo) }}" title="Edit">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                    <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                    <path d="M12 20h9" />
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4z" />
                                </svg>
                            </a>
                            <form method="POST" action="{{ route('memos.destroy', $memo) }}" onsubmit="return confirm('Hapus memo?')" style="display:inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-icon btn-outline-danger" title="Delete">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                        <path d="M4 7h16" />
                                        <path d="M10 11v6" />
                                        <path d="M14 11v6" />
                                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                                        <path d="M9 7v-3h6v3" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">Belum ada memo.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $memos->links() }}
    </div>
</div>
@endsection
