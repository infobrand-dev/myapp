<?php

namespace Tests\Feature\Chatbot;

use App\Models\AiUsageLog;
use App\Models\AiCreditTransaction;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Models\ChatbotAccount;
use App\Services\AiUsageService;
use App\Support\PlanLimit;
use App\Support\TenantPlanManager;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ChatbotAiCreditPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->register(ChatbotServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/Chatbot/database/migrations',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_03_28_130000_create_ai_usage_logs_table.php',
            '--force' => true,
        ]);
        $this->artisan('migrate', [
            '--path' => 'database/migrations/2026_03_28_131000_create_ai_credit_transactions_table.php',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutMiddleware(RoleMiddleware::class);
    }

    public function test_rule_only_account_does_not_use_ai_playground(): void
    {
        [$user] = $this->makeSuperAdminWithPlan('growth', 'tenant-credit-a');

        $account = ChatbotAccount::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Rules Bot',
            'provider' => 'openai',
            'automation_mode' => 'rule_only',
            'operation_mode' => 'ai_only',
            'api_key' => 'rule-only-disabled',
            'status' => 'active',
            'response_style' => 'balanced',
        ]);

        $this->actingAs($user)
            ->post('/chatbot/playground/send', [
                'chatbot_account_id' => $account->id,
                'message' => 'Halo bot',
            ])
            ->assertRedirect('/chatbot/playground');

        $this->assertDatabaseCount('chatbot_messages', 0);
    }

    public function test_ai_credit_usage_is_counted_from_monthly_logs(): void
    {
        [, $tenant] = $this->makeSuperAdminWithPlan('growth', 'tenant-credit-b');

        AiUsageLog::query()->create([
            'tenant_id' => $tenant->id,
            'source_module' => 'chatbot',
            'source_type' => 'playground',
            'provider' => 'openai',
            'model' => 'gpt-4o-mini',
            'prompt_tokens' => 1200,
            'completion_tokens' => 300,
            'total_tokens' => 1500,
            'credits_used' => 2,
            'used_at' => now(),
        ]);

        AiCreditTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'kind' => 'top_up',
            'credits' => 100,
            'source' => 'manual_sale',
            'reference' => 'AI-TOPUP-2',
        ]);

        $planManager = app(TenantPlanManager::class);
        $summary = app(AiUsageService::class)->summary($tenant->id);

        $this->assertSame(500, $planManager->limit(PlanLimit::AI_CREDITS_MONTHLY, $tenant->id));
        $this->assertSame(2, $planManager->usage(PlanLimit::AI_CREDITS_MONTHLY, $tenant->id));
        $this->assertSame(2, app(AiUsageService::class)->creditsForTokens(1500));
        $this->assertSame(100, $summary['top_up']);
        $this->assertSame(600, $summary['available']);
        $this->assertSame(598, $summary['remaining']);
    }

    private function makeSuperAdminWithPlan(string $planCode, string $slug): array
    {
        $tenant = Tenant::query()->create([
            'name' => ucfirst($slug),
            'slug' => $slug,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);

        Role::findOrCreate('Super-admin');
        $user->assignRole('Super-admin');

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
