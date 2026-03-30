<?php

namespace Database\Seeders;

use App\Support\PlanFeature;
use App\Support\PlanLimit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Default public-facing subscription plans.
     *
     * Limits use the PlanLimit constants as keys.
     * A limit of -1 means unlimited.
     * Features is a map of feature-key => bool.
     */
    private array $plans = [
        [
            'code' => 'free',
            'name' => 'Free',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'sort_order' => 10,
            'features' => [
                PlanFeature::MULTI_COMPANY => false,
                PlanFeature::CONVERSATIONS => false,
                PlanFeature::CRM => false,
                PlanFeature::LIVE_CHAT => false,
                PlanFeature::SOCIAL_MEDIA => false,
                PlanFeature::CHATBOT_AI => false,
                PlanFeature::EMAIL_MARKETING => false,
                PlanFeature::WHATSAPP_API => false,
                PlanFeature::WHATSAPP_WEB => false,
                PlanFeature::ADVANCED_REPORTS => false,
            ],
            'limits' => [
                PlanLimit::COMPANIES => 1,
                PlanLimit::USERS => 3,
                PlanLimit::PRODUCTS => 50,
                PlanLimit::CONTACTS => 200,
                PlanLimit::WHATSAPP_INSTANCES => 0,
                PlanLimit::EMAIL_CAMPAIGNS => 0,
                PlanLimit::AI_CREDITS_MONTHLY => 0,
            ],
            'meta' => [
                'price' => 0,
                'currency' => 'IDR',
                'tagline' => 'Untuk mencoba workspace sebelum masuk paket berbayar.',
                'description' => 'Tidak direkomendasikan untuk launch komersial.',
                'highlights' => [
                    'Tenant trial internal',
                ],
            ],
        ],
        [
            'code' => 'starter',
            'name' => 'Starter',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'sort_order' => 20,
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
                PlanLimit::USERS => 5,
                PlanLimit::PRODUCTS => 100,
                PlanLimit::CONTACTS => 2000,
                PlanLimit::WHATSAPP_INSTANCES => 0,
                PlanLimit::EMAIL_CAMPAIGNS => 0,
                PlanLimit::AI_CREDITS_MONTHLY => 0,
            ],
            'meta' => [
                'price' => 149000,
                'currency' => 'IDR',
                'tagline' => 'Inbox sosial media dan percakapan tim untuk mulai jualan.',
                'description' => 'Paket awal untuk tim yang mulai mengelola lead dan customer service dari sosial media.',
                'highlights' => [
                    'Conversation inbox tim',
                    'CRM lead pipeline dasar',
                    'Live chat widget website',
                    'Social media conversation',
                    'Kontak dan histori percakapan dasar',
                ],
            ],
        ],
        [
            'code' => 'growth',
            'name' => 'Growth',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'sort_order' => 30,
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
                PlanLimit::USERS => 15,
                PlanLimit::PRODUCTS => 1000,
                PlanLimit::CONTACTS => 10000,
                PlanLimit::WHATSAPP_INSTANCES => 1,
                PlanLimit::EMAIL_CAMPAIGNS => 0,
                PlanLimit::AI_CREDITS_MONTHLY => 500,
            ],
            'meta' => [
                'price' => 349000,
                'currency' => 'IDR',
                'tagline' => 'Omnichannel aktif dengan chatbot AI dan WhatsApp API.',
                'description' => 'Untuk tim yang mulai serius dengan otomasi, balasan AI, dan WhatsApp Business API.',
                'highlights' => [
                    'Semua fitur Starter',
                    'CRM lead pipeline',
                    'Chatbot AI',
                    '500 AI Credits per bulan',
                    'WhatsApp API',
                    'Reporting dasar omnichannel',
                ],
            ],
        ],
        [
            'code' => 'scale',
            'name' => 'Scale',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => true,
            'is_system' => false,
            'sort_order' => 40,
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
                PlanLimit::USERS => 50,
                PlanLimit::PRODUCTS => 5000,
                PlanLimit::CONTACTS => 50000,
                PlanLimit::WHATSAPP_INSTANCES => 5,
                PlanLimit::EMAIL_CAMPAIGNS => 0,
                PlanLimit::AI_CREDITS_MONTHLY => 2500,
            ],
            'meta' => [
                'price' => 799000,
                'currency' => 'IDR',
                'tagline' => 'Stack omnichannel penuh dengan WhatsApp Web dan kapasitas lebih besar.',
                'description' => 'Paket untuk tim operasional yang butuh social inbox, chatbot AI, WhatsApp API, dan WhatsApp Web.',
                'highlights' => [
                    'Semua fitur Growth',
                    'CRM pipeline untuk follow up lead',
                    '2.500 AI Credits per bulan',
                    'WhatsApp Web',
                    'Kapasitas user dan kontak lebih besar',
                    'Advanced reports',
                ],
            ],
        ],
        [
            'code' => 'internal-unlimited',
            'name' => 'Internal (Unlimited)',
            'billing_interval' => 'monthly',
            'is_active' => true,
            'is_public' => false,
            'is_system' => true,
            'sort_order' => 0,
            'features' => [
                PlanFeature::MULTI_COMPANY => true,
                PlanFeature::CONVERSATIONS => true,
                PlanFeature::CRM => true,
                PlanFeature::LIVE_CHAT => true,
                PlanFeature::SOCIAL_MEDIA => true,
                PlanFeature::CHATBOT_AI => true,
                PlanFeature::EMAIL_MARKETING => true,
                PlanFeature::WHATSAPP_API => true,
                PlanFeature::WHATSAPP_WEB => true,
                PlanFeature::ADVANCED_REPORTS => true,
            ],
            'limits' => [
                PlanLimit::COMPANIES => -1,
                PlanLimit::USERS => -1,
                PlanLimit::PRODUCTS => -1,
                PlanLimit::CONTACTS => -1,
                PlanLimit::WHATSAPP_INSTANCES => -1,
                PlanLimit::EMAIL_CAMPAIGNS => -1,
                PlanLimit::AI_CREDITS_MONTHLY => -1,
            ],
            'meta' => [
                'purpose' => 'bootstrap',
                'notes' => 'Dipakai untuk tenant internal/platform owner.',
            ],
        ],
    ];

    public function run(): void
    {
        if (!DB::getSchemaBuilder()->hasTable('subscription_plans')) {
            return;
        }

        foreach ($this->plans as $plan) {
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
                    'meta' => isset($plan['meta']) ? json_encode($plan['meta']) : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
