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

        DB::table('subscription_plans')
            ->select(['id', 'meta'])
            ->whereIn('code', ['accounting_starter', 'accounting_growth', 'accounting_scale'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $meta = json_decode((string) ($plan->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];
                $meta['sales_status'] = 'public';

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'is_public' => true,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->select(['id', 'meta'])
            ->whereIn('code', ['accounting_starter', 'accounting_growth', 'accounting_scale'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $meta = json_decode((string) ($plan->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];
                $meta['sales_status'] = 'internal';

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'is_public' => false,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }
};
