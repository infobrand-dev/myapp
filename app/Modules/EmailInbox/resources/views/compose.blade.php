@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Compose Email</h2>
        <div class="text-muted small">Kirim email operasional dari {{ $account->email_address }}.</div>
    </div>
    <a href="{{ route('email-inbox.show', $account) }}" class="btn btn-outline-secondary">Kembali</a>
</div>

<form method="POST" action="{{ route('email-inbox.send', $account) }}">
    @csrf
    <div class="card">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">To</label>
                <input type="text" name="to" value="{{ old('to') }}" class="form-control" placeholder="user@example.com, other@example.com" required>
            </div>
            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">CC</label>
                    <input type="text" name="cc" value="{{ old('cc') }}" class="form-control">
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">BCC</label>
                    <input type="text" name="bcc" value="{{ old('bcc') }}" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" class="form-control" required>
            </div>
            <div class="mb-3">
                <label class="form-label">HTML Body</label>
                <textarea name="body_html" rows="10" class="form-control">{{ old('body_html') }}</textarea>
            </div>
            <div class="mb-0">
                <label class="form-label">Plain Text Fallback</label>
                <textarea name="body_text" rows="6" class="form-control">{{ old('body_text') }}</textarea>
            </div>
        </div>
        <div class="card-footer text-end">
            <button type="submit" class="btn btn-primary">Kirim</button>
        </div>
    </div>
</form>
@endsection
