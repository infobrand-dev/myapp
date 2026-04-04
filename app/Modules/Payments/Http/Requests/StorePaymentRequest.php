<?php

namespace App\Modules\Payments\Http\Requests;

use App\Support\CompanyContext;
use App\Support\CurrencySettingsResolver;
use App\Support\TenantContext;
use App\Support\UserAccessManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() ? (bool) $this->user()->can('payments.create') : false;
    }

    public function rules(): array
    {
        $payableTypes = ['sale', 'sale_return'];
        if ($this->purchaseModuleReady()) {
            $payableTypes[] = 'purchase';
        }

        $receivedByRules = ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('tenant_id', TenantContext::currentId()))];

        if (!$this->user() || !$this->user()->can('payments.assign_receiver')) {
            $receivedByRules = ['prohibited'];
        }

        return [
            'payment_method_id' => ['required', 'integer', Rule::exists('payment_methods', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'amount' => ['required', 'numeric', 'gt:0'],
            'currency_code' => ['nullable', 'string', 'max:10'],
            'paid_at' => ['nullable', 'date'],
            'source' => ['nullable', 'string', 'max:30'],
            'channel' => ['nullable', 'string', 'max:50'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'external_reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
            'received_by' => $receivedByRules,
            'branch_id' => ['nullable', 'integer', 'min:1', Rule::exists('branches', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.payable_type' => ['required', Rule::in($payableTypes)],
            'allocations.*.payable_id' => ['required', 'integer', 'min:1'],
            'allocations.*.amount' => ['required', 'numeric', 'gt:0'],
        ];
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validateAccessScope($validator),
        ];
    }

    protected function prepareForValidation(): void
    {
        $allocations = collect($this->input('allocations', []))
            ->filter(fn ($allocation) => is_array($allocation))
            ->map(function (array $allocation) {
                return [
                    'payable_type' => strtolower((string) ($allocation['payable_type'] ?? 'sale')),
                    'payable_id' => $allocation['payable_id'] ?? null,
                    'amount' => $allocation['amount'] ?? null,
                ];
            })
            ->values()
            ->all();

        if (empty($allocations) && $this->filled('sale_id')) {
            $allocations[] = [
                'payable_type' => 'sale',
                'payable_id' => $this->input('sale_id'),
                'amount' => $this->input('amount'),
            ];
        }

        if ($this->purchaseModuleReady() && empty($allocations) && $this->filled('purchase_id')) {
            $allocations[] = [
                'payable_type' => 'purchase',
                'payable_id' => $this->input('purchase_id'),
                'amount' => $this->input('amount'),
            ];
        }

        $this->merge([
            'currency_code' => strtoupper((string) ($this->input('currency_code') ?: app(CurrencySettingsResolver::class)->defaultCurrency())),
            'source' => strtolower((string) ($this->input('source') ?: 'backoffice')),
            'branch_id' => $this->input('branch_id', $this->input('outlet_id')),
            'allocations' => $allocations,
        ]);
    }

    private function validateAccessScope(Validator $validator): void
    {
        $user = $this->user();
        $companyId = CompanyContext::currentId();
        $branchId = $this->input('branch_id');
        $accessManager = app(UserAccessManager::class);

        $allowedCompanyIds = $accessManager->companyIdsFor($user);
        if ($companyId && $allowedCompanyIds !== null && !$allowedCompanyIds->contains((int) $companyId)) {
            $validator->errors()->add('company_id', 'User tidak memiliki akses ke company aktif.');
        }

        if ($branchId) {
            $allowedBranchIds = $accessManager->branchIdsFor($user, $companyId);

            if ($allowedBranchIds !== null && !$allowedBranchIds->contains((int) $branchId)) {
                $validator->errors()->add('branch_id', 'User tidak memiliki akses ke branch yang dipilih.');
            }
        }
    }

    private function purchaseModuleReady(): bool
    {
        return class_exists(\App\Modules\Purchases\Models\Purchase::class) && Schema::hasTable('purchases');
    }
}
