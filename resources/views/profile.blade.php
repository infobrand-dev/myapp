@extends('layouts.admin')

@section('content')
<div class="page-header">
    <div class="page-pretitle">Akun</div>
    <h2 class="page-title">Profil Saya</h2>
</div>

<div class="row row-cards">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body text-center">
                @php $initial = strtoupper(substr($user->name, 0, 1)); @endphp
                <div class="mb-3" style="display:flex;justify-content:center;">
                    @if($user->avatar)
                        <span class="avatar avatar-xl rounded" style="width:80px;height:80px;">
                            <img src="{{ asset('storage/'.$user->avatar) }}" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:inherit;">
                        </span>
                    @else
                        <span class="avatar avatar-xl rounded" style="width:80px;height:80px;background:rgba(var(--tblr-primary-rgb),0.12);color:var(--tblr-primary);font-weight:700;font-size:1.5rem;">
                            {{ $initial }}
                        </span>
                    @endif
                </div>
                <div class="h5 mb-0">{{ $user->name }}</div>
                <div class="text-muted small mt-1">{{ $user->email }}</div>
                @if($user->getRoleNames()->isNotEmpty())
                    <div class="mt-2">
                        @foreach($user->getRoleNames() as $role)
                            <span class="badge bg-primary-lt text-primary">{{ $role }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header">
                <h3 class="card-title mb-0">Tampilan</h3>
            </div>
            <div class="card-body">
                <div class="btn-list">
                    <button type="button" class="btn btn-outline-secondary" data-theme-mode="light">
                        <i class="ti ti-sun me-1"></i>Light
                    </button>
                    <button type="button" class="btn btn-outline-secondary" data-theme-mode="dark">
                        <i class="ti ti-moon me-1"></i>Dark
                    </button>
                </div>
                <div class="text-muted small mt-2">Disimpan di browser ini.</div>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Ubah Profil</h3>
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
                        <div class="col-12">
                            <label class="form-label">Avatar</label>
                            <input type="file" name="avatar" class="form-control" accept="image/png,image/jpeg">
                            <div class="form-hint">PNG atau JPG, maks 2MB.</div>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="mb-1">
                        <div class="fw-semibold small">Ganti Password</div>
                        <div class="text-muted small">Biarkan kosong jika tidak ingin mengubah password.</div>
                    </div>
                    <div class="row g-3 mt-1">
                        <div class="col-md-6">
                            <label class="form-label">Password Baru</label>
                            <input type="password" name="password" class="form-control" placeholder="Password baru (opsional)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Konfirmasi Password</label>
                            <input type="password" name="password_confirmation" class="form-control" placeholder="Ulangi password baru">
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">
                            <i class="ti ti-device-floppy me-1"></i>Simpan
                        </button>
                        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">Batal</a>
                    </div>
                </form>
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
            btn.addEventListener('click', () => apply(btn.dataset.themeMode || 'light'));
        });
    })();
</script>
@endpush
