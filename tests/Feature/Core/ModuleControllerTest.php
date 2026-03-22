<?php

namespace Tests\Feature\Core;

use App\Models\Module;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ModuleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_returns_success_status_message(): void
    {
        $user = $this->moduleAdmin(['modules.install']);

        $response = $this->actingAs($user)
            ->from(route('modules.index'))
            ->post(route('modules.install', 'contacts'));

        $response->assertRedirect(route('modules.index'));
        $response->assertSessionHas('status', "Module 'contacts' berhasil di-install.");
        $this->assertDatabaseHas('modules', ['slug' => 'contacts']);
    }

    public function test_install_returns_failure_status_message_when_dependency_missing(): void
    {
        $user = $this->moduleAdmin(['modules.install']);

        $response = $this->actingAs($user)
            ->from(route('modules.index'))
            ->post(route('modules.install', 'sales'));

        $response->assertRedirect(route('modules.index'));
        $response->assertSessionHas('status', "Gagal install module 'sales': Module 'sales' membutuhkan module 'products' sudah ter-install.");
    }

    public function test_activate_returns_failure_status_message_when_dependency_inactive(): void
    {
        $user = $this->moduleAdmin(['modules.activate']);

        Module::query()->create([
            'slug' => 'sales',
            'name' => 'Sales',
            'provider' => 'App\\Modules\\Sales\\SalesServiceProvider',
            'version' => '1.0.0',
            'installed_at' => now(),
            'is_active' => false,
        ]);

        Module::query()->create([
            'slug' => 'payments',
            'name' => 'Payments',
            'provider' => 'App\\Modules\\Payments\\PaymentsServiceProvider',
            'version' => '1.0.0',
            'installed_at' => now(),
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)
            ->from(route('modules.index'))
            ->post(route('modules.activate', 'payments'));

        $response->assertRedirect(route('modules.index'));
        $response->assertSessionHas('status', "Gagal aktivasi module 'payments': Module 'payments' membutuhkan module 'sales' aktif.");
    }

    public function test_deactivate_returns_failure_status_message_when_active_dependent_exists(): void
    {
        $user = $this->moduleAdmin(['modules.deactivate']);

        Module::query()->create([
            'slug' => 'sales',
            'name' => 'Sales',
            'provider' => 'App\\Modules\\Sales\\SalesServiceProvider',
            'version' => '1.0.0',
            'installed_at' => now(),
            'is_active' => true,
        ]);

        Module::query()->create([
            'slug' => 'payments',
            'name' => 'Payments',
            'provider' => 'App\\Modules\\Payments\\PaymentsServiceProvider',
            'version' => '1.0.0',
            'installed_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->from(route('modules.index'))
            ->post(route('modules.deactivate', 'sales'));

        $response->assertRedirect(route('modules.index'));
        $response->assertSessionHas('status', "Gagal nonaktifkan module 'sales': Module 'sales' tidak bisa dinonaktifkan karena masih dipakai: payments");
    }

    private function moduleAdmin(array $permissions): User
    {
        $user = User::factory()->create();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $user->givePermissionTo($permissions);

        return $user;
    }
}
