<?php

namespace App\Console\Commands;

use App\Models\PlatformEventOutbox;
use App\Services\PlatformEventBus;
use Illuminate\Console\Command;

class PlatformDispatchOutboxCommand extends Command
{
    protected $signature = 'platform:dispatch-outbox {--limit=100 : Max events to mark as dispatched}';

    protected $description = 'Dispatch pending platform outbox events through the baseline outbox contract.';

    public function handle(PlatformEventBus $events): int
    {
        $limit = max(1, (int) $this->option('limit'));

        $count = 0;

        PlatformEventOutbox::query()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (PlatformEventOutbox $event) use ($events, &$count): void {
                try {
                    $events->markDispatched($event);
                    $count++;
                } catch (\Throwable $exception) {
                    $events->markFailed($event, $exception->getMessage());
                }
            });

        $this->info("Dispatched {$count} platform outbox event(s).");

        return self::SUCCESS;
    }
}
