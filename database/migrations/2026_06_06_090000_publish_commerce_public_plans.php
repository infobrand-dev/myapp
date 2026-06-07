<?php

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

        $updates = [
            'commerce_starter' => [
                'price' => 149000,
                'tagline' => 'Storefront-led commerce untuk mulai menerima order secara rapi.',
                'description' => 'Fokus ke order capture, payment status, shipping, dan fulfillment ringan tanpa membuka accounting penuh.',
                'highlights' => [
                    'Storefront workspace',
                    'Order list commerce',
                    'Payment status',
                    'Shipping dan fulfillment ringan',
                ],
                'sales_status' => 'current',
                'recommended' => false,
                'audience' => 'Cocok untuk bisnis yang mulai menerima order online dengan alur sederhana',
            ],
            'commerce_growth' => [
                'price' => 299000,
                'tagline' => 'Kapasitas commerce lebih besar untuk tim order yang mulai aktif.',
                'description' => 'Untuk tim yang membutuhkan katalog lebih besar dan ritme order yang lebih padat.',
                'highlights' => [
                    'Semua fitur Starter',
                    'Kapasitas user dan produk lebih besar',
                    'Siap untuk workload order yang lebih tinggi',
                ],
                'sales_status' => 'current',
                'recommended' => true,
                'audience' => 'Cocok untuk tim order yang mulai aktif dan butuh kapasitas lebih besar',
            ],
            'commerce_scale' => [
                'price' => 599000,
                'tagline' => 'Commerce untuk operasional order yang lebih besar.',
                'description' => 'Paket kapasitas tinggi untuk bisnis yang memerlukan workspace commerce lebih padat.',
                'highlights' => [
                    'Semua fitur Growth',
                    'Kapasitas company, branch, user, dan produk lebih besar',
                ],
                'sales_status' => 'current',
                'recommended' => false,
                'audience' => 'Cocok untuk operasional order yang lebih padat dan multi-tim',
            ],
        ];

        foreach ($updates as $code => $metaPatch) {
            $plan = DB::table('subscription_plans')->where('code', $code)->first();
            if (!$plan) {
                continue;
            }

            $meta = json_decode((string) ($plan->meta ?? '{}'), true);
            if (!is_array($meta)) {
                $meta = [];
            }

            $meta = array_merge($meta, $metaPatch, [
                'product_line' => 'commerce',
                'plan_revision' => $meta['plan_revision'] ?? 'v1',
            ]);

            DB::table('subscription_plans')
                ->where('code', $code)
                ->update([
                    'is_public' => $this->dbBool(true),
                    'meta' => json_encode($meta),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->whereIn('code', ['commerce_starter', 'commerce_growth', 'commerce_scale'])
            ->update([
                'is_public' => $this->dbBool(false),
                'updated_at' => now(),
            ]);
    }

    private function dbBool(bool $value)
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }
};
