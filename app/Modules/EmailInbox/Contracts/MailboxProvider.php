<?php

namespace App\Modules\EmailInbox\Contracts;

use App\Modules\EmailInbox\Models\EmailAccount;
use App\Modules\EmailInbox\Models\EmailSyncRun;

interface MailboxProvider
{
    public function fetch(EmailAccount $account, EmailSyncRun $syncRun): array;
}
