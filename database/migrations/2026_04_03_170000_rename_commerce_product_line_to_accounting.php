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
        $this->backfillPlanProductLine();
        $this->upsertAccountingPlans();
        $this->backfillTableProductLine('tenant_subscriptions');
        $this->backfillTableProductLine('platform_plan_orders');
        $this->backfillTableProductLine('platform_invoices');
    }

    public function down(): void
    {
        $this->backfillTableProductLine('platform_invoices', 'accounting', 'commerce');
        $this->backfillTableProductLine('platform_plan_orders', 'accounting', 'commerce');
        $this->backfillTableProductLine('tenant_subscriptions', 'accounting', 'commerce');

        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->whereIn('code', ['accounting_starter', 'accounting_growth', 'accounting_scale'])
            ->delete();

        DB::table('subscription_plans')
            ->select(['id', 'name', 'meta'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $meta = json_decode((string) ($plan->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];

                if (($meta['product_line'] ?? null) !== 'accounting') {
                    return;
                }

                $meta['product_line'] = 'commerce';
                $name = $plan->name === 'Accounting' ? 'Commerce' : $plan->name;

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'name' => $name,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function backfillPlanProductLine(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->select(['id', 'name', 'meta'])
            ->orderBy('id')
            ->get()
            ->each(function ($plan): void {
                $meta = json_decode((string) ($plan->meta ?? '{}'), true);
                $meta = is_array($meta) ? $meta : [];

                if (($meta['product_line'] ?? null) !== 'commerce') {
                    return;
                }

                $meta['product_line'] = 'accounting';
                $name = $plan->name === 'Commerce' ? 'Accounting' : $plan->name;

                DB::table('subscription_plans')
                    ->where('id', $plan->id)
                    ->update([
                        'name' => $name,
                        'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        'updated_at' => now(),
                    ]);
            });
    }

    private function upsertAccountingPlans(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        $now = now();

        foreach ($this->accountingPlans() as $plan) {
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

    private function backfillTableProductLine(string $table, string $from = 'commerce', string $to = 'accounting'): void
    {
        if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'product_line')) {
            return;
        }

        DB::table($table)
            ->where('product_line', $from)
            ->update([
                'product_line' => $to,
                'updated_at' => now(),
            ]);
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
    private function accountingPlans(): array
    {
        return [
            [
                'code' => 'accounting_starter',
                'name' => 'Starter',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 420,
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
                    PlanFeature::ADVANCED_REPORTS => true,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => false,
                    'inventory' => false,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::TOTAL_STORAGE_BYTES => 1073741824,
                    PlanLimit::PRODUCTS => 250,
                    PlanLimit::CONTACTS => 1000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 249000,
                    'currency' => 'IDR',
                    'tagline' => 'Paket awal accounting untuk penjualan, pembelian, pembayaran, finance ringan, dan reporting dasar. POS tersedia sebagai add-on.',
                    'description' => 'Cocok untuk tim kecil yang mulai merapikan operasional transaksi dalam satu workspace tanpa membuka channel omnichannel.',
                    'highlights' => [
                        'Sales dan purchases operasional',
                        'Payments dan finance ringan',
                        'POS Add-on tersedia',
                        'Reporting dasar',
                        'Kapasitas awal untuk tim kecil',
                    ],
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 99000,
                            'currency' => 'IDR',
                        ],
                    ],
                    'product_line' => 'accounting',
                    'plan_revision' => 'v1',
                    'sales_status' => 'internal',
                    'recommended' => false,
                ],
            ],
            [
                'code' => 'accounting_growth',
                'name' => 'Growth',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 430,
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
                    PlanFeature::ADVANCED_REPORTS => true,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => true,
                    'inventory' => false,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 15,
                    PlanLimit::TOTAL_STORAGE_BYTES => 5368709120,
                    PlanLimit::PRODUCTS => 2000,
                    PlanLimit::CONTACTS => 5000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 499000,
                    'currency' => 'IDR',
                    'tagline' => 'Paket rekomendasi untuk tim yang sudah aktif menangani transaksi harian lintas sales, purchases, payments, finance, dan reporting. POS tersedia sebagai add-on.',
                    'description' => 'Dirancang untuk operasional yang mulai padat dengan kebutuhan user, branch, storage, dan kapasitas data yang lebih besar.',
                    'highlights' => [
                        'Semua capability Starter',
                        'POS Add-on tersedia',
                        'Kapasitas user dan branch lebih besar',
                        'Storage dan produk lebih longgar',
                        'Cocok untuk tim operasional yang sedang tumbuh',
                    ],
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 149000,
                            'currency' => 'IDR',
                        ],
                    ],
                    'product_line' => 'accounting',
                    'plan_revision' => 'v1',
                    'sales_status' => 'internal',
                    'recommended' => true,
                ],
            ],
            [
                'code' => 'accounting_scale',
                'name' => 'Scale',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => false,
                'is_system' => false,
                'sort_order' => 440,
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
                    PlanFeature::ADVANCED_REPORTS => true,
                    PlanFeature::POINT_OF_SALE => false,
                    'multi_branch' => true,
                    'inventory' => false,
                    'finance' => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::BRANCHES => 10,
                    PlanLimit::USERS => 50,
                    PlanLimit::TOTAL_STORAGE_BYTES => 21474836480,
                    PlanLimit::PRODUCTS => 10000,
                    PlanLimit::CONTACTS => 20000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 0,
                    PlanLimit::LIVE_CHAT_WIDGETS => 0,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::BYO_CHATBOT_ACCOUNTS => 0,
                    PlanLimit::BYO_AI_REQUESTS_MONTHLY => 0,
                    PlanLimit::BYO_AI_TOKENS_MONTHLY => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 999000,
                    'currency' => 'IDR',
                    'tagline' => 'Kapasitas besar untuk tim multi-user dan multi-branch yang menjalankan operasional transaksi lebih padat.',
                    'description' => 'Paket tertinggi untuk organisasi yang butuh kapasitas produk, kontak, storage, dan branch yang jauh lebih besar tanpa membuka channel omnichannel.',
                    'highlights' => [
                        'Semua capability Growth',
                        'POS Add-on tersedia',
                        'Kapasitas besar untuk user dan branch',
                        'Storage, produk, dan kontak jauh lebih besar',
                        'Cocok untuk operasional yang lebih kompleks',
                    ],
                    'addons' => [
                        'point_of_sale' => [
                            'price' => 199000,
                            'currency' => 'IDR',
                        ],
                    ],
                    'product_line' => 'accounting',
                    'plan_revision' => 'v1',
                    'sales_status' => 'internal',
                    'recommended' => false,
                ],
            ],
        ];
    }
};
