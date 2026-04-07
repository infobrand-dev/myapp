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

    public function finance(Request $request, FinanceReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::finance', [
            'filters' => $filters,
            'categories' => BooleanQuery::apply(FinanceCategory::query(), 'is_active')
                ->orderBy('transaction_type')
                ->orderBy('name')
                ->get(['id', 'name']),
            ...$service->data($filters),
        ]);
    }

    public function pos(Request $request, PosReportService $service): View
    {
        $filters = $service->filters($request->all());

        return view('reports::pos', [
            'filters' => $filters,
            ...$service->data($filters),
        ]);
    }
}
