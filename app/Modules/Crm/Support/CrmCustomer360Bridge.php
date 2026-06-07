<?php

namespace App\Modules\Crm\Support;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmLead;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleQuotation;
use App\Support\PlanFeature;
use App\Support\TenantPlanManager;
use Illuminate\Support\Collection;

class CrmCustomer360Bridge
{
    public function __construct(
        private readonly CrmTimelinePublisher $timeline,
        private readonly TenantPlanManager $plans,
    ) {
    }

    public function handleQuotationCreated(SaleQuotation $quotation): void
    {
        if (!$this->canPublish((int) $quotation->tenant_id)) {
            return;
        }

        $contact = $quotation->contact;
        if (!$contact) {
            return;
        }

        $lead = $this->resolveLeadForContact($contact, data_get($quotation->meta, 'crm.lead_id'));

        $this->timeline->publishForContact(
            $contact,
            'sales_quotation_created',
            'Quotation dibuat',
            'Quotation ' . $quotation->quotation_number . ' dibuat dari workflow sales.',
            [
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'status' => $quotation->status,
                'grand_total' => (float) $quotation->grand_total,
                'currency_code' => $quotation->currency_code,
                'url' => route('sales.quotations.show', $quotation),
            ],
            'accounting',
            'sales',
            $lead,
            $lead?->owner_user_id
        );
    }

    public function handleQuotationConverted(SaleQuotation $quotation): void
    {
        if (!$this->canPublish((int) $quotation->tenant_id)) {
            return;
        }

        $contact = $quotation->contact;
        if (!$contact || !$quotation->convertedSale) {
            return;
        }

        $lead = $this->resolveLeadForContact($contact, data_get($quotation->meta, 'crm.lead_id'));

        $this->timeline->publishForContact(
            $contact,
            'sales_quotation_converted',
            'Quotation dikonversi',
            'Quotation ' . $quotation->quotation_number . ' dikonversi menjadi draft invoice/sale.',
            [
                'quotation_id' => $quotation->id,
                'quotation_number' => $quotation->quotation_number,
                'sale_id' => $quotation->convertedSale->id,
                'sale_number' => $quotation->convertedSale->sale_number,
                'url' => route('sales.show', $quotation->convertedSale),
            ],
            'accounting',
            'sales',
            $lead,
            $lead?->owner_user_id
        );
    }

    public function handleSaleFinalized(Sale $sale): void
    {
        if (!$this->canPublish((int) $sale->tenant_id)) {
            return;
        }

        $contact = $sale->contact;
        if (!$contact) {
            return;
        }

        $lead = $this->resolveLeadForContact($contact, data_get($sale->meta, 'crm.lead_id'));

        $this->timeline->publishForContact(
            $contact,
            'sales_invoice_finalized',
            'Invoice finalized',
            'Invoice ' . $sale->sale_number . ' siap ditagihkan.',
            [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'payment_status' => $sale->payment_status,
                'grand_total' => (float) $sale->grand_total,
                'balance_due' => (float) $sale->balance_due,
                'currency_code' => $sale->currency_code,
                'url' => route('sales.show', $sale),
            ],
            'accounting',
            'sales',
            $lead,
            $lead?->owner_user_id
        );
    }

    public function handleSaleVoided(Sale $sale): void
    {
        if (!$this->canPublish((int) $sale->tenant_id)) {
            return;
        }

        $contact = $sale->contact;
        if (!$contact) {
            return;
        }

        $lead = $this->resolveLeadForContact($contact, data_get($sale->meta, 'crm.lead_id'));

        $this->timeline->publishForContact(
            $contact,
            'sales_invoice_voided',
            'Invoice di-void',
            'Invoice ' . $sale->sale_number . ' di-void dari workflow sales.',
            [
                'sale_id' => $sale->id,
                'sale_number' => $sale->sale_number,
                'payment_status' => $sale->payment_status,
                'void_reason' => $sale->void_reason,
                'url' => route('sales.show', $sale),
            ],
            'accounting',
            'sales',
            $lead,
            $lead?->owner_user_id
        );
    }

    public function handlePaymentPosted(Payment $payment, Collection $payables): void
    {
        if (!$this->canPublish((int) $payment->tenant_id)) {
            return;
        }

        $payables
            ->filter(fn ($payable) => $payable instanceof Sale)
            ->unique(fn (Sale $sale) => $sale->id)
            ->each(function (Sale $sale) use ($payment): void {
                $contact = $sale->contact;
                if (!$contact) {
                    return;
                }

                $lead = $this->resolveLeadForContact($contact, data_get($sale->meta, 'crm.lead_id'));

                $this->timeline->publishForContact(
                    $contact,
                    'payment_posted',
                    'Pembayaran diposting',
                    'Payment ' . $payment->payment_number . ' tercatat untuk invoice ' . $sale->sale_number . '.',
                    [
                        'payment_id' => $payment->id,
                        'payment_number' => $payment->payment_number,
                        'sale_id' => $sale->id,
                        'sale_number' => $sale->sale_number,
                        'amount' => (float) $payment->amount,
                        'currency_code' => $payment->currency_code,
                        'paid_at' => optional($payment->paid_at)->toDateTimeString(),
                        'payment_status' => $sale->fresh()->payment_status,
                        'sale_url' => route('sales.show', $sale),
                        'payment_url' => route('payments.show', $payment),
                    ],
                    'accounting',
                    'payments',
                    $lead,
                    $lead?->owner_user_id
                );
            });
    }

    private function canPublish(int $tenantId): bool
    {
        return $this->plans->hasFeature(PlanFeature::CRM, $tenantId);
    }

    private function resolveLeadForContact(Contact $contact, mixed $preferredLeadId = null): ?CrmLead
    {
        $query = CrmLead::query()
            ->where('tenant_id', $contact->tenant_id)
            ->where('contact_id', $contact->id)
            ->latest('updated_at')
            ->latest('id');

        if ($preferredLeadId) {
            $preferred = (clone $query)->whereKey((int) $preferredLeadId)->first();
            if ($preferred) {
                return $preferred;
            }
        }

        return $query->first();
    }
}
