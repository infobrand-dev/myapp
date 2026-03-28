<?php

use App\Support\PlanFeature;
use App\Support\PlanLimit;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('billing_interval')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_public')->default(true);
            $table->boolean('is_system')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('features')->nullable();
            $table->json('limits')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        DB::table('subscription_plans')->insert([
            [
                'code' => 'internal-unlimited',
                'name' => 'Internal Unlimited',
                'billing_interval' => null,
                'is_active' => true,
                'is_public' => false,
                'is_system' => true,
                'sort_order' => 0,
                'features' => json_encode([
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::EMAIL_MARKETING => true,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                    PlanFeature::ADVANCED_REPORTS => true,
                ]),
                'limits' => json_encode([]),
                'meta' => json_encode([
                    'purpose' => 'bootstrap',
                    'notes' => 'Dipakai untuk tenant default/internal sebelum website billing aktif.',
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'starter',
                'name' => 'Starter',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 10,
                'features' => json_encode([
                    PlanFeature::MULTI_COMPANY => false,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => false,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => false,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::ADVANCED_REPORTS => false,
                ]),
                'limits' => json_encode([
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::USERS => 5,
                    PlanLimit::PRODUCTS => 100,
                    PlanLimit::CONTACTS => 2000,
                    PlanLimit::WHATSAPP_INSTANCES => 0,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                ]),
                'meta' => json_encode([
                    'price' => 149000,
                    'currency' => 'IDR',
                    'tagline' => 'Inbox sosial media dan percakapan tim untuk mulai jualan.',
                    'description' => 'Paket awal untuk tim yang mulai mengelola lead dan customer service dari sosial media.',
                    'highlights' => [
                        'Conversation inbox tim',
                        'Social media conversation',
                        'Kontak dan histori percakapan dasar',
                    ],
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'growth',
                'name' => 'Growth',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 20,
                'features' => json_encode([
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => false,
                    PlanFeature::ADVANCED_REPORTS => true,
                ]),
                'limits' => json_encode([
                    PlanLimit::COMPANIES => 1,
                    PlanLimit::USERS => 15,
                    PlanLimit::PRODUCTS => 1000,
                    PlanLimit::CONTACTS => 10000,
                    PlanLimit::WHATSAPP_INSTANCES => 1,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                ]),
                'meta' => json_encode([
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
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'code' => 'scale',
                'name' => 'Scale',
                'billing_interval' => 'monthly',
                'is_active' => true,
                'is_public' => true,
                'is_system' => false,
                'sort_order' => 30,
                'features' => json_encode([
                    PlanFeature::MULTI_COMPANY => true,
                    PlanFeature::CONVERSATIONS => true,
                    PlanFeature::SOCIAL_MEDIA => true,
                    PlanFeature::CHATBOT_AI => true,
                    PlanFeature::EMAIL_MARKETING => false,
                    PlanFeature::WHATSAPP_API => true,
                    PlanFeature::WHATSAPP_WEB => true,
                    PlanFeature::ADVANCED_REPORTS => true,
                ]),
                'limits' => json_encode([
                    PlanLimit::COMPANIES => 3,
                    PlanLimit::USERS => 50,
                    PlanLimit::PRODUCTS => 5000,
                    PlanLimit::CONTACTS => 50000,
                    PlanLimit::WHATSAPP_INSTANCES => 5,
                    PlanLimit::EMAIL_CAMPAIGNS => 0,
                ]),
                'meta' => json_encode([
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
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
