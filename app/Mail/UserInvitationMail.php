<?php

namespace App\Mail;

use App\Models\Tenant;
use App\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserInvitationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public UserInvitation $invitation,
        public Tenant $tenant,
        public string $acceptUrl,
    ) {
    }

    public function build(): self
    {
        return $this
            ->subject('Undangan akses workspace ' . $this->tenant->name)
            ->view('emails.user-invitation');
    }
}
