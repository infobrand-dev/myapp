<?php

namespace App\Modules\Products\Http\Requests;

use App\Modules\Products\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BulkProductActionRequest extends FormRequest
{
    private const TENANT_ID = 1;

    public function authorize(): bool
    {
        $user = $this->user();
        if (!$user) {
            return false;
        }

        $action = (string) $this->input('action');

        if ($action === 'delete') {
            return $user->can('products.delete');
        }

        return $user->can('products.update');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['activate', 'deactivate', 'delete'])],
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['integer', 'distinct', Rule::exists('products', 'id')->where(fn ($query) => $query->where('tenant_id', self::TENANT_ID))],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach ((array) $this->input('product_ids', []) as $index => $productId) {
                    if (!Product::query()->where('tenant_id', self::TENANT_ID)->find($productId)) {
                        $validator->errors()->add("product_ids.$index", 'Produk tidak tersedia untuk tenant aktif.');
                    }
                }
            },
        ];
    }
}
