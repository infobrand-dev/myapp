<?php

namespace Tests\Feature\Core;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RolePermissionVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function test_role_form_hides_permissions_from_inactive_modules(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('roles.view', 'web');
        Permission::findOrCreate('roles.create', 'web');
        Permission::findOrCreate('discounts.view', 'web');
        $user->givePermissionTo(['roles.view', 'roles.create']);

        Module::query()->create([
            'slug' => 'discounts',
            'name' => 'Discounts',
            'provider' => 'App\\Modules\\Discounts\\DiscountsServiceProvider',
            'version' => '1.0.0',
            'is_active' => false,
            'installed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertSee('Roles')
            ->assertDontSee('discounts.view', false);
    }

    public function test_role_form_shows_permissions_from_active_modules(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('roles.view', 'web');
        Permission::findOrCreate('roles.create', 'web');
        Permission::findOrCreate('discounts.view', 'web');
        $user->givePermissionTo(['roles.view', 'roles.create']);

        Module::query()->create([
            'slug' => 'discounts',
            'name' => 'Discounts',
            'provider' => 'App\\Modules\\Discounts\\DiscountsServiceProvider',
            'version' => '1.0.0',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('roles.create'))
            ->assertOk()
            ->assertSee('discounts.view', false);
    }

    public function test_updating_role_preserves_hidden_permissions_from_inactive_modules(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('roles.update', 'web');
        Permission::findOrCreate('users.view', 'web');
        Permission::findOrCreate('discounts.view', 'web');
        $user->givePermissionTo('roles.update');

        Module::query()->create([
            'slug' => 'discounts',
            'name' => 'Discounts',
            'provider' => 'App\\Modules\\Discounts\\DiscountsServiceProvider',
            'version' => '1.0.0',
            'is_active' => false,
            'installed_at' => now(),
        ]);

        $role = Role::create(['name' => 'Operator']);
        $role->givePermissionTo(['users.view', 'discounts.view']);

        $this->actingAs($user)
            ->put(route('roles.update', $role), [
                'name' => 'Operator',
                'permissions' => ['users.view'],
            ])
            ->assertRedirect(route('roles.index'));

        $this->assertTrue($role->fresh()->hasPermissionTo('users.view'));
        $this->assertTrue($role->fresh()->hasPermissionTo('discounts.view'));
    }
}
