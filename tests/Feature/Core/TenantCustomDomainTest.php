<?php

namespace Tests\Feature\Core;

use App\Models\CloudflareSaasSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantDomainEvent;
use App\Models\User;
use App\Services\DomainHandoffService;
use App\Services\TenantCustomDomainService;
use App\Support\TenantContext;
use App\Support\WorkspaceUrl;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TenantCustomDomainTest extends TestCase
{
    private static bool $customDomainTopologyReady = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureCustomDomainTopologyReady();

        config()->set('multitenancy.mode', 'saas');
        config()->set('multitenancy.saas_domain', 'meetra.id');
        config()->set('app.url', 'https://myapp.test');

        TenantContext::forget();

        TenantDomainEvent::query()->delete();
        TenantDomain::query()->delete();
        CloudflareSaasSetting::query()->delete();
        Tenant::query()->delete();
        User::query()->delete();

        Route::middleware('web')->get('/_tenant-custom-domain-probe', function () {
            return response()->json([
                'tenant_id' => TenantContext::currentId(),
                'tenant_slug' => optional(TenantContext::currentTenant())->slug,
            ]);
        });
    }

    public function test_request_domain_creates_cloudflare_hostname_and_dns_instructions(): void
    {
        $tenant = $this->makeTenant('acme');

        CloudflareSaasSetting::query()->create([
            'account_id' => 'acc-1',
            'zone_id' => 'zone-1',
            'api_token' => 'token-1',
            'cname_target' => 'customers.meetra.id',
            'is_active' => true,
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'cf-host-123',
                    'status' => 'pending',
                    'ssl' => ['status' => 'initializing'],
                    'ownership_verification' => [
                        'name' => '_cf-custom-hostname.acme.customer.com',
                        'value' => 'verify-me',
                    ],
                ],
            ]),
        ]);

        $domain = app(TenantCustomDomainService::class)->requestDomain($tenant, 'acme.customer.com');

        $this->assertSame(TenantDomain::STATUS_PENDING_DNS, $domain->status);
        $this->assertSame('cf-host-123', $domain->cloudflare_hostname_id);
        $this->assertSame('_cf-custom-hostname.acme.customer.com', $domain->ownership_dns_name);
        $this->assertSame('verify-me', $domain->ownership_dns_value);
        $this->assertSame('CNAME', $domain->routing_record_type);
        $this->assertSame('customers.meetra.id', $domain->routing_record_value);
        $this->assertDatabaseHas('tenant_domain_events', [
            'tenant_domain_id' => $domain->id,
            'event' => 'provider_created',
        ], 'central');
    }

    public function test_request_domain_blocks_apex_when_owner_has_no_apex_proxying(): void
    {
        $tenant = $this->makeTenant('beta');

        CloudflareSaasSetting::query()->create([
            'account_id' => 'acc-1',
            'zone_id' => 'zone-1',
            'api_token' => 'token-1',
            'cname_target' => 'customers.meetra.id',
            'apex_proxying_enabled' => false,
            'is_active' => true,
        ]);

        $domain = app(TenantCustomDomainService::class)->requestDomain($tenant, 'customer.com');

        $this->assertSame(TenantDomain::STATUS_BLOCKED, $domain->status);
        $this->assertSame('apex_proxying_required', $domain->last_error_code);
    }

    public function test_sync_domain_marks_domain_active_when_cloudflare_and_ssl_are_active(): void
    {
        $tenant = $this->makeTenant('gamma');

        CloudflareSaasSetting::query()->create([
            'account_id' => 'acc-1',
            'zone_id' => 'zone-1',
            'api_token' => 'token-1',
            'cname_target' => 'customers.meetra.id',
            'is_active' => true,
        ]);

        $domain = TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'hostname' => 'gamma.customer.com',
            'status' => TenantDomain::STATUS_PENDING_OWNERSHIP,
            'cloudflare_hostname_id' => 'cf-host-999',
            'verification_method' => 'txt',
        ]);

        Http::fake([
            '*' => Http::response([
                'success' => true,
                'result' => [
                    'id' => 'cf-host-999',
                    'status' => 'active',
                    'ssl' => ['status' => 'active'],
                    'ownership_verification' => [
                        'name' => '_cf-custom-hostname.gamma.customer.com',
                        'value' => 'verified-token',
                    ],
                ],
            ]),
        ]);

        $synced = app(TenantCustomDomainService::class)->syncDomain($domain);

        $this->assertSame(TenantDomain::STATUS_ACTIVE, $synced->status);
        $this->assertSame('active', $synced->cloudflare_ssl_status);
        $this->assertNotNull($synced->last_verified_at);
    }

    public function test_custom_domain_host_resolves_tenant_and_workspace_url_prefers_canonical_host(): void
    {
        $tenant = $this->makeTenant('delta');

        TenantDomain::query()->create([
            'tenant_id' => $tenant->id,
            'hostname' => 'app.delta-customer.com',
            'status' => TenantDomain::STATUS_ACTIVE,
            'is_primary' => true,
            'is_canonical' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user);

        $this->get('http://app.delta-customer.com/_tenant-custom-domain-probe')
            ->assertOk()
            ->assertJson([
                'tenant_id' => $tenant->id,
                'tenant_slug' => 'delta',
            ]);

        $url = app(WorkspaceUrl::class)->forCurrentUser(request(), true);

        $this->assertStringContainsString('app.delta-customer.com/dashboard', $url);
    }

    public function test_domain_handoff_route_reauthenticates_user_on_target_host(): void
    {
        $tenant = $this->makeTenant('omega');
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email_verified_at' => now(),
        ]);

        $url = app(DomainHandoffService::class)->issue($user, 'app.omega-customer.com', '/dashboard?from=handoff');

        $this->get($url)
            ->assertRedirect('/dashboard?from=handoff');

        $this->assertAuthenticatedAs($user);
    }

    private function makeTenant(string $slug): Tenant
    {
        return Tenant::query()->create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'name' => strtoupper($slug),
            'slug' => $slug,
            'is_active' => true,
            'status' => 'active',
            'schema_name' => 'public',
        ]);
    }

    private function ensureCustomDomainTopologyReady(): void
    {
        if (self::$customDomainTopologyReady) {
            return;
        }

        if (!Schema::hasTable('users')) {
            Artisan::call('migrate', [
                '--path' => 'database/migrations/2014_10_12_000000_create_users_table.php',
                '--force' => true,
            ]);
        }

        if (!Schema::connection('central')->hasTable('tenant_domains')) {
            foreach ([
                'database/migrations/2026_06_02_090000_create_tenant_registry_topology_tables.php',
                'database/migrations/2026_06_02_090100_expand_tenants_for_schema_registry.php',
            ] as $path) {
                Artisan::call('migrate', [
                    '--database' => 'central',
                    '--path' => $path,
                    '--force' => true,
                ]);
            }
        }

        if (!Schema::connection('central')->hasColumn('tenant_domains', 'hostname')) {
            Artisan::call('migrate', [
                '--database' => 'central',
                '--path' => 'database/migrations/2026_06_05_090000_expand_tenant_domains_for_cloudflare_saas.php',
                '--force' => true,
            ]);
        }

        if (!Schema::connection('central')->hasTable('tenant_domain_events')) {
            Artisan::call('migrate', [
                '--database' => 'central',
                '--path' => 'database/migrations/2026_06_05_090100_create_tenant_domain_events_and_cloudflare_settings.php',
                '--force' => true,
            ]);
        }

        self::$customDomainTopologyReady = true;
    }
}
