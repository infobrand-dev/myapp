<?php

namespace Tests\Feature\Core;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Finance\FinanceServiceProvider;
use App\Support\FeatureMode;
use App\Support\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class FeatureModeTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerModuleProviders([
            FinanceServiceProvider::class,
        ]);

        $this->bootstrapDefaultOperationalContext();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_starter_plan_defaults_to_standard_mode(): void
    {
        $user = $this->userWithPermissions(['settings.view']);
        $this->attachCompanyAccess($user);
        $this->activateAccountingPlan('starter-test', false);

        $request = $this->requestFor($user);

        $this->assertSame(FeatureMode::STANDARD, app(FeatureMode::class)->current($request, 'accounting', $user));
    }

    public function test_growth_plan_defaults_to_advanced_mode_and_can_be_overridden_to_standard(): void
    {
        $user = $this->userWithPermissions(['settings.view']);
        $this->attachCompanyAccess($user);
        $this->activateAccountingPlan('growth-test', true);

        $request = $this->requestFor($user);

        $this->assertSame(FeatureMode::ADVANCED, app(FeatureMode::class)->current($request, 'accounting', $user));
        $this->assertSame(FeatureMode::STANDARD, app(FeatureMode::class)->set($request, FeatureMode::STANDARD, 'accounting', $user));
        $this->assertSame(FeatureMode::STANDARD, app(FeatureMode::class)->current($request, 'accounting', $user));
    }

    public function test_starter_plan_cannot_switch_to_advanced_mode(): void
    {
        $user = $this->userWithPermissions(['settings.view']);
        $this->attachCompanyAccess($user);
        $this->activateAccountingPlan('starter-lock', false);

        $request = $this->requestFor($user);

        $this->assertSame(FeatureMode::STANDARD, app(FeatureMode::class)->set($request, FeatureMode::ADVANCED, 'accounting', $user));
        $this->assertSame(FeatureMode::STANDARD, app(FeatureMode::class)->current($request, 'accounting', $user));
    }

    public function test_advanced_only_route_is_blocked_when_growth_user_switches_to_standard(): void
    {
        $user = $this->userWithPermissions(['settings.view', 'finance.manage-coa']);
        $company = $this->attachCompanyAccess($user);
        $this->activateAccountingPlan('growth-route', true);

        $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->get(route('finance.chart-accounts.index'))
            ->assertForbidden();
    }

    private function requestFor(User $user): Request
    {
        $request = Request::create('/');
        $request->setLaravelSession(app('session')->driver());
        $request->setUserResolver(fn () => $user);

        return $request;
    }

    private function userWithPermissions(array $permissions): User
    {
        $user = User::factory()->create(['tenant_id' => 1]);

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }

    private function attachCompanyAccess(User $user): Company
    {
        $company = Company::query()->findOrFail(1);

        \DB::table('user_companies')->insert([
            'tenant_id' => 1,
            'user_id' => $user->id,
            'company_id' => $company->id,
            'is_default' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $company;
    }

    private function activateAccountingPlan(string $code, bool $advancedReports): void
    {
        $plan = SubscriptionPlan::query()->create([
            'code' => $code,
            'name' => strtoupper($code),
            'price' => 100000,
            'currency' => 'IDR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'features' => [
                PlanFeature::ACCOUNTING => true,
                PlanFeature::ADVANCED_REPORTS => $advancedReports,
            ],
            'limits' => [],
            'meta' => [
                'product_line' => 'accounting',
            ],
        ]);

        TenantSubscription::query()->updateOrCreate(
            [
                'tenant_id' => 1,
                'product_line' => 'accounting',
            ],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now()->subDay(),
                'ends_at' => now()->addMonth(),
                'feature_overrides' => [],
                'limit_overrides' => [],
            ]
        );
    }
}
