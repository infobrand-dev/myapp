<?php

namespace App\Modules\Finance\Http\Requests;

use App\Modules\Finance\Models\FinanceCategory;
use App\Modules\Finance\Models\FinanceTransaction;
use App\Modules\PointOfSale\Models\PosCashSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFinanceTransactionRequest extends FormRequest
{
    private const TENANT_ID = 1;

    public function authorize(): bool
    {
        $user = $this->user();

        return $user ? $user->can('finance.create') : false;
    }

    public function rules(): array
    {
        $shiftRules = ['nullable', 'integer', 'min:1'];

        if (Schema::hasTable('pos_cash_sessions')) {
            $shiftRules[] = Rule::exists('pos_cash_sessions', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID));
        }

        return [
            'transaction_type' => ['required', Rule::in([
                FinanceTransaction::TYPE_CASH_IN,
                FinanceTransaction::TYPE_CASH_OUT,
                FinanceTransaction::TYPE_EXPENSE,
            ])],
            'transaction_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'finance_category_id' => ['required', 'integer', Rule::exists('finance_categories', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
            'notes' => ['nullable', 'string'],
            'outlet_id' => ['nullable', 'integer', 'min:1'],
            'pos_cash_session_id' => $shiftRules,
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $category = FinanceCategory::query()
                ->where('tenant_id', self::TENANT_ID)
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
                ->where('tenant_id', self::TENANT_ID)
                ->find($sessionId);

            if (!$session) {
                $validator->errors()->add('pos_cash_session_id', 'Shift kasir tidak tersedia untuk tenant aktif.');
            }
        });
    }
}
