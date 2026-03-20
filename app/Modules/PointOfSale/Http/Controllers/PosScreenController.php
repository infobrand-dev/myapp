<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Contacts\Models\Contact;
use App\Modules\Payments\Models\PaymentMethod;
use App\Modules\PointOfSale\Models\PosCart;
use App\Modules\PointOfSale\Services\PosCashSessionService;
use App\Modules\Products\Models\Product;
use App\Support\TenantContext;
use Illuminate\Contracts\View\View;

class PosScreenController extends Controller
{
    private $cashSessions;

    public function __construct(PosCashSessionService $cashSessions)
    {
        $this->cashSessions = $cashSessions;
    }

    public function index(): View
    {
        $activeShift = $this->cashSessions->activeSessionFor(auth()->user());

        return view('pos::index', [
            'initialProducts' => Product::query()
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(18)
                ->get(['id', 'name', 'sku', 'barcode', 'sell_price']),
            'initialCustomers' => Contact::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('is_active', true)
                ->orderBy('name')
                ->limit(12)
                ->get(['id', 'name', 'phone', 'mobile', 'email']),
            'paymentMethods' => PaymentMethod::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->get(['id', 'code', 'name', 'type', 'requires_reference']),
            'heldCount' => PosCart::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('cashier_user_id', auth()->id())
                ->where('status', PosCart::STATUS_HELD)
                ->count(),
            'activeShift' => $activeShift,
        ]);
    }
}
