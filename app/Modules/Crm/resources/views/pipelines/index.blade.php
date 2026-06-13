@extends('layouts.tenant')

@section('content')
<div class="page-header mb-4">
    <div>
        <div class="page-pretitle">CRM</div>
        <h2 class="page-title">Pipelines</h2>
    </div>
</div>

@include('crm::partials.nav')

<div class="row g-3 mb-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title mb-0">Tambah Pipeline</h3></div>
            <div class="card-body">
                <form method="POST" action="{{ route('crm.pipelines.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama</label>
                        <input type="text" name="name" class="form-control" placeholder="Pipeline Enterprise">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="enterprise-sales">
                    </div>
                    <button class="btn btn-primary w-100">Simpan Pipeline</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <div class="small text-muted">Limit stage CRM plan saat ini: {{ $stageLimit ?? 'Unlimited' }}. Drag package belum dibutuhkan; urutan stage tetap bisa diubah aman lewat input posisi.</div>
            </div>
        </div>
    </div>
</div>

@foreach($pipelines as $pipeline)
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ $pipeline->name }}</div>
                <div class="small text-muted">{{ $pipeline->leads->count() }} deals • {{ $pipeline->is_default ? 'Default pipeline' : 'Custom pipeline' }}</div>
            </div>
            @if($pipeline->is_default)
                <span class="badge bg-primary-lt text-primary">Default</span>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive mb-4">
                <form method="POST" action="{{ route('crm.pipelines.reorder', $pipeline) }}">
                    @csrf
                    <table class="table table-sm table-vcenter">
                        <thead><tr><th>Stage</th><th>Type</th><th>Probability</th><th style="width:120px;">Position</th></tr></thead>
                        <tbody>
                        @foreach($pipeline->stages as $stage)
                            <tr>
                                <td>{{ $stage->name }}</td>
                                <td>{{ \Illuminate\Support\Str::headline($stage->stage_type) }}</td>
                                <td>{{ $stage->probability_default }}%</td>
                                <td><input type="number" min="1" name="positions[{{ $stage->id }}]" value="{{ $stage->position }}" class="form-control form-control-sm"></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <button class="btn btn-outline-secondary btn-sm">Simpan Urutan Stage</button>
                </form>
            </div>

            <form method="POST" action="{{ route('crm.pipelines.stages.store', $pipeline) }}" class="row g-2">
                @csrf
                <div class="col-md-4"><input type="text" name="name" class="form-control" placeholder="Nama stage"></div>
                <div class="col-md-3"><input type="text" name="code" class="form-control" placeholder="code-stage"></div>
                <div class="col-md-2"><input type="number" name="probability_default" min="0" max="100" class="form-control" placeholder="%"></div>
                <div class="col-md-2">
                    <select name="stage_type" class="form-select">
                        @foreach($stageTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-1"><button class="btn btn-primary w-100">+</button></div>
            </form>
        </div>
    </div>
@endforeach
@endsection

