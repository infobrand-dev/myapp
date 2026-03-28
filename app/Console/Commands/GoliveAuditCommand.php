<?php

namespace App\Console\Commands;

use App\Services\GoliveAuditService;
use Illuminate\Console\Command;

class GoliveAuditCommand extends Command
{
    protected $signature = 'golive:audit';

    protected $description = 'Audit critical go-live configuration and runtime prerequisites.';

    public function __construct(private readonly GoliveAuditService $audit)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $result = $this->audit->run();
        $checks = $result['checks'];
        $failures = $result['stats']['fail'];

        $this->line('');
        $this->info('Go-Live Audit');
        $this->line(str_repeat('-', 72));

        foreach ($checks as $check) {
            $status = match ($check['status']) {
                'pass' => '<info>PASS</info>',
                'warn' => '<comment>WARN</comment>',
                default => '<error>FAIL</error>',
            };
            $this->line(sprintf('%-18s %s [%s]', $status, $check['label'], $check['value']));
        }

        $this->line(str_repeat('-', 72));

        if ($failures > 0) {
            $this->error("Go-live audit found {$failures} blocker(s).");
            return self::FAILURE;
        }

        $this->info('Go-live audit passed.');
        return self::SUCCESS;
    }
}
