<?php

namespace Tests\Feature\Core;

use App\Models\Module;
use App\Support\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ModuleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_install_rejects_missing_required_modules(): void
    {
        $manager = app(ModuleManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'sales' membutuhkan module 'products' sudah ter-install.");

        $manager->install('sales');
    }

    public function test_activate_rejects_required_module_that_is_not_active(): void
    {
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

        $manager = app(ModuleManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'payments' membutuhkan module 'sales' aktif.");

        $manager->activate('payments');
    }

    public function test_deactivate_rejects_active_dependents(): void
    {
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

        $manager = app(ModuleManager::class);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Module 'sales' tidak bisa dinonaktifkan karena masih dipakai: payments");

        $manager->deactivate('sales');
    }

    public function test_install_and_activate_contact_module_updates_runtime_state(): void
    {
        $manager = app(ModuleManager::class);

        $manager->install('contacts');

        $installed = Module::query()->where('slug', 'contacts')->firstOrFail();
        $this->assertNotNull($installed->installed_at);
        $this->assertFalse($installed->is_active);

        $manager->activate('contacts');

        $activated = Module::query()->where('slug', 'contacts')->firstOrFail();
        $this->assertTrue($activated->is_active);
        $this->assertTrue($manager->isActive('contacts'));
        $this->assertTrue(app('router')->getRoutes()->hasNamedRoute('contacts.index'));
    }
}
