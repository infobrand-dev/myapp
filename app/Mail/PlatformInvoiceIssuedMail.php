<?php

namespace App\Mail;

use App\Models\PlatformInvoice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PlatformInvoiceIssuedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public PlatformInvoice $invoice;
    public string $invoiceUrl;

    public function __construct(PlatformInvoice $invoice, string $invoiceUrl)
    {
        $this->invoice = $invoice;
        $this->invoiceUrl = $invoiceUrl;
    }

    public function build(): self
    {
        return $this->subject('Invoice ' . $this->invoice->invoice_number . ' dari ' . config('app.name'))
            ->view('emails.platform-invoice-issued', [
                'invoice' => $this->invoice,
                'invoiceUrl' => $this->invoiceUrl,
            ]);
    }
}
