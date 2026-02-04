@extends('layouts.admin')

@section('content')
<div class="row row-cards">
    <div class="col-md-7">
        <div class="card card-stacked">
            <div class="card-body d-flex align-items-center">
                <div>
                    <div class="text-secondary mb-1">Welcome back</div>
                    <h2 class="card-title mb-2">{{ auth()->user()->name }}</h2>
                    <p class="text-muted mb-3">
                        You have <strong>5</strong> new messages and <strong>2</strong> new notifications.
                    </p>
                    <div class="d-flex gap-2">
                        <a href="#" class="btn btn-primary">View inbox</a>
                        <a href="#" class="btn btn-outline-secondary">Mark all read</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-5">
        <div class="card card-stacked h-100">
            <div class="card-body d-flex align-items-center justify-content-center">
                <img src="{{ asset('img/tabler-welcome.svg') }}" alt="Welcome illustration" class="img-fluid" style="max-height:180px;">
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-secondary">Roles</div>
                        <div class="h3 mb-0">{{ auth()->user()->getRoleNames()->join(', ') ?: 'None' }}</div>
                    </div>
                    <span class="badge bg-green-lt">Active</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary">Messages</div>
                <div class="d-flex align-items-baseline">
                    <div class="h2 mb-0 me-2">5</div>
                    <span class="text-muted">new</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="text-secondary">Notifications</div>
                <div class="d-flex align-items-baseline">
                    <div class="h2 mb-0 me-2">2</div>
                    <span class="text-muted">new</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
