<?php

namespace Tests\Feature\Crm;

use App\Http\Middleware\EnsureInstalled;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Middleware\ResolveTenantFromSubdomain;
use App\Models\Tenant;
use App\Models\User;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\CrmServiceProvider;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Crm\Support\CrmStageCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\Middleware\RoleMiddleware;
use Tests\TestCase;

class CrmModuleSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app()->register(CrmServiceProvider::class);

        Artisan::call('migrate', [
            '--path' => 'app/Modules/Contacts/Database/Migrations',
            '--force' => true,
        ]);

        Artisan::call('migrate', [
            '--path' => 'app/Modules/Crm/Database/Migrations',
            '--force' => true,
        ]);
    }

    public function test_crm_list_and_kanban_views_render(): void
    {
        Tenant::query()->firstOrCreate(
            ['slug' => 'default'],
            [
                'id' => 1,
                'name' => 'Default Tenant',
                'is_active' => true,
            ]
        );

        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $contact = Contact::query()->create([
            'tenant_id' => 1,
            'type' => 'individual',
            'name' => 'Lead Alpha',
            'email' => 'lead@example.test',
            'is_active' => true,
        ]);

        CrmLead::query()->create([
            'tenant_id' => 1,
            'contact_id' => $contact->id,
            'title' => 'Follow up Lead Alpha',
            'stage' => CrmStageCatalog::NEW_LEAD,
            'priority' => 'medium',
            'position' => 1,
            'is_archived' => false,
        ]);

        $this->withoutMiddleware([
            EnsureInstalled::class,
            ResolveTenantFromSubdomain::class,
            ResolveTenantContext::class,
            RoleMiddleware::class,
        ]);

        $this->actingAs($user)
            ->get('/crm')
            ->assertOk()
            ->assertSee('CRM')
            ->assertSee('Follow up Lead Alpha');

        $this->actingAs($user)
            ->get('/crm?view=kanban')
            ->assertOk()
            ->assertSee('New Lead')
            ->assertSee('Follow up Lead Alpha');
    }

    public function test_crm_rejects_cross_tenant_contact_and_owner_ids(): void
    {
        Tenant::query()->firstOrCreate(
            ['slug' => 'default'],
            [
                'id' => 1,
                'name' => 'Default Tenant',
                'is_active' => true,
            ]
        );

        $tenantTwo = Tenant::query()->create([
            'name' => 'Tenant Two',
            'slug' => 'tenant-two',
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        $foreignOwner = User::factory()->create([
            'tenant_id' => $tenantTwo->id,
        ]);

        $foreignContact = Contact::query()->create([
            'tenant_id' => $tenantTwo->id,
            'type' => 'individual',
            'name' => 'Foreign Lead',
            'email' => 'foreign@example.test',
            'is_active' => true,
        ]);

        $this->withoutMiddleware([
            EnsureInstalled::class,
            ResolveTenantFromSubdomain::class,
            ResolveTenantContext::class,
            RoleMiddleware::class,
        ]);

        $this->actingAs($user)
            ->post('/crm', [
                'contact_id' => $foreignContact->id,
                'owner_user_id' => $foreignOwner->id,
                'title' => 'Cross Tenant Lead',
                'stage' => CrmStageCatalog::NEW_LEAD,
                'priority' => 'medium',
            ])
            ->assertSessionHasErrors(['contact_id', 'owner_user_id']);
    }
}
