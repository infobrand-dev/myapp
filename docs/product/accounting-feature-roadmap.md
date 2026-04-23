# Accounting Feature Roadmap

## Prinsip
- `standard` dan `advanced` adalah mode UI, bukan plan berbeda.
- `products` wajib ada untuk plan accounting yang memakai `sales`.
- `inventory` adalah source of truth stok; `products` hanya master data produk.
- backlog di bawah ini fokus ke fitur bisnis yang belum lengkap atau belum nyaman dipakai, bukan sekadar refactor teknis.
- jangan membuat module baru yang terlalu generik seperti `foundation` atau `master-data`; owner module harus mengikuti domain yang benar.
- selama fase sekarang, prioritas utama adalah fungsi bisnis yang usable; test coverage boleh menyusul, tetapi status verifikasi harus ditandai jelas.

## Layer Big Picture
Roadmap ini tidak boleh dibaca sebagai "semua item setara penting". Untuk menjaga arah produk tetap masuk akal, item harus dibaca per layer kematangan.

### Layer 1: Foundation / Dasar
Layer ini adalah batas minimum agar produk layak disebut sudah punya accounting operasional dasar.

Isi utamanya:
- transaksi inti `sales`, `purchases`, `payments`, `finance`
- `products`, `contacts`, dan `inventory` sebagai source data operasional
- auto journal dasar
- manual journal
- `draft vs posted`
- trial balance
- general ledger
- balance sheet awal / provisional
- tax master dasar
- numbering dokumen lintas modul
- workflow approval dasar per dokumen bila diperlukan tenant

Prinsip layer ini:
- fokus pada transaksi benar-benar tercatat
- jurnal dan report dasar harus nyambung
- PostgreSQL / Supabase-safe lebih penting daripada kosmetik
- belum wajib punya kontrol internal tingkat enterprise

### Layer 2: Operational / Menengah
Layer ini membuat accounting bukan cuma "bisa jalan", tetapi nyaman dipakai harian dan siap diperluas.

Isi utamanya:
- document flow komersial dan procurement lebih lengkap
- costing dan valuation makin akurat
- rekonsiliasi inventory ke GL
- tax workflow yang lebih formal
- bank reconciliation formal
- drill-down auditability
- reversal journal formal
- report yang lebih konsisten dan tidak terlalu bergantung fallback

Prinsip layer ini:
- menutup gap bisnis yang paling sering bikin angka operasional dan angka accounting tidak sinkron
- membuat user tidak perlu kerja manual terlalu banyak di luar sistem

### Layer 3: Control & Governance / Lengkap
Layer ini adalah level kontrol formal. Ini penting, tetapi bukan syarat agar fondasi accounting dianggap sudah ada.

Isi utamanya:
- `maker-checker`
- approval matrix lintas modul
- control backdate / override / write-off
- separation of duties
- closing governance yang lebih formal
- audit trail sensitif dan monitoring exception yang lebih kuat

Prinsip layer ini:
- layer ini menaikkan maturity governance
- jangan tarik item layer ini ke depan sebelum gap foundation dan operational yang kritikal tertutup

## Fokus Big Picture Saat Ini
Untuk fase sekarang, fokus utama bukan menambah fitur pinggiran, tetapi menutup gap besar yang masih menghambat bentuk accounting yang utuh.

### Fondasi yang sudah cukup kuat
- GL dasar sudah ada: manual journal, posted journal, trial balance, ledger, neraca awal
- inventory costing dasar sudah mulai ada: moving average snapshot, stock valuation, auto COGS journal
- tax dasar sudah ada: master tax, mapping akun pajak, tax profile partner, rekap dasar
- document lifecycle dasar sudah ada: quotation, sales order, purchase request, purchase order, approval rule per dokumen

### Gap big picture yang masih paling penting
- `inventory costing` belum lengkap untuk edge case procurement, return, adjustment, dan rekonsiliasi ke GL
- `bank reconciliation formal` fondasinya sekarang sudah mulai ada, dan import mutasi + suggested matching awal sudah mencakup payment serta finance transaction dasar; exception handling dasar untuk line yang tidak bisa dimatch juga sudah mulai ada, format import bank umum dan duplicate candidate dasar juga sudah mulai ditangani, tetapi matching lintas source yang lebih kaya dan rule yang lebih cerdas masih belum ada
- `tax workflow formal` sekarang sudah punya fondasi inti Indonesia-ready lewat tax scope master, metadata legal, tax register formal untuk PPN/PPh dasar, draft export CSV untuk register serta e-Faktur awal, auto-generate register dasar dari sale/purchase bertax, dan numbering dokumen pajak formal; tetapi export perpajakan final, e-Faktur resmi, dan automation yang lebih dalam masih belum lengkap
- `commercial document flow` sudah ada fondasi, tetapi belum sepenuhnya matang sebagai flow operasional penuh
- `balance sheet` sudah mulai masuk arah closing-grade karena period closing awal dapat memindahkan laba/rugi ke retained earnings dan mengunci periode, tetapi reopen/reverse closing governance belum lengkap

### Yang belum perlu didorong ke depan
- `maker-checker` formal
- approval matrix lintas modul penuh
- governance enterprise lain yang berguna, tetapi berada di layer lengkap

Artinya:
- jika masih ada gap besar di costing, reconciliation, tax formal, dan document flow, maka area itu harus diprioritaskan sebelum kontrol enterprise lanjutan

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
- [x] penyesuaian neraca setelah COA formal tersedia: memakai metadata COA, parent grouping, subtotal grup, dan current earnings sementara
- [x] export finance report formal dasar: Trial Balance CSV, General Ledger CSV, dan Balance Sheet CSV
- [x] period closing awal: membuat closing journal laba/rugi ke retained earnings dan auto period lock

### Belum dijalankan atau belum lengkap
- [ ] edit manual journal di browser flow penuh
- [ ] filter finance report end-to-end dari UI
- [ ] regression test untuk journal auto-posting dari sales, purchases, payments setelah perubahan terbaru
- [ ] integration test antara finance journal governance dan reports
- [ ] verifikasi fungsi neraca terhadap kombinasi akun asset / liability / equity yang lebih beragam
- [ ] reopen / reverse period closing dengan governance formal
- [ ] acceptance test untuk workflow accounting per batch berikutnya
- [ ] migrate + smoke test COA terhadap database target
- [ ] migrate + smoke test inventory valuation columns terhadap database target
- [ ] migrate + smoke test tax master dan tax profile partner terhadap database target
- [ ] verifikasi purchase receipt, opening stock, transfer, adjustment, dan return terhadap moving average hasil nyata
- [x] integrasi auto-apply tax master ke draft sales dan purchases
- [x] rekap pajak formal per tax code, bukan hanya total sales/purchase tax

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
- [x] fondasi `document numbering rules` lintas dokumen dengan scope `company -> branch override` sudah disiapkan untuk ekspansi jangka panjang
  owner module: core settings + owner module dokumen masing-masing
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
- [x] `quotation`
  owner module: `sales`
- [x] `sales order`
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
- [-] `sales return` sekarang sudah membuat journal reversal revenue saat finalized dan reversal inventory/COGS saat restock inventory terjadi, tetapi write-off/credit memo formal masih belum lengkap
  owner module: `sales` untuk source document, `finance` untuk journal governance

### 3. Purchase / Pembelian
- [x] `purchase request`
  owner module: `purchases`
- [x] `purchase order`
  owner module: `purchases`
- [-] `bill / invoice supplier` dasar sudah ada lewat purchase draft/finalize, tetapi flow procurement formal belum lengkap
  owner module: `purchases`
- [x] `payment`
  owner module: `payments`
- [-] `hutang` dasar sudah ada, tetapi kontrol write-off, debit note, dan settlement penuh belum lengkap
  owner module: `purchases` + `payments`, report turunannya di `reports`
- [-] `biaya / inventory posting` sekarang sudah mencakup purchase finalized ke `PURCHASES` dan purchase receipt reclass ke `INVENTORY`, tetapi kontrol accounting formal masih perlu diperdalam
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
- [-] `stock valuation` dasar per stock/location/report sudah mulai ada, dan rekonsiliasi inventory vs GL sekarang sudah punya panel agregat + detail per source document; purchase receipt, opening stock, stock adjustment, dan sales return restock sudah terhubung, tetapi posting inventory edge case lain masih belum lengkap
  owner module: `inventory`, report di `reports`
- [x] `COGS journal` otomatis saat sale finalized bila sale membawa konteks `inventory_location_id`, memakai moving average inventory sebagai source valuasi
  owner module: `finance`, source movement tetap dari `inventory`
- [-] `stock adjustment` sekarang sudah membuat journal inventory adjustment saat finalized, tetapi opening stock, sales return, dan procurement edge case lain masih perlu ditutup
  owner module: `inventory` untuk movement source, `finance` untuk journal governance
- [-] `opening stock` sekarang sudah membuat journal opening inventory ke `INVENTORY` vs `OPENING_BAL_EQUITY`, tetapi procurement edge case lanjutan dan flow inventory lain masih perlu ditutup
  owner module: `inventory` untuk movement source, `finance` untuk journal governance

### 5. Cash & Bank
- [x] `kas masuk / keluar`
  owner module: `finance`
- [x] `transfer antar rekening`
  owner module: `finance`
- [-] `rekonsiliasi bank` sekarang sudah punya sesi reconciliation formal per finance account dan periode, candidate payment dari payment method yang dipetakan ke account, import bank statement, manual override match ke payment atau finance transaction, status `exception/ignored` dasar per statement line, serta duplicate candidate dasar saat import; namun matching formal lintas source dan exception handling lanjutan belum lengkap
  owner module: `payments` + `finance`
- [-] `import mutasi bank` dasar CSV/XLSX ke sesi reconciliation sudah ada, dan alias header tanggal/amount/debit/credit dasar mulai didukung; tetapi normalisasi format bank yang lebih kaya masih belum lengkap
  owner module: `finance`
- [-] `matching bank statement` ke payment dan finance transaction dasar sudah mulai ada, termasuk manual override per line, suggestion lintas source dasar, dan duplicate candidate dasar; exception status dasar juga sudah mulai ada, tetapi matching lintas source yang lebih kaya, rule scoring yang lebih cerdas, dan exception handling lanjutan belum lengkap
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
- [x] `reversal journal`
  owner module: `finance`
- [x] `trial balance`
  owner module: `reports` untuk output, dengan governance source di `finance`
- [x] `general ledger / buku besar` formal dengan drill-down
  owner module: `reports` untuk output, dengan governance source di `finance`
- [x] `balance sheet / neraca provisional` yang berbasis GL dengan dukungan COA ringan, parent grouping COA, subtotal grup, export CSV, dan current earnings sementara ke equity
  owner module: `reports` untuk output, dengan governance source di `finance`
- [-] `balance sheet / neraca closing-grade` sudah punya period closing awal dan retained earnings journal permanen, tetapi reopen/reverse closing governance belum lengkap
  owner module: `reports` untuk output, dengan governance source di `finance`

### 7. Reporting
- [x] `laba rugi` sederhana
  owner module: `reports`
- [x] `neraca provisional` sekarang sudah punya grouping COA, subtotal grup, dan export CSV
  owner module: `reports`
- [-] `neraca closing-grade` sudah punya closing process awal dan retained earnings permanen, tetapi governance reopen/reverse belum lengkap
  owner module: `reports`
- [x] `arus kas`
  owner module: `reports`
- [x] `aging piutang / hutang`
  owner module: `reports`
- [x] `buku besar`
  owner module: `reports`
- [x] `trial balance`
  owner module: `reports`
- [x] `drill-down report` dari laporan ke jurnal dan dokumen sumber
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
- [-] `PPh / withholding tax`
  owner module: kandidat `tax`
- [-] `NPWP customer / supplier`
  owner module: `contacts`
- [-] `faktur pajak`
  owner module: kandidat `tax`
- [-] `export / integrasi e-Faktur` sudah punya draft CSV PPN keluaran, tetapi belum format final resmi / integrasi
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
- [-] `COA + GL formal` fondasi awal sudah ada, termasuk period closing awal, tetapi masih butuh mapping lebih kaya dan closing governance lanjutan
- [-] `inventory costing / HPP formal` fondasi makin lengkap karena purchase receipt, opening stock, adjustment, sale COGS, dan sales return restock sudah masuk GL; namun coverage edge case dan governance costing masih belum penuh
- [-] `tax management` fondasi master dan rekap dasar sudah ada, tax scope Indonesia-ready, tax register formal dasar, draft export CSV awal, auto-generate register dari sale/purchase bertax, dan numbering dokumen pajak formal juga sudah ada; tetapi lifecycle formal penuh dan integrasi resmi masih belum lengkap

### Perlu segera setelah fondasi
- [-] `sales document flow` mulai ada lewat quotation dan sales order ke draft sale, tetapi lifecycle komersial penuh masih belum lengkap
- [-] `purchase document flow` mulai ada lewat purchase request -> purchase order -> draft purchase, tetapi approval/lifecycle penuh masih belum lengkap
- [-] `bank reconciliation` formal dasar per account/periode sudah ada, termasuk import statement, suggested matching lintas source awal, duplicate candidate dasar, dan manual override per line; tetapi matching lintas source dan exception flow belum lengkap
- [-] `reporting formal` seperti neraca, trial balance, dan buku besar sudah lebih usable dengan drill-down, export CSV formal, serta period closing awal; gap utama tersisa governance reopen/reverse closing dan validasi kombinasi akun lebih luas

### Penguat operasional dan kontrol
- [-] `manual journal`, `reversal`, `posted journal control`, dan period closing awal
- [ ] `approval matrix` lintas modul
- [ ] `maker-checker` untuk aksi berisiko tinggi
- [x] `drill-down auditability` dari report -> journal -> source document

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
- [x] reversal journal dan pembatalan yang audit-safe
- [x] trial balance
- [x] buku besar / general ledger dengan filter akun, periode, company, branch
- [x] neraca provisional berbasis GL + COA ringan, subtotal grup, current earnings sementara, dan export CSV formal
- [-] neraca closing-grade sudah punya closing process awal dan retained earnings permanen, tetapi reopen/reverse closing governance belum lengkap

### Inventory Costing
- [-] pilih dan implementasikan metode costing awal, saat ini `moving average` dasar untuk snapshot valuation
- [x] stock valuation report dasar
- [x] auto journal HPP / COGS saat sale finalized
- [-] rekonsiliasi inventory value vs akun persediaan di GL sudah punya panel agregat + detail per source document, dan posting inventory dari purchase receipt sekarang sudah formal; tetapi gap posting edge case lain masih perlu ditutup
- [-] stock adjustment sudah punya auto journal ke `INVENTORY` vs `INV_ADJ_GAIN` / `INV_ADJ_LOSS`, tetapi edge case inventory lain masih belum lengkap
- [-] opening stock sudah punya auto journal ke `INVENTORY` vs `OPENING_BAL_EQUITY`, tetapi edge case inventory lain masih belum lengkap

### Tax
- [-] master pajak dan tax rate
- [x] mapping pajak ke sales dan purchases
- [-] akun pajak keluaran dan masukan
- [-] NPWP customer / supplier
- [-] rekap pajak per periode
- [x] draft struktur faktur pajak / export data pajak dasar

## Prioritas 5
### Sales & Purchase Document Flow
- [x] quotation
- [x] sales order
- [x] purchase request
- [x] purchase order
- [x] status lifecycle dokumen yang konsisten dari pre-transaction ke invoice / bill
- [x] approval per dokumen sebelum finalize atau convert bisa diatur dari settings dokumen

### Cash & Bank
- [-] bank reconciliation formal
- [-] import mutasi bank
- [-] auto matching mutasi ke payment / finance transaction
- [x] outstanding unreconciled transaction view

### Controls & Audit
- [ ] approval matrix lintas modul
- [ ] maker-checker untuk void, edit nominal besar, backdate, write-off
- [x] drill-down dari laporan ke jurnal dan dokumen sumber
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
7. [-] `GL / COA formal`: manual journal, trial balance, buku besar, neraca provisional, dan period closing awal
8. [-] `inventory costing`: moving average dasar, stock valuation, dan auto journal HPP dari sale ber-location sudah ada; rekonsiliasi inventory ke GL masih tersisa
9. [-] `tax`: master pajak dasar, NPWP/tax profile partner, rekap pajak dasar, tax scope Indonesia-ready, tax register formal dasar, auto-generate register dari sale/purchase bertax, dan numbering dokumen pajak formal sudah ada
10. [-] `document flow`: quotation, sales order, purchase request, dan purchase order sudah ada; flow komersial/procurement penuh masih perlu diperdalam
11. [-] `cash & bank`: bank reconciliation formal dasar, import mutasi dengan normalisasi header dasar, suggested matching lintas source awal, manual override dasar, outstanding unreconciled view, duplicate candidate dasar, dan exception status dasar sudah ada; flow exception dan matching lanjutan masih belum
12. [ ] `controls`: approval matrix, maker-checker, audit drill-down
