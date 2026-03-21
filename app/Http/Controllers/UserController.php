<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\UserAccessManager;
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
            ->with(['roles', 'companies', 'branches'])
            ->latest()
            ->paginate(15);

        return view('users.index', compact('users'));
    }

    public function create()
    {
        $roles = $this->tenantRolesQuery()->orderBy('name')->get();
        [$companies, $branchesByCompany] = $this->accessOptions();

        return view('users.create', compact('roles', 'companies', 'branchesByCompany'));
    }

    public function store(Request $request, UserAccessManager $userAccessManager): RedirectResponse
    {
        app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::USERS);

        $data = $this->validateUser($request);

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
        $userAccessManager->sync(
            $user,
            $data['company_ids'] ?? [],
            $data['branch_ids'] ?? [],
            $data['default_company_id'] ?? null,
            $data['default_branch_id'] ?? null
        );

        return redirect()->route('users.index')->with('status', 'User ditambahkan.');
    }

    public function edit(User $user, UserAccessManager $userAccessManager)
    {
        $roles = $this->tenantRolesQuery()->orderBy('name')->get();
        $currentRole = $user->roles->pluck('name')->first();
        [$companies, $branchesByCompany] = $this->accessOptions();
        $selectedCompanyIds = optional($userAccessManager->companyIdsFor($user))->all() ?: [];
        $selectedBranchIds = optional($userAccessManager->branchIdsFor($user))->all() ?: [];
        $defaultCompanyId = $userAccessManager->defaultCompanyIdFor($user);
        $defaultBranchId = $userAccessManager->defaultBranchIdFor($user);

        return view('users.edit', compact(
            'user',
            'roles',
            'currentRole',
            'companies',
            'branchesByCompany',
            'selectedCompanyIds',
            'selectedBranchIds',
            'defaultCompanyId',
            'defaultBranchId'
        ));
    }

    public function update(Request $request, User $user, UserAccessManager $userAccessManager): RedirectResponse
    {
        $data = $this->validateUser($request, $user);

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
        $userAccessManager->sync(
            $user,
            $data['company_ids'] ?? [],
            $data['branch_ids'] ?? [],
            $data['default_company_id'] ?? null,
            $data['default_branch_id'] ?? null
        );

        if (auth()->id() === $user->id) {
            $request->session()->forget(['company_id', 'company_slug', 'branch_id', 'branch_slug']);
            CompanyContext::forget();
            BranchContext::forget();
        }

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

    private function validateUser(Request $request, ?User $user = null): array
    {
        $tenantId = TenantContext::currentId();

        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->where(fn ($query) => $query->where('tenant_id', $tenantId))->ignore(optional($user)->id)],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', 'min:8'],
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where(fn ($query) => $query->where('tenant_id', $tenantId)->where('guard_name', 'web'))],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'default_company_id' => ['nullable', 'integer', Rule::exists('companies', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
            'default_branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where(fn ($query) => $query->where('tenant_id', $tenantId))],
        ]);
    }

    private function accessOptions(): array
    {
        $companies = Company::query()
            ->where('tenant_id', TenantContext::currentId())
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        $branchesByCompany = Branch::query()
            ->where('tenant_id', TenantContext::currentId())
            ->with('company:id,name')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get(['id', 'company_id', 'name', 'is_active'])
            ->groupBy('company_id');

        return [$companies, $branchesByCompany];
    }
}
