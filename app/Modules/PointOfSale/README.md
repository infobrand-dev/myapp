# PointOfSale Blueprint

## 1. Struktur fitur module `PointOfSale`

```text
app/Modules/PointOfSale
|- module.json
|- PointOfSaleServiceProvider.php
|- routes/
|  |- web.php
|- Http/
|  |- Controllers/
|  |  |- PosScreenController.php
|  |  |- BarcodeScanController.php
|  |  |- PosCartController.php
|  |  |- HeldCartController.php
|  |  |- CheckoutController.php
|  |  |- ReceiptController.php
|  |- Requests/
|     |- ScanBarcodeRequest.php
|     |- StoreCartItemRequest.php
|     |- UpdateCartRequest.php
|     |- HoldCartRequest.php
|     |- CheckoutPosRequest.php
|- Actions/
|  |- BuildPosWorkspaceAction.php
|  |- ResolveBarcodeToSellableAction.php
|  |- AddCartItemAction.php
|  |- UpdateCartItemAction.php
|  |- ApplyDiscountsToCartAction.php
|  |- HoldActiveCartAction.php
|  |- ResumeHeldCartAction.php
|  |- CheckoutPosCartAction.php
|  |- BuildReceiptViewAction.php
|- Services/
|  |- PosCartService.php
|  |- PosCartStorage.php
|  |- PosCheckoutOrchestrator.php
|  |- PosReceiptService.php
|  |- PosIntegrationGate.php
|- DTO/
|  |- PosCartData.php
|  |- PosCartItemData.php
|  |- PosCheckoutData.php
|  |- PosPaymentData.php
|- resources/views/
|  |- index.blade.php
|  |- architecture.blade.php
|  |- partials/
|     |- barcode-input.blade.php
|     |- product-grid.blade.php
|     |- cart-panel.blade.php
|     |- held-carts.blade.php
|     |- checkout-modal.blade.php
|     |- receipt.blade.php
```

Prinsipnya:
- `PointOfSale` menyimpan state cart dan orchestration checkout.
- `Sales`, `Payments`, `Discounts`, `Products`, `Contacts`, `Inventory` tetap menjadi domain owner masing-masing.
- Controller tipis, logic utama di `Actions` dan `Services`.

## 2. Boundary penting dengan module lain

### Dengan `Products`
- POS hanya membaca produk aktif, barcode, SKU, nama, gambar, varian, dan harga jual yang bisa dipakai kasir.
- POS tidak membuat, mengubah, atau menyimpan master produk.
- POS boleh punya endpoint pencarian produk cepat, tetapi sumber datanya tetap `Products`.

### Dengan `Contacts`
- POS hanya memilih customer dari `Contacts` atau memakai mode walk-in.
- POS tidak menyimpan master customer baru di checkout flow cepat kecuali nanti ada fitur explicit handoff ke `Contacts`.
- Snapshot customer final tetap ikut alur `Sales`.

### Dengan `Discounts`
- POS mengirim payload cart ke `Discounts` untuk evaluasi.
- POS tidak menyimpan rule promo, formula, eligibility, quota, atau voucher engine.
- POS hanya menyimpan hasil evaluasi sementara di cart dan meneruskan usage final setelah sale sukses bila dibutuhkan.

### Dengan `Payments`
- POS wajib checkout melalui action/service di module `Payments`.
- POS tidak menyimpan payment gateway config, settlement rule, atau ledger payment.
- Cash, non-cash, split payment, reference number, dan allocation tetap domain `Payments`.

### Dengan `Sales`
- Hasil akhir checkout harus masuk ke `Sales`.
- `Sales` adalah source of truth untuk invoice number, sale number, snapshot item/customer, total final, dan status final.
- POS hanya punya cart/held cart dan flow menuju pembuatan/finalisasi sale.
- `source` atau `channel` transaksi sale harus selalu `pos`.

### Dengan `Inventory`
- POS tidak memiliki stock balance atau stock movement sendiri.
- Bila stok perlu dicek, POS hanya memanggil read model atau hook dari `Inventory`.
- Pengurangan stok final seharusnya terjadi dari hook finalisasi sale, bukan langsung dari UI POS.

## 3. Entity / cart data yang diperlukan

Minimal entity internal POS:

### `pos_cart`
- `uuid`
- `status`: `active`, `held`, `checking_out`, `completed`, `cancelled`
- `cashier_user_id`
- `outlet_id` nullable
- `register_id` nullable
- `contact_id` nullable
- `customer_label` nullable, default `Walk-in Customer`
- `currency_code`
- `notes`
- `item_count`
- `subtotal`
- `item_discount_total`
- `order_discount_total`
- `tax_total`
- `grand_total`
- `discount_snapshot` json
- `meta` json
- `held_at` nullable
- `completed_sale_id` nullable

### `pos_cart_items`
- `uuid`
- `pos_cart_id`
- `line_no`
- `product_id`
- `product_variant_id` nullable
- `barcode_scanned` nullable
- `sku_snapshot`
- `barcode_snapshot`
- `product_name_snapshot`
- `variant_name_snapshot` nullable
- `unit_name_snapshot` nullable
- `qty`
- `unit_price`
- `manual_price_override` boolean
- `discount_total`
- `tax_total`
- `line_total`
- `notes` nullable
- `meta` json

### `pos_cart_payments` sementara saat checkout
- `payment_method_id`
- `amount`
- `reference_number` nullable
- `type_snapshot`
- `meta`

Catatan:
- Cart data boleh disimpan di database agar hold/resume lintas tab dan stabil untuk kasir.
- Jangan jadikan tabel POS sebagai transaksi final permanen. Relasi final cukup ke `sale_id`.

## 4. Flow barcode scan

Flow yang direkomendasikan:

1. Fokus default ada di field barcode tersembunyi atau input utama.
2. USB scanner bertindak sebagai keyboard dan mengirim barcode lalu `Enter`.
3. Alpine menangkap `keydown.enter` atau submit event.
4. Request dikirim ke endpoint ringan `POST /pos/barcode/scan`.
5. `ResolveBarcodeToSellableAction` mencari urutan:
   - exact match `product_variants.barcode`
   - exact match `products.barcode`
   - fallback exact match `sku` bila diizinkan
6. Jika produk ditemukan:
   - tentukan sellable snapshot
   - tambahkan qty `+1` ke cart aktif bila line sudah ada
   - jika belum ada, buat line baru
   - recalc subtotal dan total cart
   - reset input barcode dan kembalikan fokus
7. Jika tidak ditemukan:
   - tampilkan toast ringan
   - bunyikan feedback error opsional
   - tetap fokus ke input barcode

Kunci UX:
- Debounce jangan terlalu agresif pada barcode input.
- Gunakan exact match, bukan pencarian fuzzy, untuk hasil scan.
- Response JSON harus kecil supaya round-trip cepat.

## 5. Flow hold / resume cart

### Hold
1. Kasir klik `Hold Cart`.
2. Validasi cart tidak kosong.
3. `HoldActiveCartAction` mengubah status cart menjadi `held`.
4. Simpan label hold opsional, misalnya nomor meja atau nama customer.
5. Buat cart aktif baru kosong untuk kasir yang sama.

### Resume
1. Kasir buka panel held carts.
2. Ambil daftar cart `held` milik outlet/register sesuai scope akses.
3. Klik salah satu held cart.
4. Jika kasir sudah punya cart aktif kosong, cart aktif itu bisa dipakai ulang.
5. `ResumeHeldCartAction` memindahkan cart held menjadi `active`.
6. Pastikan hanya satu cart aktif per kasir/register untuk menghindari konflik.

Aturan penting:
- Hold cart tetap milik POS.
- Hold cart belum membuat sale final.
- Tidak ada movement stok final saat hold.

## 6. Flow checkout ke `Payments` dan finalisasi ke `Sales`

Urutan yang aman:

1. POS validasi cart aktif.
2. POS panggil `Discounts` evaluate agar total cart final valid.
3. POS membuat atau meng-update draft sale di `Sales` dengan:
   - `source = pos`
   - `external_reference = pos_cart.uuid`
   - item snapshot dari cart
   - customer dari `contact_id` atau guest
4. POS siapkan payload payment ke `Payments`:
   - cash, non-cash, atau split rows
   - allocation diarahkan ke sale draft tersebut
   - `source = pos`
   - `channel = pos`
5. `Payments` membuat payment posted + allocations.
6. POS minta `Sales` finalize draft sale.
7. `Sales` menjadi transaksi final.
8. Jika sukses:
   - tandai `pos_cart` completed
   - simpan `completed_sale_id`
   - tampilkan receipt preview
   - buka print view
9. Jika payment sukses tetapi finalize sale gagal:
   - pakai transaction boundary atau compensating action yang jelas
   - idealnya orchestrator membungkus create payment + finalize sale dalam flow yang idempotent

Pilihan implementasi:
- Paling cocok: `PosCheckoutOrchestrator` di POS yang memanggil `CreateDraftSaleAction`, `CreatePaymentAction`, lalu `FinalizeSaleAction`.
- Jangan simpan total final terpisah di POS sebagai sumber utama.

## 7. UI structure POS screen

Layout desktop:

### Header bar
- outlet/register info
- cashier name
- current clock
- held cart counter
- shortcut help

### Left pane 65-70%
- barcode input besar dan selalu fokus
- search input cepat
- filter kategori atau quick tabs
- quick product grid
- recent scanned items atau suggested items

### Right pane 30-35%
- customer picker
- active cart line items
- item note editor
- transaction note
- discount section
- sticky summary
- payment buttons
- action bar: `Hold`, `Clear`, `Checkout`, `Print`

### Mobile / tablet
- cart panel jadi drawer bawah atau tab kedua
- barcode/search tetap ada di atas
- summary dan checkout sticky di bawah layar

Shortcut yang direkomendasikan:
- `F3` fokus barcode
- `F4` fokus search
- `F8` hold cart
- `F9` checkout
- `Esc` clear focus modal

## 8. Routes

Route web yang disarankan:

```php
Route::middleware(['web', 'auth'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/', PosScreenController::class)->middleware('permission:pos.use')->name('index');
    Route::get('/workspace', [PosWorkspaceController::class, 'show'])->middleware('permission:pos.use')->name('workspace');

    Route::post('/cart', [PosCartController::class, 'store'])->middleware('permission:pos.use')->name('cart.store');
    Route::get('/cart/active', [PosCartController::class, 'active'])->middleware('permission:pos.use')->name('cart.active');
    Route::patch('/cart/active', [PosCartController::class, 'update'])->middleware('permission:pos.use')->name('cart.update');
    Route::delete('/cart/active', [PosCartController::class, 'clear'])->middleware('permission:pos.use')->name('cart.clear');

    Route::post('/cart/items', [PosCartItemController::class, 'store'])->middleware('permission:pos.use')->name('cart.items.store');
    Route::patch('/cart/items/{item}', [PosCartItemController::class, 'update'])->middleware('permission:pos.use')->name('cart.items.update');
    Route::delete('/cart/items/{item}', [PosCartItemController::class, 'destroy'])->middleware('permission:pos.use')->name('cart.items.destroy');

    Route::post('/barcode/scan', [BarcodeScanController::class, 'store'])->middleware('permission:pos.use')->name('barcode.scan');

    Route::get('/held-carts', [HeldCartController::class, 'index'])->middleware('permission:pos.resume-cart')->name('held.index');
    Route::post('/held-carts', [HeldCartController::class, 'store'])->middleware('permission:pos.hold-cart')->name('held.store');
    Route::post('/held-carts/{cart}/resume', [HeldCartController::class, 'resume'])->middleware('permission:pos.resume-cart')->name('held.resume');

    Route::post('/discounts/evaluate', [PosDiscountController::class, 'evaluate'])->middleware('permission:pos.use')->name('discounts.evaluate');

    Route::post('/checkout', [CheckoutController::class, 'store'])->middleware('permission:pos.checkout')->name('checkout.store');

    Route::get('/receipts/{sale}', [ReceiptController::class, 'show'])->middleware('permission:pos.print-receipt')->name('receipts.show');
    Route::get('/receipts/{sale}/print', [ReceiptController::class, 'print'])->middleware('permission:pos.print-receipt')->name('receipts.print');
    Route::post('/receipts/{sale}/reprint', [ReceiptController::class, 'reprint'])->middleware('permission:pos.reprint-receipt')->name('receipts.reprint');
});
```

## 9. Permission list

Minimal permission:
- `pos.use`
- `pos.hold-cart`
- `pos.resume-cart`
- `pos.checkout`
- `pos.print-receipt`
- `pos.reprint-receipt`
- `pos.override-price`
- `pos.override-discount`

Opsional bila outlet/register lebih kompleks:
- `pos.use-all-registers`
- `pos.resume-any-cart`
- `pos.void-pos-sale`
- `pos.refund`

## 10. Rekomendasi stack frontend ringan dan cepat

Stack utama:
- Blade untuk shell dan partial
- Alpine.js untuk state UI lokal
- `fetch` API untuk JSON actions ringan
- Tailwind yang sudah ada via Vite, atau tetap kelas Tabler untuk visual consistency
- Tanpa SPA framework berat

Alasan:
- render awal cepat
- cocok untuk keyboard-first workflow
- mudah jaga payload kecil
- mudah dipecah ke partial Blade per panel

Pola frontend yang disarankan:
- satu komponen Alpine root `posScreen()`
- state kecil dan eksplisit: `cart`, `heldCarts`, `productResults`, `selectedCustomer`, `paymentDraft`
- endpoint JSON khusus untuk scan barcode, add/update item, hold, resume, checkout
- gunakan optimistic UI ringan hanya untuk aksi aman seperti update qty
- pertahankan input barcode selalu reusable dan auto-focus

Tambahan praktis:
- gunakan `x-ref` untuk kontrol fokus barcode
- gunakan `requestAnimationFrame` atau `setTimeout(0)` untuk refocus setelah submit scan
- gunakan toast ringan, bukan modal blocking, untuk scan gagal
- gunakan partial receipt printable yang terpisah dari screen POS utama

## Rekomendasi implementasi awal

Phase 1:
- POS screen
- active cart
- barcode scan
- customer select
- hold/resume
- checkout cash dan non-cash sederhana
- receipt preview dan print

Phase 2:
- split payment
- keyboard shortcuts lengkap
- offline draft tolerance terbatas
- register/outlet awareness
- stock availability indicator dari `Inventory`
