<?php

namespace App\Console\Commands;

use App\Multitenancy\QueryReadinessAuditService;
use Illuminate\Console\Command;

class TenantQueryReadinessAuditCommand extends Command
{
    protected $signature = 'tenant:query-readiness-audit';

    protected $description = 'Audit query ownership, route-binding scope, raw-query guards, and migration manifest readiness.';

    public function handle(QueryReadinessAuditService $audit): int
    {
        $result = $audit->audit();
        $failed = false;

        foreach ($result as $section => $issues) {
            $issues = array_values(array_unique(array_filter($issues)));

            if ($issues === []) {
                $this->info($section . ': ok');
                continue;
            }

            $failed = true;
            $this->warn($section . ':');
            foreach ($issues as $issue) {
                $this->line('- ' . $issue);
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
