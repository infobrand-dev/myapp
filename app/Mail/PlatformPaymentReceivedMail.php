<?php

namespace App\Mail;

use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformPaymentReceivedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PlatformInvoice $invoice;
    public PlatformPayment $payment;
    public string $invoiceUrl;

    public function __construct(PlatformInvoice $invoice, PlatformPayment $payment, string $invoiceUrl)
    {
        $this->invoice = $invoice;
        $this->payment = $payment;
        $this->invoiceUrl = $invoiceUrl;
    }

    public function build(): self
    {
        return $this->subject('Pembayaran diterima untuk ' . $this->invoice->invoice_number)
            ->view('emails.platform-payment-received', [
                'invoice' => $this->invoice,
                'payment' => $this->payment,
                'invoiceUrl' => $this->invoiceUrl,
            ]);
    }
}
