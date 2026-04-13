<?php

namespace App\Modules\Inventory\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Inventory\Actions\CreateOpeningStockAction;
use App\Modules\Inventory\Http\Requests\ImportOpeningStockRequest;
use App\Modules\Inventory\Http\Requests\StoreOpeningStockRequest;
use App\Modules\Inventory\Models\StockOpening;
use App\Modules\Inventory\Repositories\StockRepository;
use App\Modules\Products\Models\Product;
use App\Modules\Products\Models\ProductVariant;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SimpleSpreadsheet;
use App\Support\TenantContext;
use Illuminate\Http\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OpeningStockController extends Controller
{

    public function index(): View
    {
        return view('inventory::openings.index', [
            'openings' => StockOpening::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with(['location', 'creator'])
                ->tap(fn ($query) => BranchContext::applyScope($query))
                ->latest()
                ->paginate(15),
        ]);
    }

    public function create(StockRepository $stocks): View
    {
        $prefillProduct = request()->integer('product_id')
            ? Product::query()->where('tenant_id', TenantContext::currentId())->find(request()->integer('product_id'))
            : null;

        return view('inventory::openings.create', [
            'locations' => $stocks->locations(),
            'products' => Product::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('track_stock', true)
                ->orderBy('name')
                ->get(),
            'initialItems' => $prefillProduct ? [[
                'product_id' => $prefillProduct->id,
                'product_variant_id' => null,
                'quantity' => '0.0000',
                'minimum_quantity' => number_format((float) $prefillProduct->minimum_stock, 4, '.', ''),
                'reorder_quantity' => number_format((float) $prefillProduct->reorder_point, 4, '.', ''),
                'notes' => 'Opening stock helper dari halaman product.',
            ]] : [],
        ]);
    }

    public function store(StoreOpeningStockRequest $request, CreateOpeningStockAction $action): RedirectResponse
    {
        $opening = $action->execute($request->validated(), $request->user());

        return redirect()->route('inventory.openings.index')->with('status', "Stok awal {$opening->code} diposting.");
    }

    public function importPage(StockRepository $stocks): View
    {
        return view('inventory::openings.import', [
            'locations' => $stocks->locations(),
        ]);
    }

    public function downloadTemplate(string $format): Response
    {
        $rows = [
            ['sku', 'variant_sku', 'product_name', 'quantity', 'minimum_quantity', 'reorder_quantity', 'notes'],
            ['SKU-KOPI-001', '', 'Kopi Arabica', '25', '5', '10', 'Stok awal gudang utama'],
        ];

        if ($format === 'csv') {
            return response(SimpleSpreadsheet::buildTemplate($rows, 'csv'), 200, [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="opening-stock-import-template.csv"',
            ]);
        }

        if ($format === 'xlsx') {
            $binary = SimpleSpreadsheet::buildTemplate($rows, 'xlsx');

            return response($binary, 200, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="opening-stock-import-template.xlsx"',
                'Content-Length' => (string) strlen($binary),
            ]);
        }

        abort(404);
    }

    public function import(ImportOpeningStockRequest $request, CreateOpeningStockAction $action): RedirectResponse
    {
        $data = $request->validated();
        $file = $data['import_file'];
        $rows = SimpleSpreadsheet::parseUploadedFile($file->getRealPath(), (string) $file->getClientOriginalExtension());

        if (count($rows) < 2) {
            throw ValidationException::withMessages([
                'import_file' => 'File import harus berisi header dan minimal satu baris data.',
            ]);
        }

        $headerMap = $this->resolveImportHeaders($rows[0]);
        $items = [];
        $skipped = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            if ($this->rowIsEmpty($row)) {
                continue;
            }

            $rowNumber = $index + 2;
            $payload = $this->mapImportedRow($row, $headerMap);

            try {
                $product = $this->resolveImportedProduct($payload);
                if (!$product) {
                    throw new \RuntimeException('Produk tidak ditemukan dari SKU atau nama.');
                }

                $variantId = $this->resolveImportedVariantId($payload, $product->id);
                $quantity = (float) ($payload['quantity'] ?? 0);

                if ($quantity <= 0) {
                    throw new \RuntimeException('Quantity harus lebih besar dari nol.');
                }

                $items[] = [
                    'product_id' => $product->id,
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'minimum_quantity' => (float) ($payload['minimum_quantity'] ?? $product->minimum_stock ?? 0),
                    'reorder_quantity' => (float) ($payload['reorder_quantity'] ?? $product->reorder_point ?? 0),
                    'notes' => $payload['notes'] ?? null,
                ];
            } catch (\Throwable $e) {
                $skipped[] = "Baris {$rowNumber}: {$e->getMessage()}";
            }
        }

        if (empty($items)) {
            throw ValidationException::withMessages([
                'import_file' => 'Tidak ada item opening stock yang valid untuk diposting.',
            ]);
        }

        $opening = $action->execute([
            'inventory_location_id' => $data['inventory_location_id'],
            'opening_date' => $data['opening_date'],
            'notes' => $data['notes'] ?? 'Import opening stock',
            'items' => $items,
        ], $request->user());

        return redirect()
            ->route('inventory.openings.index')
            ->with('status', "Import opening stock {$opening->code} selesai. " . count($items) . ' item diposting.')
            ->with('import_skipped', $skipped);
    }

    private function resolveImportHeaders(array $headers): array
    {
        $aliases = [
            'sku' => 'sku',
            'variantsku' => 'variant_sku',
            'productname' => 'product_name',
            'nama' => 'product_name',
            'quantity' => 'quantity',
            'qty' => 'quantity',
            'minimumquantity' => 'minimum_quantity',
            'minimumstock' => 'minimum_quantity',
            'reorderquantity' => 'reorder_quantity',
            'reorderpoint' => 'reorder_quantity',
            'notes' => 'notes',
        ];

        return collect($headers)
            ->map(fn ($header) => $aliases[(string) preg_replace('/[^a-z0-9]+/', '', strtolower(trim((string) $header)))] ?? null)
            ->all();
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

    private function resolveImportedProduct(array $payload): ?Product
    {
        return Product::query()
            ->where('tenant_id', TenantContext::currentId())
            ->when(!empty($payload['sku']), fn ($query) => $query->where('sku', $payload['sku']), fn ($query) => $query->where('name', $payload['product_name'] ?? ''))
            ->first();
    }

    private function resolveImportedVariantId(array $payload, int $productId): ?int
    {
        if (empty($payload['variant_sku'])) {
            return null;
        }

        $variantId = ProductVariant::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('product_id', $productId)
            ->where('sku', $payload['variant_sku'])
            ->value('id');

        if ($variantId === null) {
            throw new \RuntimeException('Variant SKU tidak cocok dengan produk yang dipilih.');
        }

        return $variantId;
    }

    private function rowIsEmpty(array $row): bool
    {
        return collect($row)->every(fn ($value) => trim((string) $value) === '');
    }
}
