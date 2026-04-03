<?php

namespace Tests\Feature\SocialMedia;

use App\Modules\SocialMedia\Services\SocialPlatformRegistry;
use Tests\TestCase;

class SocialPlatformRegistryTest extends TestCase
{
    public function test_only_meta_channels_are_publicly_enabled_for_now()
    {
        $summary = app(SocialPlatformRegistry::class)->summary();

        $publicKeys = collect($summary)
            ->filter(fn (array $platform) => (bool) ($platform['public_enabled'] ?? false))
            ->pluck('key')
            ->values()
            ->all();

        $this->assertSame(['facebook', 'instagram', 'x'], $publicKeys);
    }

    public function test_threads_x_and_tiktok_are_scaffolded_but_not_public()
    {
        $registry = app(SocialPlatformRegistry::class);

        $threads = $registry->find('threads');
        $x = $registry->find('x');
        $tiktok = $registry->find('tiktok');

        $this->assertSame('research', $threads['status']);
        $this->assertFalse($threads['public_enabled']);

        $this->assertSame('active', $x['status']);
        $this->assertTrue($x['public_enabled']);
        $this->assertContains('oauth_connect', $x['capabilities'] ?? []);

        $this->assertSame('research', $tiktok['status']);
        $this->assertFalse($tiktok['public_enabled']);
    }

    public function test_x_is_publicly_enabled_with_oauth_capability()
    {
        $x = app(SocialPlatformRegistry::class)->find('x');

        $this->assertSame('active', $x['status']);
        $this->assertTrue($x['public_enabled']);
        $this->assertContains('oauth_connect', $x['capabilities'] ?? []);
    }
}
