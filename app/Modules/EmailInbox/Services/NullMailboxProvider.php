<?php

namespace App\Modules\EmailInbox\Services;

use App\Modules\EmailInbox\Contracts\MailboxProvider;
use App\Modules\EmailInbox\Models\EmailAccount;
use App\Modules\EmailInbox\Models\EmailSyncRun;
use RuntimeException;

class NullMailboxProvider implements MailboxProvider
{
    public function fetch(EmailAccount $account, EmailSyncRun $syncRun): array
    {
        throw new RuntimeException("Inbound provider untuk account {$account->email_address} belum didukung.");
    }
}
