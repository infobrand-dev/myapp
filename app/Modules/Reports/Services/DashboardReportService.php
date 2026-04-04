<?php

namespace App\Modules\Reports\Services;

use App\Support\PlanFeature;
use App\Support\TenantPlanManager;
use App\Support\CurrencySettingsResolver;
use App\Support\MoneyFormatter;

class DashboardReportService extends BaseReportService
{
    private SalesReportService $salesReports;

    private PaymentReportService $paymentReports;

    private InventoryReportService $inventoryReports;

    private PurchaseReportService $purchaseReports;

    private FinanceReportService $financeReports;

    private PosReportService $posReports;

    private MoneyFormatter $money;

    private CurrencySettingsResolver $currencySettings;

    private TenantPlanManager $plans;

    public function __construct(
        SalesReportService $salesReports,
        PaymentReportService $paymentReports,
        InventoryReportService $inventoryReports,
        PurchaseReportService $purchaseReports,
        FinanceReportService $financeReports,
        PosReportService $posReports,
        TenantPlanManager $plans,
        MoneyFormatter $money,
        CurrencySettingsResolver $currencySettings
    ) {
        $this->salesReports = $salesReports;
        $this->paymentReports = $paymentReports;
        $this->inventoryReports = $inventoryReports;
        $this->purchaseReports = $purchaseReports;
        $this->financeReports = $financeReports;
        $this->posReports = $posReports;
        $this->plans = $plans;
        $this->money = $money;
        $this->currencySettings = $currencySettings;
    }

    public function filters(array $filters): array
    {
        return $this->baseFilters($filters);
    }

    public function cards(array $filters): array
    {
        $sales = $this->salesReports->summaryOnly($this->salesReports->filters($filters));
        $payments = $this->paymentReports->summaryOnly($this->paymentReports->filters($filters));
        $finance = $this->financeReports->summaryOnly($this->financeReports->filters($filters));
        $currency = $this->currencySettings->defaultCurrency();
        $hasAdvancedReports = $this->plans->hasFeature(PlanFeature::ADVANCED_REPORTS);

        $cards = [
            ['title' => 'Sales', 'value' => $this->money->format((float) $sales['gross_total'], $currency), 'meta' => $sales['transaction_count'] . ' transaksi', 'route' => $hasAdvancedReports ? route('reports.sales') : null],
            ['title' => 'Payments', 'value' => $this->money->format((float) $payments['total_amount'], $currency), 'meta' => $payments['payment_count'] . ' pembayaran posted', 'route' => $hasAdvancedReports ? route('reports.payments') : null],
            ['title' => 'Finance', 'value' => $this->money->format((float) $finance['net_total'], $currency), 'meta' => 'Net cash flow', 'route' => $hasAdvancedReports ? route('reports.finance') : null],
        ];

        if ($this->plans->hasFeature(PlanFeature::PURCHASES)) {
            $purchases = $this->purchaseReports->summaryOnly($this->purchaseReports->filters($filters));
            $cards[] = ['title' => 'Purchases', 'value' => $this->money->format((float) $purchases['grand_total'], $currency), 'meta' => $purchases['purchase_count'] . ' dokumen', 'route' => $hasAdvancedReports ? route('reports.purchases') : null];
        }

        if ($this->plans->hasFeature(PlanFeature::INVENTORY)) {
            $inventory = $this->inventoryReports->summaryOnly($this->inventoryReports->filters($filters));
            $cards[] = ['title' => 'Inventory', 'value' => number_format((float) $inventory['total_quantity'], 2, ',', '.'), 'meta' => $inventory['low_stock_count'] . ' item low stock', 'route' => $hasAdvancedReports ? route('reports.inventory') : null];
        }

        if ($this->plans->hasFeature(PlanFeature::POINT_OF_SALE)) {
            $pos = $this->posReports->summaryOnly($this->posReports->filters($filters));
            $cards[] = ['title' => 'POS / Cashier', 'value' => $this->money->format((float) $pos['difference_total'], $currency), 'meta' => $pos['shift_count'] . ' shift', 'route' => $hasAdvancedReports ? route('reports.pos') : null];
        }

        return $cards;
    }

    public function catalog(): array
    {
        if (!$this->plans->hasFeature(PlanFeature::ADVANCED_REPORTS)) {
            return [];
        }

        $catalog = [
            ['title' => 'Sales Reports', 'route' => route('reports.sales'), 'items' => ['Summary', 'By date', 'By product', 'By customer', 'By cashier']],
            ['title' => 'Payment Reports', 'route' => route('reports.payments'), 'items' => ['Summary', 'By method', 'Cash vs non-cash']],
            ['title' => 'Finance Reports', 'route' => route('reports.finance'), 'items' => ['Cash in/out', 'Expense by category']],
        ];

        if ($this->plans->hasFeature(PlanFeature::PURCHASES)) {
            $catalog[] = ['title' => 'Purchase Reports', 'route' => route('reports.purchases'), 'items' => ['Purchase summary', 'Supplier report', 'Received vs pending']];
        }

        if ($this->plans->hasFeature(PlanFeature::INVENTORY)) {
            $catalog[] = ['title' => 'Inventory Reports', 'route' => route('reports.inventory'), 'items' => ['Stock list', 'Low stock', 'Stock movement', 'Adjustment', 'Opname']];
        }

        if ($this->plans->hasFeature(PlanFeature::POINT_OF_SALE)) {
            $catalog[] = ['title' => 'POS / Cashier Reports', 'route' => route('reports.pos'), 'items' => ['Shift summary', 'Cash difference']];
        }

        return $catalog;
    }
}
