<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TenantTransactionalTestMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $workspaceName,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly ?string $replyToEmail = null,
    ) {
    }

    public function build(): self
    {
        $mail = $this->subject('Test transactional email - ' . $this->workspaceName)
            ->from($this->fromEmail, $this->fromName)
            ->view('emails.tenant-transactional-test', [
                'workspaceName' => $this->workspaceName,
                'fromEmail' => $this->fromEmail,
                'replyToEmail' => $this->replyToEmail,
            ]);

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail);
        }

        return $mail;
    }
}
