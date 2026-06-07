<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Services\PlatformActivityRecorder;
use App\Services\PlatformAuditLogger;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use App\Support\TenantRoleCatalog;
use App\Support\TenantRoleProvisioner;
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

        $roles = $this->tenantRolesQuery()->get()->sortBy(function ($role) {
            return sprintf('%04d-%s', TenantRoleCatalog::sortOrder($role->name), $role->name);
        })->values();
        [$companies, $branchesByCompany] = $this->accessOptions();
        $invitations = \App\Models\UserInvitation::query()
            ->where('tenant_id', TenantContext::currentId())
            ->pending()
            ->latest('id')
            ->limit(10)
            ->get();

        return view('users.index', compact('users', 'roles', 'companies', 'branchesByCompany', 'invitations'));
    }

    public function create()
    {
        $roles = $this->tenantRolesQuery()->get()->sortBy(function ($role) {
            return sprintf('%04d-%s', TenantRoleCatalog::sortOrder($role->name), $role->name);
        })->values();
        [$companies, $branchesByCompany] = $this->accessOptions();
        $roleDescriptions = $this->roleDescriptions($roles);

        return view('users.create', compact('roles', 'companies', 'branchesByCompany', 'roleDescriptions'));
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

        event(new Registered($user));

        $user->syncRoles([$role->name]);
        $userAccessManager->sync(
            $user,
            $data['company_ids'] ?? [],
            $data['branch_ids'] ?? [],
            $data['default_company_id'] ?? null,
            $data['default_branch_id'] ?? null
        );
        app(PlatformAuditLogger::class)->logModel(
            'user.created',
            $user,
            ['name', 'email', 'role', 'company_ids', 'branch_ids', 'default_company_id', 'default_branch_id'],
            null,
            $this->userAuditSnapshot($user, $role->name, $data)
        );
        app(PlatformActivityRecorder::class)->record(
            'core',
            'user.created',
            User::class,
            $user->getKey(),
            'User ' . $user->name . ' dibuat.',
            $this->userActivityPayload($user, $role->name, $data),
            $this->userActivityActions($user)
        );

        return redirect()->route('users.index')->with('status', 'User ditambahkan. Email verifikasi sudah dikirim.');
    }

    public function edit(User $user, UserAccessManager $userAccessManager)
    {
        $roles = $this->tenantRolesQuery()->get()->sortBy(function ($role) {
            return sprintf('%04d-%s', TenantRoleCatalog::sortOrder($role->name), $role->name);
        })->values();
        $currentRole = $user->roles->pluck('name')->first();
        [$companies, $branchesByCompany] = $this->accessOptions();
        $selectedCompanyIds = optional($userAccessManager->companyIdsFor($user))->all() ?: [];
        $selectedBranchIds = optional($userAccessManager->branchIdsFor($user))->all() ?: [];
        $defaultCompanyId = $userAccessManager->defaultCompanyIdFor($user);
        $defaultBranchId = $userAccessManager->defaultBranchIdFor($user);
        $roleDescriptions = $this->roleDescriptions($roles);

        return view('users.edit', compact(
            'user',
            'roles',
            'currentRole',
            'companies',
            'branchesByCompany',
            'selectedCompanyIds',
            'selectedBranchIds',
            'defaultCompanyId',
            'defaultBranchId',
            'roleDescriptions'
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
        $before = $this->userAuditSnapshot($user, $user->roles->pluck('name')->first(), [
            'company_ids' => optional($userAccessManager->companyIdsFor($user))->all() ?: [],
            'branch_ids' => optional($userAccessManager->branchIdsFor($user))->all() ?: [],
            'default_company_id' => $userAccessManager->defaultCompanyIdFor($user),
            'default_branch_id' => $userAccessManager->defaultBranchIdFor($user),
        ]);
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
        $after = $this->userAuditSnapshot($user->fresh('roles'), $role->name, $data);
        app(PlatformAuditLogger::class)->logModel(
            'user.updated',
            $user,
            ['name', 'email', 'role', 'company_ids', 'branch_ids', 'default_company_id', 'default_branch_id'],
            $before,
            $after
        );
        app(PlatformActivityRecorder::class)->record(
            'core',
            'user.updated',
            User::class,
            $user->getKey(),
            'User ' . $user->name . ' diperbarui.',
            $this->userActivityPayload($user, $role->name, $data),
            $this->userActivityActions($user)
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
            return back()->with('error', 'Tidak bisa menghapus akun sendiri.');
        }

        // Prevent deleting the last Super-admin in the tenant.
        if ($user->hasRole('Super-admin')) {
            $superAdminCount = User::query()
                ->where('tenant_id', TenantContext::currentId())
                ->whereHas('roles', fn ($q) => $q->where('name', 'Super-admin'))
                ->count();

            if ($superAdminCount <= 1) {
                return back()->with('error', 'Tidak dapat menghapus satu-satunya Super-admin. Tetapkan Super-admin lain terlebih dahulu.');
            }
        }

        app(PlatformAuditLogger::class)->logModel(
            'user.deleted',
            $user,
            ['name', 'email', 'role'],
            $this->userAuditSnapshot($user, $user->roles->pluck('name')->first(), [
                'company_ids' => optional(app(UserAccessManager::class)->companyIdsFor($user))->all() ?: [],
                'branch_ids' => optional(app(UserAccessManager::class)->branchIdsFor($user))->all() ?: [],
                'default_company_id' => app(UserAccessManager::class)->defaultCompanyIdFor($user),
                'default_branch_id' => app(UserAccessManager::class)->defaultBranchIdFor($user),
            ]),
            null
        );
        app(PlatformActivityRecorder::class)->record(
            'core',
            'user.deleted',
            User::class,
            $user->getKey(),
            'User ' . $user->name . ' dihapus.',
            [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->roles->pluck('name')->first(),
            ]
        );
        $user->delete();

        return back()->with('status', 'User dihapus.');
    }

    private function tenantRolesQuery()
    {
        app(TenantRoleProvisioner::class)->ensureForTenant(TenantContext::currentId());

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

    private function roleDescriptions($roles): array
    {
        return collect($roles)
            ->mapWithKeys(fn ($role) => [$role->name => TenantRoleCatalog::description($role->name)])
            ->filter(fn (?string $description) => filled($description))
            ->all();
    }

    private function userAuditSnapshot(User $user, ?string $roleName, array $data): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleName,
            'company_ids' => array_values($data['company_ids'] ?? []),
            'branch_ids' => array_values($data['branch_ids'] ?? []),
            'default_company_id' => $data['default_company_id'] ?? null,
            'default_branch_id' => $data['default_branch_id'] ?? null,
        ];
    }

    private function userActivityPayload(User $user, ?string $roleName, array $data): array
    {
        return [
            'name' => $user->name,
            'email' => $user->email,
            'role' => $roleName,
            'company_ids' => array_values($data['company_ids'] ?? []),
            'branch_ids' => array_values($data['branch_ids'] ?? []),
        ];
    }

    private function userActivityActions(User $user): array
    {
        return [[
            'label' => 'Buka user',
            'url' => route('users.edit', ['user' => $user->getKey()]),
        ]];
    }
}
