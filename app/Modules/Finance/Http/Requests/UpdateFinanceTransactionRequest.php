<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceAccount;
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
            'entry_mode' => ['nullable', Rule::in([
                FinanceTransaction::ENTRY_MODE_STANDARD,
                FinanceTransaction::ENTRY_MODE_TRANSFER,
            ])],
            'transaction_type' => ['required', Rule::in([
                FinanceTransaction::TYPE_CASH_IN,
                FinanceTransaction::TYPE_CASH_OUT,
                FinanceTransaction::TYPE_EXPENSE,
            ])],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'finance_account_id' => ['required', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'finance_category_id' => ['nullable', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'counterparty_finance_account_id' => ['nullable', 'integer', Rule::exists('finance_accounts', 'id')->where(fn ($query) => $query
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId()))],
            'attachment' => ['nullable', 'file', 'max:4096'],
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
            'entry_mode' => $this->input('entry_mode', $this->has('counterparty_finance_account_id') ? FinanceTransaction::ENTRY_MODE_TRANSFER : FinanceTransaction::ENTRY_MODE_STANDARD),
            'branch_id' => $this->input('branch_id', $this->input('outlet_id', BranchContext::currentId())),
        ]);
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $account = FinanceAccount::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($this->input('finance_account_id'));

            if (!$account || !$account->is_active) {
                $validator->errors()->add('finance_account_id', 'Finance account tidak aktif atau tidak tersedia.');
                return;
            }

            $isTransfer = $this->input('entry_mode') === FinanceTransaction::ENTRY_MODE_TRANSFER;

            if ($isTransfer && !$this->filled('counterparty_finance_account_id')) {
                $validator->errors()->add('counterparty_finance_account_id', 'Pilih account tujuan transfer.');
            }

            if ($isTransfer && (string) $this->input('finance_account_id') === (string) $this->input('counterparty_finance_account_id')) {
                $validator->errors()->add('counterparty_finance_account_id', 'Account sumber dan tujuan transfer harus berbeda.');
            }

            if (!$isTransfer && !$this->filled('finance_category_id')) {
                $validator->errors()->add('finance_category_id', 'Category wajib dipilih untuk transaksi non-transfer.');
                return;
            }

            $category = FinanceCategory::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->find($this->input('finance_category_id'));

            if (!$isTransfer && (!$category || !$category->is_active)) {
                $validator->errors()->add('finance_category_id', 'Category finance tidak aktif atau tidak tersedia.');
                return;
            }

            if (!$isTransfer && $category->transaction_type !== $this->input('transaction_type')) {
                $validator->errors()->add('finance_category_id', 'Category tidak cocok dengan tipe transaksi yang dipilih.');
            }

            if ($isTransfer) {
                $counterparty = FinanceAccount::query()
                    ->where('tenant_id', TenantContext::currentId())
                    ->where('company_id', CompanyContext::currentId())
                    ->find($this->input('counterparty_finance_account_id'));

                if (!$counterparty || !$counterparty->is_active) {
                    $validator->errors()->add('counterparty_finance_account_id', 'Account tujuan transfer tidak aktif atau tidak tersedia.');
                }
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
