@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Edit Payment Method</h2>
        <div class="text-muted small">{{ $method->name }}</div>
    </div>
    <a href="{{ route('payments.methods.index') }}" class="btn btn-outline-secondary">Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('payments.methods.update', $method) }}" class="row g-3">
            @csrf
            @method('PUT')
            @include('payments::partials.method-form', ['method' => $method])
            <div class="col-12">
                <button type="submit" class="btn btn-primary">Update Method</button>
            </div>
        </form>
    </div>
</div>
@endsection
