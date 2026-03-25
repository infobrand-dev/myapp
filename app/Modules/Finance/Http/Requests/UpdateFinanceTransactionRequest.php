<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\PointOfSale\Models\PosCashSession;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\TenantContext;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateFinanceTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('finance.create') : false;
    }

    public function rules(): array
    {
        $shiftRules = ['nullable', 'integer', 'min:1'];

        if (Schema::hasTable('pos_cash_sessions')) {
            $shiftRules[] = Rule::exists('pos_cash_sessions', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()));
        }

        return [
            'transaction_type' => ['required', Rule::in([
                FinanceTransaction::TYPE_CASH_IN,
                FinanceTransaction::TYPE_CASH_OUT,
                FinanceTransaction::TYPE_EXPENSE,
            ])],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'finance_category_id' => ['required', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'notes' => ['nullable', 'string'],
            'branch_id' => ['nullable', 'integer', 'min:1', Rule::exists('branches', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'pos_cash_session_id' => $shiftRules,
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'branch_id' => $this->input('branch_id', $this->input('outlet_id', BranchContext::currentId())),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $category = FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($this->input('finance_category_id'));

            if (!$category || !$category->is_active) {
                $validator->errors()->add('finance_category_id', 'Category finance tidak aktif atau tidak tersedia.');
                return;
            }

            if ($category->transaction_type !== $this->input('transaction_type')) {
                $validator->errors()->add('finance_category_id', 'Category tidak cocok dengan tipe transaksi yang dipilih.');
            }

            $sessionId = $this->input('pos_cash_session_id');
            if (!$sessionId) {
                return;
            }

            $session = PosCashSession::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($sessionId);

            if (!$session) {
                $validator->errors()->add('pos_cash_session_id', 'Shift kasir tidak tersedia untuk tenant aktif.');
            }
        });
    }
}
