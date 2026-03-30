<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformAffiliateRegisteredMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $affiliateName,
        public readonly string $referralCode,
        public readonly string $referralLink,
        public readonly string $commissionType,
        public readonly float $commissionRate,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akun affiliate Anda di ' . config('app.name') . ' sudah aktif',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-affiliate-registered',
            with: [
                'affiliateName' => $this->affiliateName,
                'referralCode' => $this->referralCode,
                'referralLink' => $this->referralLink,
                'commissionType' => $this->commissionType,
                'commissionRate' => $this->commissionRate,
            ],
        );
    }
}
