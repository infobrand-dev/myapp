<?php

namespace Tests\Feature\Core;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CorePermissionAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_with_users_view_permission_can_access_users_index_without_super_admin_role(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('users.view', 'web');
        $user->givePermissionTo('users.view');

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertOk();
    }

    public function test_user_without_users_view_permission_is_forbidden_from_users_index(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('users.index'))
            ->assertForbidden();
    }

    public function test_user_with_modules_view_permission_can_access_modules_index_without_super_admin_role(): void
    {
        $user = User::factory()->create();
        Permission::findOrCreate('modules.view', 'web');
        $user->givePermissionTo('modules.view');

        $this->actingAs($user)
            ->get(route('modules.index'))
            ->assertOk();
    }

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
