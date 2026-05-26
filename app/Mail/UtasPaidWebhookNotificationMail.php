<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UtasPaidWebhookNotificationMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly array $payload
    ) {
    }

    public function envelope(): Envelope
    {
        $store = trim((string) ($this->payload['store'] ?? 'UTAS'));
        $buyerName = trim((string) ($this->payload['name'] ?? 'Pelanggan'));

        return new Envelope(
            subject: 'UTAS Paid Order - ' . $store . ' - ' . $buyerName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.utas-paid-notification',
            with: [
                'payload' => $this->payload,
                'items' => is_array($this->payload['items'] ?? null) ? $this->payload['items'] : [],
            ],
        );
    }
}
