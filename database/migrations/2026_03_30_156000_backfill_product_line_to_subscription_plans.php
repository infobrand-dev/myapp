<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('subscription_plans')
            ->select(['id', 'code', 'meta'])
            ->orderBy('id')
            ->chunkById(100, function ($plans): void {
                foreach ($plans as $plan) {
                    $meta = json_decode($plan->meta ?? '{}', true);

                    if (! is_array($meta) || array_key_exists('product_line', $meta)) {
                        continue;
                    }

                    $meta['product_line'] = match ($plan->code) {
                        'free', 'starter', 'growth', 'scale', 'starter-v2', 'growth-v2', 'scale-v2' => 'omnichannel',
                        'internal-unlimited' => 'internal',
                        default => null,
                    };

                    DB::table('subscription_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'updated_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('subscription_plans')
            ->select(['id', 'meta'])
            ->orderBy('id')
            ->chunkById(100, function ($plans): void {
                foreach ($plans as $plan) {
                    $meta = json_decode($plan->meta ?? '{}', true);

                    if (! is_array($meta)) {
                        continue;
                    }

                    unset($meta['product_line']);

                    DB::table('subscription_plans')
                        ->where('id', $plan->id)
                        ->update([
                            'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                            'updated_at' => now(),
                        ]);
                }
            });
    }
};
