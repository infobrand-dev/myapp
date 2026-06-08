<?php

namespace App\Modules\SampleData\Support;

use App\Models\TenantTransactionalMailSetting;
use App\Modules\Contacts\Database\Seeders\ContactSampleSeeder;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Models\PaymentAllocation;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Products\Database\Seeders\ProductSampleSeeder;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\ProductUnit;
use App\Modules\Sales\Database\Seeders\SaleSampleSeeder;
use App\Modules\Sales\Models\Sale;
use App\Modules\Sales\Models\SaleItem;
use App\Modules\Storefront\Mail\StorefrontOrderAccessMail;
use App\Modules\Storefront\Services\StorefrontOrderSettlementService;
use App\Services\AccountingTransactionalMailService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SampleDataUserResolver;
use App\Support\TenantContext;
use App\Support\WorkspaceContextProvisioner;
use Illuminate\Support\Facades\Mail;

class CommerceDemoBuilder
{
    private const TENANT_ID = 1;
    private const ORDER_NUMBER = 'SAL-COM-DEMO-001';
    private const PAYMENT_REFERENCE = 'ORDER-COM-DEMO-001';
    private const PRODUCT_SKU = 'DEMO-ECOM-DIGI-001';
    private const BUYER_EMAIL = 'buyer-commerce@demo-nusantara.test';

    private WorkspaceContextProvisioner $workspaceProvisioner;
    private CreatePaymentAction $createPayment;
    private StorefrontOrderSettlementService $settlementService;
    private AccountingTransactionalMailService $transactionalMail;

    public function __construct(
        WorkspaceContextProvisioner $workspaceProvisioner,
        CreatePaymentAction $createPayment,
        StorefrontOrderSettlementService $settlementService,
        AccountingTransactionalMailService $transactionalMail
    ) {
        $this->workspaceProvisioner = $workspaceProvisioner;
        $this->createPayment = $createPayment;
        $this->settlementService = $settlementService;
        $this->transactionalMail = $transactionalMail;
    }

    /**
     * @return array{
     *     sale: \App\Modules\Sales\Models\Sale,
     *     payment: \App\Modules\Payments\Models\Payment,
     *     payment_created: bool,
     *     notes: array<int, string>
     * }
     */
    public function run(bool $withTransactionalMails = false): array
    {
        $previousTenantId = TenantContext::currentId();
        $previousCompanyId = CompanyContext::currentId();
        $previousBranchId = BranchContext::currentId();

        TenantContext::setCurrentId(self::TENANT_ID);

        $user = SampleDataUserResolver::resolve();
        [$company] = $this->workspaceProvisioner->ensureForTenant(self::TENANT_ID, $user);
        $userId = optional($user)->id;

        CompanyContext::setCurrentId((int) $company->id);
        BranchContext::setCurrentId(null);

        try {
            (new ProductSampleSeeder())->run();
            (new ContactSampleSeeder())->run();
            (new SaleSampleSeeder())->run();

            $contact = $this->ensureBuyerContact((int) $company->id);
            $product = $this->ensureCommerceProduct($userId);
            $sale = $this->upsertCommerceSale((int) $company->id, $contact, $product, $userId);

            ['payment' => $payment, 'created' => $paymentCreated] = $this->ensureOrderPayment($sale, $user);

            $sale = $sale->fresh(['items']);
            $payment = $payment->fresh(['method', 'allocations.payable']);

            $notes = [
                'Commerce demo order ' . $sale->sale_number . ' siap dipakai.',
                'Payment ' . $payment->payment_number . ' terhubung ke order demo.',
                $paymentCreated
                    ? 'Email akses order customer dikirim saat payment diposting.'
                    : 'Payment demo sudah ada dari run sebelumnya.',
            ];

            if (!$paymentCreated && $withTransactionalMails) {
                Mail::to((string) $sale->customer_email_snapshot)->send(new StorefrontOrderAccessMail($sale));
                $notes[] = 'Email akses order dikirim ulang ke customer demo.';
            }

            if ($withTransactionalMails) {
                $notes = array_merge($notes, $this->queueTransactionalMails($sale, $payment, $userId));
            }

            return [
                'sale' => $sale,
                'payment' => $payment,
                'payment_created' => $paymentCreated,
                'notes' => $notes,
            ];
        } finally {
            TenantContext::setCurrentId($previousTenantId);
            CompanyContext::setCurrentId($previousCompanyId);
            BranchContext::setCurrentId($previousBranchId);
        }
    }

    private function ensureBuyerContact(int $companyId): Contact
    {
        return Contact::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'type' => 'individual', 'email' => self::BUYER_EMAIL],
            [
                'tenant_id' => self::TENANT_ID,
                'company_id' => $companyId,
                'branch_id' => null,
                'name' => 'Buyer Commerce Demo',
                'job_title' => 'Demo Customer',
                'phone' => '0215550099',
                'mobile' => '628111000199',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'notes' => 'Kontak buyer demo untuk order commerce dan email test.',
                'is_active' => true,
            ]
        );
    }

    private function ensureCommerceProduct(?int $userId): Product
    {
        $category = ProductCategory::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('slug', 'minuman-demo')
            ->first();
        $brand = ProductBrand::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('slug', 'demo-brand')
            ->first();
        $unit = ProductUnit::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('code', 'PCS')
            ->first();

        return Product::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'sku' => self::PRODUCT_SKU],
            [
                'tenant_id' => self::TENANT_ID,
                'type' => 'simple',
                'category_id' => optional($category)->id,
                'brand_id' => optional($brand)->id,
                'unit_id' => optional($unit)->id,
                'name' => 'Commerce Demo Digital Kit',
                'slug' => 'commerce-demo-digital-kit',
                'barcode' => '8999000000099',
                'description' => 'Produk digital demo untuk order commerce dan email akses customer.',
                'cost_price' => 15000,
                'sell_price' => 99000,
                'is_active' => true,
                'track_stock' => false,
                'meta' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                    'public_offer' => [
                        'enabled' => true,
                        'headline' => 'Commerce Demo Digital Kit',
                    ],
                    'commerce' => [
                        'fulfillment_type' => 'digital',
                    ],
                ],
                'created_by' => $userId,
                'updated_by' => $userId,
            ]
        );
    }

    private function upsertCommerceSale(int $companyId, Contact $contact, Product $product, ?int $userId): Sale
    {
        $sale = Sale::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'company_id' => $companyId, 'sale_number' => self::ORDER_NUMBER],
            [
                'tenant_id' => self::TENANT_ID,
                'company_id' => $companyId,
                'external_reference' => self::PAYMENT_REFERENCE,
                'contact_id' => $contact->id,
                'customer_name_snapshot' => $contact->name,
                'customer_email_snapshot' => $contact->email,
                'customer_phone_snapshot' => $contact->mobile,
                'customer_address_snapshot' => trim(implode(', ', array_filter([$contact->city, $contact->country]))),
                'customer_snapshot' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                ],
                'status' => Sale::STATUS_FINALIZED,
                'payment_status' => Sale::PAYMENT_UNPAID,
                'source' => Sale::SOURCE_ONLINE,
                'transaction_date' => now()->subHours(2),
                'due_date' => now()->addDay()->toDateString(),
                'finalized_at' => now()->subHours(2),
                'subtotal' => 99000,
                'discount_total' => 0,
                'tax_total' => 0,
                'grand_total' => 99000,
                'paid_total' => 0,
                'balance_due' => 99000,
                'currency_code' => 'IDR',
                'notes' => 'Order commerce demo hasil bootstrap sample data.',
                'customer_note' => 'Order ini dipakai untuk demo checkout commerce dan email akses customer.',
                'totals_snapshot' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                    'shipping_total' => 0,
                ],
                'meta' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                    'commerce' => [
                        'channel' => 'public_storefront',
                        'status' => 'pending_payment',
                        'fulfillment_type' => 'digital',
                        'fulfillment_method' => 'pickup',
                        'fulfillment' => [
                            'status' => 'pending',
                        ],
                        'payment' => [
                            'status' => 'pending',
                            'requested_method' => 'manual',
                            'provider' => 'manual',
                            'reference' => self::PAYMENT_REFERENCE,
                        ],
                        'shipping' => [
                            'status' => 'pending',
                        ],
                        'buyer_access' => [
                            'status' => 'pending',
                        ],
                        'timeline' => [
                            [
                                'event' => 'sample_data_bootstrap',
                                'at' => now()->toIso8601String(),
                            ],
                        ],
                        'expires_at' => now()->addDay()->toIso8601String(),
                    ],
                ],
                'created_by' => $userId,
                'updated_by' => $userId,
                'finalized_by' => $userId,
            ]
        );

        SaleItem::query()->updateOrCreate(
            ['tenant_id' => self::TENANT_ID, 'company_id' => $companyId, 'sale_id' => $sale->id, 'line_no' => 1],
            [
                'tenant_id' => self::TENANT_ID,
                'company_id' => $companyId,
                'product_id' => $product->id,
                'product_variant_id' => null,
                'product_name_snapshot' => $product->name,
                'variant_name_snapshot' => null,
                'sku_snapshot' => $product->sku,
                'barcode_snapshot' => $product->barcode,
                'unit_snapshot' => optional($product->unit)->code,
                'product_snapshot' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                    'fulfillment_type' => 'digital',
                ],
                'qty' => 1,
                'unit_price' => 99000,
                'line_subtotal' => 99000,
                'discount_total' => 0,
                'tax_total' => 0,
                'line_total' => 99000,
                'pricing_snapshot' => [
                    'seeded' => true,
                    'sample_data' => 'commerce_demo',
                ],
                'sort_order' => 1,
            ]
        );

        return $sale->fresh(['items']);
    }

    /**
     * @return array{payment: \App\Modules\Payments\Models\Payment, created: bool}
     */
    private function ensureOrderPayment(Sale $sale, $user): array
    {
        $existingAllocation = PaymentAllocation::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('company_id', (int) $sale->company_id)
            ->where('payable_type', $sale->getMorphClass())
            ->where('payable_id', $sale->id)
            ->whereHas('payment', fn ($query) => $query->where('status', Payment::STATUS_POSTED))
            ->with('payment')
            ->latest('id')
            ->first();

        if ($existingAllocation && $existingAllocation->payment) {
            $payment = $existingAllocation->payment->fresh(['method', 'allocations.payable']);
            $this->settlementService->handle($payment, collect([$sale]), collect([$existingAllocation]));

            return [
                'payment' => $payment,
                'created' => false,
            ];
        }

        $method = PaymentMethod::query()
            ->where('tenant_id', self::TENANT_ID)
            ->where('company_id', (int) $sale->company_id)
            ->where('code', PaymentMethod::CODE_MANUAL)
            ->first()
            ?? PaymentMethod::query()
                ->where('tenant_id', self::TENANT_ID)
                ->where('company_id', (int) $sale->company_id)
                ->where('code', PaymentMethod::CODE_QRIS)
                ->first();

        if (!$method) {
            $method = PaymentMethod::query()->create([
                'tenant_id' => self::TENANT_ID,
                'company_id' => (int) $sale->company_id,
                'code' => PaymentMethod::CODE_MANUAL,
                'name' => 'Manual Demo',
                'type' => PaymentMethod::TYPE_MANUAL,
                'requires_reference' => false,
                'is_active' => true,
                'is_system' => false,
                'sort_order' => 99,
                'created_by' => optional($user)->id,
                'updated_by' => optional($user)->id,
            ]);
        }

        $payment = $this->createPayment->execute([
            'payment_method_id' => $method->id,
            'amount' => (float) $sale->grand_total,
            'currency_code' => (string) $sale->currency_code,
            'paid_at' => now()->subHour(),
            'source' => Payment::SOURCE_ONLINE,
            'channel' => 'storefront',
            'reference_number' => self::PAYMENT_REFERENCE,
            'external_reference' => self::PAYMENT_REFERENCE,
            'branch_id' => null,
            'notes' => 'Pembayaran demo untuk commerce order sample data.',
            'received_by' => optional($user)->id,
            'allocations' => [[
                'payable_type' => 'sale',
                'payable_id' => $sale->id,
                'amount' => (float) $sale->grand_total,
            ]],
        ], $user);

        return [
            'payment' => $payment,
            'created' => true,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function queueTransactionalMails(Sale $sale, Payment $payment, ?int $userId): array
    {
        $notes = [];

        $setting = TenantTransactionalMailSetting::query()->firstOrNew([
            'tenant_id' => self::TENANT_ID,
        ]);

        $setting->fill([
            'is_enabled' => true,
            'delivery_mode' => $setting->delivery_mode ?: TenantTransactionalMailSetting::DELIVERY_MODE_MANAGED,
            'from_name' => $setting->from_name ?: config('app.name'),
            'from_email' => $setting->from_email ?: (string) config('mail.from.address'),
            'reply_to' => $setting->reply_to ?: (string) config('mail.from.address'),
            'created_by' => $setting->created_by ?: $userId,
            'updated_by' => $userId,
        ]);
        $setting->save();

        try {
            $this->transactionalMail->sendInvoice($sale, $userId);
            $notes[] = 'Invoice email customer diantrikan.';
        } catch (\Throwable $e) {
            $notes[] = 'Invoice email customer dilewati: ' . $e->getMessage();
        }

        try {
            $this->transactionalMail->sendPaymentReceipt($payment, $userId);
            $notes[] = 'Payment receipt customer diantrikan.';
        } catch (\Throwable $e) {
            $notes[] = 'Payment receipt customer dilewati: ' . $e->getMessage();
        }

        if ((string) config('queue.default') !== 'sync') {
            $notes[] = 'Queue saat ini bukan sync. Jalankan worker agar email queued benar-benar terkirim.';
        }

        return $notes;
    }
}
