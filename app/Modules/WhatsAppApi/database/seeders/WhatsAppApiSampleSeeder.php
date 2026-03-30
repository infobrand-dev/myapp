<?php

namespace App\Modules\WhatsAppApi\Database\Seeders;

use App\Modules\Conversations\Database\Seeders\ConversationDemoSeeder;
use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastRecipient;
use App\Modules\WhatsAppApi\Models\WAFlow;
use App\Modules\WhatsAppApi\Models\WATemplate;
use App\Modules\WhatsAppApi\Models\WhatsAppInstance;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class WhatsAppApiSampleSeeder extends Seeder
{
    private const TENANT_ID = 1;

    public function run(): void
    {
        (new WhatsAppInstanceDummySeeder())->run();
        (new ConversationDemoSeeder())->run();

        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;
        $instance = WhatsAppInstance::query()->where('tenant_id', self::TENANT_ID)->orderBy('id')->first();

        if (!$instance) {
            return;
        }

        $template = WATemplate::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'name' => 'promo_demo_launch'],
            [
                'tenant_id' => self::TENANT_ID,
                'meta_name' => 'promo_demo_launch',
                'language' => 'id',
                'category' => 'marketing',
                'namespace' => 'demo_namespace',
                'meta_template_id' => 'tmpl_demo_001',
                'body' => 'Halo {{1}}, promo demo terbaru sudah aktif hari ini.',
                'components' => [['type' => 'body']],
                'variable_mappings' => ['1' => 'contact_name'],
                'status' => 'approved',
            ]
        );

        WAFlow::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'instance_id' => $instance->id, 'name' => 'Demo Lead Qualification'],
            [
                'tenant_id' => self::TENANT_ID,
                'categories' => ['support'],
                'endpoint_uri' => 'https://example.com/webhooks/wa-flow-demo',
                'meta_flow_id' => 'flow_demo_001',
                'status' => 'published',
                'json_version' => '7.1',
                'data_api_version' => '3.0',
                'validation_errors' => [],
                'health_status' => ['status' => 'ok'],
                'flow_json' => json_encode(['seeded' => true]),
                'preview_url' => 'https://example.com/preview/wa-flow-demo',
                'preview_expires_at' => now()->addDay(),
            ]
        );

        $campaign = WABlastCampaign::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'instance_id' => $instance->id, 'name' => 'Blast Demo Launch'],
            [
                'tenant_id' => self::TENANT_ID,
                'template_id' => $template->id,
                'created_by' => $userId,
                'status' => 'scheduled',
                'total_count' => 1,
                'queued_count' => 1,
                'sent_count' => 0,
                'failed_count' => 0,
                'scheduled_at' => now()->addHour(),
                'settings' => ['seeded' => true],
            ]
        );

        WABlastRecipient::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'campaign_id' => $campaign->id, 'phone_number' => '628123456789'],
            [
                'tenant_id' => self::TENANT_ID,
                'contact_name' => 'Demo Contact',
                'variables' => ['1' => 'Demo Contact'],
                'status' => 'queued',
                'queued_at' => now(),
            ]
        );
    }
}




