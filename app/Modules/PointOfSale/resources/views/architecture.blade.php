@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Point Of Sale Architecture</h2>
        <div class="text-muted small">Blueprint module `PointOfSale`.</div>
    </div>
    <a href="{{ route('pos.index') }}" class="btn btn-outline-secondary">Back to POS</a>
</div>

<div class="card">
    <div class="card-body">
        <pre class="mb-0 text-wrap" style="white-space: pre-wrap;">{{ $blueprint }}</pre>
    </div>
</div>
@endsection
