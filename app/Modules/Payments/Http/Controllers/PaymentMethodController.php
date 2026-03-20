<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Http\Requests\StorePaymentMethodRequest;
use App\Modules\Payments\Http\Requests\UpdatePaymentMethodRequest;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentLookupService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    private $lookupService;

    public function __construct(PaymentLookupService $lookupService)
    {
        $this->lookupService = $lookupService;
    }

    public function index(): View
    {
        return view('payments::methods.index', [
            'methods' => PaymentMethod::query()
                ->where('tenant_id', TenantContext::currentId())
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'typeOptions' => $this->lookupService->paymentMethodTypeOptions(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        PaymentMethod::query()->create(array_merge(
            $request->validated(),
            [
            'tenant_id' => TenantContext::currentId(),
            'requires_reference' => $request->boolean('requires_reference'),
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $request->user() ? $request->user()->id : null,
            'updated_by' => $request->user() ? $request->user()->id : null,
            ]
        ));

        return redirect()->route('payments.methods.index')->with('status', 'Payment method ditambahkan.');
    }

    public function edit(PaymentMethod $method): View
    {
        return view('payments::methods.edit', [
            'method' => $method,
            'typeOptions' => $this->lookupService->paymentMethodTypeOptions(),
        ]);
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $method): RedirectResponse
    {
        $payload = $request->validated();

        if ($method->is_system) {
            unset($payload['code']);
        }

        $method->update(array_merge(
            $payload,
            [
            'requires_reference' => $request->boolean('requires_reference'),
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $request->user() ? $request->user()->id : null,
            ]
        ));

        return redirect()->route('payments.methods.index')->with('status', 'Payment method diperbarui.');
    }
}
