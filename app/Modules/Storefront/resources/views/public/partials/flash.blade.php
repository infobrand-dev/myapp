@if(session('status'))
    <div class="alert alert-success border-0 shadow-sm mb-4">{{ session('status') }}</div>
@endif

@if(session('warning'))
    <div class="alert alert-warning border-0 shadow-sm mb-4">{{ session('warning') }}</div>
@endif
