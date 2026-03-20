<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\PointOfSale\Http\Requests\CloseCashSessionRequest;
use App\Modules\PointOfSale\Http\Requests\OpenCashSessionRequest;
use App\Modules\PointOfSale\Http\Requests\StoreCashSessionMovementRequest;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Modules\PointOfSale\Services\PosCashSessionService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Validation\ValidationException;

class CashSessionController extends Controller
{

    private $service;

    public function __construct(PosCashSessionService $service)
    {
        $this->service = $service;
    }

    public function index(): View
    {
        return view('pos::cash-sessions.index', [
            'activeSession' => $this->service->activeSessionFor(request()->user()),
            'company' => CompanyContext::currentCompany(),
            'sessions' => PosCashSession::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['cashier', 'closer'])
                ->latest('opened_at')
                ->paginate(15),
        ]);
    }

    public function create(): View
    {
        return view('pos::cash-sessions.create', [
            'activeSession' => $this->service->activeSessionFor(request()->user()),
            'company' => CompanyContext::currentCompany(),
        ]);
    }

    public function store(OpenCashSessionRequest $request): RedirectResponse
    {
        $session = $this->service->open($request->user(), $request->validated());

        return redirect()->route('pos.shifts.show', $session)->with('status', 'Shift kasir berhasil dibuka.');
    }

    public function show(PosCashSession $shift): View
    {
        $shift->load([
            'cashier',
            'closer',
            'sales',
            'payments.method',
            'cashMovements.creator',
        ]);

        return view('pos::cash-sessions.show', [
            'shift' => $shift,
            'company' => CompanyContext::currentCompany(),
            'expectedCash' => $this->service->expectedCashAmount($shift),
            'saleCount' => $shift->sales->count(),
            'salesTotal' => (float) $shift->sales->sum('grand_total'),
            'cashPaymentTotal' => (float) $shift->payments->filter(function ($payment) {
                return $payment->method && $payment->method->code === 'cash' && $payment->status === 'posted';
            })->sum('amount'),
        ]);
    }

    public function close(PosCashSession $shift, CloseCashSessionRequest $request): RedirectResponse
    {
        try {
            $this->service->close($shift, $request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Shift berhasil ditutup.');
    }

    public function storeMovement(PosCashSession $shift, StoreCashSessionMovementRequest $request): RedirectResponse
    {
        try {
            $this->service->recordMovement($shift, $request->user(), $request->validated());
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors());
        }

        return back()->with('status', 'Cash movement berhasil dicatat.');
    }
}
