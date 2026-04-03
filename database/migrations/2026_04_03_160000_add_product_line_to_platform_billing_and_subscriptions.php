<?php

use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('tenant_subscriptions') && !Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            Schema::table('tenant_subscriptions', function (Blueprint $table) {
                $table->string('product_line', 50)->nullable()->after('subscription_plan_id');
                $table->index(['tenant_id', 'product_line', 'status', 'starts_at'], 'tenant_subscriptions_tenant_product_status_start_idx');
            });
        }

        if (Schema::hasTable('platform_plan_orders') && !Schema::hasColumn('platform_plan_orders', 'product_line')) {
            Schema::table('platform_plan_orders', function (Blueprint $table) {
                $table->string('product_line', 50)->nullable()->after('subscription_plan_id');
                $table->index(['tenant_id', 'product_line', 'status'], 'platform_plan_orders_tenant_product_status_idx');
            });
        }

        if (Schema::hasTable('platform_invoices') && !Schema::hasColumn('platform_invoices', 'product_line')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->string('product_line', 50)->nullable()->after('subscription_plan_id');
                $table->index(['tenant_id', 'product_line', 'status'], 'platform_invoices_tenant_product_status_idx');
            });
        }

        $this->backfillProductLines();
    }

    public function down(): void
    {
        if (Schema::hasTable('platform_invoices') && Schema::hasColumn('platform_invoices', 'product_line')) {
            Schema::table('platform_invoices', function (Blueprint $table) {
                $table->dropIndex('platform_invoices_tenant_product_status_idx');
                $table->dropColumn('product_line');
            });
        }

        if (Schema::hasTable('platform_plan_orders') && Schema::hasColumn('platform_plan_orders', 'product_line')) {
            Schema::table('platform_plan_orders', function (Blueprint $table) {
                $table->dropIndex('platform_plan_orders_tenant_product_status_idx');
                $table->dropColumn('product_line');
            });
        }

        if (Schema::hasTable('tenant_subscriptions') && Schema::hasColumn('tenant_subscriptions', 'product_line')) {
            Schema::table('tenant_subscriptions', function (Blueprint $table) {
                $table->dropIndex('tenant_subscriptions_tenant_product_status_start_idx');
                $table->dropColumn('product_line');
            });
        }
    }

    private function backfillProductLines(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $plans = SubscriptionPlan::query()
            ->select('id', 'meta')
            ->get()
            ->mapWithKeys(function (SubscriptionPlan $plan): array {
                $productLine = is_array($plan->meta) ? ($plan->meta['product_line'] ?? null) : null;
                $resolved = is_string($productLine) && trim($productLine) !== '' ? trim($productLine) : 'default';

                return [$plan->id => $resolved];
            })
            ->all();

        foreach ([
            'tenant_subscriptions',
            'platform_plan_orders',
            'platform_invoices',
        ] as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'product_line')) {
                continue;
            }

            DB::table($table)
                ->select('id', 'subscription_plan_id', 'product_line')
                ->orderBy('id')
                ->chunkById(200, function ($rows) use ($table, $plans): void {
                    foreach ($rows as $row) {
                        if (!empty($row->product_line)) {
                            continue;
                        }

                        $productLine = $plans[$row->subscription_plan_id] ?? 'default';

                        DB::table($table)
                            ->where('id', $row->id)
                            ->update([
                                'product_line' => $productLine,
                                'updated_at' => now(),
                            ]);
                    }
                });
        }
    }
};
