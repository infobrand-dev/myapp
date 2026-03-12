@php
    $selectedRole = old('role', $currentRole ?? '');
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name ?? '') }}" required>
        @error('name')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email ?? '') }}" required>
        @error('email')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ isset($user) ? '(opsional)' : '' }}</label>
        <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" {{ isset($user) ? '' : 'required' }} placeholder="{{ isset($user) ? 'Biarkan kosong jika tidak diganti' : '' }}">
        @error('password')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" {{ isset($user) ? '' : 'required' }}>
        @error('password_confirmation')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role" class="form-select @error('role') is-invalid @enderror" required>
            <option value="">- Pilih role -</option>
            @foreach($roles as $role)
                @php $roleName = $role->name; @endphp
                <option value="{{ $roleName }}" {{ $selectedRole === $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
            @endforeach
        </select>
        @error('role')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
        <div class="text-muted small mt-1">Hak akses user mengikuti role yang dipilih di sini dan bisa diubah lagi kapan saja dari halaman Users.</div>
    </div>
</div>
