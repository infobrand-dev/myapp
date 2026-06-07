<?php

namespace Tests\Feature\Crm;

use App\Models\Tenant;
use App\Models\User;
use App\Modules\Contacts\ContactsServiceProvider;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmIntegrationService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\Concerns\BootstrapsModuleContext;
use Tests\Concerns\RefreshesPgsqlDatabase;
use Tests\TestCase;

class CrmLeadCaptureIntegrationTest extends TestCase
{
    use BootstrapsModuleContext;
    use RefreshesPgsqlDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        if (config('database.default') === 'sqlite') {
            $this->markTestSkipped('CrmLeadCaptureIntegrationTest harus dijalankan di PostgreSQL atau database non-SQLite yang setara dengan runtime aplikasi.');
        }

        $this->registerModuleProviders([
            ContactsServiceProvider::class,
            CrmServiceProvider::class,
        ]);

        $this->migrateModulePaths([
            'database/migrations',
            'app/Modules/Contacts/database/migrations',
            'app/Modules/Crm/database/migrations',
        ]);

        Tenant::query()->firstOrCreate([
            'id' => 1,
        ], [
            'name' => 'Default Tenant',
            'slug' => 'default',
            'is_active' => true,
        ]);

        $this->bootstrapDefaultOperationalContext();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_authenticated_api_can_capture_lead_and_create_follow_up(): void
    {
        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        Permission::findOrCreate('crm.create', 'web');
        $user->givePermissionTo('crm.create');

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/crm/leads', [
            'title' => 'Lead API Jakarta',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.test',
            'mobile' => '628111111111',
            'lead_source' => 'meta_ads',
            'provider' => 'meta_ads',
            'external_reference' => 'meta-1001',
            'estimated_value' => 2500000,
            'currency' => 'IDR',
            'next_follow_up_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'campaign_name' => 'Jakarta Juni',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('stage', 'new_lead');

        $lead = CrmLead::query()->firstOrFail();

        $this->assertSame('Lead API Jakarta', $lead->title);
        $this->assertSame('meta-1001', data_get($lead->meta, 'integration.external_reference'));
        $this->assertDatabaseHas('contacts', [
            'tenant_id' => 1,
            'email' => 'budi@example.test',
            'mobile' => '628111111111',
        ]);
        $this->assertDatabaseHas('crm_activities', [
            'tenant_id' => 1,
            'lead_id' => $lead->id,
            'activity_type' => 'external_lead_captured',
        ]);
        $this->assertDatabaseHas('crm_follow_up_tasks', [
            'tenant_id' => 1,
            'lead_id' => $lead->id,
            'status' => 'pending',
        ]);
    }

    public function test_meta_webhook_can_map_payload_and_store_receipt(): void
    {
        $tenant = Tenant::query()->findOrFail(1);
        app(CrmIntegrationService::class)->update($tenant, []);
        $token = app(CrmIntegrationService::class)->settings($tenant)['lead_capture_token'];

        $this->withoutMiddleware(VerifyCsrfToken::class);
        TenantContext::setCurrentId(1);
        CompanyContext::setCurrentId(1);

        $response = $this->withHeader('X-Lead-Capture-Token', $token)
            ->postJson('/crm/webhooks/meta-leads', [
                'entry' => [[
                    'changes' => [[
                        'value' => [
                            'leadgen_id' => 'leadgen_12345',
                            'campaign_name' => 'Campaign Jakarta',
                            'adset_name' => 'Lookalike 3%',
                            'form_name' => 'Form Promo',
                            'field_data' => [
                                ['name' => 'full_name', 'values' => ['Siti Customer']],
                                ['name' => 'email', 'values' => ['siti@example.test']],
                                ['name' => 'phone_number', 'values' => ['628222222222']],
                            ],
                        ],
                    ]],
                ]],
            ]);

        $response
            ->assertCreated()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('external_reference', 'leadgen_12345');

        $lead = CrmLead::query()->where('meta->integration->external_reference', 'leadgen_12345')->firstOrFail();

        $this->assertSame('meta_ads', $lead->lead_source);
        $this->assertDatabaseHas('platform_webhook_receipts', [
            'tenant_id' => 1,
            'provider' => 'crm',
            'endpoint' => 'crm.webhooks.meta-leads',
            'status' => 'processed',
        ]);
        $this->assertDatabaseHas('crm_activities', [
            'tenant_id' => 1,
            'lead_id' => $lead->id,
            'activity_type' => 'external_lead_captured',
        ]);
    }
}
