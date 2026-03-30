<?php

namespace App\Modules\SocialMedia\Database\Seeders;

use App\Modules\SocialMedia\Models\SocialAccount;
use App\Support\SampleDataUserResolver;
use Illuminate\Database\Seeder;

class SocialMediaSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = SampleDataUserResolver::resolve();
        $userId = optional($user)->id;

        SocialAccount::query()->updateOrCreate(
            ['platform' => 'instagram', 'page_id' => 'ig-demo-page-001'],
            [
                'ig_business_id' => 'ig-demo-business-001',
                'access_token' => 'demo_social_access_token',
                'name' => 'Demo Brand Instagram',
                'status' => 'active',
                'metadata' => ['seeded' => true, 'followers' => 1250],
                'created_by' => $userId,
            ]
        );
    }
}




