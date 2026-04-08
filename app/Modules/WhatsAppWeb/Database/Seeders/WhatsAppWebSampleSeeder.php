<?php

namespace App\Modules\WhatsAppWeb\Database\Seeders;

use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WhatsAppWebSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        DB::table('whatsapp_web_settings')->updateOrInsert(
            ['provider' => 'whatsapp_web'],
            [
                'base_url' => 'http://localhost:3020',
                'verify_token' => 'demo-whatsapp-web-verify',
                'default_sender_name' => 'Demo Support',
                'is_active' => true,
                'timeout_seconds' => 30,
                'notes' => 'Setting sample untuk modul WhatsApp Web.',
                'created_by' => $userId,
                'updated_by' => $userId,
                'last_tested_at' => now()->subMinutes(10),
                'last_test_status' => 'success',
                'last_test_message' => 'Sample connection ready.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}




