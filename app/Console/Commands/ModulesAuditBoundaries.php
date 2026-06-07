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
        $violations = $findings
            ->reject(fn (array $finding) => in_array($finding['type'], [
                'approved_core_module_reference',
                'approved_core_module_migration_touch',
            ], true))
            ->values();

        $this->line('Core/module boundary audit');
        $this->line('Module-owned tables discovered: ' . count($result['module_tables']));
        $this->newLine();

        if ($violations->isEmpty()) {
            $this->info('No boundary violations found in scanned core PHP files and core migrations.');

            if ($findings->isNotEmpty()) {
                $this->comment('Approved transitional references: ' . $findings->where('type', 'approved_core_module_reference')->count());
            }

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

        $this->warn('Boundary violations found. New core-to-module references are blocked unless they are explicitly approved and documented.');

        return self::FAILURE;
    }
}
