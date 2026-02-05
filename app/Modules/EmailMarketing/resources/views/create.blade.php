@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="mb-0">Buat Campaign Email</h2>
    <a href="{{ route('email-marketing.index') }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('email-marketing.store') }}" class="row g-3">
            @csrf
            <div class="col-md-6">
                <label class="form-label">Nama Campaign</label>
                <input type="text" name="name" class="form-control" required>
            </div>
            <div class="col-md-6">
                <label class="form-label">Subject Email</label>
                <input type="text" name="subject" class="form-control" required>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">Lanjut Edit Body Email</button>
            </div>
        </form>
    </div>
</div>
@endsection
