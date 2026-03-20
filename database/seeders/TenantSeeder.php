<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tenants')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Default tenant',
                'slug' => 'default',
                'is_active' => true,
                'meta' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (DB::getSchemaBuilder()->hasTable('subscription_plans') && DB::getSchemaBuilder()->hasTable('tenant_subscriptions')) {
            $bootstrapPlanId = DB::table('subscription_plans')
                ->where('code', 'internal-unlimited')
                ->value('id');

            if ($bootstrapPlanId) {
                DB::table('tenant_subscriptions')->updateOrInsert(
                    [
                        'tenant_id' => 1,
                        'subscription_plan_id' => $bootstrapPlanId,
                        'status' => 'active',
                    ],
                    [
                        'billing_provider' => null,
                        'billing_reference' => null,
                        'starts_at' => now(),
                        'ends_at' => null,
                        'trial_ends_at' => null,
                        'auto_renews' => false,
                        'feature_overrides' => null,
                        'limit_overrides' => null,
                        'meta' => json_encode(['bootstrap' => true]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        }
    }
}
