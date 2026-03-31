<?php

namespace App\Console\Commands;

use App\Support\ModuleFilesystemAudit;
use Illuminate\Console\Command;

class ModulesAuditStructure extends Command
{
    protected $signature = 'modules:audit-structure';

    protected $description = 'Audit installed/active module filesystem structure and flag missing or wrongly-cased paths.';

    public function handle(ModuleFilesystemAudit $audit): int
    {
        $issues = $audit->activeInstalledIssues();

        if (empty($issues)) {
            $this->info('All installed and active modules have the expected filesystem structure.');
            return self::SUCCESS;
        }

        foreach ($issues as $module) {
            $this->newLine();
            $this->warn(sprintf('[%s] %s', $module['slug'], $module['name']));

            foreach ($module['issues'] as $issue) {
                $this->line(' - ' . $issue);
            }
        }

        $this->newLine();
        $this->error('One or more modules have missing filesystem paths. Re-deploy app/Modules with exact casing.');

        return self::FAILURE;
    }
}
