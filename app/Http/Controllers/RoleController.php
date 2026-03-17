<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->with(['users:id,name,email'])
            ->with('permissions:id,name')
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);
        $permissionGroups = $this->permissionGroups();

        return view('roles.index', compact('roles', 'permissionGroups'));
    }

    public function create()
    {
        $permissionGroups = $this->permissionGroups();

        return view('roles.create', compact('permissionGroups'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);

        $role = Role::create(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', 'Role ditambahkan.');
    }

    public function edit(Role $role)
    {
        $role->load([
            'users' => fn ($query) => $query->select('users.id', 'name', 'email')->orderBy('name'),
            'permissions:id,name',
        ]);
        $permissionGroups = $this->permissionGroups();
        $selectedPermissions = $role->permissions->pluck('name')->all();

        return view('roles.edit', compact('role', 'permissionGroups', 'selectedPermissions'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ]);
        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return redirect()->route('roles.index')->with('status', 'Role diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();
        return back()->with('status', 'Role dihapus.');
    }

    private function permissionGroups(): array
    {
        return Permission::query()
            ->orderBy('name')
            ->get(['id', 'name'])
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

    private function permissionLabel(string $permission): string
    {
        $segments = explode('.', $permission);
        array_shift($segments);

        if (empty($segments)) {
            return $permission;
        }

        return ucfirst(str_replace('-', ' ', str_replace('_', ' ', implode(' ', $segments))));
    }
}
