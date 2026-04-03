# Accounting Product Plan

## Tujuan
- Membuka lini produk `accounting` yang terpisah dari `commerce`.
- Menempatkan integrasi seperti `Accurate`, `Zahir`, `Jurnal`, dan software akuntansi lain di domain yang tepat.
- Menjaga batas yang jelas: `commerce` mengelola transaksi operasional, `accounting` mengelola pembukuan, buku besar, rekonsiliasi, dan pelaporan keuangan.

## Posisi terhadap commerce
- `commerce` tetap menjadi domain transaksi operasional: produk, stok, pembelian, penjualan, pembayaran, dan POS.
- `finance` yang ada saat ini tetap diposisikan sebagai cash flow operasional ringan, bukan akuntansi formal.
- `accounting` menjadi product line baru untuk kebutuhan pembukuan formal, closing, rekonsiliasi, pajak dasar, dan integrasi ke software akuntansi.

## Module scope yang di-include

### 1. `accounting_core`
Fondasi akuntansi formal untuk tenant.

Ruang lingkup:
- chart of accounts
- fiscal period dan status closing
- journal entry manual
- ledger / buku besar
- trial balance
- accounting locks dan audit trail dasar

Ketergantungan awal:
- tidak wajib bergantung pada `sales` atau `purchases`
- harus siap tenant-aware dan company-aware

### 2. `accounting_receivables`
Piutang usaha dan alokasi pembayaran customer.

Ruang lingkup:
- customer invoice
- aging receivables
- payment allocation
- write-off ringan
- statement of account customer

Ketergantungan awal:
- `contacts`
- adapter opsional ke `sales`

### 3. `accounting_payables`
Hutang usaha dan alokasi pembayaran vendor.

Ruang lingkup:
- vendor bill
- aging payables
- payment allocation
- debit note / adjustment dasar
- statement vendor

Ketergantungan awal:
- `contacts`
- adapter opsional ke `purchases`

### 4. `accounting_cashbank`
Kas, bank, mutasi, dan rekonsiliasi.

Ruang lingkup:
- cash / bank account register
- cash in / cash out formal
- transfer antar akun
- bank reconciliation
- outstanding transaction matching

Ketergantungan awal:
- `accounting_core`

### 5. `accounting_assets`
Asset tetap dan depresiasi dasar.

Ruang lingkup:
- fixed asset register
- acquisition
- depreciation schedule
- disposal / write-off

Ketergantungan awal:
- `accounting_core`

### 6. `accounting_tax`
Lapisan pajak dasar yang tidak memaksa semua tenant memakai flow yang sama.

Ruang lingkup:
- tax code
- tax mapping per transaksi
- keluaran laporan dasar PPN/PPh sesuai kebutuhan rollout
- export-ready summary

Ketergantungan awal:
- `accounting_core`
- jangan hardcode aturan pajak yang seharusnya configurable

### 7. `accounting_reports`
Pelaporan keuangan formal.

Ruang lingkup:
- laba rugi
- neraca
- arus kas
- general ledger report
- journal report
- receivable / payable aging report

Ketergantungan awal:
- `accounting_core`
- bergantung pada modul accounting lain sesuai report

### 8. `accounting_integrations`
Hub integrasi ke software akuntansi eksternal.

Ruang lingkup:
- mapping account
- mapping customer / vendor / item
- sync status dashboard
- export queue / import queue
- error log integrasi

Sub-adapter awal yang disiapkan:
- `accurate`
- `zahir`
- `jurnal`

Catatan:
- provider/channel-specific logic harus tetap dekat dengan adapter integrasi yang memilikinya
- jangan campur normalized accounting behavior dengan transport/provider detail

## Rekomendasi urutan rollout

### Phase 1
- `accounting_core`
- `accounting_cashbank`
- `accounting_reports`

Hasil:
- tenant sudah bisa punya COA, jurnal, buku besar, trial balance, dan laporan dasar

### Phase 2
- `accounting_receivables`
- `accounting_payables`

Hasil:
- tenant bisa mengelola invoice/bill dan aging tanpa menunggu integrasi commerce penuh

### Phase 3
- adapter `sales -> accounting`
- adapter `purchases -> accounting`
- adapter `payments -> accounting`
- adapter `point_of_sale -> accounting`

Hasil:
- transaksi operasional mulai mem-posting ke jurnal secara terkendali

### Phase 4
- `accounting_integrations`
  - `accurate`
  - `zahir`
  - `jurnal`

Hasil:
- tenant dapat memilih tetap bekerja di dalam app atau sinkron ke sistem akuntansi eksternal

### Phase 5
- `accounting_assets`
- `accounting_tax`
- closing workflow yang lebih ketat

## Batas domain yang perlu dijaga
- `sales` tetap source of truth untuk transaksi penjualan operasional.
- `purchases` tetap source of truth untuk transaksi pembelian operasional.
- `payments` tetap source of truth untuk payment event operasional.
- `accounting` menerima posting, adjustment, reconciliation, dan pelaporan formal.
- Jangan membuat ulang transaksi commerce di dalam modul accounting bila cukup memakai adapter posting.

## Naming recommendation
- category baru: `accounting`
- product line public: `Accounting`
- hindari memakai `commerce` untuk modul yang fungsi utamanya pembukuan formal
- pertahankan `finance` saat ini sebagai domain cashflow operasional ringan sampai nanti diputuskan apakah akan tetap berdiri sendiri atau diserap sebagian oleh `accounting_cashbank`

## Landing page messaging
- fokus pada `rapi, siap closing, siap audit, siap integrasi`
- tonjolkan bahwa cocok untuk bisnis yang sudah punya transaksi berjalan dan butuh pembukuan yang lebih formal
- tampilkan `Accurate`, `Zahir`, dan `Jurnal` sebagai target integrasi, bukan klaim integrasi live jika implementasinya belum selesai
