<?php

namespace App\Modules\Crm\Support;

use App\Modules\Crm\Models\CrmLead;
use App\Modules\Sales\Models\Sale;
use App\Support\ModuleManager;
use App\Support\PlanFeature;
use App\Support\TenantPlanManager;

class CrmWonAutomationService
{
    public function __construct(
        private readonly CrmIntegrationService $integrations,
        private readonly CrmTimelinePublisher $timeline,
        private readonly TenantPlanManager $plans,
        private readonly ModuleManager $modules,
    ) {
    }

    public function handle(CrmLead $lead): void
    {
        if ($lead->stage !== CrmStageCatalog::WON) {
            return;
        }

        $settings = $this->integrations->current();
        $onWon = (array) ($settings['on_won'] ?? []);

        if (empty($onWon['enabled'])) {
            return;
        }

        $meta = (array) ($lead->meta ?? []);
        if (!empty($meta['automation']['won_processed_at'])) {
            return;
        }

        $result = [
            'quotation_id' => null,
            'sale_id' => null,
            'finalized_sale_id' => null,
            'skipped' => [],
        ];

        if (!empty($onWon['create_sales_quotation'])) {
            $result['quotation_id'] = $this->createQuotation($lead, $onWon, $result['skipped']);
        }

        if (!empty($onWon['create_draft_sale'])) {
            $result['sale_id'] = $this->createDraftSale($lead, $onWon, $result['skipped']);
        }

        if (!empty($onWon['finalize_draft_sale']) && !empty($result['sale_id'])) {
            $result['finalized_sale_id'] = $this->finalizeDraftSale($lead, (int) $result['sale_id'], $result['skipped']);
        }

        $meta['automation'] = array_merge((array) ($meta['automation'] ?? []), [
            'won_processed_at' => now()->toIso8601String(),
            'won_result' => $result,
        ]);

        $lead->forceFill(['meta' => $meta])->save();

        $description = $result['quotation_id'] || $result['sale_id']
            ? 'Automation won dijalankan untuk membuat dokumen lanjutan.'
            : 'Automation won dilewati karena prasyarat modul/plan/config belum lengkap.';

        $this->timeline->publish(
            $lead->fresh(),
            'won_automation_processed',
            'Won automation diproses',
            $description,
            $result,
            'crm',
            'crm_automation'
        );
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $skipped
     */
    private function createQuotation(CrmLead $lead, array $config, array &$skipped): ?int
    {
        if (!$this->modules->isActive('sales') || !$this->plans->hasFeature(PlanFeature::ACCOUNTING, $lead->tenant_id)) {
            $skipped[] = 'quotation_missing_sales_or_accounting';
            return null;
        }

        $productId = (int) ($config['default_product_id'] ?? 0);
        if ($productId <= 0) {
            $skipped[] = 'quotation_missing_default_product';
            return null;
        }

        $payload = [
            'contact_id' => $lead->contact_id,
            'quotation_date' => now()->toDateTimeString(),
            'valid_until_date' => now()->addDays(14)->toDateString(),
            'currency_code' => $lead->currency ?: 'IDR',
            'notes' => 'Auto-generated dari CRM won deal: ' . $lead->title,
            'customer_note' => $lead->notes,
            'meta' => [
                'crm' => [
                    'lead_id' => $lead->id,
                    'lead_title' => $lead->title,
                ],
            ],
            'items' => [[
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => (float) ($lead->estimated_value ?? 0),
                'discount_total' => 0,
                'tax_total' => 0,
                'notes' => 'Lead won automation',
            ]],
        ];

        $quotation = app(\App\Modules\Sales\Actions\CreateSaleQuotationAction::class)->execute($payload, auth()->user());

        return $quotation->id;
    }

    /**
     * @param  array<string, mixed>  $config
     * @param  array<int, string>  $skipped
     */
    private function createDraftSale(CrmLead $lead, array $config, array &$skipped): ?int
    {
        if (!$this->modules->isActive('sales') || !$this->hasSalesPlan($lead)) {
            $skipped[] = 'sale_missing_sales_plan_or_module';
            return null;
        }

        $productId = (int) ($config['default_product_id'] ?? 0);
        if ($productId <= 0) {
            $skipped[] = 'sale_missing_default_product';
            return null;
        }

        $payload = [
            'contact_id' => $lead->contact_id,
            'payment_status' => 'unpaid',
            'source' => 'crm',
            'transaction_date' => now()->toDateTimeString(),
            'currency_code' => $lead->currency ?: 'IDR',
            'notes' => 'Auto-generated dari CRM won deal: ' . $lead->title,
            'customer_note' => $lead->notes,
            'external_reference' => 'crm-lead-' . $lead->id,
            'meta' => [
                'crm' => [
                    'lead_id' => $lead->id,
                    'lead_title' => $lead->title,
                ],
            ],
            'items' => [[
                'product_id' => $productId,
                'qty' => 1,
                'unit_price' => (float) ($lead->estimated_value ?? 0),
                'discount_total' => 0,
                'tax_total' => 0,
                'notes' => 'Lead won automation',
            ]],
        ];

        $sale = app(\App\Modules\Sales\Actions\CreateDraftSaleAction::class)->execute($payload, auth()->user());

        return $sale->id;
    }

    /**
     * @param  array<int, string>  $skipped
     */
    private function finalizeDraftSale(CrmLead $lead, int $saleId, array &$skipped): ?int
    {
        if (!$this->modules->isActive('sales') || !$this->plans->hasFeature(PlanFeature::ACCOUNTING, $lead->tenant_id)) {
            $skipped[] = 'finalize_missing_sales_or_accounting';
            return null;
        }

        $sale = Sale::query()
            ->where('tenant_id', $lead->tenant_id)
            ->whereKey($saleId)
            ->first();

        if (!$sale || !$sale->isDraft()) {
            $skipped[] = 'finalize_missing_draft_sale';
            return null;
        }

        $finalized = app(\App\Modules\Sales\Actions\FinalizeSaleAction::class)->execute($sale, [
            'payment_status' => Sale::PAYMENT_UNPAID,
            'reason' => 'CRM won automation',
        ], auth()->user());

        return $finalized->id;
    }

    private function hasSalesPlan(CrmLead $lead): bool
    {
        return $this->plans->hasFeature(PlanFeature::ACCOUNTING, $lead->tenant_id)
            || $this->plans->hasFeature(PlanFeature::COMMERCE, $lead->tenant_id);
    }
}
