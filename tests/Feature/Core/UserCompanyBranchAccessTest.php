<?php

namespace Tests\Feature\Core;

use App\Models\Branch;
use App\Models\Company;
use App\Models\User;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use App\Support\CorePermissions;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UserCompanyBranchAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_create_and_update_can_store_company_and_branch_access(): void
    {
        $admin = $this->adminUser();
        $role = Role::findOrCreate('Manager', 'web');
        $role->tenant_id = 1;
        $role->save();

        $companyA = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Alpha',
            'slug' => 'pt-alpha',
            'code' => 'ALPHA',
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Beta',
            'slug' => 'pt-beta',
            'code' => 'BETA',
            'is_active' => true,
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Barat',
            'slug' => 'alpha-barat',
            'code' => 'AB',
            'is_active' => true,
        ]);

        $branchB = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyB->id,
            'name' => 'Beta Timur',
            'slug' => 'beta-timur',
            'code' => 'BT',
            'is_active' => true,
        ]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Scoped User',
                'email' => 'scoped@example.test',
                'password' => 'password123',
                'password_confirmation' => 'password123',
                'role' => 'Manager',
                'company_ids' => [$companyA->id, $companyB->id],
                'default_company_id' => $companyB->id,
                'branch_ids' => [$branchB->id],
                'default_branch_id' => $branchB->id,
            ])
            ->assertRedirect(route('users.index'));

        $user = User::query()->where('tenant_id', 1)->where('email', 'scoped@example.test')->firstOrFail();

        $this->assertDatabaseHas('user_companies', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'is_default' => false,
        ]);

        $this->assertDatabaseHas('user_companies', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyB->id,
            'is_default' => true,
        ]);

        $this->assertDatabaseHas('user_branches', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyB->id,
            'branch_id' => $branchB->id,
            'is_default' => true,
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $user), [
                'name' => 'Scoped User Updated',
                'email' => 'scoped@example.test',
                'role' => 'Manager',
                'company_ids' => [$companyA->id],
                'default_company_id' => $companyA->id,
                'branch_ids' => [$branchA->id],
                'default_branch_id' => $branchA->id,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('user_companies', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyB->id,
        ]);

        $this->assertDatabaseHas('user_branches', [
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'is_default' => true,
        ]);
    }

    public function test_company_and_branch_context_respect_user_membership(): void
    {
        $companyA = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Alpha',
            'slug' => 'pt-alpha',
            'code' => 'ALPHA',
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Beta',
            'slug' => 'pt-beta',
            'code' => 'BETA',
            'is_active' => true,
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Barat',
            'slug' => 'alpha-barat',
            'code' => 'AB',
            'is_active' => true,
        ]);

        $user = User::factory()->create(['tenant_id' => 1]);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_branches')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        TenantContext::setCurrentId(1);
        $this->be($user);

        $companyRequest = Request::create('/dashboard', 'GET', ['company_id' => $companyB->id]);
        $companyRequest->setLaravelSession(app('session.store'));
        $companyId = CompanyContext::resolveIdFromRequest($companyRequest);

        CompanyContext::setCurrentId($companyId);

        $branchRequest = Request::create('/dashboard', 'GET', ['branch_id' => $branchA->id]);
        $branchRequest->setLaravelSession(app('session.store'));
        $branchId = BranchContext::resolveIdFromRequest($branchRequest);

        $this->assertSame($companyA->id, $companyId);
        $this->assertSame($branchA->id, $branchId);
    }

    public function test_switch_company_and_branch_routes_forbid_scope_outside_membership(): void
    {
        $companyA = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Alpha',
            'slug' => 'pt-alpha',
            'code' => 'ALPHA',
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Beta',
            'slug' => 'pt-beta',
            'code' => 'BETA',
            'is_active' => true,
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Barat',
            'slug' => 'alpha-barat',
            'code' => 'AB',
            'is_active' => true,
        ]);

        $branchB = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Timur',
            'slug' => 'alpha-timur',
            'code' => 'AT',
            'is_active' => true,
        ]);

        $user = $this->settingsUser();

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_branches')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('settings.company.switch', $companyB))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession([
                'company_id' => $companyA->id,
                'company_slug' => $companyA->slug,
            ])
            ->post(route('settings.branch.switch', $branchB))
            ->assertForbidden();

        $this->actingAs($user)
            ->withSession([
                'company_id' => $companyA->id,
                'company_slug' => $companyA->slug,
            ])
            ->post(route('settings.branch.switch', $branchA))
            ->assertSessionHas('branch_id', $branchA->id);
    }

    public function test_settings_topbar_switcher_only_shows_allowed_company_and_branch_options(): void
    {
        $companyA = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Alpha',
            'slug' => 'pt-alpha',
            'code' => 'ALPHA',
            'is_active' => true,
        ]);

        $companyB = Company::query()->create([
            'tenant_id' => 1,
            'name' => 'PT Beta',
            'slug' => 'pt-beta',
            'code' => 'BETA',
            'is_active' => true,
        ]);

        $branchA = Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Barat',
            'slug' => 'alpha-barat',
            'code' => 'AB',
            'is_active' => true,
        ]);

        Branch::query()->create([
            'tenant_id' => 1,
            'company_id' => $companyA->id,
            'name' => 'Alpha Timur',
            'slug' => 'alpha-timur',
            'code' => 'AT',
            'is_active' => true,
        ]);

        $user = $this->settingsUser();

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \DB::table('user_branches')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $companyA->id,
            'branch_id' => $branchA->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->withSession([
                'company_id' => $companyA->id,
                'company_slug' => $companyA->slug,
            ])
            ->get(route('settings.general'))
            ->assertOk()
            ->assertSee('PT Alpha')
            ->assertSee('Alpha Barat')
            ->assertDontSee('PT Beta')
            ->assertDontSee('Alpha Timur');
    }

    private function adminUser(): User
    {
        foreach (['users.view', 'users.create', 'users.update'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('Super-admin', 'web');
        $role->tenant_id = 1;
        $role->save();
        $role->syncPermissions(['users.view', 'users.create', 'users.update']);

        $user = User::factory()->create(['tenant_id' => 1]);
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->assignRole($role);

        return $user;
    }

    private function settingsUser(): User
    {
        foreach (['settings.view', 'settings.manage'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $role = Role::findOrCreate('Settings-admin', 'web');
        $role->tenant_id = 1;
        $role->save();
        $role->syncPermissions(['settings.view', 'settings.manage']);

        $user = User::factory()->create(['tenant_id' => 1]);
        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->assignRole($role);

        return $user;
    }
}
