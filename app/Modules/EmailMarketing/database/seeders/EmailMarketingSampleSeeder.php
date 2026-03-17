<?php

namespace App\Modules\EmailMarketing\Database\Seeders;

use App\Models\User;
use App\Modules\Contacts\Database\Seeders\ContactSampleSeeder;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\Models\EmailAttachmentTemplate;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use Illuminate\Database\Seeder;

class EmailMarketingSampleSeeder extends Seeder
{
    public function run(): void
    {
        (new ContactSampleSeeder())->run();

        $user = User::query()->where('email', 'superadmin@myapp.test')->first() ?? User::query()->first();
        $userId = optional($user)->id;
        $contact = Contact::query()->where('email', 'procurement@demo-nusantara.test')->first();

        $template = EmailAttachmentTemplate::query()->updateOrCreate(
            ['name' => 'Proposal Attachment Demo'],
            [
                'description' => 'Template attachment sample.',
                'filename' => 'proposal-demo.pdf',
                'html' => '<h1>Proposal Demo</h1><p>Dokumen contoh untuk campaign email.</p>',
                'mime' => 'application/pdf',
                'created_by' => $userId,
                'paper_size' => 'A4',
            ]
        );

        $campaign = EmailCampaign::query()->updateOrCreate(
            ['name' => 'Campaign Demo Launch'],
            [
                'subject' => 'Promo Demo Launch Minggu Ini',
                'status' => 'draft',
                'body_html' => '<p>Halo, berikut promo demo terbaru untuk pelanggan prioritas.</p>',
                'filter_json' => ['segment' => 'vip'],
                'scheduled_at' => now()->addDay(),
            ]
        );

        $campaign->dynamicTemplates()->syncWithoutDetaching([$template->id]);

        if ($contact) {
            EmailCampaignRecipient::query()->updateOrCreate(
                ['tracking_token' => 'email-demo-tracking-001'],
                [
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact->id,
                    'recipient_name' => $contact->name,
                    'recipient_email' => $contact->email,
                    'delivery_status' => 'delivered',
                    'delivered_at' => now()->subHours(6),
                    'opened_at' => now()->subHours(5),
                ]
            );
        }
    }
}
