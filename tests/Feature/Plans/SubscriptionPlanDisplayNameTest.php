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

    public function test_billing_interval_label_is_human_readable(): void
    {
        $semiannual = SubscriptionPlan::query()->create([
            'code' => 'omnichannel-growth-6m',
            'name' => 'Growth',
            'billing_interval' => 'semiannual',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 10,
            'features' => [],
            'limits' => [],
            'meta' => [
                'product_line' => 'omnichannel',
            ],
        ]);

        $yearly = SubscriptionPlan::query()->create([
            'code' => 'omnichannel-growth-yearly',
            'name' => 'Growth',
            'billing_interval' => 'yearly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => false,
            'sort_order' => 11,
            'features' => [],
            'limits' => [],
            'meta' => [
                'product_line' => 'omnichannel',
            ],
        ]);

        $this->assertSame('6 Bulanan', $semiannual->billing_interval_label);
        $this->assertSame('Tahunan', $yearly->billing_interval_label);
    }
}
