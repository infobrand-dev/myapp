<?php

namespace App\Console\Commands;

use App\Services\StaleOnboardingCleanupService;
use Illuminate\Console\Command;

class OnboardingCleanupStale extends Command
{
    protected $signature = 'onboarding:cleanup-stale {--dry-run : Show stale tenants without deleting them}';

    protected $description = 'Delete stale pending-payment or expired trial onboarding workspaces and keep slug locks temporarily.';

    public function handle(StaleOnboardingCleanupService $cleanup): int
    {
        $result = $cleanup->cleanup((bool) $this->option('dry-run'));

        $this->info('Processed: ' . $result['count']);

        foreach ($result['results'] as $row) {
            $this->line(sprintf('%s | tenant=%s | slug=%s', $row['action'], $row['tenant_id'], $row['slug']));
        }

        return self::SUCCESS;
    }
}
