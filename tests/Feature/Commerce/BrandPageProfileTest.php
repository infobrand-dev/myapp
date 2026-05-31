<?php

namespace Tests\Feature\Commerce;

use App\Models\Tenant;
use App\Modules\Storefront\Services\BrandPageService;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class BrandPageProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_page_profile_uses_structured_sections_and_updates_tenant_meta(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Creator Lab',
            'slug' => 'creator-lab',
            'is_active' => true,
            'meta' => [],
        ]);

        TenantContext::setCurrentId($tenant->id);

        $service = app(BrandPageService::class);
        $profile = $service->profile($tenant);

        $this->assertSame('Creator Lab', $profile['name']);
        $this->assertCount(7, $profile['sections']);

        $request = Request::create('/storefront/brand', 'PUT', [
            'name' => 'Creator Lab Pro',
            'description' => 'Brand page untuk digital offers.',
            'hero_title' => 'Scale your creator commerce',
            'hero_subtitle' => 'Catalog, offers, and delivery in one place.',
            'accent' => '#114477',
            'cta_links' => [
                ['label' => 'Join Waitlist', 'url' => 'https://example.test/waitlist'],
            ],
            'footer_links' => [
                ['label' => 'Privacy', 'url' => 'https://example.test/privacy'],
            ],
            'sections' => [
                'hero' => ['enabled' => '1', 'order' => 5],
                'faq' => ['enabled' => '1', 'order' => 80],
            ],
        ]);
        $request->setUserResolver(fn () => null);

        $updated = $service->updateFromRequest($request, $tenant);

        $this->assertSame('Creator Lab Pro', $updated['name']);
        $this->assertSame('#114477', $updated['accent']);
        $this->assertSame('Join Waitlist', $updated['cta_links'][0]['label']);
        $this->assertTrue(collect($updated['sections'])->firstWhere('key', 'faq')['enabled']);

        $tenant->refresh();
        $this->assertSame('Creator Lab Pro', data_get($tenant->meta, 'commerce_creator.brand.name'));
        $this->assertSame('Scale your creator commerce', data_get($tenant->meta, 'commerce_creator.brand.hero_title'));
    }
}
