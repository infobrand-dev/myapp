<?php

namespace Tests\Feature\WhatsAppApi;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Models\User;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Modules\WhatsAppApi\WhatsAppApiServiceProvider;
use App\Support\PlanFeature;
use App\Support\PlanLimit;
use App\Support\TenantContext;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class WATemplateStorageQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.key', 'base64:' . base64_encode(str_repeat('a', 32)));

        $this->app->register(WhatsAppApiServiceProvider::class);

        $this->artisan('migrate', [
            '--path' => 'app/Modules/WhatsAppApi/Database/Migrations',
            '--force' => true,
        ]);

        $this->seed(SubscriptionPlanSeeder::class);
        $this->withoutMiddleware(RoleMiddleware::class);
        $this->withoutMiddleware(PermissionMiddleware::class);
    }

    protected function tearDown(): void
    {
        TenantContext::forget();

        parent::tearDown();
    }

    public function test_template_media_upload_is_blocked_when_storage_limit_would_be_exceeded(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->makeTenantUserWithPlan([
            PlanFeature::WHATSAPP_API => true,
            PlanLimit::TOTAL_STORAGE_BYTES => 1024,
        ]);

        TenantContext::setCurrentId($tenant->id);
        $instance = $this->createCloudInstance($tenant->id);

        $file = UploadedFile::fake()->create('header.png', 10, 'image/png');

        $this->actingAs($user)
            ->post('/whatsapp-api/templates', [
                'name' => 'Promo Header',
                'language' => 'en',
                'category' => 'utility',
                'instance_id' => $instance->id,
                'body' => 'Hello customer',
                'status' => 'draft',
                'header_type' => 'image',
                'header_media_file' => $file,
            ])
            ->assertSessionHasErrors('plan');

        $this->assertSame(0, WATemplate::query()->where('tenant_id', $tenant->id)->count());
    }

    public function test_template_media_upload_succeeds_when_storage_capacity_is_available(): void
    {
        Storage::fake('public');

        [$tenant, $user] = $this->makeTenantUserWithPlan([
            PlanFeature::WHATSAPP_API => true,
            PlanLimit::TOTAL_STORAGE_BYTES => 5242880,
        ]);

        TenantContext::setCurrentId($tenant->id);
        $instance = $this->createCloudInstance($tenant->id);

        $file = UploadedFile::fake()->create('header.png', 64, 'image/png');

        $this->actingAs($user)
            ->post('/whatsapp-api/templates', [
                'name' => 'Promo Header',
                'language' => 'en',
                'category' => 'utility',
                'instance_id' => $instance->id,
                'body' => 'Hello customer',
                'status' => 'draft',
                'header_type' => 'image',
                'header_media_file' => $file,
            ])
            ->assertRedirect(route('whatsapp-api.templates.index'));

        $template = WATemplate::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $storedPath = data_get($template->components, '0.parameters.0.storage_path')
            ?: data_get($template->components, '1.parameters.0.storage_path');

        $this->assertNotEmpty($storedPath);
        Storage::disk('public')->assertExists($storedPath);
    }

    public function test_existing_template_can_be_updated_without_new_upload_while_over_limit(): void
    {
        Storage::fake('public');

        [$tenant, $user, $plan] = $this->makeTenantUserWithPlan([
            PlanFeature::WHATSAPP_API => true,
            PlanLimit::TOTAL_STORAGE_BYTES => 5242880,
        ]);

        TenantContext::setCurrentId($tenant->id);
        $instance = $this->createCloudInstance($tenant->id);

        $createFile = UploadedFile::fake()->create('header.png', 64, 'image/png');

        $this->actingAs($user)->post('/whatsapp-api/templates', [
            'name' => 'Promo Header',
            'language' => 'en',
            'category' => 'utility',
            'instance_id' => $instance->id,
            'body' => 'Hello customer',
            'status' => 'draft',
            'header_type' => 'image',
            'header_media_file' => $createFile,
        ])->assertRedirect(route('whatsapp-api.templates.index'));

        $plan->update([
            'limits' => array_merge($plan->limits ?? [], [
                PlanLimit::TOTAL_STORAGE_BYTES => 1024,
            ]),
        ]);

        $template = WATemplate::query()->where('tenant_id', $tenant->id)->firstOrFail();

        $this->actingAs($user)
            ->put('/whatsapp-api/templates/' . $template->id, [
                'name' => 'Promo Header',
                'language' => 'en',
                'category' => 'utility',
                'instance_id' => $instance->id,
                'body' => 'Updated body only',
                'status' => 'draft',
                'header_type' => 'image',
                'header_media_url' => data_get($template->components, '0.parameters.0.link')
                    ?: data_get($template->components, '1.parameters.0.link'),
            ])
            ->assertRedirect(route('whatsapp-api.templates.index'));

        $this->assertSame('Updated body only', $template->fresh()->body);
    }

    private function makeTenantUserWithPlan(array $limitsOrFeatures): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'WA Storage Workspace',
            'slug' => 'wa-storage-' . Tenant::query()->count(),
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $features = [];
        $limits = [];

        foreach ($limitsOrFeatures as $key => $value) {
            if (str_starts_with((string) $key, 'max_') || str_ends_with((string) $key, '_monthly')) {
                $limits[$key] = $value;
                continue;
            }

            $features[$key] = (bool) $value;
        }

        $plan = SubscriptionPlan::query()->create([
            'code' => 'wa-storage-plan-' . $tenant->id,
            'name' => 'WA Storage Plan ' . $tenant->id,
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 999,
            'features' => $features,
            'limits' => $limits,
        ]);

        TenantSubscription::query()->create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'billing_provider' => 'test',
            'billing_reference' => 'wa-storage-plan-' . $tenant->id,
            'starts_at' => now()->subMinute(),
            'ends_at' => now()->addMonth(),
            'auto_renews' => false,
        ]);

        return [$tenant, $user, $plan];
    }

    private function createCloudInstance(int $tenantId): WhatsAppInstance
    {
        return WhatsAppInstance::query()->create([
            'tenant_id' => $tenantId,
            'name' => 'Cloud Instance',
            'provider' => 'cloud',
            'status' => 'connected',
            'is_active' => true,
            'cloud_business_account_id' => 'waba-' . $tenantId,
            'phone_number_id' => 'phone-' . $tenantId,
            'cloud_token' => 'token-' . $tenantId,
        ]);
    }
}
