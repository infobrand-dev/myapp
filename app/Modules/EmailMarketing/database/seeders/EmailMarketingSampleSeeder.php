<?php

namespace App\Modules\EmailMarketing\Database\Seeders;

use App\Modules\Contacts\Database\Seeders\ContactSampleSeeder;
use App\Modules\Contacts\Models\Contact;
use App\Modules\EmailMarketing\Models\EmailAttachmentTemplate;
use App\Modules\EmailMarketing\Models\EmailCampaign;
use App\Modules\EmailMarketing\Models\EmailCampaignRecipient;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class EmailMarketingSampleSeeder extends Seeder
{
    private const TENANT_ID = 1;

    public function run(): void
    {
        (new ContactSampleSeeder())->run();

        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;
        $contact = Contact::query()->where('tenant_id', self::TENANT_ID)->where('email', 'procurement@demo-nusantara.test')->first();

        $template = EmailAttachmentTemplate::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'name' => 'Proposal Attachment Demo'],
            [
                'tenant_id' => self::TENANT_ID,
                'description' => 'Template attachment sample.',
                'filename' => 'proposal-demo.pdf',
                'html' => '<h1>Proposal Demo</h1><p>Dokumen contoh untuk campaign email.</p>',
                'mime' => 'application/pdf',
                'created_by' => $userId,
                'paper_size' => 'A4',
            ]
        );

        $campaign = EmailCampaign::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'name' => 'Campaign Demo Launch'],
            [
                'tenant_id' => self::TENANT_ID,
                'subject' => 'Promo Demo Launch Minggu Ini',
                'status' => 'draft',
                'body_html' => '<p>Halo, berikut promo demo terbaru untuk pelanggan prioritas.</p>',
                'filter_json' => ['segment' => 'vip'],
                'scheduled_at' => now()->addDay(),
            ]
        );

        $campaign->dynamicTemplates()->syncWithoutDetaching([
            $template->id => ['tenant_id' => self::TENANT_ID],
        ]);

        if ($contact) {
            EmailCampaignRecipient::query()->updateOrCreate(
                ['tenant_id' => self::TENANT_ID, 'tracking_token' => 'email-demo-tracking-001'],
                [
                    'tenant_id' => self::TENANT_ID,
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
