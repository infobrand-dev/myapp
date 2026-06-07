<?php

namespace App\Modules\Crm\Support;

use App\Modules\Contacts\Models\Contact;
use App\Modules\Crm\Models\CrmActivity;
use App\Modules\Crm\Models\CrmFollowUpTask;
use App\Modules\Crm\Models\CrmLead;
use App\Support\PlanFeature;
use App\Support\TenantPlanManager;
use App\Support\TenantContext;

class Customer360TimelineBuilder
{
    public function __construct(
        private readonly TenantPlanManager $plans,
    ) {
    }

    public function build(Contact $contact): array
    {
        $tenantId = TenantContext::currentId();

        $openDeals = CrmLead::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $contact->id)
            ->whereNotIn('stage', [CrmStageCatalog::WON, CrmStageCatalog::LOST])
            ->with(['owner', 'stageModel'])
            ->orderByRaw("CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('next_follow_up_at')
            ->limit(6)
            ->get();

        $pendingFollowUps = CrmFollowUpTask::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $contact->id)
            ->where('status', 'pending')
            ->with(['owner', 'lead'])
            ->orderByRaw("CASE WHEN due_at IS NULL THEN 1 ELSE 0 END")
            ->orderBy('due_at')
            ->limit(8)
            ->get();

        $timeline = CrmActivity::query()
            ->where('tenant_id', $tenantId)
            ->where('contact_id', $contact->id)
            ->with(['owner', 'lead'])
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->limit(20)
            ->get();

        $accountingEnabled = $this->plans->hasFeature(PlanFeature::ACCOUNTING, $tenantId)
            && class_exists(\App\Modules\Sales\Models\Sale::class)
            && class_exists(\App\Modules\Sales\Models\SaleQuotation::class)
            && class_exists(\App\Modules\Payments\Models\Payment::class);

        $recentQuotations = collect();
        $openInvoices = collect();
        $recentPayments = collect();

        if ($accountingEnabled) {
            $recentQuotations = \App\Modules\Sales\Models\SaleQuotation::query()
                ->where('tenant_id', $tenantId)
                ->where('contact_id', $contact->id)
                ->latest('quotation_date')
                ->limit(5)
                ->get();

            $openInvoices = \App\Modules\Sales\Models\Sale::query()
                ->where('tenant_id', $tenantId)
                ->where('contact_id', $contact->id)
                ->where('status', \App\Modules\Sales\Models\Sale::STATUS_FINALIZED)
                ->where('balance_due', '>', 0)
                ->latest('transaction_date')
                ->limit(5)
                ->get();

            $recentPayments = \App\Modules\Payments\Models\Payment::query()
                ->where('tenant_id', $tenantId)
                ->whereHas('allocations', function ($query) use ($contact, $tenantId): void {
                    $query->where('payable_type', 'sale')
                        ->whereIn('payable_id', \App\Modules\Sales\Models\Sale::query()
                            ->where('tenant_id', $tenantId)
                            ->where('contact_id', $contact->id)
                            ->select('id'));
                })
                ->with(['allocations.payable'])
                ->latest('paid_at')
                ->limit(5)
                ->get();
        }

        return [
            'openDeals' => $openDeals,
            'pendingFollowUps' => $pendingFollowUps,
            'timeline' => $timeline,
            'accounting' => [
                'enabled' => $accountingEnabled,
                'recentQuotations' => $recentQuotations,
                'openInvoices' => $openInvoices,
                'recentPayments' => $recentPayments,
            ],
            'integrationPlaceholders' => [
                'accounting' => [
                    'title' => 'Accounting belum aktif',
                    'description' => 'Quotation, invoice, payment, dan outstanding balance akan muncul di sini saat suite Accounting aktif.',
                ],
                'omnichannel' => [
                    'title' => 'Omnichannel belum aktif',
                    'description' => 'WhatsApp, email, call log, dan chat activity akan masuk ke timeline ini saat suite Omnichannel aktif.',
                ],
            ],
        ];
    }
}
