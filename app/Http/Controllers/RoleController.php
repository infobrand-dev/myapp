<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::query()
            ->with(['users:id,name,email'])
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        $roleAccessMap = $this->roleAccessMap();

        return view('roles.index', compact('roles', 'roleAccessMap'));
    }

    public function create()
    {
        return view('roles.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);
        Role::create(['name' => $data['name']]);

        return redirect()->route('roles.index')->with('status', 'Role ditambahkan.');
    }

    public function edit(Role $role)
    {
        $role->load(['users' => fn ($query) => $query->select('users.id', 'name', 'email')->orderBy('name')]);
        $roleAccess = $this->roleAccessMap()[$role->name] ?? $this->defaultRoleAccess();

        return view('roles.edit', compact('role', 'roleAccess'));
    }

    public function update(Request $request, Role $role): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role->id)],
        ]);
        $role->update(['name' => $data['name']]);

        return redirect()->route('roles.index')->with('status', 'Role diperbarui.');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $role->delete();
        return back()->with('status', 'Role dihapus.');
    }

    private function roleAccessMap(): array
    {
        return [
            'Super-admin' => [
                'summary' => 'Akses penuh ke dashboard, users, roles, modules, dan seluruh fitur admin.',
                'items' => [
                    'Dashboard',
                    'Profile',
                    'Users',
                    'Roles',
                    'Modules',
                    'Semua module yang aktif',
                ],
            ],
            'Admin' => [
                'summary' => 'Akses operasional ke dashboard dan module kerja, tanpa manajemen users, roles, atau modules.',
                'items' => [
                    'Dashboard',
                    'Profile',
                    'Contacts',
                    'Conversations',
                    'Task/Internal Memo jika module aktif',
                    'WhatsApp/Email tools sesuai module aktif',
                ],
            ],
        ];
    }

    private function defaultRoleAccess(): array
    {
        return [
            'summary' => 'Akses mengikuti middleware dan navigasi yang dipasang untuk role ini.',
            'items' => [
                'Profile',
                'Fitur/module yang mengizinkan role ini',
            ],
        ];
    }
}
