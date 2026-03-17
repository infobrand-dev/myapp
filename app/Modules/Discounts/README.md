# Discounts Module

## Boundary
- `Products` tetap menyimpan master produk, base/default price, category, brand, unit, variant, tax default, dan atribut produk lain.
- `Discounts` menjadi source of truth untuk promo, voucher, campaign discount, schedule, condition, target, stacking, dan rule evaluation.
- `Inventory` tetap sumber kebenaran stok.
- `PointOfSale` atau `Checkout` hanya meminta evaluasi discount ke module ini lalu menyimpan snapshot hasil final transaksi.
- `Sales` menyimpan hasil akhir transaksi yang sudah terdampak discount, bukan definisi rule discount.

## Dependency
- Required: `products`
- Implicit framework dependency: `users`
- Optional runtime reference tanpa FK keras: `contacts`, `point-of-sale`, `sales`, `outlets`, `membership`, `customer-groups`

## Produk Cleanup Checklist
- Jangan tambah field `discount_*`, `promo_*`, `voucher_*`, `campaign_*`, `flash_sale_*` ke tabel `products` atau `product_variants`.
- Jangan buat halaman CRUD promo di module `Products`.
- `wholesale_price` dan `member_price` diperlakukan sebagai base pricing tier, bukan promo sementara.
- Jika butuh badge "discount active" di UI produk, badge itu hanya indikator hasil query ke `Discounts`, bukan data yang disimpan di `Products`.

## Database Structure
- `discounts`: master discount, priority, scope, status, schedule, rule payload, stacking, usage limits.
- `discount_targets`: target product, variant, category, brand, customer, outlet, channel.
- `discount_conditions`: syarat minimum qty, subtotal, date range, day/time range, specific customer/product.
- `discount_vouchers`: optional promo codes.
- `discount_usages`: audit trail penerapan discount pada transaksi/reference tertentu.
- `discount_usage_lines`: snapshot line-level hasil discount.

## Main Flow
1. Create/Edit discount: simpan master, targets, conditions, vouchers.
2. Evaluate discount: POS/Checkout mengirim cart context ke endpoint/service `EvaluateDiscountsAction`.
3. Rule engine memfilter active discounts, cek voucher, target, condition, limit, lalu hitung hasil berdasarkan priority.
4. Caller menyimpan transaksi final dan memanggil `RecordDiscountUsageAction` untuk audit snapshot.

## Extensibility
- Voucher/coupon sudah opsional dan terpisah.
- Customer-specific discount memakai `customer_reference_type` dan `customer_reference_id` agar tidak mengunci ke module tertentu.
- Outlet/channel targeting disimpan sebagai reference/code agar aman saat module optional belum terpasang.
- `rule_payload` dipakai untuk menambah bentuk rule baru tanpa migrasi besar setiap kali ada skenario baru.
