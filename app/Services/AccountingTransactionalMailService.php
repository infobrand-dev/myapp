<?php

namespace App\Services;

use App\Jobs\SendTenantTransactionalMailJob;
use App\Models\TenantTransactionalMailLog;
use App\Modules\Payments\Models\Payment;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleOrder;
use App\Modules\Sales\Models\SaleQuotation;
use Illuminate\Support\Collection;
use RuntimeException;

class AccountingTransactionalMailService
{
    public function __construct(
        private readonly TenantTransactionalMailConfigResolver $configResolver,
    ) {
    }

    public function sendQuotation(SaleQuotation $quotation, ?int $actorId = null): TenantTransactionalMailLog
    {
        return $this->queueDocument(
            documentType: 'sale_quotation',
            documentId: (int) $quotation->id,
            tenantId: (int) $quotation->tenant_id,
            companyId: (int) $quotation->company_id,
            branchId: $quotation->branch_id ? (int) $quotation->branch_id : null,
            actorId: $actorId,
            recipientEmail: $this->recipientEmail($quotation->customer_email_snapshot),
            recipientName: $quotation->customer_name_snapshot,
            templateKey: 'quotation_sent',
            subject: 'Quotation ' . $quotation->quotation_number,
            view: 'emails.accounting.quotation-sent',
            data: [
                'documentNumber' => $quotation->quotation_number,
                'customerName' => $quotation->customer_name_snapshot ?: 'Customer',
                'documentDate' => optional($quotation->quotation_date)?->format('d M Y'),
                'validUntilDate' => optional($quotation->valid_until_date)?->format('d M Y'),
                'grandTotal' => (float) $quotation->grand_total,
                'currencyCode' => (string) $quotation->currency_code,
                'notes' => (string) ($quotation->customer_note ?: ''),
            ],
        );
    }

    public function sendOrder(SaleOrder $order, ?int $actorId = null): TenantTransactionalMailLog
    {
        return $this->queueDocument(
            documentType: 'sale_order',
            documentId: (int) $order->id,
            tenantId: (int) $order->tenant_id,
            companyId: (int) $order->company_id,
            branchId: $order->branch_id ? (int) $order->branch_id : null,
            actorId: $actorId,
            recipientEmail: $this->recipientEmail($order->customer_email_snapshot),
            recipientName: $order->customer_name_snapshot,
            templateKey: 'sale_order_sent',
            subject: 'Sales Order ' . $order->order_number,
            view: 'emails.accounting.order-sent',
            data: [
                'documentNumber' => $order->order_number,
                'customerName' => $order->customer_name_snapshot ?: 'Customer',
                'documentDate' => optional($order->order_date)?->format('d M Y'),
                'expectedDeliveryDate' => optional($order->expected_delivery_date)?->format('d M Y'),
                'grandTotal' => (float) $order->grand_total,
                'currencyCode' => (string) $order->currency_code,
                'notes' => (string) ($order->customer_note ?: ''),
            ],
        );
    }

    public function sendInvoice(Sale $sale, ?int $actorId = null): TenantTransactionalMailLog
    {
        return $this->queueDocument(
            documentType: 'sale',
            documentId: (int) $sale->id,
            tenantId: (int) $sale->tenant_id,
            companyId: (int) $sale->company_id,
            branchId: $sale->branch_id ? (int) $sale->branch_id : null,
            actorId: $actorId,
            recipientEmail: $this->recipientEmail($sale->customer_email_snapshot),
            recipientName: $sale->customer_name_snapshot,
            templateKey: 'invoice_sent',
            subject: 'Invoice ' . $sale->sale_number,
            view: 'emails.accounting.invoice-sent',
            data: [
                'documentNumber' => $sale->sale_number,
                'customerName' => $sale->customer_name_snapshot ?: 'Customer',
                'documentDate' => optional($sale->transaction_date)?->format('d M Y'),
                'dueDate' => optional($sale->due_date)?->format('d M Y'),
                'grandTotal' => (float) $sale->grand_total,
                'paidTotal' => (float) $sale->paid_total,
                'balanceDue' => (float) $sale->balance_due,
                'currencyCode' => (string) $sale->currency_code,
                'notes' => (string) ($sale->customer_note ?: ''),
            ],
        );
    }

    public function sendPaymentReminder(Sale $sale, ?int $actorId = null): TenantTransactionalMailLog
    {
        if ((float) $sale->balance_due <= 0) {
            throw new RuntimeException('Sale ini tidak punya saldo jatuh tempo yang perlu diingatkan.');
        }

        return $this->queueDocument(
            documentType: 'sale',
            documentId: (int) $sale->id,
            tenantId: (int) $sale->tenant_id,
            companyId: (int) $sale->company_id,
            branchId: $sale->branch_id ? (int) $sale->branch_id : null,
            actorId: $actorId,
            recipientEmail: $this->recipientEmail($sale->customer_email_snapshot),
            recipientName: $sale->customer_name_snapshot,
            templateKey: 'payment_reminder',
            subject: 'Pengingat Pembayaran ' . $sale->sale_number,
            view: 'emails.accounting.payment-reminder',
            data: [
                'documentNumber' => $sale->sale_number,
                'customerName' => $sale->customer_name_snapshot ?: 'Customer',
                'dueDate' => optional($sale->due_date)?->format('d M Y'),
                'grandTotal' => (float) $sale->grand_total,
                'paidTotal' => (float) $sale->paid_total,
                'balanceDue' => (float) $sale->balance_due,
                'currencyCode' => (string) $sale->currency_code,
                'notes' => (string) ($sale->customer_note ?: ''),
            ],
        );
    }

    public function sendPaymentReceipt(Payment $payment, ?int $actorId = null): TenantTransactionalMailLog
    {
        $sales = $payment->allocations
            ->map(fn ($allocation) => $allocation->payable)
            ->filter(fn ($payable) => $payable instanceof Sale)
            ->values();

        /** @var Sale|null $primarySale */
        $primarySale = $sales->first();
        if (!$primarySale) {
            throw new RuntimeException('Payment receipt saat ini hanya didukung untuk payment yang dialokasikan ke sale.');
        }

        return $this->queueDocument(
            documentType: 'payment',
            documentId: (int) $payment->id,
            tenantId: (int) $payment->tenant_id,
            companyId: (int) $payment->company_id,
            branchId: $payment->branch_id ? (int) $payment->branch_id : null,
            actorId: $actorId,
            recipientEmail: $this->recipientEmail($primarySale->customer_email_snapshot),
            recipientName: $primarySale->customer_name_snapshot,
            templateKey: 'payment_receipt',
            subject: 'Tanda Terima Pembayaran ' . $payment->payment_number,
            view: 'emails.accounting.payment-receipt',
            data: [
                'paymentNumber' => $payment->payment_number,
                'customerName' => $primarySale->customer_name_snapshot ?: 'Customer',
                'paidAt' => optional($payment->paid_at)?->format('d M Y H:i'),
                'amount' => (float) $payment->amount,
                'currencyCode' => (string) $payment->currency_code,
                'paymentMethod' => (string) optional($payment->method)->name,
                'referenceNumber' => (string) ($payment->reference_number ?: ''),
                'sales' => $sales->map(fn (Sale $sale) => [
                    'sale_number' => $sale->sale_number,
                    'grand_total' => (float) $sale->grand_total,
                    'balance_due' => (float) $sale->balance_due,
                ])->all(),
                'notes' => (string) ($payment->notes ?: ''),
            ],
        );
    }

    public function latestLog(string $documentType, int $documentId, int $tenantId): ?TenantTransactionalMailLog
    {
        return TenantTransactionalMailLog::query()
            ->where('tenant_id', $tenantId)
            ->where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->latest('id')
            ->first();
    }

    public function recentLogsForTenant(int $tenantId, int $limit = 15): Collection
    {
        return TenantTransactionalMailLog::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    private function queueDocument(
        string $documentType,
        int $documentId,
        int $tenantId,
        int $companyId,
        ?int $branchId,
        ?int $actorId,
        string $recipientEmail,
        ?string $recipientName,
        string $templateKey,
        string $subject,
        string $view,
        array $data,
    ): TenantTransactionalMailLog {
        $setting = $this->configResolver->assertCanDispatch($tenantId);

        $log = TenantTransactionalMailLog::query()->create([
            'tenant_id' => $tenantId,
            'company_id' => $companyId,
            'branch_id' => $branchId,
            'document_type' => $documentType,
            'document_id' => $documentId,
            'template_key' => $templateKey,
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName ?: null,
            'subject' => $subject,
            'status' => 'queued',
            'mailer_source' => $this->configResolver->usesManagedMail($setting) ? 'managed' : 'tenant_smtp',
            'queued_at' => now(),
            'created_by' => $actorId,
            'meta' => [
                'template_key' => $templateKey,
                'delivery_mode' => $setting->deliveryMode(),
            ],
        ]);

        dispatch(new SendTenantTransactionalMailJob($log->id, [
            'recipient_email' => $recipientEmail,
            'recipient_name' => $recipientName,
            'subject' => $subject,
            'view' => $view,
            'data' => $data,
        ]));

        return $log;
    }

    private function recipientEmail(?string $email): string
    {
        $email = trim((string) $email);
        if ($email === '') {
            throw new RuntimeException('Customer belum memiliki email tujuan.');
        }

        return $email;
    }
}
