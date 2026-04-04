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

        $now = now();

        foreach ($this->plans() as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['code' => $plan['code']],
                [
                    'name' => $plan['name'],
                    'billing_interval' => $plan['billing_interval'],
                    'is_active' => $this->databaseBoolean($plan['is_active']),
                    'is_public' => $this->databaseBoolean($plan['is_public']),
                    'is_system' => $this->databaseBoolean($plan['is_system']),
                    'sort_order' => $plan['sort_order'],
                    'features' => json_encode($plan['features'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'limits' => json_encode($plan['limits'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'meta' => json_encode($plan['meta'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    'created_at' => $now,
                    'updated_at' => $now,
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
            ->whereIn('code', [
                'accounting_starter_6m',
                'accounting_growth_6m',
                'accounting_scale_6m',
                'accounting_starter_yearly',
                'accounting_growth_yearly',
                'accounting_scale_yearly',
            ])
            ->delete();
    }

    private function databaseBoolean(bool $value): bool|string
    {
        return DB::connection()->getDriverName() === 'pgsql'
            ? ($value ? 'true' : 'false')
            : $value;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function plans(): array
    {
        return [
            $this->starter('accounting_starter_6m', 'semiannual', 1344000, 520, 'Hemat sekitar 10% untuk komitmen 6 bulan.', 'Cocok untuk UMKM yang ingin lebih tenang selama 6 bulan.', true),
            $this->growth('accounting_growth_6m', 'semiannual', 2694000, 530, 'Paket rekomendasi 6 bulan untuk operasional yang mulai rapi.', 'Untuk bisnis yang sudah aktif dan ingin lebih hemat untuk komitmen 6 bulan.', true),
            $this->scale('accounting_scale_6m', 'semiannual', 5394000, 540, 'Paket 6 bulan untuk tim besar dengan kapasitas lebih longgar.', 'Untuk operasional padat yang ingin harga lebih efisien selama 6 bulan.', true),
            $this->starter('accounting_starter_yearly', 'yearly', 2490000, 620, 'Hemat sekitar 17% untuk komitmen tahunan.', 'Cocok untuk UMKM yang sudah yakin ingin jalan rapi selama setahun.', true),
            $this->growth('accounting_growth_yearly', 'yearly', 4990000, 630, 'Paket tahunan paling pas untuk bisnis yang ingin operasional stabil.', 'Untuk bisnis yang ingin purchases, inventory, dan full reports aktif sepanjang tahun.', true),
            $this->scale('accounting_scale_yearly', 'yearly', 9990000, 640, 'Paket tahunan untuk operasional besar dengan kapasitas penuh.', 'Untuk tim multi-user dan multi-branch yang ingin lebih efisien secara tahunan.', true),
        ];
    }

    private function starter(string $code, string $interval, int $price, int $sortOrder, string $tagline, string $audience, bool $public): array
    {
        return [
            'code' => $code,
            'name' => 'Starter',
            'billing_interval' => $interval,
            'is_active' => true,
            'is_public' => $public,
            'is_system' => false,
            'sort_order' => $sortOrder,
            'features' => [
                PlanFeature::MULTI_COMPANY => false,
                PlanFeature::CONVERSATIONS => false,
                PlanFeature::CRM => false,
                PlanFeature::COMMERCE => true,
                PlanFeature::PROJECT_MANAGEMENT => false,
                PlanFeature::LIVE_CHAT => false,
                PlanFeature::SOCIAL_MEDIA => false,
                PlanFeature::CHATBOT_AI => false,
                PlanFeature::CHATBOT_BYO_AI => false,
                PlanFeature::EMAIL_MARKETING => false,
                PlanFeature::WHATSAPP_API => false,
                PlanFeature::WHATSAPP_WEB => false,
                PlanFeature::PURCHASES => false,
                PlanFeature::INVENTORY => false,
                PlanFeature::ADVANCED_REPORTS => false,
                PlanFeature::POINT_OF_SALE => false,
                'multi_branch' => false,
                'finance' => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES => 1,
                PlanLimit::BRANCHES => 1,
                PlanLimit::USERS => 5,
                PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                PlanLimit::PRODUCTS => 250,
                PlanLimit::CONTACTS => 1000,
            ],
            'meta' => [
                'price' => $price,
                'currency' => 'IDR',
                'tagline' => $tagline,
                'description' => $audience,
                'highlights' => [
                    'Sales, payments, finance, products, dan contacts',
                    'Basic reports untuk pembacaan cepat',
                    $interval === 'yearly' ? 'Komitmen 1 tahun dengan harga lebih hemat' : 'Komitmen 6 bulan dengan harga lebih hemat',
                    'POS Add-on tersedia',
                ],
                'addons' => [
                    'point_of_sale' => [
                        'price' => 99000,
                        'currency' => 'IDR',
                    ],
                ],
                'product_line' => 'accounting',
                'plan_revision' => 'v1',
                'sales_status' => 'public',
                'recommended' => false,
            ],
        ];
    }

    private function growth(string $code, string $interval, int $price, int $sortOrder, string $tagline, string $audience, bool $public): array
    {
        return [
            'code' => $code,
            'name' => 'Growth',
            'billing_interval' => $interval,
            'is_active' => true,
            'is_public' => $public,
            'is_system' => false,
            'sort_order' => $sortOrder,
            'features' => [
                PlanFeature::MULTI_COMPANY => true,
                PlanFeature::CONVERSATIONS => false,
                PlanFeature::CRM => false,
                PlanFeature::COMMERCE => true,
                PlanFeature::PROJECT_MANAGEMENT => false,
                PlanFeature::LIVE_CHAT => false,
                PlanFeature::SOCIAL_MEDIA => false,
                PlanFeature::CHATBOT_AI => false,
                PlanFeature::CHATBOT_BYO_AI => false,
                PlanFeature::EMAIL_MARKETING => false,
                PlanFeature::WHATSAPP_API => false,
                PlanFeature::WHATSAPP_WEB => false,
                PlanFeature::PURCHASES => true,
                PlanFeature::INVENTORY => true,
                PlanFeature::ADVANCED_REPORTS => true,
                PlanFeature::POINT_OF_SALE => false,
                'multi_branch' => true,
                'finance' => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES => 1,
                PlanLimit::BRANCHES => 3,
                PlanLimit::USERS => 15,
                PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                PlanLimit::PRODUCTS => 2000,
                PlanLimit::CONTACTS => 5000,
            ],
            'meta' => [
                'price' => $price,
                'currency' => 'IDR',
                'tagline' => $tagline,
                'description' => $audience,
                'highlights' => [
                    'Semua fitur Starter',
                    'Purchases dan inventory aktif',
                    'Full reports operasional',
                    $interval === 'yearly' ? 'Komitmen 1 tahun dengan harga lebih hemat' : 'Komitmen 6 bulan dengan harga lebih hemat',
                    'POS Add-on tersedia',
                ],
                'addons' => [
                    'point_of_sale' => [
                        'price' => 149000,
                        'currency' => 'IDR',
                    ],
                ],
                'product_line' => 'accounting',
                'plan_revision' => 'v1',
                'sales_status' => 'public',
                'recommended' => true,
            ],
        ];
    }

    private function scale(string $code, string $interval, int $price, int $sortOrder, string $tagline, string $audience, bool $public): array
    {
        return [
            'code' => $code,
            'name' => 'Scale',
            'billing_interval' => $interval,
            'is_active' => true,
            'is_public' => $public,
            'is_system' => false,
            'sort_order' => $sortOrder,
            'features' => [
                PlanFeature::MULTI_COMPANY => true,
                PlanFeature::CONVERSATIONS => false,
                PlanFeature::CRM => false,
                PlanFeature::COMMERCE => true,
                PlanFeature::PROJECT_MANAGEMENT => false,
                PlanFeature::LIVE_CHAT => false,
                PlanFeature::SOCIAL_MEDIA => false,
                PlanFeature::CHATBOT_AI => false,
                PlanFeature::CHATBOT_BYO_AI => false,
                PlanFeature::EMAIL_MARKETING => false,
                PlanFeature::WHATSAPP_API => false,
                PlanFeature::WHATSAPP_WEB => false,
                PlanFeature::PURCHASES => true,
                PlanFeature::INVENTORY => true,
                PlanFeature::ADVANCED_REPORTS => true,
                PlanFeature::POINT_OF_SALE => false,
                'multi_branch' => true,
                'finance' => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES => 3,
                PlanLimit::BRANCHES => 10,
                PlanLimit::USERS => 50,
                PlanLimit::TOTAL_STORAGE_BYTES => 21474836480,
                PlanLimit::PRODUCTS => 10000,
                PlanLimit::CONTACTS => 20000,
            ],
            'meta' => [
                'price' => $price,
                'currency' => 'IDR',
                'tagline' => $tagline,
                'description' => $audience,
                'highlights' => [
                    'Semua fitur Growth',
                    'Kapasitas user, branch, produk, dan kontak lebih besar',
                    'Full reports operasional',
                    $interval === 'yearly' ? 'Komitmen 1 tahun dengan harga lebih hemat' : 'Komitmen 6 bulan dengan harga lebih hemat',
                    'POS Add-on tersedia',
                ],
                'addons' => [
                    'point_of_sale' => [
                        'price' => 199000,
                        'currency' => 'IDR',
                    ],
                ],
                'product_line' => 'accounting',
                'plan_revision' => 'v1',
                'sales_status' => 'public',
                'recommended' => false,
            ],
        ];
    }
};
