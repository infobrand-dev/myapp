<?php

namespace Tests\Feature\Core;

use App\Models\DocumentSetting;
use App\Models\DocumentNumberingRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_user_with_settings_view_permission_can_access_settings_page(): void
    {
        $user = $this->settingsUser(['settings.view']);

        $this->actingAs($user)
            ->get(route('settings.general'))
            ->assertOk()
            ->assertSee('Settings');
    }

    public function test_settings_sidebar_shows_subscription_and_workspace_shortcuts(): void
    {
        $user = $this->settingsUser(['settings.view']);

        $this->actingAs($user)
            ->get(route('settings.general'))
            ->assertOk()
            ->assertSee('Settings')
            ->assertSee('Subscription & Billing', false)
            ->assertSee('Documents', false)
            ->assertSee('Users & Access', false);
    }

    public function test_user_can_create_company_create_branch_and_switch_context(): void
    {
        $user = $this->settingsUser(['settings.view', 'settings.manage']);

        $this->actingAs($user)
            ->post(route('settings.company.store'), [
                'name' => 'PT Multi Usaha',
                'slug' => 'pt-multi-usaha',
                'code' => 'MULTI',
                'is_active' => 1,
            ])
            ->assertRedirect(route('settings.company'));

        $this->assertDatabaseHas('companies', [
            'tenant_id' => 1,
            'name' => 'PT Multi Usaha',
            'slug' => 'pt-multi-usaha',
            'code' => 'MULTI',
        ]);

        $companyId = (int) \DB::table('companies')
            ->where('tenant_id', 1)
            ->where('slug', 'pt-multi-usaha')
            ->value('id');

        $this->actingAs($user)
            ->withSession([
                'company_id' => $companyId,
                'company_slug' => 'pt-multi-usaha',
            ])
            ->post(route('settings.branch.store'), [
                'name' => 'Cabang Barat',
                'slug' => 'cabang-barat',
                'code' => 'CBR',
                'is_active' => 1,
            ])
            ->assertRedirect(route('settings.branch'));

        $this->assertDatabaseHas('branches', [
            'tenant_id' => 1,
            'company_id' => $companyId,
            'name' => 'Cabang Barat',
            'slug' => 'cabang-barat',
        ]);

        $branchId = (int) \DB::table('branches')
            ->where('tenant_id', 1)
            ->where('company_id', $companyId)
            ->where('slug', 'cabang-barat')
            ->value('id');

        $this->actingAs($user)
            ->post(route('settings.company.switch', $companyId))
            ->assertSessionHas('company_id', $companyId)
            ->assertSessionMissing('branch_id');

        $this->actingAs($user)
            ->withSession([
                'company_id' => $companyId,
                'company_slug' => 'pt-multi-usaha',
            ])
            ->post(route('settings.branch.switch', $branchId))
            ->assertSessionHas('branch_id', $branchId);
    }

    public function test_user_can_save_company_and_branch_document_settings(): void
    {
        $user = $this->settingsUser(['settings.view', 'settings.manage']);

        $this->actingAs($user)
            ->withSession([
                'company_id' => 1,
                'company_slug' => 'default-company',
                'branch_id' => 1,
                'branch_slug' => 'main-branch',
            ])
            ->put(route('settings.documents.save'), [
                'company_numbering' => [
                    'sale' => [
                        'prefix' => 'INV',
                        'number_format' => '{PREFIX}-{SEQ}',
                        'padding' => 6,
                        'next_number' => 12,
                        'reset_period' => 'monthly',
                    ],
                ],
                'company_document_header' => 'Header company',
                'company_document_footer' => 'Footer company',
                'company_receipt_footer' => 'Receipt company',
                'company_notes' => 'Company note',
                'branch_numbering' => [
                    'sale' => [
                        'prefix' => 'BR',
                        'number_format' => '{PREFIX}-{SEQ}',
                        'padding' => 4,
                        'next_number' => 3,
                        'reset_period' => 'yearly',
                    ],
                ],
                'branch_document_header' => 'Header branch',
                'branch_document_footer' => 'Footer branch',
                'branch_receipt_footer' => 'Receipt branch',
                'branch_notes' => 'Branch note',
            ])
            ->assertRedirect(route('settings.documents'));

        $this->assertDatabaseHas('document_numbering_rules', [
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'document_type' => 'sale',
            'prefix' => 'INV',
            'number_format' => '{PREFIX}-{SEQ}',
            'padding' => 6,
            'next_number' => 12,
            'reset_period' => 'monthly',
        ]);

        $this->assertDatabaseHas('document_numbering_rules', [
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => 1,
            'document_type' => 'sale',
            'prefix' => 'BR',
            'number_format' => '{PREFIX}-{SEQ}',
            'padding' => 4,
            'next_number' => 3,
            'reset_period' => 'yearly',
        ]);

        $this->assertSame(2, DocumentSetting::query()->count());
    }

    public function test_documents_page_shows_company_branch_and_effective_preview(): void
    {
        $user = $this->settingsUser(['settings.view']);

        DocumentSetting::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'document_header' => "Header company\nLine 2",
            'document_footer' => 'Footer company',
            'receipt_footer' => 'Receipt company',
        ]);

        DocumentSetting::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => 1,
            'document_header' => 'Header branch',
            'document_footer' => 'Footer branch',
            'receipt_footer' => 'Receipt branch',
        ]);

        DocumentNumberingRule::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => null,
            'scope_key' => DocumentNumberingRule::scopeKeyFor(),
            'document_type' => 'sale',
            'prefix' => 'INV',
            'number_format' => '{PREFIX}-{SEQ}',
            'padding' => 4,
            'next_number' => 12,
            'last_period' => now()->format('Y-m'),
            'reset_period' => 'monthly',
        ]);

        DocumentNumberingRule::query()->create([
            'tenant_id' => 1,
            'company_id' => 1,
            'branch_id' => 1,
            'scope_key' => DocumentNumberingRule::scopeKeyFor(1),
            'document_type' => 'sale',
            'prefix' => 'BR',
            'number_format' => '{PREFIX}-{SEQ}',
            'padding' => 3,
            'next_number' => 5,
            'last_period' => now()->format('Y'),
            'reset_period' => 'yearly',
        ]);

        $this->actingAs($user)
            ->withSession([
                'company_id' => 1,
                'company_slug' => 'default-company',
                'branch_id' => 1,
                'branch_slug' => 'main-branch',
            ])
            ->get(route('settings.documents'))
            ->assertOk()
            ->assertSee('BR-005')
            ->assertSee('Branch override')
            ->assertSee('Sales Invoice')
            ->assertSee('Numbering sudah disatukan ke rule per dokumen dengan scope company lalu branch override.')
            ->assertSee('Header branch')
            ->assertSee('Receipt branch');
    }

    private function settingsUser(array $permissions): User
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user = User::factory()->create([
            'tenant_id' => 1,
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId(1);
        $user->givePermissionTo($permissions);

        return $user;
    }
}
