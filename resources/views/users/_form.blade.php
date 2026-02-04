@php
    $selectedRole = old('role', $currentRole ?? '');
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Nama</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $user->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $user->email ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Password {{ isset($user) ? '(opsional)' : '' }}</label>
        <input type="password" name="password" class="form-control" {{ isset($user) ? '' : 'required' }} placeholder="{{ isset($user) ? 'Biarkan kosong jika tidak diganti' : '' }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">Konfirmasi Password</label>
        <input type="password" name="password_confirmation" class="form-control" {{ isset($user) ? '' : 'required' }}>
    </div>
    <div class="col-md-6">
        <label class="form-label">Role</label>
        <select name="role" class="form-select" required>
            <option value="">- Pilih role -</option>
            @foreach($roles as $role)
                @php $roleName = $role->name; @endphp
                <option value="{{ $roleName }}" {{ $selectedRole === $roleName ? 'selected' : '' }}>{{ $roleName }}</option>
            @endforeach
        </select>
    </div>
</div>
