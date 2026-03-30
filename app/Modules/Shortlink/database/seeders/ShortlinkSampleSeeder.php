<?php

namespace App\Modules\Shortlink\database\seeders;

use App\Modules\Shortlink\Models\Shortlink;
use App\Modules\Shortlink\Models\ShortlinkClick;
use App\Modules\Shortlink\Models\ShortlinkCode;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class ShortlinkSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        $shortlink = Shortlink::query()->updateOrCreate(
            ['destination_url' => 'https://example.com/promo-launch'],
            [
                'title' => 'Promo Launch Demo',
                'utm_source' => 'instagram',
                'utm_medium' => 'social',
                'utm_campaign' => 'launch_demo',
                'is_active' => true,
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );

        $code = ShortlinkCode::query()->updateOrCreate(
            ['code' => 'demo-launch'],
            [
                'shortlink_id' => $shortlink->id,
                'is_primary' => true,
                'is_active' => true,
            ]
        );

        ShortlinkClick::query()->updateOrCreate(
            [
                'shortlink_id' => $shortlink->id,
                'shortlink_code_id' => $code->id,
                'code_used' => 'demo-launch',
            ],
            [
                'utm_source' => 'instagram',
                'utm_medium' => 'social',
                'utm_campaign' => 'launch_demo',
                'referer' => 'https://instagram.com/demo-brand',
                'user_agent' => 'SampleDataBot/1.0',
                'ip_address' => '127.0.0.1',
                'query' => 'utm_source=instagram&utm_medium=social',
            ]
        );
    }
}


