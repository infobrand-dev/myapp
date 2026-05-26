<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AccountingTransactionalDocumentMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $viewName,
        public readonly array $viewData,
        public readonly string $fromEmail,
        public readonly string $fromName,
        public readonly ?string $replyToEmail = null,
    ) {
    }

    public function build(): self
    {
        $mail = $this->subject($this->subjectLine)
            ->from($this->fromEmail, $this->fromName)
            ->view($this->viewName, $this->viewData);

        if ($this->replyToEmail) {
            $mail->replyTo($this->replyToEmail);
        }

        return $mail;
    }
}
