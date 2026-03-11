<?php

namespace App\Modules\WhatsAppApi\Database\Seeders;

use App\Models\User;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WhatsAppInstanceDummySeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()
            ->where('email', 'superadmin@myapp.test')
            ->first() ?? User::query()->first();

        $cloud = WhatsAppInstance::firstOrCreate(
            ['name' => 'Dummy WA Cloud - Sales'],
            [
                'phone_number' => '6281110000001',
                'provider' => 'cloud',
                'status' => 'connected',
                'is_active' => true,
                'phone_number_id' => '123456789012345',
                'cloud_business_account_id' => '987654321098765',
                'cloud_token' => 'EAA_DUMMY_CLOUD_TOKEN_REPLACE_ME',
                'settings' => [
                    'wa_cloud_verify_token' => 'wa_verify_dummy_sales',
                    'wa_cloud_app_secret' => 'dummy_app_secret_sales',
                ],
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        $gateway = WhatsAppInstance::firstOrCreate(
            ['name' => 'Dummy WA Gateway - Support'],
            [
                'phone_number' => '6281110000002',
                'provider' => 'third_party',
                'api_base_url' => 'https://wa-gateway.example.test',
                'api_token' => 'dummy_gateway_token_support',
                'status' => 'connected',
                'is_active' => true,
                'created_by' => $user?->id,
                'updated_by' => $user?->id,
            ]
        );

        if ($user && Schema::hasTable('whatsapp_instance_user')) {
            $cloud->users()->syncWithoutDetaching([
                $user->id => ['role' => 'owner'],
            ]);
            $gateway->users()->syncWithoutDetaching([
                $user->id => ['role' => 'owner'],
            ]);
        }

        if ($this->command) {
            $this->command->info('Dummy WhatsApp instances ready:');
            $this->command->line('- ' . $cloud->name);
            $this->command->line('- ' . $gateway->name);
        }
    }
}
