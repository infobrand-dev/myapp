<?php

namespace App\Modules\Reports\Services;

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

    public function __construct(
        SalesReportService $salesReports,
        PaymentReportService $paymentReports,
        InventoryReportService $inventoryReports,
        PurchaseReportService $purchaseReports,
        FinanceReportService $financeReports,
        PosReportService $posReports,
        MoneyFormatter $money,
        CurrencySettingsResolver $currencySettings
    ) {
        $this->salesReports = $salesReports;
        $this->paymentReports = $paymentReports;
        $this->inventoryReports = $inventoryReports;
        $this->purchaseReports = $purchaseReports;
        $this->financeReports = $financeReports;
        $this->posReports = $posReports;
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
        $inventory = $this->inventoryReports->summaryOnly($this->inventoryReports->filters($filters));
        $purchases = $this->purchaseReports->summaryOnly($this->purchaseReports->filters($filters));
        $finance = $this->financeReports->summaryOnly($this->financeReports->filters($filters));
        $pos = $this->posReports->summaryOnly($this->posReports->filters($filters));
        $currency = $this->currencySettings->defaultCurrency();

        return [
            ['title' => 'Sales', 'value' => $this->money->format((float) $sales['gross_total'], $currency), 'meta' => $sales['transaction_count'] . ' transaksi', 'route' => route('reports.sales')],
            ['title' => 'Payments', 'value' => $this->money->format((float) $payments['total_amount'], $currency), 'meta' => $payments['payment_count'] . ' pembayaran posted', 'route' => route('reports.payments')],
            ['title' => 'Inventory', 'value' => number_format((float) $inventory['total_quantity'], 2, ',', '.'), 'meta' => $inventory['low_stock_count'] . ' item low stock', 'route' => route('reports.inventory')],
            ['title' => 'Purchases', 'value' => $this->money->format((float) $purchases['grand_total'], $currency), 'meta' => $purchases['purchase_count'] . ' dokumen', 'route' => route('reports.purchases')],
            ['title' => 'Finance', 'value' => $this->money->format((float) $finance['net_total'], $currency), 'meta' => 'Net cash flow', 'route' => route('reports.finance')],
            ['title' => 'POS / Cashier', 'value' => $this->money->format((float) $pos['difference_total'], $currency), 'meta' => $pos['shift_count'] . ' shift', 'route' => route('reports.pos')],
        ];
    }

    public function catalog(): array
    {
        return [
            ['title' => 'Sales Reports', 'route' => route('reports.sales'), 'items' => ['Summary', 'By date', 'By product', 'By customer', 'By cashier']],
            ['title' => 'Payment Reports', 'route' => route('reports.payments'), 'items' => ['Summary', 'By method', 'Cash vs non-cash']],
            ['title' => 'Inventory Reports', 'route' => route('reports.inventory'), 'items' => ['Stock list', 'Low stock', 'Stock movement', 'Adjustment', 'Opname']],
            ['title' => 'Purchase Reports', 'route' => route('reports.purchases'), 'items' => ['Purchase summary', 'Supplier report', 'Received vs pending']],
            ['title' => 'Finance Reports', 'route' => route('reports.finance'), 'items' => ['Cash in/out', 'Expense by category']],
            ['title' => 'POS / Cashier Reports', 'route' => route('reports.pos'), 'items' => ['Shift summary', 'Cash difference']],
        ];
    }
}
