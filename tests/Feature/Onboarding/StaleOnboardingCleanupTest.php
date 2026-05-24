<?php

namespace Tests\Feature\Onboarding;

use App\Models\Tenant;
use App\Models\User;
use App\Services\StaleOnboardingCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaleOnboardingCleanupTest extends TestCase
{
    use RefreshDatabase;

    public function test_cleanup_deletes_stale_pending_workspace_and_keeps_slug_locked(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Abandoned Workspace',
            'slug' => 'abandoned',
            'is_active' => false,
            'meta' => [
                'onboarding_status' => 'pending_payment',
            ],
        ]);
        $tenant->forceFill([
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ])->save();

        User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Owner',
            'email' => 'owner@abandoned.test',
            'password' => bcrypt('Secret123!!'),
        ]);

        $result = app(StaleOnboardingCleanupService::class)->cleanup();

        $this->assertSame(1, $result['count']);
        $this->assertDatabaseMissing('tenants', [
            'slug' => 'abandoned',
        ]);
        $this->assertDatabaseHas('tenant_slug_reservations', [
            'slug' => 'abandoned',
            'tenant_id' => null,
            'source' => 'cleanup_lock',
        ]);
    }
}
