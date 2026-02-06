@extends('layouts.admin')

@section('content')
<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                @php $initial = strtoupper(substr($user->name, 0, 1)); @endphp
                <div class="avatar avatar-xl mb-3" style="width:96px;height:96px; margin:0 auto;">
                    @if($user->avatar)
                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="Avatar" class="avatar-img rounded">
                    @else
                        <span class="avatar avatar-xl rounded" style="width:96px;height:96px; background: #e9ecef; display:flex; align-items:center; justify-content:center; font-weight:600; font-size:24px;">
                            {{ $initial }}
                        </span>
                    @endif
                </div>
                <div class="h4 mb-1">{{ $user->name }}</div>
                <div class="text-muted">{{ $user->email }}</div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Ubah Profile</h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control">
                            <small class="text-muted">PNG/JPG maks 2MB.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Password (opsional)</label>
                            <input type="password" name="password" class="form-control" placeholder="Biarkan kosong jika tidak diganti">
                            <input type="password" name="password_confirmation" class="form-control mt-2" placeholder="Konfirmasi password">
                        </div>
                    </div>
                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Simpan</button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
            </div>
        </div>
        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Tampilan (Light/Dark)</h3>
            </div>
            <div class="card-body">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-secondary" data-theme-mode="light">Light</button>
                    <button type="button" class="btn btn-outline-secondary" data-theme-mode="dark">Dark</button>
                </div>
                <div class="text-muted small mt-2">Pilihan disimpan di browser ini (localStorage).</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    (function(){
        const apply = (mode) => {
            document.documentElement.setAttribute('data-bs-theme', mode);
            localStorage.setItem('theme-mode', mode);
        };
        document.querySelectorAll('[data-theme-mode]').forEach(btn => {
            btn.addEventListener('click', () => {
                apply(btn.dataset.themeMode || 'light');
            });
        });
    })();
</script>
@endpush
