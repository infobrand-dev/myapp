<?php

namespace App\Modules\WhatsAppApi\Console\Commands;

use App\Modules\WhatsAppApi\Jobs\ProcessWABlastCampaign;
use App\Modules\WhatsAppApi\Models\WABlastCampaign;
use Illuminate\Console\Command;

class DispatchScheduledWABlasts extends Command
{
    protected $signature = 'whatsapp:dispatch-scheduled-blasts';

    protected $description = 'Dispatch scheduled WhatsApp blast campaigns that are due';

    public function handle(): int
    {
        $dueCampaigns = WABlastCampaign::query()
            ->where('status', 'scheduled')
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->orderBy('scheduled_at')
            ->limit(20)
            ->get();

        if ($dueCampaigns->isEmpty()) {
            $this->info('No due scheduled campaigns.');
            return self::SUCCESS;
        }

        foreach ($dueCampaigns as $campaign) {
            $campaign->update([
                'status' => 'running',
                'finished_at' => null,
                'last_error' => null,
            ]);
            ProcessWABlastCampaign::dispatch($campaign->id);
            $this->line("Dispatched campaign #{$campaign->id} ({$campaign->name})");
        }

        $this->info('Done.');

        return self::SUCCESS;
    }
}
