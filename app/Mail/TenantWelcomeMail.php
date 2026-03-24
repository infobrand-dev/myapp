<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantWelcomeMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $adminName,
        public readonly string $adminEmail,
        public readonly string $tenantName,
        public readonly string $tenantSlug,
        public readonly string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Selamat datang di ' . config('app.name') . '!',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.tenant-welcome',
            with: [
                'adminName'  => $this->adminName,
                'adminEmail' => $this->adminEmail,
                'tenantName' => $this->tenantName,
                'tenantSlug' => $this->tenantSlug,
                'loginUrl'   => $this->loginUrl,
            ],
        );
    }
}
