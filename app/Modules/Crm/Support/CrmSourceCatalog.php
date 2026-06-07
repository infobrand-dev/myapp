<?php

namespace App\Modules\Crm\Support;

class CrmSourceCatalog
{
    public static function options(): array
    {
        return [
            'website' => 'Website',
            'whatsapp' => 'WhatsApp',
            'meta_ads' => 'Meta Ads',
            'google_ads' => 'Google Ads',
            'tiktok_ads' => 'TikTok Ads',
            'manual' => 'Manual',
            'api' => 'API',
        ];
    }
}
