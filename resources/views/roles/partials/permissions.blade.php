@php($selectedPermissions = $selectedPermissions ?? [])

<div class="mb-3">
    <label class="form-label">Permissions</label>
    <div class="text-muted small mb-3">
        Permission didaftarkan oleh masing-masing module. Default role diberikan saat seeding, lalu bisa diubah kapan saja dari halaman ini.
    </div>

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
