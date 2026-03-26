<?php

namespace App\Modules\EmailInbox\Services;

use App\Modules\EmailInbox\Contracts\MailboxProvider;
use App\Modules\EmailInbox\Models\EmailAccount;

class MailboxProviderManager
{
    public function forAccount(EmailAccount $account): MailboxProvider
    {
        if (($account->inbound_protocol ?? 'imap') === 'imap') {
            return new ImapMailboxProvider();
        }

        return new NullMailboxProvider();
    }
}
