<?php

namespace Tests\Feature\Dashboard;

use App\Models\AiCreditTransaction;
use App\Models\AiUsageLog;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardAiCreditsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_03_28_130000_create_ai_usage_logs_table.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_03_28_131000_create_ai_credit_transactions_table.php',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
    }

    public function test_dashboard_shows_ai_credit_summary(): void
    {
        [$user, $tenant] = $this->makeTenantWithPlan('growth', 'tenant-dashboard-ai');

        AiUsageLog::query()->create([
            'tenant_id' => $tenant->id,
            'source_module' => 'chatbot',
            'source_type' => 'playground',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_tokens' => 1000,
            'completion_tokens' => 1000,
            'total_tokens' => 2000,
            'credits_used' => 2,
            'used_at' => now(),
        ]);

        AiCreditTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'kind' => 'top_up',
            'credits' => 100,
            'source' => 'manual_sale',
            'reference' => 'AI-TOPUP-1',
        ]);

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeText('AI Credits')
            ->assertSeeText('Monthly AI usage')
            ->assertSeeText('598');
    }

    private function makeTenantWithPlan(string $planCode, string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        $plan = SubscriptionPlan::query()->where('code', $planCode)->firstOrFail();

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'test-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$user, $tenant, $plan];
    }
}
