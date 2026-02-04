@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Task Templates</h2>
        <div class="text-muted">Gunakan template untuk autofill daftar task di internal memo.</div>
    </div>
    <a href="{{ route('tasktemplates.create') }}" class="btn btn-primary">Buat Template</a>
</div>
<div class="row row-cards">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Daftar Template</h3>
                <a class="btn btn-outline-primary btn-sm" href="{{ route('tasktemplates.index') }}">Refresh</a>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Items</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                        <tr>
                            <td class="fw-bold">{{ $template->title }}</td>
                            <td class="text-muted small">{{ $template->description }}</td>
                            <td>
                                @if($template->items->count())
                                    <ul class="list-unstyled mb-0">
                                        @foreach($template->items->sortBy('position') as $item)
                                        <li class="d-flex align-items-center gap-2">
                                            <span class="badge bg-azure-lt text-azure">{{ $item->position }}</span>
                                            <span>{{ $item->title }}</span>
                                        </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <span class="text-muted small">No items</span>
                                @endif
                            </td>
                            <td class="text-end">
                                <div class="btn-list flex-nowrap mb-0">
                                    <a class="btn btn-icon btn-outline-secondary" href="{{ route('tasktemplates.edit', $template) }}" title="Edit">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                            <path d="M12 20h9" />
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3l-11 11l-4 1l1 -4z" />
                                        </svg>
                                    </a>
                                    <form method="POST" action="{{ route('tasktemplates.destroy', $template) }}" onsubmit="return confirm('Delete template?')" style="display:inline">
                                        @csrf
                                        @method('DELETE')
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
                        <tr><td colspan="4" class="text-center text-muted">No templates</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
