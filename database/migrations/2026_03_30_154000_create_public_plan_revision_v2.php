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

        $legacyPublicCodes = ['free', 'starter', 'growth', 'scale'];

        DB::table('subscription_plans')
            ->whereIn('code', $legacyPublicCodes)
            ->update([
                'is_public' => false,
                'updated_at' => $now,
            ]);

        $plans = [
            [
                'code' => 'starter-v2',
                'name' => 'Starter',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 120,
                'features' => [
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::PRODUCTS => 100,
                    PlanLimit::CONTACTS => 2000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::SOCIAL_ACCOUNTS => 2,
                    PlanLimit::LIVE_CHAT_WIDGETS => 1,
                    PlanLimit::CHATBOT_ACCOUNTS => 0,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 0,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 0,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 149000,
                    'currency' => 'IDR',
                    'tagline' => 'Inbox sosial media, live chat, dan CRM lite untuk mulai jualan dengan aman.',
                    'description' => 'Plan public konservatif untuk tim kecil yang butuh social inbox, live chat, dan pipeline follow-up dasar tanpa biaya AI atau channel mahal.',
                    'highlights' => [
                        'Conversation inbox tim',
                        'CRM lead pipeline dasar',
                        'Live chat widget website',
                        'Social media conversation',
                        'Kapasitas terkendali untuk launch',
                    ],
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'starter',
                ],
            ],
            [
                'code' => 'growth-v2',
                'name' => 'Growth',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 130,
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::ADVANCED_REPORTS => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::BRANCHES => 3,
                    PlanLimit::USERS => 15,
                    PlanLimit::PRODUCTS => 1000,
                    PlanLimit::CONTACTS => 10000,
                    PlanLimit::WHATSAPP_INSTANCES => 1,
                    PlanLimit::SOCIAL_ACCOUNTS => 5,
                    PlanLimit::LIVE_CHAT_WIDGETS => 2,
                    PlanLimit::CHATBOT_ACCOUNTS => 2,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 1,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 1500,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 500,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 25,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 349000,
                    'currency' => 'IDR',
                    'tagline' => 'Omnichannel aktif dengan chatbot AI, WhatsApp API, dan batas biaya yang lebih aman.',
                    'description' => 'Untuk tim yang mulai serius dengan AI, otomasi awal, dan WhatsApp Business API tanpa membuka limit mahal secara terlalu longgar.',
                    'highlights' => [
                        'Semua fitur Starter',
                        'CRM lead pipeline',
                        'Chatbot AI',
                        '500 AI Credits per bulan',
                        'WhatsApp API',
                        'Limit koneksi dan recipient lebih terjaga',
                    ],
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'growth',
                ],
            ],
            [
                'code' => 'scale-v2',
                'name' => 'Scale',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 140,
                'features' => [
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                    PlanFeature::ADVANCED_REPORTS => true,
                ],
                'limits' => [
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::BRANCHES => 10,
                    PlanLimit::USERS => 50,
                    PlanLimit::PRODUCTS => 5000,
                    PlanLimit::CONTACTS => 50000,
                    PlanLimit::WHATSAPP_INSTANCES => 5,
                    PlanLimit::SOCIAL_ACCOUNTS => 15,
                    PlanLimit::LIVE_CHAT_WIDGETS => 5,
                    PlanLimit::CHATBOT_ACCOUNTS => 10,
                    PlanLimit::EMAIL_INBOX_ACCOUNTS => 3,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                    PlanLimit::WA_BLAST_RECIPIENTS_MONTHLY => 10000,
                    PlanLimit::EMAIL_RECIPIENTS_MONTHLY => 0,
                    PlanLimit::AI_CREDITS_MONTHLY => 2500,
                    PlanLimit::CHATBOT_KNOWLEDGE_DOCUMENTS => 200,
                    PlanLimit::AUTOMATION_WORKFLOWS => 0,
                    PlanLimit::AUTOMATION_EXECUTIONS_MONTHLY => 0,
                ],
                'meta' => [
                    'price' => 799000,
                    'currency' => 'IDR',
                    'tagline' => 'Stack omnichannel penuh dengan WhatsApp Web dan kapasitas besar, tetap terukur.',
                    'description' => 'Paket untuk tim operasional yang membutuhkan social inbox, chatbot AI, WhatsApp API, dan WhatsApp Web dengan limit konservatif yang tetap scalable.',
                    'highlights' => [
                        'Semua fitur Growth',
                        'CRM pipeline untuk follow up lead',
                        '2.500 AI Credits per bulan',
                        'WhatsApp Web',
                        'Kapasitas user, kontak, dan channel lebih besar',
                        'Advanced reports',
                    ],
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'scale',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            DB::table('subscription_plans')->updateOrInsert(
                ['code' => $plan['code']],
                [
                    'name' => $plan['name'],
                    'billing_interval' => $plan['billing_interval'],
                    'is_active' => $plan['is_active'],
                    'is_public' => $plan['is_public'],
                    'is_system' => $plan['is_system'],
                    'sort_order' => $plan['sort_order'],
                    'features' => json_encode($plan['features']),
                    'limits' => json_encode($plan['limits']),
                    'meta' => json_encode($plan['meta']),
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
            ->whereIn('code', ['starter-v2', 'growth-v2', 'scale-v2'])
            ->delete();

        DB::table('subscription_plans')
            ->whereIn('code', ['free', 'starter', 'growth', 'scale'])
            ->update([
                'is_public' => true,
                'updated_at' => now(),
            ]);
    }
};
