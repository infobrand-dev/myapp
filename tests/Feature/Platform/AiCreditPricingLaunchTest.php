<?php

namespace Tests\Feature\Platform;

use App\Models\AiCreditPricingSetting;
use App\Services\AiCreditPricingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiCreditPricingLaunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_credit_pricing_service_uses_launch_defaults(): void
    {
        $snapshot = app(AiCreditPricingService::class)->snapshot();

        $this->assertSame('IDR', $snapshot['currency']);
        $this->assertSame(1000, $snapshot['unit_tokens']);
        $this->assertSame(100, $snapshot['price_per_credit']);
        $this->assertSame([500, 1000], $snapshot['pack_options']);
        $this->assertSame(50000, $snapshot['packs'][0]['price']);
        $this->assertSame(100000, $snapshot['packs'][1]['price']);
    }

    public function test_ai_credit_pricing_service_prefers_persisted_platform_settings(): void
    {
        AiCreditPricingSetting::query()->create([
            'tenant_id' => 1,
            'currency' => 'IDR',
            'unit_tokens' => 1000,
            'price_per_credit' => 125,
            'pack_options' => [200, 400],
        ]);

        $snapshot = app(AiCreditPricingService::class)->snapshot();

        $this->assertSame('IDR', $snapshot['currency']);
        $this->assertSame(1000, $snapshot['unit_tokens']);
        $this->assertSame(125, $snapshot['price_per_credit']);
        $this->assertSame([200, 400], $snapshot['pack_options']);
        $this->assertSame(25000, $snapshot['packs'][0]['price']);
        $this->assertSame(50000, $snapshot['packs'][1]['price']);
    }
}
