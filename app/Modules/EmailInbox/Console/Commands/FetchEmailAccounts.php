<?php

namespace App\Modules\EmailInbox\Console\Commands;

use App\Modules\EmailInbox\Jobs\FetchMailboxMessages;
use App\Modules\EmailInbox\Models\EmailAccount;
use App\Support\TenantContext;
use Illuminate\Console\Command;

class FetchEmailAccounts extends Command
{
    protected $signature = 'email-inbox:fetch {accountId? : Optional email account id}';

    protected $description = 'Fetch inbound messages for active email inbox accounts';

    public function handle(): int
    {
        $accountId = $this->argument('accountId');

        $query = EmailAccount::query()
            ->where('sync_enabled', true)
            ->orderBy('id');

        if ($accountId) {
            $query->whereKey((int) $accountId);
        }

        $accounts = $query->get();

        if ($accounts->isEmpty()) {
            $this->info('No email inbox accounts eligible for sync.');

            return self::SUCCESS;
        }

        foreach ($accounts as $account) {
            TenantContext::setCurrentId((int) $account->tenant_id);
            FetchMailboxMessages::dispatchSync($account->id);
            $this->line("Synced account #{$account->id} {$account->email_address}");
        }

        TenantContext::forget();

        return self::SUCCESS;
    }
}
