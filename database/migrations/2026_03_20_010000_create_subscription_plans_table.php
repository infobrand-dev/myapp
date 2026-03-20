<?php

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
                    'multi_company' => true,
                    'email_marketing' => true,
                    'whatsapp_api' => true,
                    'advanced_reports' => true,
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
                    'multi_company' => false,
                    'email_marketing' => false,
                    'whatsapp_api' => false,
                    'advanced_reports' => false,
                ]),
                'limits' => json_encode([
                    'max_companies' => 1,
                    'max_users' => 3,
                    'max_products' => 100,
                    'max_contacts' => 1000,
                ]),
                'meta' => null,
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
                    'multi_company' => true,
                    'email_marketing' => true,
                    'whatsapp_api' => true,
                    'advanced_reports' => false,
                ]),
                'limits' => json_encode([
                    'max_companies' => 3,
                    'max_users' => 15,
                    'max_products' => 2000,
                    'max_contacts' => 10000,
                ]),
                'meta' => null,
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
                    'multi_company' => true,
                    'email_marketing' => true,
                    'whatsapp_api' => true,
                    'advanced_reports' => true,
                ]),
                'limits' => json_encode([
                    'max_companies' => 10,
                    'max_users' => 100,
                    'max_products' => 50000,
                    'max_contacts' => 250000,
                ]),
                'meta' => null,
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
