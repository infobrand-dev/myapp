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
- [x] opening stock helper yang redirect atau terhubung ke workflow `inventory`
- [x] margin preview dari `cost_price` vs `sell_price`
- [x] default supplier per product
- [x] riwayat harga beli dan harga jual
- [x] minimum stock dan reorder point

### Sales
- [x] header-level discount
- [x] header-level tax
- [x] due date / tempo untuk piutang
- [x] attachment dokumen transaksi
- [x] note internal vs note customer
- [x] status piutang yang lebih jelas di detail sale

### Payments
- [x] edit allocation setelah payment dibuat
- [x] overpayment dan underpayment handling
- [x] upload bukti bayar
- [x] reconciliation status
- [x] branch-aware payment posting yang lebih jelas

### Finance
- [x] transfer antar account
- [x] running balance per account
- [x] cashbook view yang lebih eksplisit
- [x] attachment bukti transaksi
- [x] opening balance account

## Prioritas 2
### Purchases
- [x] expected receive date
- [x] UX partial receiving yang lebih mudah
- [x] biaya tambahan pembelian / landed cost
- [x] status hutang supplier yang lebih eksplisit
- [x] supplier bill tracking

### Contacts
- [x] payment term
- [x] credit limit
- [x] contact person
- [x] billing vs shipping address
- [x] segment atau tag customer/supplier

### Reports
- [x] laporan laba rugi sederhana
- [x] arus kas
- [x] aging piutang
- [x] aging hutang
- [x] margin per product
- [x] sales by customer
- [x] purchase by supplier

## Prioritas 3
- [x] auto journal dari sales, purchases, payments, refunds
- [x] period closing / lock transaksi
- [x] approval flow untuk void atau edit transaksi sensitif
- [x] import bulk untuk products, contacts, opening balance
- [x] audit trail yang lebih kaya untuk perubahan harga dan perubahan status

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
2. [x] `sales`: due date, header discount/tax, attachment
3. [x] `payments`: proof of payment, better allocation management
4. [x] `finance`: transfer account, running balance, opening balance
5. [x] `purchases`: landed cost, expected receive date, supplier bill tracking
6. [x] `reports`: arus kas, aging, laba rugi sederhana
