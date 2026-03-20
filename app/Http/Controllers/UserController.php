<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->where('tenant_id', TenantContext::currentId())
            ->with('roles')
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->tenantRolesQuery()->orderBy('name')->get();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::USERS);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))],
            'password' => ['required', 'confirmed', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())->where('guard_name', 'web'))],
        ]);

        $role = $this->tenantRolesQuery()
            ->where('name', $data['role'])
            ->firstOrFail();

        $user = User::create([
            'tenant_id' => TenantContext::currentId(),
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);

        $user->syncRoles([$role->name]);

        return redirect()->route('users.index')->with('status', 'User ditambahkan.');
    }

    public function edit(User $user)
    {
        $roles = $this->tenantRolesQuery()->orderBy('name')->get();
        $currentRole = $user->roles->pluck('name')->first();
        return view('users.edit', compact('user', 'roles', 'currentRole'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))->ignore($user->id)],
            'password' => ['nullable', 'confirmed', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId())->where('guard_name', 'web'))],
        ]);

        $role = $this->tenantRolesQuery()
            ->where('name', $data['role'])
            ->firstOrFail();

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
        ];
        if (!empty($data['password'])) {
            $payload['password'] = bcrypt($data['password']);
        }

        $user->update($payload);
        $user->syncRoles([$role->name]);

        return redirect()->route('users.index')->with('status', 'User diperbarui.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if (auth()->id() === $user->id) {
            return back()->with('status', 'Tidak bisa menghapus akun sendiri.');
        }
        $user->delete();
        return back()->with('status', 'User dihapus.');
    }

    private function tenantRolesQuery()
    {
        return Role::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('guard_name', 'web');
    }
}
