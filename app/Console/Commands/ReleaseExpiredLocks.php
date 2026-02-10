<?php

namespace App\Console\Commands;

use App\Modules\Conversations\Models\Conversation;
use Illuminate\Console\Command;

class ReleaseExpiredLocks extends Command
{
    protected $signature = 'conversations:release-expired-locks';
    protected $description = 'Release conversation locks that have passed locked_until';

    public function handle(): int
    {
        $count = Conversation::whereNotNull('locked_until')
            ->where('locked_until', '<', now())
            ->update([
                'owner_id' => null,
                'claimed_at' => null,
                'locked_until' => null,
            ]);

        $this->info("Released {$count} expired locks.");
        return Command::SUCCESS;
    }
}
