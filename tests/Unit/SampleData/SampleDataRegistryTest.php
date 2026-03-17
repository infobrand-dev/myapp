<?php

namespace Tests\Unit\SampleData;

use App\Models\Module;
use App\Modules\SampleData\Support\SampleDataRegistry;
use App\Support\ModuleManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SampleDataRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_active_modules_and_marks_registered_sample_seeders(): void
    {
        Module::query()->create([
            'slug' => 'whatsapp_api',
            'name' => 'WhatsApp API',
            'provider' => 'App\\Modules\\WhatsAppApi\\WhatsAppApiServiceProvider',
            'version' => '1.0.0',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        Module::query()->create([
            'slug' => 'shortlink',
            'name' => 'Shortlink',
            'provider' => 'App\\Modules\\Shortlink\\ShortlinkServiceProvider',
            'version' => '1.0.0',
            'is_active' => true,
            'installed_at' => now(),
        ]);

        $registry = new SampleDataRegistry(app(ModuleManager::class));
        $modules = $registry->activeModules()->keyBy('slug');

        $this->assertTrue($modules->has('whatsapp_api'));
        $this->assertTrue($modules->get('whatsapp_api')['ready']);
        $this->assertCount(2, $modules->get('whatsapp_api')['seeders']);

        $this->assertTrue($modules->has('shortlink'));
        $this->assertTrue($modules->get('shortlink')['ready']);
        $this->assertCount(1, $modules->get('shortlink')['seeders']);
    }
}
