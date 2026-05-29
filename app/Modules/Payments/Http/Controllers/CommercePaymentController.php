<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Models\Payment;
use App\Modules\Payments\Repositories\PaymentRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CommercePaymentController extends Controller
{
    private PaymentRepository $repository;

    public function __construct(PaymentRepository $repository)
    {
        $this->repository = $repository;
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

        $filters['source'] = $filters['source'] ?: Payment::SOURCE_ONLINE;
        $filters['scope'] = 'all';

        return view('payments::commerce.index', [
            'payments' => $this->repository->paginateForIndex($filters),
            'summary' => $this->repository->summary($filters),
            'filters' => $filters,
        ]);
    }

    public function show(Payment $payment): View
    {
        return view('payments::commerce.show', [
            'payment' => $this->repository->findForDetail($payment),
        ]);
    }
}
