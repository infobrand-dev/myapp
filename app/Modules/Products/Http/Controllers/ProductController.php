<?php

namespace App\Modules\Products\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Products\Http\Requests\BulkProductActionRequest;
use App\Modules\Products\Http\Requests\ImportProductRequest;
use App\Modules\Products\Http\Requests\UpsertProductRequest;
use App\Modules\Products\Models\ProductBrand;
use App\Modules\Products\Models\ProductCategory;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductUnit;
use App\Modules\Products\Repositories\ProductRepository;
use App\Modules\Products\Services\ProductLookupService;
use App\Modules\Products\Services\ProductService;
use App\Support\PlanLimit;
use App\Support\SimpleSpreadsheet;
use App\Support\TenantContext;
use App\Support\TenantPlanManager;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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

        return redirect()->route('products.show', $product)->with('status', 'Produk ditambahkan.');
    }

    public function show(Product $product): View
    {
        return view('products::show', [
            'product' => $this->repository->findForDetail($product)->loadMissing(['creator', 'updater', 'priceHistories.changer', 'priceHistories.variant']),
            'activities' => $product->activities()->with('causer')->latest()->get(),
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

        return redirect()->route('products.show', $product)->with('status', 'Produk diperbarui.');
    }

    public function destroy(Product $product, Request $request): RedirectResponse
    {
        $this->productService->delete($product, $request->user());

        return redirect()
            ->route('products.index')
            ->with('status', 'Produk dihapus.');
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

        return back()->with('status', 'Aksi massal dijalankan.');
    }

    public function importPage(): View
    {
        return view('products::import');
    }

    public function downloadTemplate(string $format): Response
    {
        $rows = [
            $this->importHeaders(),
            ['simple', 'Kopi Arabica', 'SKU-KOPI-001', '899001', 'Minuman', 'Arabica', 'Pcs', 'PT Supplier Kopi', '18000', '25000', '5', '10', '1', '1', 'Produk contoh import'],
        ];

        if ($format === 'csv') {
            return response(SimpleSpreadsheet::buildTemplate($rows, 'csv'), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="products-import-template.csv"',
            ]);
        }

        if ($format === 'xlsx') {
            $binary = SimpleSpreadsheet::buildTemplate($rows, 'xlsx');

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="products-import-template.xlsx"',
                'Content-Length' => (string) strlen($binary),
            ]);
        }

        abort(404);
    }

    public function import(ImportProductRequest $request): RedirectResponse
    {
        $file = $request->validated()['import_file'];
        $rows = SimpleSpreadsheet::parseUploadedFile($file->getRealPath(), (string) $file->getClientOriginalExtension());

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'import_file' => 'File import harus berisi header dan minimal satu baris data.',
            ]);
        }

        $headerMap = $this->resolveImportHeaders($rows[0]);
        if (!in_array('name', $headerMap, true)) {
            throw ValidationException::withMessages([
                'import_file' => 'Header wajib mengandung kolom name.',
            ]);
        }

        $increment = $this->estimateImportIncrement(array_slice($rows, 1), $headerMap);
        if ($increment > 0) {
            app(TenantPlanManager::class)->ensureWithinLimit(PlanLimit::PRODUCTS, $increment);
        }

        $result = DB::transaction(function () use ($rows, $headerMap, $request) {
            $created = 0;
            $updated = 0;
            $skipped = [];

            foreach (array_slice($rows, 1) as $index => $row) {
                if ($this->rowIsEmpty($row)) {
                    continue;
                }

                $rowNumber = $index + 2;
                $payload = $this->normalizeImportedRow($this->mapImportedRow($row, $headerMap));

                if ($payload['name'] === '') {
                    $skipped[] = "Baris {$rowNumber}: nama wajib diisi.";
                    continue;
                }

                try {
                    $product = Product::query()
                        ->where('tenant_id', TenantContext::currentId())
                        ->when($payload['sku'] !== null, fn ($query) => $query->where('sku', $payload['sku']), fn ($query) => $query->where('name', $payload['name']))
                        ->first();

                    if ($product) {
                        $this->productService->update($product, $payload, $request->user());
                        $updated++;
                    } else {
                        $this->productService->create($payload, $request->user());
                        $created++;
                    }
                } catch (\Throwable $e) {
                    $skipped[] = "Baris {$rowNumber}: {$e->getMessage()}";
                }
            }

            return compact('created', 'updated', 'skipped');
        });

        $message = "Import products selesai. {$result['created']} dibuat, {$result['updated']} diperbarui.";
        if (!empty($result['skipped'])) {
            $message .= ' ' . count($result['skipped']) . ' baris dilewati.';
        }

        return redirect()
            ->route('products.import-page')
            ->with('status', $message)
            ->with('import_skipped', $result['skipped']);
    }

    private function formViewData(Product $product): array
    {
        return [
            'product' => $product,
            'categories' => $this->lookupService->categories(),
            'brands' => $this->lookupService->brands(),
            'units' => $this->lookupService->units(),
            'suppliers' => $this->lookupService->suppliers(),
            'priceLevels' => $this->lookupService->priceLevels(),
            'dependencies' => $this->lookupService->dependencyMap(),
        ];
    }

    private function importHeaders(): array
    {
        return [
            'type',
            'name',
            'sku',
            'barcode',
            'category',
            'brand',
            'unit',
            'supplier',
            'cost_price',
            'sell_price',
            'minimum_stock',
            'reorder_point',
            'is_active',
            'track_stock',
            'description',
        ];
    }

    private function resolveImportHeaders(array $headers): array
    {
        $aliases = [
            'type' => 'type',
            'name' => 'name',
            'nama' => 'name',
            'sku' => 'sku',
            'barcode' => 'barcode',
            'category' => 'category',
            'kategori' => 'category',
            'brand' => 'brand',
            'unit' => 'unit',
            'satuan' => 'unit',
            'supplier' => 'supplier',
            'costprice' => 'cost_price',
            'buyprice' => 'cost_price',
            'sellprice' => 'sell_price',
            'saleprice' => 'sell_price',
            'minimumstock' => 'minimum_stock',
            'minstock' => 'minimum_stock',
            'reorderpoint' => 'reorder_point',
            'isactive' => 'is_active',
            'activestatus' => 'is_active',
            'trackstock' => 'track_stock',
            'description' => 'description',
            'deskripsi' => 'description',
        ];

        return collect($headers)
            ->map(fn ($header) => $aliases[$this->normalizeHeader((string) $header)] ?? null)
            ->all();
    }

    private function normalizeHeader(string $header): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim($header)));
    }

    private function mapImportedRow(array $row, array $headerMap): array
    {
        $payload = [];
        foreach ($row as $index => $value) {
            $field = $headerMap[$index] ?? null;
            if ($field !== null) {
                $payload[$field] = is_string($value) ? trim($value) : $value;
            }
        }

        return $payload;
    }

    private function normalizeImportedRow(array $payload): array
    {
        $categoryName = $this->nullableString($payload['category'] ?? null);
        $brandName = $this->nullableString($payload['brand'] ?? null);
        $unitName = $this->nullableString($payload['unit'] ?? null);
        $supplierName = $this->nullableString($payload['supplier'] ?? null);

        return [
            'type' => in_array(($payload['type'] ?? 'simple'), ['simple', 'variant', 'service'], true) ? $payload['type'] : 'simple',
            'name' => trim((string) ($payload['name'] ?? '')),
            'sku' => $this->nullableString($payload['sku'] ?? null),
            'barcode' => $this->nullableString($payload['barcode'] ?? null),
            'new_category_name' => $categoryName,
            'new_brand_name' => $brandName,
            'new_unit_name' => $unitName,
            'default_supplier_contact_id' => $this->resolveSupplierId($supplierName),
            'cost_price' => (float) ($payload['cost_price'] ?? 0),
            'sell_price' => (float) ($payload['sell_price'] ?? 0),
            'minimum_stock' => (float) ($payload['minimum_stock'] ?? 0),
            'reorder_point' => (float) ($payload['reorder_point'] ?? 0),
            'is_active' => $this->normalizeBoolean($payload['is_active'] ?? '1'),
            'track_stock' => $this->normalizeBoolean($payload['track_stock'] ?? '1'),
            'description' => $this->nullableString($payload['description'] ?? null),
        ];
    }

    private function estimateImportIncrement(array $rows, array $headerMap): int
    {
        $increment = 0;

        foreach ($rows as $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $payload = $this->normalizeImportedRow($this->mapImportedRow($row, $headerMap));
            if ($payload['name'] === '') {
                continue;
            }

            $exists = Product::query()
                ->where('tenant_id', TenantContext::currentId())
                ->when($payload['sku'] !== null, fn ($query) => $query->where('sku', $payload['sku']), fn ($query) => $query->where('name', $payload['name']))
                ->exists();

            if (!$exists) {
                $increment++;
            }
        }

        return $increment;
    }

    private function resolveSupplierId(?string $supplierName): ?int
    {
        if (!$supplierName) {
            return null;
        }

        return \App\Modules\Contacts\Models\Contact::query()
            ->where('tenant_id', TenantContext::currentId())
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($supplierName)])
            ->value('id');
    }

    private function normalizeBoolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y', 'aktif', 'active'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
