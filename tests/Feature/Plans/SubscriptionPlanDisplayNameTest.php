<?php

namespace Tests\Feature\Plans;

use App\Models\SubscriptionPlan;
use Database\Seeders\SubscriptionPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionPlanDisplayNameTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_omnichannel_plan_exposes_display_name(): void
    {
        $this->seed(SubscriptionPlanSeeder::class);

        $plan = SubscriptionPlan::query()->where('code', 'growth-v2')->firstOrFail();

        $this->assertSame('omnichannel', $plan->productLine());
        $this->assertSame('Omnichannel', $plan->productLineLabel());
        $this->assertSame('Omnichannel Growth', $plan->display_name);
    }

    public function test_display_name_does_not_duplicate_product_line_prefix(): void
    {
        $plan = SubscriptionPlan::query()->create([
            'code' => 'crm-pro',
            'name' => 'CRM Pro',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 10,
            'features' => [],
            'limits' => [],
            'meta' => [
                'product_line' => 'crm',
            ],
        ]);

        $this->assertSame('CRM Pro', $plan->display_name);
    }
}
