<?php

namespace Tests\Feature\Finance;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Finance\FinanceServiceProvider;
use App\Modules\Finance\Models\FinanceAccount;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Support\FeatureMode;
use App\Support\PlanFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\TestCase;

class FinanceTransactionStandardModeTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('FinanceTransactionStandardModeTest harus dijalankan di PostgreSQL atau database non-SQLite yang setara dengan runtime aplikasi.');
        }

        $this->registerModuleProviders([
            FinanceServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'app/Modules/Finance/database/migrations',
            'app/Modules/PointOfSale/database/migrations',
        ]);

        $this->bootstrapDefaultOperationalContext(companyAttributes: [
            'meta' => [],
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_standard_mode_can_create_non_transfer_finance_transaction(): void
    {
        $user = $this->userWithPermissions(['finance.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();
        [$account, $category] = $this->financeSetup();

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('finance.transactions.store'), [
                'entry_mode' => FinanceTransaction::ENTRY_MODE_STANDARD,
                'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
                'transaction_date' => now()->format('Y-m-d H:i:s'),
                'amount' => 15000,
                'finance_account_id' => $account->id,
                'finance_category_id' => $category->id,
                'notes' => 'Standard expense',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('finance_transactions', [
            'tenant_id' => 1,
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'amount' => 15000,
        ]);
    }

    public function test_standard_mode_rejects_transfer_finance_transaction(): void
    {
        $user = $this->userWithPermissions(['finance.create']);
        $company = $this->attachCompanyAccess($user);
        $this->activateStarterPlan();
        [$account] = $this->financeSetup();

        $targetAccount = FinanceAccount::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Bank B',
            'slug' => 'bank-b',
            'account_type' => FinanceAccount::TYPE_BANK,
            'currency_code' => 'IDR',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession([
                'company_id' => $company->id,
                'company_slug' => $company->slug,
                FeatureMode::SESSION_KEY => FeatureMode::STANDARD,
            ])
            ->post(route('finance.transactions.store'), [
                'entry_mode' => FinanceTransaction::ENTRY_MODE_TRANSFER,
                'transaction_type' => FinanceTransaction::TYPE_CASH_OUT,
                'transaction_date' => now()->format('Y-m-d H:i:s'),
                'amount' => 15000,
                'finance_account_id' => $account->id,
                'counterparty_finance_account_id' => $targetAccount->id,
                'notes' => 'Blocked transfer',
            ]);

        $response->assertSessionHasErrors('entry_mode');
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

    private function activateStarterPlan(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'code' => 'accounting-starter-finance',
            'name' => 'Starter Finance',
            'price' => 100000,
            'currency' => 'IDR',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'features' => [
                PlanFeature::ACCOUNTING => true,
                PlanFeature::ADVANCED_REPORTS => false,
            ],
            'limits' => [],
            'meta' => ['product_line' => 'accounting'],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => 1,
            'subscription_plan_id' => $plan->id,
            'product_line' => 'accounting',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
        ]);
    }

    private function financeSetup(): array
    {
        $account = FinanceAccount::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Kas Utama',
            'slug' => 'kas-utama',
            'account_type' => FinanceAccount::TYPE_CASH,
            'currency_code' => 'IDR',
            'is_active' => true,
            'is_default' => true,
        ]);

        $category = FinanceCategory::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'name' => 'Biaya Operasional',
            'slug' => 'biaya-operasional',
            'transaction_type' => FinanceTransaction::TYPE_EXPENSE,
            'is_active' => true,
        ]);

        return [$account, $category];
    }
}
