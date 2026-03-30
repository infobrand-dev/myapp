<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PlatformAffiliateSaleGeneratedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public readonly string $affiliateName,
        public readonly string $tenantName,
        public readonly string $orderNumber,
        public readonly string $planName,
        public readonly float $orderAmount,
        public readonly string $orderCurrency,
        public readonly float $commissionAmount,
        public readonly string $referralLink,
        public readonly array $policy,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Ada penjualan baru dari link affiliate Anda',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.platform-affiliate-sale-generated',
            with: [
                'affiliateName' => $this->affiliateName,
                'tenantName' => $this->tenantName,
                'orderNumber' => $this->orderNumber,
                'planName' => $this->planName,
                'orderAmount' => $this->orderAmount,
                'orderCurrency' => $this->orderCurrency,
                'commissionAmount' => $this->commissionAmount,
                'referralLink' => $this->referralLink,
                'policy' => $this->policy,
            ],
        );
    }
}
