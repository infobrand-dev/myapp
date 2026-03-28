<?php

namespace Tests\Feature\Chatbot;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\Chatbot\ChatbotServiceProvider;
use App\Modules\Chatbot\Models\ChatbotAccount;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class ChatbotTenantScopeTest extends TestCase
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

        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutMiddleware(RoleMiddleware::class);
    }

    public function test_chatbot_accounts_index_only_shows_current_tenant_accounts(): void
    {
        [$user] = $this->makeSuperAdminWithPlan('growth', 'tenant-b');
        $otherTenant = Tenant::query()->create([
            'name' => 'Tenant A',
            'slug' => 'tenant-a',
            'is_active' => true,
        ]);

        ChatbotAccount::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Other Tenant Bot',
            'provider' => 'openai',
            'api_key' => 'sk-other',
            'status' => 'active',
            'response_style' => 'balanced',
            'operation_mode' => 'ai_only',
        ]);

        ChatbotAccount::query()->create([
            'tenant_id' => $user->tenant_id,
            'name' => 'Current Tenant Bot',
            'provider' => 'openai',
            'api_key' => 'sk-current',
            'status' => 'active',
            'response_style' => 'balanced',
            'operation_mode' => 'ai_only',
        ]);

        $this->actingAs($user)
            ->get('/chatbot/accounts')
            ->assertOk()
            ->assertSeeText('Current Tenant Bot')
            ->assertDontSeeText('Other Tenant Bot');
    }

    public function test_chatbot_account_route_binding_rejects_other_tenant_account(): void
    {
        [$user] = $this->makeSuperAdminWithPlan('growth', 'tenant-c');
        $otherTenant = Tenant::query()->create([
            'name' => 'Tenant D',
            'slug' => 'tenant-d',
            'is_active' => true,
        ]);

        $foreignAccount = ChatbotAccount::query()->create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Bot',
            'provider' => 'openai',
            'api_key' => 'sk-foreign',
            'status' => 'active',
            'response_style' => 'balanced',
            'operation_mode' => 'ai_only',
        ]);

        $this->actingAs($user)
            ->get('/chatbot/accounts/' . $foreignAccount->id . '/edit')
            ->assertNotFound();
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
