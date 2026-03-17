<?php

namespace App\Modules\WhatsAppBro\Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppBroSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'superadmin@myapp.test')->first() ?? User::query()->first();

        DB::table('whatsapp_api_settings')->updateOrInsert(
            ['provider' => 'whatsapp_bro_demo'],
            [
                'base_url' => 'http://localhost:3020',
                'phone_number_id' => 'demo-phone-id',
                'waba_id' => 'demo-waba-id',
                'access_token' => 'demo-whatsapp-bro-token',
                'verify_token' => 'demo-whatsapp-bro-verify',
                'default_sender_name' => 'Demo Support',
                'is_active' => true,
                'timeout_seconds' => 30,
                'notes' => 'Setting sample untuk modul WhatsApp Bro.',
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
                'last_tested_at' => now()->subMinutes(10),
                'last_test_status' => 'success',
                'last_test_message' => 'Sample connection ready.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
