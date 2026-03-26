<?php

namespace App\Modules\EmailInbox\Jobs;

use App\Modules\EmailInbox\Models\EmailAccount;
use App\Modules\EmailInbox\Models\EmailSyncRun;
use App\Modules\EmailInbox\Services\MailboxProviderManager;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchMailboxMessages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $accountId)
    {
    }

    public function handle(MailboxProviderManager $providers): void
    {
        $account = EmailAccount::query()->find($this->accountId);
        if (!$account) {
            return;
        }

        TenantContext::setCurrentId((int) $account->tenant_id);

        $syncRun = EmailSyncRun::query()->create([
            'tenant_id' => $account->tenant_id,
            'account_id' => $account->id,
            'sync_type' => 'inbound_fetch',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $account->update([
            'sync_status' => 'running',
            'last_error_at' => null,
            'last_error_message' => null,
        ]);

        try {
            $result = $providers->forAccount($account)->fetch($account, $syncRun);

            $syncRun->update([
                'status' => 'success',
                'finished_at' => now(),
                'stats_json' => $result,
            ]);

            $account->update([
                'sync_status' => 'idle',
                'last_synced_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $syncRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            $account->update([
                'sync_status' => 'failed',
                'last_error_at' => now(),
                'last_error_message' => $e->getMessage(),
            ]);
        } finally {
            TenantContext::forget();
        }
    }
}
