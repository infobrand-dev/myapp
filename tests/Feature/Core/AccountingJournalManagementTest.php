<?php

namespace Tests\Feature\Core;

use App\Models\AccountingJournal;
use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Finance\FinanceServiceProvider;
use App\Support\CompanyContext;
use App\Support\PlanFeature;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AccountingJournalManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(FinanceServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_04_12_130000_create_accounting_governance_tables.php',
            '--realpath' => false,
        ])->run();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);
    }

    public function test_user_can_create_manual_journal_as_draft(): void
    {
        $user = $this->financeUser(['finance.view-journal', 'finance.manage-journal']);

        $this->actingAs($user)
            ->withSession([
                'company_id' => 1,
                'company_slug' => 'default-company',
            ])
            ->post(route('finance.journals.store'), [
                'entry_date' => now()->format('Y-m-d H:i:s'),
                'status' => 'draft',
                'description' => 'Opening balance adjustment',
                'lines' => [
                    [
                        'account_code' => 'CASH',
                        'account_name' => 'Cash',
                        'debit' => 500000,
                        'credit' => 0,
                        'notes' => 'Kas kecil awal',
                    ],
                    [
                        'account_code' => 'EQUITY',
                        'account_name' => 'Owner Equity',
                        'debit' => 0,
                        'credit' => 500000,
                        'notes' => 'Modal awal',
                    ],
                ],
            ])
            ->assertRedirect(route('finance.journals.index'));

        $journal = AccountingJournal::query()
            ->where('tenant_id', 1)
            ->where('company_id', 1)
            ->where('entry_type', 'manual')
            ->firstOrFail();

        $this->assertSame('draft', $journal->status);
        $this->assertSame('Opening balance adjustment', $journal->description);
        $this->assertCount(2, $journal->lines);
        $this->assertSame('Kas kecil awal', data_get($journal->lines->first()->meta, 'notes'));
    }

    public function test_user_can_post_existing_manual_journal(): void
    {
        $user = $this->financeUser(['finance.view-journal', 'finance.manage-journal']);

        $journal = AccountingJournal::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'entry_type' => 'manual',
            'source_type' => AccountingJournal::class,
            'source_id' => 999,
            'journal_number' => 'JRNL-MANUAL-TEST',
            'entry_date' => now(),
            'status' => 'draft',
            'description' => 'Draft adjustment',
            'meta' => ['manual' => true],
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);

        $journal->forceFill(['source_id' => $journal->id])->save();

        $journal->lines()->createMany([
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 1,
                'account_code' => 'AR',
                'account_name' => 'Accounts Receivable',
                'debit' => 100000,
                'credit' => 0,
            ],
            [
                'tenant_id' => 1,
                'company_id' => 1,
                'line_no' => 2,
                'account_code' => 'SALES',
                'account_name' => 'Sales Revenue',
                'debit' => 0,
                'credit' => 100000,
            ],
        ]);

        $this->actingAs($user)
            ->withSession([
                'company_id' => 1,
                'company_slug' => 'default-company',
            ])
            ->post(route('finance.journals.post', $journal->id))
            ->assertRedirect(route('finance.journals.index'));

        $this->assertSame('posted', $journal->fresh()->status);
    }

    private function financeUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->givePermissionTo($permissions);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'accounting-test-' . uniqid(),
            'name' => 'Accounting Test',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 1,
            'features' => [
                PlanFeature::ACCOUNTING => true,
            ],
            'limits' => [],
            'meta' => [
                'product_line' => 'accounting',
            ],
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => 1,
            'subscription_plan_id' => $plan->id,
            'product_line' => 'accounting',
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'acct-test-' . uniqid(),
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return $user;
    }
}
