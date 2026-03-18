<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\Products\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class PosScreenController extends Controller
{
    public function index(): View
    {
        return view('pos::index', [
            'initialProducts' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(18)
                ->get(['id', 'name', 'sku', 'barcode', 'sell_price']),
            'initialCustomers' => Contact::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(12)
                ->get(['id', 'name', 'phone', 'mobile', 'email']),
            'paymentMethods' => PaymentMethod::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name', 'type', 'requires_reference']),
            'heldCount' => PosCart::query()
                ->where('cashier_user_id', auth()->id())
                ->where('status', PosCart::STATUS_HELD)
                ->count(),
        ]);
    }

    public function architecture(): View
    {
        return view('pos::architecture', [
            'blueprint' => File::get(__DIR__ . '/../../README.md'),
        ]);
    }
}
