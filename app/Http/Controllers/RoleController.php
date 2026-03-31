<?php

namespace App\Http\Controllers;

use App\Support\CorePermissions;
use App\Support\ModuleManager;
use App\Support\TenantContext;
use App\Support\TenantRoleProvisioner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(ModuleManager $modules)
    {
        $roles = $this->tenantRolesQuery()
            ->with(['users:id,name,email'])
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);
        $permissionGroups = $this->permissionGroups($modules);

        return view('roles.index', compact('roles', 'permissionGroups'));
    }

    public function create(ModuleManager $modules)
    {
        $permissionGroups = $this->permissionGroups($modules);

        return view('roles.create', compact('permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())->where('guard_name', 'web'))],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
            'tenant_id' => TenantContext::currentId(),
        ]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', 'Role ditambahkan.');
    }

    public function edit(int $role, ModuleManager $modules)
    {
        $role = $this->findTenantRole($role);
        $role->load([
            'users' => fn ($query) => $query->select('users.id', 'name', 'email')->orderBy('name'),
            'permissions:id,name',
        ]);
        $permissionGroups = $this->permissionGroups($modules);
        $selectedPermissions = $role->permissions->pluck('name')->all();
        $visiblePermissions = collect($permissionGroups)->flatMap(fn (array $group) => collect($group['permissions'])->pluck('name'))->all();
        $inactiveAssignedPermissions = $role->permissions
            ->pluck('name')
            ->reject(fn (string $permission) => in_array($permission, $visiblePermissions, true))
            ->values()
            ->all();

        return view('roles.edit', compact('role', 'permissionGroups', 'selectedPermissions', 'inactiveAssignedPermissions'));
    }

    public function update(Request $request, int $role, ModuleManager $modules): RedirectResponse
    {
        $role = $this->findTenantRole($role);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())->where('guard_name', 'web'))->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $visiblePermissions = collect($this->permissionGroups($modules))
            ->flatMap(fn (array $group) => collect($group['permissions'])->pluck('name'))
            ->all();
        $inactiveAssignedPermissions = $role->permissions
            ->pluck('name')
            ->reject(fn (string $permission) => in_array($permission, $visiblePermissions, true))
            ->all();

        $role->update(['name' => $data['name']]);
        $role->syncPermissions(array_values(array_unique(array_merge(
            $data['permissions'] ?? [],
            $inactiveAssignedPermissions
        ))));

        return redirect()->route('roles.index')->with('status', 'Role diperbarui.');
    }

    public function destroy(int $role): RedirectResponse
    {
        $role = $this->findTenantRole($role);
        $role->delete();
        return back()->with('status', 'Role dihapus.');
    }

    private function permissionGroups(ModuleManager $modules): array
    {
        $visibleGroups = array_fill_keys($this->visiblePermissionGroups($modules), true);

        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->filter(function (Permission $permission) use ($visibleGroups) {
                $segments = explode('.', $permission->name, 2);
                $group = $segments[0] ?? 'general';

                return isset($visibleGroups[$group]);
            })
            ->groupBy(function (Permission $permission) {
                $segments = explode('.', $permission->name, 2);

                return $segments[0] ?? 'general';
            })
            ->map(function ($permissions, $group) {
                return [
                    'key' => $group,
                    'label' => ucfirst(str_replace('-', ' ', str_replace('_', ' ', (string) $group))),
                    'permissions' => $permissions->map(function (Permission $permission) {
                        return [
                            'name' => $permission->name,
                            'label' => $this->permissionLabel($permission->name),
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    private function visiblePermissionGroups(ModuleManager $modules): array
    {
        $coreGroups = collect(CorePermissions::PERMISSIONS)
            ->map(fn (string $permission) => explode('.', $permission, 2)[0] ?? 'general');

        $activeModuleGroups = collect($modules->all())
            ->filter(fn (array $module) => $module['installed'] && $module['active'])
            ->pluck('slug');

        return $coreGroups
            ->merge($activeModuleGroups)
            ->unique()
            ->values()
            ->all();
    }

    private function permissionLabel(string $permission): string
    {
        $segments = explode('.', $permission);
        array_shift($segments);

        if (empty($segments)) {
            return $permission;
        }

        return ucfirst(str_replace('-', ' ', str_replace('_', ' ', implode(' ', $segments))));
    }

    private function tenantRolesQuery()
    {
        app(TenantRoleProvisioner::class)->ensureForTenant(TenantContext::currentId());

        return Role::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('guard_name', 'web');
    }

    private function findTenantRole(int $roleId): Role
    {
        return $this->tenantRolesQuery()->findOrFail($roleId);
    }
}
