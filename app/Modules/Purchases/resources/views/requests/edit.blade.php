@extends('layouts.admin')

@section('title', 'Edit Purchase Request')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Purchases</div>
            <h2 class="page-title">Edit {{ $requestModel->request_number }}</h2>
        </div>
    </div>
</div>

@include('purchases::requests.partials.form', [
    'submitRoute' => route('purchases.requests.update', $requestModel),
    'method' => 'PUT',
])
@endsection
