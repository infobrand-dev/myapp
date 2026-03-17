<?php

namespace App\Modules\Discounts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Discounts\Repositories\DiscountRepository;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscountUsageController extends Controller
{
    public function __construct(
        private readonly DiscountRepository $repository,
    ) {
    }

    public function index(Request $request): View
    {
        return view('discounts::usages.index', [
            'usages' => $this->repository->paginateUsages($request->only(['discount_id', 'usage_status'])),
            'filters' => $request->only(['discount_id', 'usage_status']),
        ]);
    }
}
