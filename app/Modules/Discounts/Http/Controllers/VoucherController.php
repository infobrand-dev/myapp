<?php

namespace App\Modules\Discounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discounts\Repositories\DiscountRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VoucherController extends Controller
{
    public function __construct(
        private readonly DiscountRepository $repository,
    ) {
    }

    public function index(Request $request): View
    {
        abort_unless($request->user()?->can('discounts.manage-vouchers'), 403);

        return view('discounts::vouchers.index', [
            'vouchers' => $this->repository->paginateVouchers($request->only('search')),
        ]);
    }
}
