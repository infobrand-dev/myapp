<?php

namespace App\Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Payments\Http\Requests\StorePaymentMethodRequest;
use App\Modules\Payments\Http\Requests\UpdatePaymentMethodRequest;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\Payments\Services\PaymentLookupService;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
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
        $companyId = $this->requireCurrentCompanyId();

        return view('payments::methods.index', [
            'methods' => PaymentMethod::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', $companyId)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'typeOptions' => $this->lookupService->paymentMethodTypeOptions(),
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $companyId = $this->requireCurrentCompanyId();

        PaymentMethod::query()->create(array_merge(
            $request->validated(),
            [
                'tenant_id' => TenantContext::currentId(),
                'company_id' => $companyId,
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

    public function destroy(PaymentMethod $method): RedirectResponse
    {
        if ($method->is_system) {
            return back()->with('error', 'Metode sistem tidak bisa dihapus.');
        }

        if ($method->payments()->exists()) {
            return back()->with('error', 'Tidak bisa dihapus — sudah digunakan pada transaksi.');
        }

        $method->delete();

        return redirect()->route('payments.methods.index')->with('status', 'Payment method dihapus.');
    }

    private function requireCurrentCompanyId(): int
    {
        $companyId = CompanyContext::currentId();

        if ($companyId) {
            return (int) $companyId;
        }

        throw ValidationException::withMessages([
            'company' => 'Pilih company aktif terlebih dahulu sebelum mengelola payment method.',
        ]);
    }
}
