<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Actions\CreatePaymentAction;
use App\Modules\Payments\Actions\VoidPaymentAction;
use App\Modules\Payments\Http\Requests\StorePaymentRequest;
use App\Modules\Payments\Http\Requests\VoidPaymentRequest;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Repositories\PaymentRepository;
use App\Modules\Payments\Services\PaymentLookupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentController extends Controller
{
    private $repository;
    private $lookupService;
    private $createPayment;
    private $voidPayment;

    public function __construct(
        PaymentRepository $repository,
        PaymentLookupService $lookupService,
        CreatePaymentAction $createPayment,
        VoidPaymentAction $voidPayment
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createPayment = $createPayment;
        $this->voidPayment = $voidPayment;
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
            'paymentSourceOptions' => $this->lookupService->paymentSourceOptions(),
            'receivers' => $this->lookupService->receivers(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('payments::create', [
            'paymentMethods' => $this->lookupService->paymentMethods(),
            'paymentSourceOptions' => $this->lookupService->paymentSourceOptions(),
            'payableTypeOptions' => $this->lookupService->payableTypeOptions(),
            'saleOptions' => $this->lookupService->saleOptions(),
            'saleReturnOptions' => $this->lookupService->saleReturnOptions(),
            'purchaseOptions' => $this->lookupService->purchaseOptions(),
            'receivers' => $this->lookupService->receivers(),
            'prefillSaleId' => $request->integer('sale_id') ?: null,
            'prefillSaleReturnId' => $request->integer('sale_return_id') ?: null,
            'prefillPurchaseId' => $request->integer('purchase_id') ?: null,
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $payment = $this->createPayment->execute($request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Payment berhasil dicatat.');
    }

    public function show(Payment $payment): View
    {
        $this->authorize('view', $payment);

        return view('payments::show', [
            'payment' => $this->repository->findForDetail($payment),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
        ]);
    }

    public function void(VoidPaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('void', $payment);

        $payment = $this->voidPayment->execute($payment, $request->validated(), $request->user());

        return redirect()->route('payments.show', $payment)->with('status', 'Payment berhasil di-void.');
    }
}
