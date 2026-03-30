<?php

namespace App\Modules\EmailInbox\database\seeders;

use App\Modules\EmailInbox\Models\EmailAccount;
use Illuminate\Database\Seeder;

class EmailInboxSampleSeeder extends Seeder
{
    public function run(): void
    {
        EmailAccount::query()->updateOrCreate(
            [
                'tenant_id' => 1,
                'email_address' => 'support@example.test',
            ],
            [
                'name' => 'Support Inbox',
                'provider' => 'sample',
                'direction_mode' => 'inbound_outbound',
                'inbound_protocol' => 'imap',
                'inbound_host' => 'imap.example.test',
                'inbound_port' => 993,
                'inbound_encryption' => 'ssl',
                'inbound_username' => 'support@example.test',
                'inbound_password' => 'replace-me',
                'outbound_host' => 'smtp.example.test',
                'outbound_port' => 587,
                'outbound_encryption' => 'tls',
                'outbound_username' => 'support@example.test',
                'outbound_password' => 'replace-me',
                'outbound_from_name' => 'Support Team',
                'sync_enabled' => false,
            ]
        );
    }
}


