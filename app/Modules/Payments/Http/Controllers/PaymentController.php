<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Actions\UpdatePaymentAction;
use App\Modules\Payments\Actions\VoidPaymentAction;
use App\Modules\Payments\Http\Requests\StorePaymentRequest;
use App\Modules\Payments\Http\Requests\UpdatePaymentRequest;
use App\Modules\Payments\Http\Requests\VoidPaymentRequest;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Payments\Services\PaymentLookupService;
use App\Support\CurrencySettingsResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private $repository;
    private $lookupService;
    private $createPayment;
    private $updatePayment;
    private $voidPayment;
    private $currencySettings;

    public function __construct(
        PaymentRepository $repository,
        PaymentLookupService $lookupService,
        CreatePaymentAction $createPayment,
        UpdatePaymentAction $updatePayment,
        VoidPaymentAction $voidPayment,
        CurrencySettingsResolver $currencySettings
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createPayment = $createPayment;
        $this->updatePayment = $updatePayment;
        $this->voidPayment = $voidPayment;
        $this->currencySettings = $currencySettings;
    }

    public function index(Request $request): View
    {
        $filters = $request->only([
            'search',
            'status',
            'payment_method_id',
            'source',
            'received_by',
            'date_from',
            'date_to',
        ]);

        if ($request->user() && $request->user()->can('payments.view_all')) {
            $filters['scope'] = 'all';
        } else {
            $filters['scope'] = 'own';
            $filters['user_id'] = $request->user() ? $request->user()->id : null;
        }

        return view('payments::index', [
            'payments' => $this->repository->paginateForIndex($filters),
            'summary' => $this->repository->summary($filters),
            'filters' => $filters,
            'paymentMethods' => $this->lookupService->paymentMethods(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'reconciliationStatusOptions' => $this->lookupService->reconciliationStatusOptions(),
            'paymentSourceOptions' => $this->lookupService->paymentSourceOptions(),
            'receivers' => $this->lookupService->receivers(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('payments::create', [
            'payment' => new Payment(),
            'paymentMethods' => $this->lookupService->paymentMethods(),
            'paymentSourceOptions' => $this->lookupService->paymentSourceOptions(),
            'reconciliationStatusOptions' => $this->lookupService->reconciliationStatusOptions(),
            'payableTypeOptions' => $this->lookupService->payableTypeOptions(),
            'saleOptions' => $this->lookupService->saleOptions(),
            'saleReturnOptions' => $this->lookupService->saleReturnOptions(),
            'purchaseOptions' => $this->lookupService->purchaseOptions(),
            'receivers' => $this->lookupService->receivers(),
            'defaultCurrency' => $this->currencySettings->defaultCurrency(),
            'prefillSaleId' => $request->integer('sale_id') ?: null,
            'prefillSaleReturnId' => $request->integer('sale_return_id') ?: null,
            'prefillPurchaseId' => $request->integer('purchase_id') ?: null,
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', \App\Support\TenantContext::currentId())
                ->where('company_id', \App\Support\CompanyContext::currentId())
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->createPayment->execute($request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Pembayaran dicatat.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('payments::show', [
            'payment' => $this->repository->findForDetail($payment),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'reconciliationStatusOptions' => $this->lookupService->reconciliationStatusOptions(),
            'activities' => $payment->activities()->with('causer')->latest()->get(),
        ]);
    }

    public function edit(Payment $payment): View
    {
        $this->authorize('update', $payment);

        $payment = $this->repository->findForDetail($payment);

        return view('payments::create', [
            'payment' => $payment,
            'paymentMethods' => $this->lookupService->paymentMethods(),
            'paymentSourceOptions' => $this->lookupService->paymentSourceOptions(),
            'reconciliationStatusOptions' => $this->lookupService->reconciliationStatusOptions(),
            'payableTypeOptions' => $this->lookupService->payableTypeOptions(),
            'saleOptions' => $this->lookupService->saleOptions($payment->id),
            'saleReturnOptions' => $this->lookupService->saleReturnOptions($payment->id),
            'purchaseOptions' => $this->lookupService->purchaseOptions($payment->id),
            'receivers' => $this->lookupService->receivers(),
            'defaultCurrency' => $this->currencySettings->defaultCurrency(),
            'prefillSaleId' => null,
            'prefillSaleReturnId' => null,
            'prefillPurchaseId' => null,
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', \App\Support\TenantContext::currentId())
                ->where('company_id', \App\Support\CompanyContext::currentId())
                ->active()
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function update(UpdatePaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('update', $payment);

        $payment = $this->updatePayment->execute($payment, $request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Pembayaran diperbarui.');
    }

    public function void(VoidPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('void', $payment);

        $payment = $this->voidPayment->execute($payment, $request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Pembayaran di-void.');
    }
}
