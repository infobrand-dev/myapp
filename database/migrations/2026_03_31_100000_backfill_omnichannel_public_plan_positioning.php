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

        $now = now();

        $definitions = [
            'starter-v2' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                ],
                'meta' => [
                    'price' => 149000,
                    'currency' => 'IDR',
                    'tagline' => 'Social inbox, live chat, dan CRM lite untuk tim kecil yang baru mulai omnichannel.',
                    'description' => 'Cocok untuk UKM sales dan customer service yang ingin mulai dari social inbox, live chat website, dan pipeline follow-up tanpa biaya AI atau channel WhatsApp.',
                    'highlights' => [
                        'Shared inbox untuk percakapan tim',
                        'CRM lite untuk follow-up lead',
                        'Live chat website',
                        'Social media inbox',
                        'Belum termasuk AI dan WhatsApp',
                    ],
                    'product_line' => 'omnichannel',
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'starter',
                    'recommended' => false,
                ],
            ],
            'growth-v2' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                ],
                'meta' => [
                    'price' => 349000,
                    'currency' => 'IDR',
                    'tagline' => 'Paket rekomendasi untuk omnichannel aktif dengan AI, WhatsApp API, dan WhatsApp Web.',
                    'description' => 'Untuk tim yang mulai serius mengelola lead dan support lintas channel, dengan AI, WhatsApp API, dan WhatsApp Web yang dihubungkan dari akun bisnis Anda sendiri.',
                    'highlights' => [
                        'Semua fitur Starter',
                        'CRM lite untuk follow-up lead',
                        'Chatbot AI dengan kuota bawaan',
                        '500 AI Credits per bulan + top up tersedia',
                        'WhatsApp API',
                        'WhatsApp Web',
                        'Limit channel tetap terukur',
                    ],
                    'product_line' => 'omnichannel',
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'growth',
                    'recommended' => true,
                ],
            ],
            'scale-v2' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::CRM => true,
                    PlanFeature::LIVE_CHAT => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                ],
                'meta' => [
                    'price' => 799000,
                    'currency' => 'IDR',
                    'tagline' => 'Stack omnichannel lengkap untuk tim yang butuh kapasitas besar dan channel penuh.',
                    'description' => 'Paket premium self-serve untuk operasional yang butuh social inbox, AI, WhatsApp API, dan WhatsApp Web dengan batas user, kontak, dan channel yang lebih tinggi.',
                    'highlights' => [
                        'Semua fitur Growth',
                        'CRM lite untuk follow-up lead',
                        '2.500 AI Credits per bulan + top up tersedia',
                        'WhatsApp Web',
                        'Kapasitas user, kontak, dan channel lebih besar',
                        'Advanced reports',
                    ],
                    'product_line' => 'omnichannel',
                    'plan_revision' => 'v2',
                    'sales_status' => 'current',
                    'replaces_legacy_code' => 'scale',
                    'recommended' => false,
                ],
            ],
        ];

        foreach ($definitions as $code => $definition) {
            $plan = DB::table('subscription_plans')->where('code', $code)->first();
            if (!$plan) {
                continue;
            }

            $existingFeatures = json_decode((string) ($plan->features ?? '[]'), true) ?: [];
            $existingMeta = json_decode((string) ($plan->meta ?? '[]'), true) ?: [];

            DB::table('subscription_plans')
                ->where('code', $code)
                ->update([
                    'features' => json_encode(array_merge($existingFeatures, $definition['features'])),
                    'meta' => json_encode(array_merge($existingMeta, $definition['meta'])),
                    'updated_at' => $now,
                ]);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('subscription_plans')) {
            return;
        }

        DB::table('subscription_plans')
            ->whereIn('code', ['starter-v2', 'growth-v2', 'scale-v2'])
            ->update(['updated_at' => now()]);
    }
};
