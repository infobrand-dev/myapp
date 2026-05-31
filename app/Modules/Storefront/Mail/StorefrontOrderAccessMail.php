<?php

namespace App\Modules\Storefront\Mail;

use App\Models\Tenant;
use App\Modules\Sales\Models\Sale;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\URL;

class StorefrontOrderAccessMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly Sale $sale,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Akses Order ' . $this->sale->sale_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'storefront::mail.order-access',
            with: [
                'sale' => $this->sale->loadMissing('items'),
                'orderUrl' => URL::signedRoute('storefront.public.orders.show', $this->orderRouteParameters()),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function orderRouteParameters(): array
    {
        $parameters = ['sale' => $this->sale];
        $slug = (string) Tenant::query()->whereKey($this->sale->tenant_id)->value('slug');

        if ($slug !== '') {
            $parameters['account'] = $slug;
        }

        return $parameters;
    }
}
