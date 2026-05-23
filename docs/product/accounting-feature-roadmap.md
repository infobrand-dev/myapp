# Accounting Feature Roadmap

Dokumen ini adalah peta besar accounting. Tujuannya bukan mencatat semua ide secara acak, tetapi menjaga urutan kerja tetap jelas dari fondasi sampai governance.

Roadmap ini dibaca dengan urutan:
1. prinsip
2. layer kematangan
3. snapshot status saat ini
4. checklist besar per domain
5. urutan eksekusi

## Prinsip
- `standard` dan `advanced` adalah mode UI, bukan plan berbeda.
- `products` wajib ada untuk plan accounting yang memakai `sales`.
- `inventory` adalah source of truth stok; `products` hanya master data produk.
- backlog di bawah ini fokus ke fitur bisnis yang belum lengkap atau belum nyaman dipakai, bukan sekadar refactor teknis.
- jangan membuat module baru yang terlalu generik seperti `foundation` atau `master-data`; owner module harus mengikuti domain yang benar.
- selama fase sekarang, prioritas utama adalah fungsi bisnis yang usable; test coverage boleh menyusul, tetapi status verifikasi harus ditandai jelas.
- PostgreSQL / Supabase-safe lebih penting daripada kosmetik implementasi.

## Layer Kematangan

### Layer 1: Foundation / Dasar
Layer minimum agar produk layak disebut sudah punya accounting operasional dasar.

Isi utama:
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
- belum wajib punya kontrol internal tingkat enterprise

### Layer 2: Operational / Menengah
Layer ini membuat accounting bukan cuma bisa jalan, tetapi nyaman dipakai harian dan siap diperluas.

Isi utama:
- document flow komersial dan procurement lebih lengkap
- costing dan valuation makin akurat
- rekonsiliasi inventory ke GL
- tax workflow yang lebih formal
- bank reconciliation formal
- drill-down auditability
- reversal journal formal
- report yang lebih konsisten dan tidak terlalu bergantung fallback

### Layer 3: Control & Governance / Lengkap
Layer ini adalah level kontrol formal. Penting, tetapi bukan syarat agar fondasi accounting dianggap sudah ada.

Isi utama:
- `maker-checker`
- approval matrix lintas modul
- control backdate / override / write-off
- separation of duties
- closing governance yang lebih formal
- audit trail sensitif dan monitoring exception yang lebih kuat

## Snapshot Saat Ini

### Fondasi yang sudah cukup kuat
- GL dasar sudah ada: manual journal, posted journal, trial balance, ledger, neraca awal.
- inventory costing dasar sudah mulai ada: moving average snapshot, stock valuation, auto COGS journal, opening stock journal, stock adjustment journal, transfer in-transit.
- tax dasar sudah ada: master tax, mapping akun pajak, tax profile partner, rekap dasar, tax register formal dasar, numbering dokumen pajak formal, draft export PPN dan withholding.
- document lifecycle dasar sudah ada: quotation, sales order, purchase request, purchase order, approval rule per dokumen.
- period closing dasar sudah ada: closing journal retained earnings, auto lock, reopen/reverse closing governance dasar.
- approval lintas modul dasar dan maker-checker dasar untuk aksi sensitif sudah ada.

### Gap besar yang masih paling penting
- `inventory costing` belum lengkap untuk seluruh edge case dan governance costing formal.
- `bank reconciliation` sudah ada fondasi, tetapi matching lintas source, scoring rule, dan exception flow lanjutan belum lengkap.
- `tax workflow formal` sudah Indonesia-ready di level dasar, tetapi export resmi, integrasi final, dan automation lebih dalam belum lengkap.
- `commercial document flow` sudah ada fondasi, tetapi belum sepenuhnya matang sebagai flow operasional penuh.
- `COA + GL formal` masih butuh pendalaman mapping akun dan governance lanjutan.

### Yang tidak perlu ditarik terlalu depan
- governance enterprise yang terlalu dalam sebelum gap foundation dan operational tertutup
- split module baru tanpa boundary yang jelas
- refactor kosmetik yang tidak mengubah usability bisnis

## Status Verifikasi
Bagian ini khusus untuk pekerjaan implementasi dan verifikasi yang relevan untuk AI di codebase.

Tidak dimasukkan ke checklist ini:
- migrate yang Anda jalankan sendiri
- deploy / release production
- smoke test operasional yang Anda jalankan langsung di environment target
- langkah manual production lain di luar scope perubahan code

Artinya, checklist di bawah ini dipakai untuk membedakan:
- fitur yang sudah diimplementasikan di codebase
- verifikasi yang masih perlu saya kerjakan dari sisi code / browser flow / QA berbasis fitur
- area yang memang belum selesai secara implementasi
### Sudah dijalankan di codebase
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
- [x] reopen / reverse period closing dengan governance formal dasar
- [x] integrasi auto-apply tax master ke draft sales dan purchases
- [x] rekap pajak formal per tax code, bukan hanya total sales/purchase tax

### Belum dijalankan oleh AI atau belum lengkap
- [x] edit manual journal di browser flow penuh
- [x] filter finance report end-to-end dari UI
- [x] regression test untuk journal auto-posting dari sales, purchases, payments setelah perubahan terbaru
- [x] integration test antara finance journal governance dan reports
- [x] verifikasi fungsi neraca terhadap kombinasi akun asset / liability / equity yang lebih beragam
- [x] acceptance test untuk workflow accounting per batch berikutnya
- [x] verifikasi purchase receipt, opening stock, transfer, adjustment, dan return terhadap moving average hasil nyata

## Big Picture Checklist
Status:
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
- [-] `warehouse / gudang` dasar sudah ada lewat `inventory location`, tetapi governance multi-gudang formal masih belum matang
  owner module: `inventory`
- [x] `payment term` dasar per customer / supplier
  owner module: `contacts`
- [x] `billing / shipping / tax profile` dasar per customer / supplier
  owner module: `contacts`
- [-] `profil billing pajak` yang lebih lengkap per customer / supplier masih belum formal
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
- [-] `sales return` sekarang sudah membuat journal reversal revenue saat finalized dan reversal inventory/COGS saat restock inventory terjadi, tetapi write-off / credit memo formal masih belum lengkap
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
- [-] `warehouse / location model` dasar untuk multi-gudang sudah ada, tetapi management flow dan governance formalnya belum matang
  owner module: `inventory`
- [x] `stock opname`
  owner module: `inventory`
- [-] `HPP / costing method` awal `moving average` untuk valuation stock snapshot sudah mulai ada
  owner module: `inventory`, jurnal turunannya di `finance`
- [-] `stock valuation` dasar per stock/location/report sudah mulai ada, dan rekonsiliasi inventory vs GL sekarang sudah punya panel agregat + detail per source document; purchase receipt, opening stock, stock adjustment, stock opname adjustment trail, sales return restock, dan stock transfer in-transit sudah terhubung, tetapi posting inventory edge case lain masih belum lengkap
  owner module: `inventory`, report di `reports`
- [x] `COGS journal` otomatis saat sale finalized bila sale membawa konteks `inventory_location_id`, memakai moving average inventory sebagai source valuasi
  owner module: `finance`, source movement tetap dari `inventory`
- [-] `stock adjustment` sekarang sudah membuat journal inventory adjustment saat finalized dan stock opname sudah punya audit trail ke adjustment/journal, tetapi procurement edge case lain masih perlu ditutup
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
- [x] `balance sheet / neraca closing-grade` sudah punya period closing, retained earnings journal permanen, serta reopen/reverse closing governance dasar
  owner module: `reports` untuk output, dengan governance source di `finance`

### 7. Reporting
- [x] `laba rugi` sederhana
  owner module: `reports`
- [x] `neraca provisional` sekarang sudah punya grouping COA, subtotal grup, dan export CSV
  owner module: `reports`
- [x] `neraca closing-grade` sudah punya closing process, retained earnings permanen, dan governance reopen/reverse dasar
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
- [x] `tax code / tax rate` dasar
  owner module: `finance` dulu, kandidat split ke `tax`
- [-] `PPN keluaran`
  owner module: kandidat `tax` bila domain sudah formal
- [-] `PPN masukan`
  owner module: kandidat `tax` bila domain sudah formal
- [-] `PPh / withholding tax` sudah punya register formal, auto-journal dasar ke AP/AR/akun PPh, dan draft export bukti potong CSV; tetapi export resmi dan direction manual yang lebih kaya belum lengkap
  owner module: kandidat `tax`
- [x] `NPWP customer / supplier` dasar
  owner module: `contacts`
- [-] `faktur pajak`
  owner module: kandidat `tax`
- [-] `export / integrasi e-Faktur` sudah punya draft CSV PPN keluaran, tetapi belum format final resmi / integrasi
  owner module: kandidat `tax`

### 9. User, Permission, Approval, Audit
- [x] `role & permission`
  owner module: core / app shell
- [x] `approval system` dasar untuk aksi sensitif sudah menjadi workflow lintas modul awal dengan approval matrix basic, threshold nominal, dan multi-approver dasar
  owner module: `finance` dulu, kandidat split ke `approvals`
- [x] `audit trail` dasar
  owner module: mengikuti module owner masing-masing
- [x] `maker-checker` formal dasar untuk void, backdate governance awal, edit/delete transaksi finance, serta approval posting/reversal/reopen awal
  owner module: `finance` dulu, kandidat split ke `approvals`

## Fokus Eksekusi Berikutnya
Bagian ini adalah urutan kerja yang disarankan agar roadmap tidak loncat-loncat.

### Batch utama
1. `inventory costing`
   fokus: edge case costing, valuation coverage, reconciliation yang makin formal
2. `tax formal`
   fokus: export resmi, workflow formal, automation yang lebih dalam
3. `sales & purchase document flow`
   fokus: lifecycle komersial dan procurement yang lebih matang
4. `cash & bank`
   fokus: matching lintas source, scoring, exception flow
5. `COA + GL formal`
   fokus: account mapping lebih kaya dan governance lanjutan
6. `controls & audit lanjutan`
   fokus: separation of duties, checker routing, write-off governance

### Aturan eksekusi hemat token
- kerjakan satu batch besar yang boundary-nya jelas, lalu selesaikan sampai UI, alur, dan data utamanya usable
- hindari loncat-loncat antar `GL`, `tax`, `inventory costing`, `sales document flow`, dan `bank reconciliation` dalam satu putaran
- gunakan module existing lebih dulu sebelum mempertimbangkan split module baru
- tunda refactor kosmetik dan test menyeluruh jika belum dibutuhkan untuk menjaga fungsi utama

## Module Ownership
Bagian ini menjelaskan penempatan fitur agar pengembangan accounting tidak berubah menjadi kumpulan module acak tanpa boundary.

### Aturan umum
- `contacts` tetap menjadi shared external relationship master lintas modul, bukan module accounting-only
- `finance` tetap dipakai untuk finance operasional dan fondasi accounting ringan selama scope-nya belum menuntut pemecahan domain
- `reports` hanya menjadi owner query/reporting, bukan tempat logika bisnis utama
- module baru hanya layak dibuat jika scope-nya sudah cukup besar, punya boundary jelas, dan tidak sehat bila terus dipaksakan menumpuk di module existing

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

### Candidate split menjadi module baru
- `accounting`
  layak dipisah dari `finance` bila scope sudah mencakup `COA formal`, posting policy, journal governance penuh, trial balance, ledger, closing, balance sheet, dan kontrol akuntansi formal lain
- `tax`
  layak dipisah bila scope sudah mencakup `tax code`, `PPN keluaran`, `PPN masukan`, `PPh`, faktur pajak, e-Faktur export/integration, dan lifecycle perpajakan yang jelas
- `approvals`
  layak dipisah bila approval matrix sudah benar-benar lintas modul dan tidak lagi spesifik pada transaksi sensitif finance saja

## Dampak ke Plan / Pricing
- `Accounting Starter`: `products`, `sales`, `payments`, `finance`, `reports`, `contacts`
- `Accounting Growth`: Starter + `purchases` + `inventory`
- `Accounting Scale`: Growth + kapasitas dan reporting lebih besar
- `POS`: add-on

Implikasi:
- `products` bukan add-on untuk accounting starter, karena `sales` membutuhkannya secara implementasi
- `inventory` tidak wajib untuk starter
- fitur stok lanjutan tetap dikunci di `inventory`, bukan ditaruh di `products`
