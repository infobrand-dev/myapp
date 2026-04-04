<?php

use App\Support\PlanFeature;
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
            ->select(['id', 'code', 'features', 'meta'])
            ->whereIn('code', ['accounting_starter', 'accounting_growth', 'accounting_scale'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];
                $meta = json_decode((string) ($plan->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];

                $isStarter = $plan->code === 'accounting_starter';

                $features[PlanFeature::PURCHASES] = !$isStarter;
                $features[PlanFeature::INVENTORY] = !$isStarter;
                $features[PlanFeature::ADVANCED_REPORTS] = !$isStarter;
                $features[PlanFeature::POINT_OF_SALE] = false;
                unset($features['inventory'], $features['pos']);
                $meta['sales_status'] = 'public';

                if ($isStarter) {
                    $meta['tagline'] = 'Paket simple untuk UMKM dengan sales, payments, finance ringan, products, contacts, dan dashboard report ringkas. POS tersedia sebagai add-on.';
                    $meta['description'] = 'Cocok untuk tim kecil yang mulai merapikan operasional transaksi dalam satu workspace tanpa langsung membuka workflow purchases atau inventory.';
                    $meta['highlights'] = [
                        'Sales, products, dan contacts operasional',
                        'Payments dan finance ringan',
                        'Basic reports untuk pembacaan harian',
                        'POS Add-on tersedia',
                        'Kapasitas awal untuk tim kecil',
                    ];
                } elseif ($plan->code === 'accounting_growth') {
                    $meta['tagline'] = 'Paket lengkap untuk tim yang sudah aktif dengan purchases, inventory, full reports, dan kapasitas workspace yang lebih longgar.';
                    $meta['description'] = 'Dirancang untuk operasional yang mulai padat dengan kebutuhan pembelian supplier, stok, user, branch, storage, dan pembacaan report yang lebih detail.';
                    $meta['highlights'] = [
                        'Semua capability Starter',
                        'Purchases dan inventory aktif',
                        'Full reports operasional',
                        'POS Add-on tersedia',
                        'Kapasitas user dan branch lebih besar',
                        'Cocok untuk tim operasional yang sedang tumbuh',
                    ];
                } else {
                    $meta['tagline'] = 'Isi paket sama dengan Growth, dengan kapasitas besar untuk tim multi-user dan multi-branch yang lebih padat.';
                    $meta['description'] = 'Paket tertinggi untuk organisasi yang butuh purchases, inventory, full reports, dan kapasitas produk, kontak, storage, serta branch yang jauh lebih besar.';
                    $meta['highlights'] = [
                        'Semua capability Growth',
                        'POS Add-on tersedia',
                        'Kapasitas besar untuk user dan branch',
                        'Storage, produk, dan kontak jauh lebih besar',
                        'Cocok untuk operasional yang lebih kompleks',
                    ];
                }

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
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
            ->select(['id', 'code', 'features'])
            ->whereIn('code', ['accounting_starter', 'accounting_growth', 'accounting_scale'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $features = json_decode((string) ($plan->features ?? '{}'), true);
                $features = is_array($features) ? $features : [];

                unset($features[PlanFeature::PURCHASES], $features[PlanFeature::INVENTORY]);
                $features[PlanFeature::ADVANCED_REPORTS] = true;
                $features['inventory'] = false;

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'features' => json_encode($features, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }
};
