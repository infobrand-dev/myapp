@php
    $selectedPermissions = $selectedPermissions ?? [];
    $inactiveAssignedPermissions = $inactiveAssignedPermissions ?? [];
@endphp

<div class="mb-3">
    <label class="form-label">Permissions</label>
    <div class="text-muted small mb-3">
        Hanya permission core dan module yang sedang aktif yang ditampilkan di sini. Permission dari module yang belum aktif tidak bisa diubah dari form ini.
    </div>

    @if(!empty($inactiveAssignedPermissions))
        <div class="alert alert-warning">
            Role ini masih memiliki permission dari module yang belum aktif:
            {{ implode(', ', $inactiveAssignedPermissions) }}.
            Permission tersebut dipertahankan sampai module terkait aktif kembali atau dibersihkan manual.
        </div>
    @endif

    @if(empty($permissionGroups))
        <div class="alert alert-secondary mb-0">Belum ada permission yang terdaftar.</div>
    @else
        <div class="row g-3">
            @foreach($permissionGroups as $group)
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title mb-0">{{ $group['label'] }}</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex flex-column gap-2">
                                @foreach($group['permissions'] as $permission)
                                    <label class="form-check">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission['name'] }}"
                                            @checked(in_array($permission['name'], old('permissions', $selectedPermissions), true))
                                        >
                                        <span class="form-check-label">
                                            <span class="fw-semibold">{{ $permission['label'] }}</span>
                                            <span class="text-muted small d-block">{{ $permission['name'] }}</span>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
