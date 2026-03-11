@extends('layouts.admin')

@section('content')
<div class="row row-cards">
    <div class="col-12 col-lg-7">
        <div class="card card-stacked h-100">
            <div class="card-body d-flex align-items-center p-3 p-sm-4">
                <div>
                    <div class="text-secondary mb-1">Welcome back</div>
                    <h2 class="card-title mb-2 fs-2">{{ auth()->user()->name }}</h2>
                    <p class="text-muted mb-3 pe-lg-4">
                        You have <strong>5</strong> new messages and <strong>2</strong> new notifications.
                    </p>
                    <div class="d-grid d-sm-flex gap-2">
                        <a href="#" class="btn btn-primary">View inbox</a>
                        <a href="#" class="btn btn-outline-secondary">Mark all read</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-lg-5">
        <div class="card card-stacked h-100">
            <div class="card-body d-flex align-items-center justify-content-center p-3 p-sm-4">
                <img src="{{ asset('img/tabler-welcome.svg') }}" alt="Welcome illustration" class="img-fluid" style="max-height:180px; width:min(100%, 280px);">
            </div>
        </div>
    </div>
</div>

<div class="row row-cards mt-3">
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body p-3 p-sm-4">
                <div class="d-flex justify-content-between align-items-start gap-3">
                    <div>
                        <div class="text-secondary">Roles</div>
                        <div class="h3 mb-0 mt-1">{{ auth()->user()->getRoleNames()->join(', ') ?: 'None' }}</div>
                    </div>
                    <span class="badge bg-green-lt mt-1">Active</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body p-3 p-sm-4">
                <div class="text-secondary">Messages</div>
                <div class="d-flex align-items-baseline mt-1">
                    <div class="h2 mb-0 me-2">5</div>
                    <span class="text-muted">new</span>
                </div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body p-3 p-sm-4">
                <div class="text-secondary">Notifications</div>
                <div class="d-flex align-items-baseline mt-1">
                    <div class="h2 mb-0 me-2">2</div>
                    <span class="text-muted">new</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
