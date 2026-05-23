<?php

namespace App\Mail;

use App\Models\CoreNotification;
use App\Models\User;
use Illuminate\Mail\Mailable;

class CoreNotificationMail extends Mailable
{
    public function __construct(
        public readonly CoreNotification $notification,
        public readonly User $user,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('[Alert] ' . $this->notification->title)
            ->view('emails.core-notification');
    }
}
