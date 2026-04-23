<?php

namespace App\Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Inventory\Models\InventoryLocation;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Reports\Services\DashboardReportService;
use App\Modules\Reports\Services\FinanceReportService;
use App\Modules\Reports\Services\InventoryReportService;
use App\Modules\Reports\Services\PaymentReportService;
use App\Modules\Reports\Services\PosReportService;
use App\Modules\Reports\Services\PurchaseReportService;
use App\Modules\Reports\Services\SalesReportService;
use App\Modules\Sales\Models\Sale;
use App\Support\AccountingSourceReferenceService;
use App\Support\BooleanQuery;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function dashboard(Request $request, DashboardReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::dashboard', [
            'filters' => $filters,
            'cards' => $service->cards($filters),
            'reportCatalog' => $service->catalog(),
        ]);
    }

    public function sales(Request $request, SalesReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::sales', [
            'filters' => $filters,
            'sourceOptions' => [
                Sale::SOURCE_MANUAL => 'Manual',
                Sale::SOURCE_POS => 'POS',
                Sale::SOURCE_ONLINE => 'Online',
                Sale::SOURCE_API => 'API',
            ],
            ...$service->data($filters),
        ]);
    }

    public function payments(Request $request, PaymentReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::payments', [
            'filters' => $filters,
            'methods' => PaymentMethod::query()->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            ...$service->data($filters),
        ]);
    }

    public function inventory(Request $request, InventoryReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::inventory', [
            'filters' => $filters,
            'locations' => InventoryLocation::query()->orderBy('name')->get(['id', 'name']),
            ...$service->data($filters),
        ]);
    }

    public function purchases(Request $request, PurchaseReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::purchases', [
            'filters' => $filters,
            ...$service->data($filters),
        ]);
    }

    public function finance(Request $request, FinanceReportService $service, AccountingSourceReferenceService $sourceReferenceService): View
    {
        $filters = $service->filters($request->all());
        $reportData = $service->data($filters);
        $generalLedgerRows = collect($reportData['generalLedger'] ?? [])->flatten(1);
        $inventoryReconciliationDetailRows = collect($reportData['inventoryGlReconciliationDetails'] ?? []);

        return view('reports::finance', [
            'filters' => $filters,
            'categories' => BooleanQuery::apply(FinanceCategory::query(), 'is_active')
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(['id', 'name']),
            'journalReferences' => $sourceReferenceService->buildForJournals($generalLedgerRows),
            'inventorySourceReferences' => $sourceReferenceService->buildForSources($inventoryReconciliationDetailRows),
            ...$reportData,
        ]);
    }

    public function exportFinanceTrialBalance(Request $request, FinanceReportService $service)
    {
        $filters = $service->filters($request->all());
        $rows = $service->trialBalance($filters);

        return $this->streamCsv('trial-balance-' . now()->format('Ymd-His') . '.csv', function ($handle) use ($rows) {
            fputcsv($handle, ['Account Code', 'Account Name', 'Debit', 'Credit']);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    $row->account_code,
                    $row->account_name,
                    round((float) $row->debit_total, 2),
                    round((float) $row->credit_total, 2),
                ]);
            }
        });
    }

    public function exportFinanceGeneralLedger(Request $request, FinanceReportService $service)
    {
        $filters = $service->filters($request->all());
        $ledger = $service->generalLedger($filters);

        return $this->streamCsv('general-ledger-' . now()->format('Ymd-His') . '.csv', function ($handle) use ($ledger) {
            fputcsv($handle, [
                'Entry Date',
                'Journal Number',
                'Entry Type',
                'Status',
                'Account Code',
                'Account Name',
                'Description',
                'Debit',
                'Credit',
                'Running Balance',
                'Source Type',
                'Source ID',
            ]);

            foreach ($ledger as $accountCode => $entries) {
                $runningBalance = 0.0;

                foreach ($entries as $entry) {
                    $runningBalance += (float) $entry->debit - (float) $entry->credit;

                    fputcsv($handle, [
                        $entry->entry_date,
                        $entry->journal_number,
                        $entry->entry_type,
                        $entry->status,
                        $accountCode,
                        $entry->account_name,
                        $entry->description,
                        round((float) $entry->debit, 2),
                        round((float) $entry->credit, 2),
                        round($runningBalance, 2),
                        $entry->source_type,
                        $entry->source_id,
                    ]);
                }
            }
        });
    }

    public function exportFinanceBalanceSheet(Request $request, FinanceReportService $service)
    {
        $filters = $service->filters($request->all());
        $balanceSheet = $service->balanceSheet($filters);

        return $this->streamCsv('balance-sheet-' . now()->format('Ymd-His') . '.csv', function ($handle) use ($balanceSheet) {
            fputcsv($handle, ['Section', 'Group', 'Account Code', 'Account Name', 'Balance']);

            $this->writeBalanceSheetSection($handle, 'Assets', $balanceSheet['assets']);
            fputcsv($handle, ['Assets', 'Total Assets', '', '', round((float) $balanceSheet['asset_total'], 2)]);

            $this->writeBalanceSheetSection($handle, 'Liabilities', $balanceSheet['liabilities']);
            fputcsv($handle, ['Liabilities', 'Total Liabilities', '', '', round((float) $balanceSheet['liability_total'], 2)]);

            $this->writeBalanceSheetSection($handle, 'Equity', $balanceSheet['equity']);
            fputcsv($handle, ['Equity', 'Total Equity', '', '', round((float) $balanceSheet['equity_total'], 2)]);
            fputcsv($handle, ['Liabilities + Equity', 'Total', '', '', round((float) $balanceSheet['liability_and_equity_total'], 2)]);
            fputcsv($handle, ['Status', $balanceSheet['is_balanced'] ? 'BALANCED' : 'PROVISIONAL', '', '', '']);
            fputcsv($handle, ['Basis', $balanceSheet['basis'], '', '', '']);
        });
    }

    public function pos(Request $request, PosReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::pos', [
            'filters' => $filters,
            ...$service->data($filters),
        ]);
    }

    private function streamCsv(string $filename, callable $callback)
    {
        return response()->streamDownload(function () use ($callback) {
            $handle = fopen('php://output', 'w');
            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            $callback($handle);
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function writeBalanceSheetSection($handle, string $section, $groups): void
    {
        foreach ($groups as $group => $rows) {
            foreach ($rows as $row) {
                fputcsv($handle, [
                    $section,
                    $group,
                    $row['account_code'],
                    $row['account_name'],
                    round((float) $row['balance'], 2),
                ]);
            }

            fputcsv($handle, [
                $section,
                $group . ' Total',
                '',
                '',
                round((float) $rows->sum('balance'), 2),
            ]);
        }
    }
}
