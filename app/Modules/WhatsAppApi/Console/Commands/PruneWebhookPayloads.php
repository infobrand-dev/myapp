<?php

namespace App\Modules\WhatsAppApi\Console\Commands;

use App\Modules\WhatsAppApi\Models\WhatsAppWebhookEvent;
use Illuminate\Console\Command;

class PruneWebhookPayloads extends Command
{
    protected $signature = 'whatsapp:prune-webhook-payloads {--days=14 : Retention days for raw webhook payloads and headers}';

    protected $description = 'Remove old raw WhatsApp webhook headers and payload bodies while keeping event records';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $count = WhatsAppWebhookEvent::query()
            ->where('received_at', '<', $cutoff)
            ->where(function ($query): void {
                $query->whereNotNull('headers')
                    ->orWhereNotNull('payload');
            })
            ->update([
                'headers' => null,
                'payload' => null,
            ]);

        $this->info("Pruned raw payload data for {$count} WhatsApp webhook event(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
