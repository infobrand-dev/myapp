<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\File;

class PosScreenController extends Controller
{
    public function index(): View
    {
        return view('pos::index', [
            'boundaries' => [
                'Products tetap menjadi master produk, barcode, SKU, varian, dan pricing dasar.',
                'Contacts tetap menjadi master customer; POS hanya memilih customer atau walk-in.',
                'Discounts tetap mengevaluasi promo; POS hanya mengirim cart context dan memakai hasil valid.',
                'Payments tetap membuat payment posted dan allocation; POS hanya mengorkestrasi checkout.',
                'Sales tetap menyimpan draft/final sale dengan source `pos`; POS bukan source of truth transaksi final.',
                'Inventory tetap memiliki stock movement dan stock balance; POS hanya boleh memanggil read/check hook bila dibutuhkan.',
            ],
            'featureBlocks' => [
                [
                    'title' => 'POS Screen',
                    'items' => [
                        'Search cepat + quick product grid',
                        'Barcode input yang fokus terus',
                        'Cart panel terpisah dan sticky summary',
                        'Hold, clear, checkout, dan print receipt',
                    ],
                ],
                [
                    'title' => 'Checkout Flow',
                    'items' => [
                        'Cart aktif per kasir/per outlet',
                        'Apply discount via Discounts module',
                        'Checkout via Payments module',
                        'Finalize sale ke Sales module dengan source `pos`',
                    ],
                ],
            ],
        ]);
    }

    public function architecture(): View
    {
        return view('pos::architecture', [
            'blueprint' => File::get(__DIR__ . '/../../README.md'),
        ]);
    }
}
