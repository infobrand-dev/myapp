# Accounting Feature Roadmap

## Prinsip
- `standard` dan `advanced` adalah mode UI, bukan plan berbeda.
- `products` wajib ada untuk plan accounting yang memakai `sales`.
- `inventory` adalah source of truth stok; `products` hanya master data produk.
- backlog di bawah ini fokus ke fitur bisnis yang belum lengkap atau belum nyaman dipakai, bukan sekadar refactor teknis.
- jangan membuat module baru yang terlalu generik seperti `foundation` atau `master-data`; owner module harus mengikuti domain yang benar.
- selama fase sekarang, prioritas utama adalah fungsi bisnis yang usable; test coverage boleh menyusul, tetapi status verifikasi harus ditandai jelas.

## Aturan Eksekusi Hemat Token
Agar pengerjaan tetap hemat API credit / token dan tidak boros konteks, eksekusi roadmap dilakukan per batch domain, bukan menyapu semua item kecil secara paralel.

### Cara kerja
- kerjakan satu batch besar yang boundary-nya jelas, lalu selesaikan sampai UI, alur, dan data utamanya usable
- hindari loncat-loncat antar `GL`, `tax`, `inventory costing`, `sales document flow`, dan `bank reconciliation` dalam satu putaran
- gunakan module existing lebih dulu sebelum mempertimbangkan split module baru
- tunda refactor kosmetik dan test menyeluruh jika belum dibutuhkan untuk menjaga fungsi utama

### Batch yang disarankan
- Batch 1: `GL / COA formal ringan`
  isi: manual journal, `draft/posted`, trial balance, general ledger, neraca
- Batch 2: `inventory costing`
  isi: moving average, stock valuation, COGS journal, rekonsiliasi inventory ke GL
- Batch 3: `tax`
  isi: master pajak, tax rate, akun pajak, NPWP partner, rekap pajak
- Batch 4: `sales & purchase document flow`
  isi: quotation, sales order, purchase request, purchase order, lifecycle dokumen
- Batch 5: `cash & bank control`
  isi: reconciliation formal, import mutasi, matching
- Batch 6: `controls & approval`
  isi: approval matrix, maker-checker, audit drill-down

### Aturan test sementara
- setiap batch boleh jalan dengan fokus `fungsi dulu`
- test yang belum dibuat atau belum dijalankan harus ditandai, jangan dianggap implicit sudah aman
- jika ada test yang sudah pernah dijalankan untuk sebagian fitur, tandai hanya area yang benar-benar sudah diverifikasi

## Status Verifikasi
Bagian ini khusus untuk membedakan:
- fitur yang sudah jalan secara fungsi
- fitur yang sudah punya test
- fitur yang masih butuh verifikasi

### Sudah dijalankan
- [x] manual journal dasar: create draft dan post journal
- [x] finance GL report dasar: trial balance dan general ledger dari posted journal
- [x] balance sheet awal berbasis klasifikasi posted journal dengan fallback heuristic dan dukungan metadata dari COA formal ringan
- [x] chart of accounts formal ringan di `finance` dengan CRUD dasar, default seed, dan navigasi ke journal/report
- [x] pemisahan `standard` vs `advanced` pada accounting UI utama beserta navigasinya
- [x] inventory valuation dasar: moving average snapshot, inventory value per stock, dan tampilan valuation di inventory/report
- [x] tax master dasar di `finance`: tax rate, mapping akun pajak, dan rekap pajak dasar
- [x] tax profile partner dasar di `contacts`: NPWP/Tax ID, tax name, tax address, dan status PKP

### Belum dijalankan atau belum lengkap
- [ ] edit manual journal di browser flow penuh
- [ ] filter finance report end-to-end dari UI
- [ ] regression test untuk journal auto-posting dari sales, purchases, payments setelah perubahan terbaru
- [ ] integration test antara finance journal governance dan reports
- [ ] verifikasi fungsi neraca terhadap kombinasi akun asset / liability / equity yang lebih beragam
- [ ] penyesuaian neraca setelah COA formal tersedia
- [ ] acceptance test untuk workflow accounting per batch berikutnya
- [ ] migrate + smoke test COA terhadap database target
- [ ] migrate + smoke test inventory valuation columns terhadap database target
- [ ] migrate + smoke test tax master dan tax profile partner terhadap database target
- [ ] verifikasi purchase receipt, opening stock, transfer, adjustment, dan return terhadap moving average hasil nyata
- [ ] integrasi auto-apply tax master ke draft sales dan purchases
- [ ] rekap pajak formal per tax code, bukan hanya total sales/purchase tax

## Rencana Module Ownership
Bagian ini menjelaskan rencana penempatan fitur agar pengembangan accounting tidak berubah menjadi kumpulan module acak tanpa boundary.

### Aturan umum
- `contacts` tetap menjadi shared external relationship master lintas modul, bukan module accounting-only.
- `finance` tetap dipakai untuk finance operasional dan fondasi accounting ringan selama scope-nya belum menuntut pemecahan domain.
- `reports` hanya menjadi owner query/reporting, bukan tempat logika bisnis utama.
- module baru hanya layak dibuat jika scope-nya sudah cukup besar, punya boundary jelas, dan tidak sehat bila terus dipaksakan menumpuk di module existing.

### Owner module per domain
- `customer / supplier`, contact person, billing/shipping address, segment, credit profile partner: `contacts`
- `product master`, SKU, varian, price list dasar: `products`
- `warehouse`, stock movement, stock opname, costing layer, stock valuation: `inventory`
- `sales document flow` seperti invoice, retur, quotation, sales order: `sales`
- `purchase document flow` seperti supplier bill, purchase request, purchase order: `purchases`
- `payment posting`, receive payment, allocation, reconciliation status: `payments`
- `cash / bank operasional`, transfer antar account, manual journal ringan, period lock, governance journal awal: `finance`
- `financial statements`, trial balance view, general ledger report, balance sheet report: `reports`
- `roles`, permission, auth, user management: core / app shell

### Rencana module per layar accounting UI
- `finance`
  owner untuk layar operasional dan governance accounting: transactions, finance accounts, categories, chart of accounts, manual journals, approvals, period locks
- `reports`
  owner untuk layar output accounting: finance reports, trial balance, general ledger, balance sheet, cash flow, profit/loss
- `contacts`
  owner untuk partner master lintas domain: customer, supplier, NPWP, payment term, billing profile
- `products` + `inventory`
  owner untuk item master, stock movement, valuation source, dan costing input
- jangan buat module `accounting-ui`; navigasi accounting tetap disusun dari owner module yang memang memegang domainnya

### Domain yang sementara tetap di module existing
- `COA formal` mulai di `finance` dulu selama masih menyatu dengan journal governance dan belum memerlukan domain accounting terpisah.
- `manual journal`, `draft vs posted`, `reversal`, `posting governance` tetap di `finance` pada fase awal.
- `tax master` sederhana pada fase awal boleh hidup di `finance` selama masih berupa tarif pajak, mapping akun pajak, dan rekap dasar.
- `NPWP customer / supplier` lebih dekat ke `contacts` karena itu atribut partner eksternal, bukan inti perhitungan jurnal.

### Candidate split menjadi module baru
- `accounting`
  Layak dipisah dari `finance` bila scope sudah mencakup `COA formal`, posting policy, journal governance penuh, trial balance, ledger, closing, balance sheet, dan kontrol akuntansi formal lain yang tidak lagi cocok disebut finance operasional ringan.
- `tax`
  Layak dipisah bila scope sudah mencakup `tax code`, `PPN keluaran`, `PPN masukan`, `PPh`, faktur pajak, e-Faktur export/integration, dan lifecycle perpajakan yang jelas.
- `approvals`
  Layak dipisah bila approval matrix sudah benar-benar lintas modul dan tidak lagi spesifik pada transaksi sensitif finance saja.

### Yang jangan dilakukan
- jangan membuat module `foundation`
- jangan memindahkan `contacts` menjadi subdomain accounting
- jangan meletakkan logika costing atau stock valuation ke `reports`
- jangan meletakkan core tax workflow permanen di `sales` atau `purchases`
- jangan membuat module baru hanya karena satu form atau satu tabel tambahan

### Peta singkat keputusan
- jika fitur berkaitan dengan relasi pihak eksternal: masuk `contacts`
- jika fitur berkaitan dengan stok fisik dan valuasi persediaan: masuk `inventory`
- jika fitur berkaitan dengan dokumen transaksi penjualan: masuk `sales`
- jika fitur berkaitan dengan dokumen transaksi pembelian: masuk `purchases`
- jika fitur berkaitan dengan penerimaan/pembayaran dan alokasi: masuk `payments`
- jika fitur berkaitan dengan journal governance dan finance operasional: masuk `finance`
- jika fitur berkaitan dengan laporan dari data journal/transaksi: masuk `reports`
- jika scope sudah menjadi akuntansi formal penuh: pertimbangkan module baru `accounting`
- jika scope sudah menjadi perpajakan formal penuh: pertimbangkan module baru `tax`

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

## Big Picture Checklist
Checklist ini memetakan proses accounting end-to-end agar roadmap tidak berhenti di fitur transaksi dasar. Status dibagi menjadi:
- `[x]` sudah ada dan sudah usable
- `[-]` sudah mulai ada tetapi belum lengkap / belum formal
- `[ ]` belum ada atau belum layak dianggap selesai

### 1. Master Data / Foundation
- [-] `chart of accounts (COA)` formal ringan sudah ada, tetapi masih perlu pendalaman mapping dan governance lanjutan
  owner module: `finance` dulu, kandidat pindah ke `accounting` jika domain formal membesar
- [x] `customer / supplier`
  owner module: `contacts`
- [x] `produk / inventory`
  owner module: `products` + `inventory`
- [-] `pajak` master dasar sudah ada di `finance`, tetapi integrasi workflow pajak formal belum lengkap
  owner module: `finance` dulu untuk fase awal, kandidat split ke `tax`
- [x] `user & role`
  owner module: core / app shell
- [ ] `warehouse / gudang` formal bila inventory multi-lokasi ingin diperdalam
  owner module: `inventory`
- [ ] `payment term` dan profil billing pajak yang lebih lengkap per customer / supplier
  owner module: `contacts`

### 2. Sales / Penjualan
- [ ] `quotation`
  owner module: `sales`
- [ ] `sales order`
  owner module: `sales`
- [-] `invoice / sale` dasar sudah ada, tetapi flow dokumen komersial penuh belum lengkap
  owner module: `sales`
- [x] `payment / receive payment`
  owner module: `payments`
- [x] `retur penjualan`
  owner module: `sales`
- [-] `piutang` dasar sudah ada, tetapi kontrol write-off, dispute, dan settlement penuh belum lengkap
  owner module: `sales` + `payments`, report turunannya di `reports`
- [-] `revenue posting` otomatis dasar sudah ada lewat auto journal, tetapi flow accounting formal masih perlu diperdalam
  owner module: `finance` untuk journal governance, `reports` untuk output laporan

### 3. Purchase / Pembelian
- [ ] `purchase request`
  owner module: `purchases`
- [ ] `purchase order`
  owner module: `purchases`
- [-] `bill / invoice supplier` dasar sudah ada lewat purchase draft/finalize, tetapi flow procurement formal belum lengkap
  owner module: `purchases`
- [x] `payment`
  owner module: `payments`
- [-] `hutang` dasar sudah ada, tetapi kontrol write-off, debit note, dan settlement penuh belum lengkap
  owner module: `purchases` + `payments`, report turunannya di `reports`
- [-] `biaya / inventory posting` dasar sudah ada, tetapi kontrol accounting formal masih perlu diperdalam
  owner module: `finance` untuk journal governance, `inventory` untuk valuation source

### 4. Inventory
- [x] `stock masuk / keluar`
  owner module: `inventory`
- [ ] `warehouse / location model` formal untuk multi-gudang yang matang
  owner module: `inventory`
- [x] `stock opname`
  owner module: `inventory`
- [-] `HPP / costing method` awal `moving average` untuk valuation stock snapshot sudah mulai ada
  owner module: `inventory`, jurnal turunannya di `finance`
- [-] `stock valuation` dasar per stock/location/report sudah mulai ada, tetapi rekonsiliasi formal ke GL belum selesai
  owner module: `inventory`, report di `reports`
- [ ] `COGS journal` otomatis yang konsisten dari pergerakan inventory ke penjualan
  owner module: `finance`, source movement tetap dari `inventory`

### 5. Cash & Bank
- [x] `kas masuk / keluar`
  owner module: `finance`
- [x] `transfer antar rekening`
  owner module: `finance`
- [-] `rekonsiliasi bank` dasar mulai ada lewat reconciliation status, tetapi belum formal
  owner module: `payments` + `finance`
- [ ] `import mutasi bank`
  owner module: `finance`
- [ ] `matching bank statement` ke payment / finance transaction
  owner module: `payments` + `finance`

### 6. General Ledger / Jurnal
- [x] `jurnal otomatis` dari modul lain
  owner module: `finance`
- [x] `jurnal manual` yang lengkap dan nyaman dipakai finance
  owner module: `finance`
- [x] `posting & locking period` dasar
  owner module: `finance`
- [x] `draft vs posted journal` yang formal
  owner module: `finance`
- [ ] `reversal journal`
  owner module: `finance`
- [x] `trial balance`
  owner module: `reports` untuk output, dengan governance source di `finance`
- [x] `general ledger / buku besar` formal dengan drill-down
  owner module: `reports` untuk output, dengan governance source di `finance`
- [-] `balance sheet / neraca` yang berbasis GL dengan dukungan COA ringan, tetapi belum closing-grade
  owner module: `reports` untuk output, dengan governance source di `finance`

### 7. Reporting
- [x] `laba rugi` sederhana
  owner module: `reports`
- [-] `neraca`
  owner module: `reports`
- [x] `arus kas`
  owner module: `reports`
- [x] `aging piutang / hutang`
  owner module: `reports`
- [x] `buku besar`
  owner module: `reports`
- [x] `trial balance`
  owner module: `reports`
- [ ] `drill-down report` dari laporan ke jurnal dan dokumen sumber
  owner module: `reports`

### 8. Tax / Pajak
- [-] `master pajak`
  owner module: `finance` dulu, kandidat split ke `tax`
- [-] `tax code / tax rate`
  owner module: `finance` dulu, kandidat split ke `tax`
- [-] `PPN keluaran`
  owner module: kandidat `tax` bila domain sudah formal
- [-] `PPN masukan`
  owner module: kandidat `tax` bila domain sudah formal
- [ ] `PPh / withholding tax`
  owner module: kandidat `tax`
- [-] `NPWP customer / supplier`
  owner module: `contacts`
- [ ] `faktur pajak`
  owner module: kandidat `tax`
- [ ] `export / integrasi e-Faktur`
  owner module: kandidat `tax`

### 9. User, Permission, Approval, Audit
- [x] `role & permission`
  owner module: core / app shell
- [-] `approval system` dasar untuk aksi sensitif sudah mulai ada, tetapi belum jadi workflow generik lintas modul
  owner module: `finance` dulu, kandidat split ke `approvals`
- [x] `audit trail` dasar
  owner module: mengikuti module owner masing-masing
- [ ] `maker-checker` formal untuk void, backdate, edit nominal, dan approval posting
  owner module: `finance` dulu, kandidat split ke `approvals`

## Gap Utama Saat Ini
Masih banyak pekerjaan, tetapi bukan berarti fondasinya kosong. Yang paling besar justru ada di area yang membuat sistem naik kelas dari operasional transaksi menjadi accounting formal.

### Paling krusial
- [-] `COA + GL formal` fondasi awal sudah ada, tetapi masih butuh reversal, mapping lebih kaya, dan closing governance
- [ ] `inventory costing / HPP formal` agar penjualan barang benar-benar masuk ke accounting
- [-] `tax management` fondasi master dan rekap dasar sudah ada, tetapi auto-apply dan lifecycle formal belum lengkap

### Perlu segera setelah fondasi
- [ ] `sales document flow` lengkap: quotation -> sales order -> invoice -> receive payment -> return
- [ ] `purchase document flow` lengkap: purchase request -> purchase order -> supplier bill -> payment
- [ ] `bank reconciliation` formal
- [ ] `reporting formal` seperti neraca, trial balance, buku besar

### Penguat operasional dan kontrol
- [-] `manual journal`, `reversal`, dan `posted journal control`
- [ ] `approval matrix` lintas modul
- [ ] `maker-checker` untuk aksi berisiko tinggi
- [ ] `drill-down auditability` dari report -> journal -> source document

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

## Prioritas 4
### General Ledger / COA
- [-] COA formal ringan yang bisa dikelola tenant tanpa hardcode struktur akun
- [x] manual journal yang lengkap dengan debit/credit validation
- [x] status journal `draft` vs `posted`
- [ ] reversal journal dan pembatalan yang audit-safe
- [x] trial balance
- [x] buku besar / general ledger dengan filter akun, periode, company, branch
- [-] neraca berbasis GL + COA ringan

### Inventory Costing
- [-] pilih dan implementasikan metode costing awal, saat ini `moving average` dasar untuk snapshot valuation
- [x] stock valuation report dasar
- [ ] auto journal HPP / COGS saat sale finalized
- [ ] rekonsiliasi inventory value vs akun persediaan di GL

### Tax
- [-] master pajak dan tax rate
- [ ] mapping pajak ke sales dan purchases
- [-] akun pajak keluaran dan masukan
- [-] NPWP customer / supplier
- [-] rekap pajak per periode
- [ ] draft struktur faktur pajak / export data pajak

## Prioritas 5
### Sales & Purchase Document Flow
- [ ] quotation
- [ ] sales order
- [ ] purchase request
- [ ] purchase order
- [ ] status lifecycle dokumen yang konsisten dari pre-transaction ke invoice / bill
- [ ] approval per dokumen sebelum finalize bila dibutuhkan

### Cash & Bank
- [ ] bank reconciliation formal
- [ ] import mutasi bank
- [ ] auto matching mutasi ke payment / finance transaction
- [ ] outstanding unreconciled transaction view

### Controls & Audit
- [ ] approval matrix lintas modul
- [ ] maker-checker untuk void, edit nominal besar, backdate, write-off
- [ ] drill-down dari laporan ke jurnal dan dokumen sumber
- [ ] penguatan audit trail untuk perubahan master data sensitif

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
7. [-] `GL / COA formal`: manual journal, trial balance, buku besar, neraca provisional
8. [-] `inventory costing`: moving average dasar dan stock valuation sudah mulai ada, auto journal HPP belum
9. [-] `tax`: master pajak dasar, NPWP/tax profile partner, dan rekap pajak dasar sudah ada
10. [ ] `document flow`: quotation, sales order, purchase request, purchase order
11. [ ] `cash & bank`: bank reconciliation formal dan import mutasi
12. [ ] `controls`: approval matrix, maker-checker, audit drill-down
