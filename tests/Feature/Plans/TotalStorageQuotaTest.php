<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Tests\TestCase;

class TotalStorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_storage_usage_state_counts_tenant_owned_files_in_bytes(): void
    {
        Storage::fake('public');

        [$tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::TOTAL_STORAGE_BYTES => 1024,
        ]);

        TenantContext::setCurrentId($tenant->id);

        Storage::disk('public')->put('avatars/test-avatar.png', str_repeat('a', 256));

        User::factory()->create([
            'tenant_id' => $tenant->id,
            'avatar' => 'avatars/test-avatar.png',
        ]);

        $otherTenant = Tenant::query()->create([
            'name' => 'Other Storage Workspace',
            'slug' => 'other-storage-workspace-' . Tenant::query()->count(),
            'is_active' => true,
        ]);
        User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'avatar' => 'avatars/other-tenant.png',
        ]);
        Storage::disk('public')->put('avatars/other-tenant.png', str_repeat('b', 512));

        $state = app(TenantPlanManager::class)->usageState(PlanLimit::TOTAL_STORAGE_BYTES, $tenant->id);

        $this->assertSame(1024, $state['limit']);
        $this->assertSame(256, $state['usage']);
        $this->assertSame(768, $state['remaining']);
        $this->assertSame('ok', $state['status']);
    }

    public function test_subscription_settings_displays_total_storage_quota(): void
    {
        [$tenant, $user] = $this->makeTenantWithPlanLimits([
            PlanLimit::USERS => 5,
            PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
        ]);

        TenantContext::setCurrentId($tenant->id);

        $this->actingAs($user)
            ->followingRedirects()
            ->get(route('settings.subscription'))
            ->assertOk()
            ->assertSee('Total Storage');
    }

    public function test_storage_usage_counts_local_omnichannel_files_only(): void
    {
        Storage::fake('public');

        [$tenant] = $this->makeTenantWithPlanLimits([
            PlanLimit::TOTAL_STORAGE_BYTES => 4096,
        ]);

        TenantContext::setCurrentId($tenant->id);

        if (!Schema::hasTable('conversations')) {
            Schema::create('conversations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->string('channel')->default('internal');
                $table->unsignedBigInteger('instance_id')->default(0);
                $table->string('contact_external_id')->nullable();
                $table->string('contact_name')->nullable();
                $table->string('status')->default('open');
                $table->timestamp('last_message_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('conversation_messages')) {
            Schema::create('conversation_messages', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->unsignedBigInteger('conversation_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('direction')->default('in');
                $table->string('type')->default('text');
                $table->longText('body')->nullable();
                $table->string('media_url')->nullable();
                $table->string('media_mime')->nullable();
                $table->string('status')->default('queued');
                $table->json('payload')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('live_chat_widgets')) {
            Schema::create('live_chat_widgets', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->default(1)->index();
                $table->string('name');
                $table->string('widget_token')->unique();
                $table->string('theme_color')->nullable();
                $table->string('logo_url')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Storage::disk('public')->put('wa_messages/2026/04/manual.pdf', str_repeat('m', 300));
        Storage::disk('public')->put('live_chat/logos/widget-logo.png', str_repeat('l', 200));

        DB::table('conversations')->insert([
            'id' => 1001,
            'tenant_id' => $tenant->id,
            'channel' => 'whatsapp_api',
            'instance_id' => 1,
            'contact_external_id' => 'contact-1',
            'contact_name' => 'Customer',
            'status' => 'open',
            'last_message_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('conversation_messages')->insert([
            'tenant_id' => $tenant->id,
            'conversation_id' => 1001,
            'direction' => 'out',
            'type' => 'document',
            'body' => 'Manual',
            'media_url' => url('/storage/wa_messages/2026/04/manual.pdf'),
            'media_mime' => 'application/pdf',
            'status' => 'queued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('live_chat_widgets')->insert([
            'tenant_id' => $tenant->id,
            'name' => 'Website Widget',
            'widget_token' => 'widget-token-1001',
            'theme_color' => '#206bc4',
            'logo_url' => url('/storage/live_chat/logos/widget-logo.png'),
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('conversation_messages')->insert([
            'tenant_id' => $tenant->id,
            'conversation_id' => 1001,
            'direction' => 'in',
            'type' => 'image',
            'body' => 'Remote',
            'media_url' => 'https://cdn.example.com/remote-image.jpg',
            'media_mime' => 'image/jpeg',
            'status' => 'delivered',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $state = app(TenantPlanManager::class)->usageState(PlanLimit::TOTAL_STORAGE_BYTES, $tenant->id);

        $this->assertSame(500, $state['usage']);
        $this->assertSame(3596, $state['remaining']);
    }

    private function makeTenantWithPlanLimits(array $limits): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Storage Workspace',
            'slug' => 'storage-workspace-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $plan = SubscriptionPlan::query()->create([
            'code' => 'storage-plan-' . $tenant->id,
            'name' => 'Storage Plan ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => [],
            'limits' => $limits,
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'storage-plan-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$tenant, $user, $plan];
    }
}
