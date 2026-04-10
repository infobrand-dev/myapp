# Accounting Feature Roadmap

## Prinsip
- `standard` dan `advanced` adalah mode UI, bukan plan berbeda.
- `products` wajib ada untuk plan accounting yang memakai `sales`.
- `inventory` adalah source of truth stok; `products` hanya master data produk.
- backlog di bawah ini fokus ke fitur bisnis yang belum lengkap atau belum nyaman dipakai, bukan sekadar refactor teknis.

## Status Saat Ini
Sudah ada:
- [x] `products` untuk master produk, varian, harga bertingkat, media
- [x] `sales` untuk draft/finalize sale dan return dasar
- [x] `payments` untuk posting pembayaran dan allocation
- [x] `finance` untuk cash in, cash out, expense, account, category
- [x] `purchases` untuk draft/finalize/receiving dasar
- [x] `inventory` untuk stok, mutasi, opening, adjustment, transfer

Sudah diperbaiki:
- [x] mode UI `standard` dan `advanced`
- [x] audit log detail hanya di `advanced`
- [x] field teknis seperti `slug` tidak lagi diisi manual user
- [x] boundary `products` vs `inventory` diperjelas
- [x] tooltip edukatif reusable untuk field accounting utama
- [x] due date / tempo dasar untuk `sales` dan `purchases`
- [x] indikator receivable / payable overdue di detail dan index

## Prioritas 1
### Products
- [ ] opening stock helper yang redirect atau terhubung ke workflow `inventory`
- [x] margin preview dari `cost_price` vs `sell_price`
- [x] default supplier per product
- [ ] riwayat harga beli dan harga jual
- [x] minimum stock dan reorder point

### Sales
- [ ] header-level discount
- [ ] header-level tax
- [x] due date / tempo untuk piutang
- [ ] attachment dokumen transaksi
- [ ] note internal vs note customer
- [x] status piutang yang lebih jelas di detail sale

### Payments
- [ ] edit allocation setelah payment dibuat
- [ ] overpayment dan underpayment handling
- [x] upload bukti bayar
- [x] reconciliation status
- [ ] branch-aware payment posting yang lebih jelas

### Finance
- [ ] transfer antar account
- [ ] running balance per account
- [ ] cashbook view yang lebih eksplisit
- [ ] attachment bukti transaksi
- [ ] opening balance account

## Prioritas 2
### Purchases
- [ ] expected receive date
- [ ] UX partial receiving yang lebih mudah
- [ ] biaya tambahan pembelian / landed cost
- [x] status hutang supplier yang lebih eksplisit
- [ ] supplier bill tracking

### Contacts
- [ ] payment term
- [ ] credit limit
- [ ] contact person
- [ ] billing vs shipping address
- [ ] segment atau tag customer/supplier

### Reports
- [ ] laporan laba rugi sederhana
- [ ] arus kas
- [ ] aging piutang
- [ ] aging hutang
- [ ] margin per product
- [ ] sales by customer
- [ ] purchase by supplier

## Prioritas 3
- [ ] auto journal dari sales, purchases, payments, refunds
- [ ] period closing / lock transaksi
- [ ] approval flow untuk void atau edit transaksi sensitif
- [ ] import bulk untuk products, contacts, opening balance
- [ ] audit trail yang lebih kaya untuk perubahan harga dan perubahan status

## Dampak ke Plan / Pricing
- `Accounting Starter`: `products`, `sales`, `payments`, `finance`, `reports`, `contacts`
- `Accounting Growth`: Starter + `purchases` + `inventory`
- `Accounting Scale`: Growth + kapasitas dan reporting lebih besar
- `POS`: add-on

Implikasi:
- `products` bukan add-on untuk accounting starter, karena `sales` membutuhkannya secara implementasi
- `inventory` tidak wajib untuk starter
- fitur stok lanjutan tetap dikunci di `inventory`, bukan ditaruh di `products`

## Urutan Eksekusi yang Disarankan
1. [x] `products`: supplier default, margin preview, min stock, reorder point
2. [ ] `sales`: due date, header discount/tax, attachment
3. [ ] `payments`: proof of payment, better allocation management
4. [ ] `finance`: transfer account, running balance, opening balance
5. [ ] `purchases`: landed cost, expected receive date, supplier bill tracking
6. [ ] `reports`: arus kas, aging, laba rugi sederhana
