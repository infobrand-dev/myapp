<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TenantRoleProvisioner
{
    public function ensureForTenant(?int $tenantId = null): void
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('roles')) {
            return;
        }

        $tenantId ??= TenantContext::currentId();
        $registrar = app(PermissionRegistrar::class);
        $originalTeamId = $registrar->getPermissionsTeamId();
        $availablePermissions = Permission::query()->pluck('name')->flip();

        $registrar->setPermissionsTeamId($tenantId);

        try {
            foreach ($this->defaultRolePermissions() as $roleName => $permissions) {
                $role = Role::findOrCreate($roleName, 'web');

                $role->syncPermissions(
                    collect($permissions)
                        ->filter(fn (string $permission) => $availablePermissions->has($permission))
                        ->unique()
                        ->values()
                        ->all()
                );
            }
        } finally {
            $registrar->setPermissionsTeamId($originalTeamId);
            $registrar->forgetCachedPermissions();
        }
    }

    public function ensureForAllTenants(): void
    {
        if (!Schema::hasTable('tenants')) {
            $this->ensureForTenant(1);

            return;
        }

        $tenantIds = Tenant::query()
            ->active()
            ->orderBy('id')
            ->pluck('id');

        if ($tenantIds->isEmpty()) {
            $this->ensureForTenant(1);

            return;
        }

        foreach ($tenantIds as $tenantId) {
            $this->ensureForTenant((int) $tenantId);
        }
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function defaultRolePermissions(): array
    {
        $definitions = CorePermissions::DEFAULT_ROLE_PERMISSIONS;

        foreach ($this->roleSources() as $source) {
            if (!defined($source . '::DEFAULT_ROLE_PERMISSIONS')) {
                continue;
            }

            foreach ($source::DEFAULT_ROLE_PERMISSIONS as $roleName => $permissions) {
                $definitions[$roleName] = array_values(array_unique(array_merge(
                    $definitions[$roleName] ?? [],
                    $permissions
                )));
            }
        }

        return $definitions;
    }

    /**
     * @return array<int, class-string>
     */
    private function roleSources(): array
    {
        $providers = [];

        foreach (app(ModuleManager::class)->all() as $module) {
            $provider = $module['provider'] ?? null;

            if (is_string($provider) && $provider !== '' && class_exists($provider)) {
                $providers[] = $provider;
            }
        }

        return array_values(array_unique($providers));
    }
}
