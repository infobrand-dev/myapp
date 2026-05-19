<?php

namespace App\Console\Commands;

use App\Support\CoreModuleBoundaryAudit;
use Illuminate\Console\Command;

class ModulesAuditBoundaries extends Command
{
    protected $signature = 'modules:audit-boundaries';

    protected $description = 'Audit core vs module boundaries in PHP code and migrations.';

    public function handle(CoreModuleBoundaryAudit $audit): int
    {
        $result = $audit->run();
        $findings = collect($result['findings']);

        $this->line('Core/module boundary audit');
        $this->line('Module-owned tables discovered: ' . count($result['module_tables']));
        $this->newLine();

        if ($findings->isEmpty()) {
            $this->info('No boundary violations found in scanned core PHP files and core migrations.');

            return self::SUCCESS;
        }

        $this->table(
            ['Type', 'File', 'Line', 'Detail'],
            $findings->map(fn (array $finding) => [
                $finding['type'],
                $finding['file'],
                $finding['line'],
                $finding['detail'],
            ])->all()
        );

        $this->newLine();
        $this->table(
            ['Type', 'Count'],
            $findings
                ->groupBy('type')
                ->map(fn ($items, $type) => [$type, $items->count()])
                ->values()
                ->all()
        );

        $this->warn('Boundary violations found. Keep optional business logic and schema ownership inside the owning module.');

        return self::FAILURE;
    }
}
