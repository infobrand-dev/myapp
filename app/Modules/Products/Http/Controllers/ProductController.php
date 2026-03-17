<?php

namespace App\Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Http\Requests\BulkProductActionRequest;
use App\Modules\Products\Http\Requests\UpsertProductRequest;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ProductLookupService;
use App\Modules\Products\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    private ProductRepository $repository;

    private ProductLookupService $lookupService;

    private ProductService $productService;

    public function __construct(
        ProductRepository $repository,
        ProductLookupService $lookupService,
        ProductService $productService
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->productService = $productService;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'status', 'category_id', 'brand_id']);

        return view('products::index', [
            'products' => $this->repository->paginateForIndex($filters, 15),
            'filters' => $filters,
            'categories' => $this->lookupService->categories(),
            'brands' => $this->lookupService->brands(),
            'dependencies' => $this->lookupService->dependencyMap(),
        ]);
    }

    public function create(): View
    {
        return view('products::create', $this->formViewData(new Product([
            'type' => 'simple',
            'is_active' => true,
            'track_stock' => true,
        ])));
    }

    public function store(UpsertProductRequest $request): RedirectResponse
    {
        $product = $this->productService->create($request->validated(), $request->user());

        return redirect()->route('products.show', $product)->with('status', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        return view('products::show', [
            'product' => $this->repository->findForDetail($product),
            'dependencies' => $this->lookupService->dependencyMap(),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products::edit', $this->formViewData($this->repository->findForEdit($product)));
    }

    public function update(UpsertProductRequest $request, Product $product): RedirectResponse
    {
        $product = $this->productService->update($product, $request->validated(), $request->user());

        return redirect()->route('products.show', $product)->with('status', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product, Request $request): RedirectResponse
    {
        $this->productService->delete($product, $request->user());

        return redirect()
            ->route('products.index')
            ->with('status', 'Produk di-soft delete. Permanent delete sengaja tidak diekspos dari UI.');
    }

    public function toggleStatus(Product $product): RedirectResponse
    {
        $product = $this->productService->toggleStatus($product);

        return back()->with('status', "Status produk '{$product->name}' diperbarui.");
    }

    public function bulkAction(BulkProductActionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $this->productService->bulkAction($data['product_ids'], $data['action'], $request->user());

        return back()->with('status', 'Bulk action berhasil dijalankan.');
    }

    private function formViewData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => $this->lookupService->categories(),
            'brands' => $this->lookupService->brands(),
            'units' => $this->lookupService->units(),
            'priceLevels' => $this->lookupService->priceLevels(),
            'dependencies' => $this->lookupService->dependencyMap(),
        ];
    }
}
