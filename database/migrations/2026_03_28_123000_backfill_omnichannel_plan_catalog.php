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

        $catalog = [
            'starter' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                ],
                'meta' => [
                    'price' => 149000,
                    'currency' => 'IDR',
                    'tagline' => 'Inbox sosial media dan percakapan tim untuk mulai jualan.',
                    'description' => 'Paket awal untuk tim yang mulai mengelola lead dan customer service dari sosial media.',
                    'highlights' => [
                        'Conversation inbox tim',
                        'Social media conversation',
                        'Kontak dan histori percakapan dasar',
                    ],
                ],
            ],
            'growth' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => false,
                ],
                'meta' => [
                    'price' => 349000,
                    'currency' => 'IDR',
                    'tagline' => 'Omnichannel aktif dengan chatbot AI dan WhatsApp API.',
                    'description' => 'Untuk tim yang mulai serius dengan otomasi, balasan AI, dan WhatsApp Business API.',
                    'highlights' => [
                        'Semua fitur Starter',
                        'Chatbot AI',
                        'WhatsApp API',
                        'Reporting dasar omnichannel',
                    ],
                ],
            ],
            'scale' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                ],
                'meta' => [
                    'price' => 799000,
                    'currency' => 'IDR',
                    'tagline' => 'Stack omnichannel penuh dengan WhatsApp Web dan kapasitas lebih besar.',
                    'description' => 'Paket untuk tim operasional yang butuh social inbox, chatbot AI, WhatsApp API, dan WhatsApp Web.',
                    'highlights' => [
                        'Semua fitur Growth',
                        'WhatsApp Web',
                        'Kapasitas user dan kontak lebih besar',
                        'Advanced reports',
                    ],
                ],
            ],
            'internal-unlimited' => [
                'features' => [
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                ],
                'meta' => [
                    'purpose' => 'bootstrap',
                ],
            ],
        ];

        foreach ($catalog as $code => $payload) {
            $row = DB::table('subscription_plans')->where('code', $code)->first();
            if (!$row) {
                continue;
            }

            $features = json_decode($row->features ?? '[]', true) ?: [];
            $meta = json_decode($row->meta ?? '[]', true) ?: [];

            DB::table('subscription_plans')
                ->where('id', $row->id)
                ->update([
                    'features' => json_encode(array_merge($features, $payload['features'])),
                    'meta' => json_encode(array_merge($meta, $payload['meta'])),
                    'updated_at' => now(),
                ]);
        }
    }

    public function down(): void
    {
    }
};
