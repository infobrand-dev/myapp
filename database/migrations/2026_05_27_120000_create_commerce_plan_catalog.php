<?php

use App\Support\PlanFeature;
use App\Support\PlanLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = [
            [
                'code' => 'commerce_starter',
                'name' => 'Starter',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 760,
                'features' => [
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::ACCOUNTING => false,
                    PlanFeature::COMMERCE => true,
                    PlanFeature::STOREFRONT => true,
                    PlanFeature::SHIPPING => true,
                    PlanFeature::FULFILLMENT => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                    PlanLimit::PRODUCTS => 100,
                    PlanLimit::CONTACTS => 2000,
                ],
                'meta' => [
                    'product_line' => 'commerce',
                    'plan_revision' => 'v1',
                    'tagline' => 'Storefront-led commerce untuk mulai menerima order secara rapi.',
                ],
            ],
            [
                'code' => 'commerce_growth',
                'name' => 'Growth',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 770,
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::ACCOUNTING => false,
                    PlanFeature::COMMERCE => true,
                    PlanFeature::STOREFRONT => true,
                    PlanFeature::SHIPPING => true,
                    PlanFeature::FULFILLMENT => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 15,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 1000,
                    PlanLimit::CONTACTS => 10000,
                ],
                'meta' => [
                    'product_line' => 'commerce',
                    'plan_revision' => 'v1',
                    'tagline' => 'Kapasitas commerce lebih besar untuk tim order yang mulai aktif.',
                ],
            ],
            [
                'code' => 'commerce_scale',
                'name' => 'Scale',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 780,
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::ACCOUNTING => false,
                    PlanFeature::COMMERCE => true,
                    PlanFeature::STOREFRONT => true,
                    PlanFeature::SHIPPING => true,
                    PlanFeature::FULFILLMENT => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::BRANCHES => 10,
                    PlanLimit::USERS => 50,
                    PlanLimit::TOTAL_STORAGE_BYTES => 21474836480,
                    PlanLimit::PRODUCTS => 5000,
                    PlanLimit::CONTACTS => 50000,
                ],
                'meta' => [
                    'product_line' => 'commerce',
                    'plan_revision' => 'v1',
                    'tagline' => 'Commerce untuk operasional order yang lebih besar.',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['code' => $plan['code']],
                [
                    'name' => $plan['name'],
                    'billing_interval' => $plan['billing_interval'],
                    'is_active' => $this->dbBool($plan['is_active']),
                    'is_public' => $this->dbBool($plan['is_public']),
                    'is_system' => $this->dbBool($plan['is_system']),
                    'sort_order' => $plan['sort_order'],
                    'features' => json_encode($plan['features']),
                    'limits' => json_encode($plan['limits']),
                    'meta' => json_encode($plan['meta']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->whereIn('code', ['commerce_starter', 'commerce_growth', 'commerce_scale'])
            ->delete();
    }

    private function dbBool(bool $value)
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
};
