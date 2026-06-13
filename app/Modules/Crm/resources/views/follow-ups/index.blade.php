@extends('layouts.tenant')

@section('content')
<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-start gap-3">
        <div>
            <div class="page-pretitle">CRM</div>
            <h2 class="page-title">Follow-Up Queue</h2>
        </div>
    </div>
</div>

@include('crm::partials.nav')

<div class="row g-3">
    <div class="col-xl-4">
        <div class="card sticky-top" style="top:1rem;">
            <div class="card-header"><h3 class="card-title mb-0">Quick Add</h3></div>
            <div class="card-body">
                <div class="btn-list mb-3">
                    @foreach(['today' => 'Today', 'overdue' => 'Overdue', 'upcoming' => 'Upcoming', 'completed' => 'Completed', 'mine' => 'Mine'] as $key => $label)
                        <a href="{{ route('crm.follow-ups', ['filter' => $key]) }}" class="btn {{ $filter === $key ? 'btn-primary' : 'btn-outline-secondary' }}">{{ $label }}</a>
                    @endforeach
                </div>
                <form method="POST" action="{{ route('crm.follow-ups.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Subject</label>
                        <input type="text" name="subject" class="form-control" placeholder="Call back prospect" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Lead</label>
                        <select name="lead_id" class="form-select">
                            <option value="">Tanpa deal</option>
                            @foreach($leads as $lead)
                                <option value="{{ $lead->id }}">{{ $lead->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Owner</label>
                        <select name="owner_user_id" class="form-select">
                            <option value="">Auto assign</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}">{{ $owner->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2">
                        <div class="col-sm-7">
                            <label class="form-label">Due At</label>
                            <input type="datetime-local" name="due_at" class="form-control">
                        </div>
                        <div class="col-sm-5">
                            <label class="form-label">Priority</label>
                            <select name="priority" class="form-select">
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" rows="3" class="form-control" placeholder="Ringkas next action agar sales berikutnya tidak bingung"></textarea>
                    </div>
                    <button class="btn btn-primary w-100 mt-3">Tambah Follow-Up</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-xl-8">
        @forelse($tasks as $task)
            <div class="card mb-3">
                <div class="card-body">
                    <div class="d-flex justify-content-between gap-3">
                        <div class="min-width-0">
                            <div class="fw-semibold">{{ $task->subject }}</div>
                            <div class="text-muted small">{{ $task->contact?->name ?? $task->lead?->title ?? 'Tanpa relasi customer' }}</div>
                            <div class="small mt-2 {{ $task->due_at && $task->due_at->isPast() && $task->status === 'pending' ? 'text-danger' : 'text-muted' }}">
                                {{ $task->due_at ? $task->due_at->translatedFormat('d M Y H:i') : 'Tanpa jadwal' }}
                            </div>
                            @if($task->description)
                                <div class="small mt-2">{{ $task->description }}</div>
                            @endif
                        </div>
                        <div class="text-end">
                            <div class="small text-muted mb-2">{{ $task->owner?->name ?? 'Unassigned' }}</div>
                            <span class="badge {{ $task->status === 'completed' ? 'bg-success-lt text-success' : 'bg-warning-lt text-warning' }}">{{ \Illuminate\Support\Str::headline($task->status) }}</span>
                            @if($task->status === 'pending')
                                <form method="POST" action="{{ route('crm.follow-ups.complete', $task->id) }}" class="mt-2">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-primary">Mark Complete</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="card"><div class="card-body text-center text-muted py-5">Tidak ada follow-up untuk filter ini.</div></div>
        @endforelse

        @if($tasks->hasPages())
            <div class="mt-3">{{ $tasks->links() }}</div>
        @endif
    </div>
</div>
@endsection

