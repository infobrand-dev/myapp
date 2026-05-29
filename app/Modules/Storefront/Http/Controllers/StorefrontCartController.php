<?php

namespace App\Modules\Storefront\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Models\Product;
use App\Modules\Storefront\Support\StorefrontCartService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StorefrontCartController extends Controller
{
    public function __construct(
        private readonly StorefrontCartService $cart,
    ) {
    }

    public function add(Request $request, Product $product): RedirectResponse
    {
        abort_unless((bool) $product->is_active, 404);

        $qty = max(1, (int) $request->input('qty', 1));
        $redirect = (string) $request->input('redirect', 'back');

        $this->cart->add($product, $qty);

        if ($redirect === 'checkout') {
            return redirect()
                ->route('storefront.public.checkout')
                ->with('status', 'Produk ditambahkan ke cart dan siap di-checkout.');
        }

        if ($redirect === 'cart') {
            return redirect()
                ->route('storefront.public.cart')
                ->with('status', 'Produk berhasil ditambahkan ke cart.');
        }

        return back()->with('status', 'Produk berhasil ditambahkan ke cart.');
    }

    public function buyNow(Request $request, Product $product): RedirectResponse
    {
        abort_unless((bool) $product->is_active, 404);

        $qty = max(1, (int) $request->input('qty', 1));
        $this->cart->replaceWith($product, $qty);

        return redirect()->route('storefront.public.checkout');
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $qty = (int) $request->input('qty', 1);
        $this->cart->update($product, $qty);

        return back()->with('status', $qty > 0
            ? 'Jumlah item di cart diperbarui.'
            : 'Produk dihapus dari cart.');
    }

    public function remove(Product $product): RedirectResponse
    {
        $this->cart->remove($product);

        return back()->with('status', 'Produk dihapus dari cart.');
    }

    public function clear(): RedirectResponse
    {
        $this->cart->clear();

        return redirect()->route('storefront.public.index')
            ->with('status', 'Cart berhasil dikosongkan.');
    }
}
