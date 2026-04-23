# Accounting Operational Flow

Dokumen ini menjelaskan cara kerja accounting operasional yang saat ini hidup di modul existing:
- `sales`
- `payments`
- `finance`
- `inventory`
- `reports`

Dokumen ini bukan roadmap. Fokusnya adalah perilaku sistem yang sedang berlaku sekarang, boundary antar modul, dan aturan praktis "jika ini maka ini".

## Tujuan
- Menjelaskan alur transaksi accounting yang benar-benar dipakai aplikasi saat ini.
- Mengurangi kebingungan di area seperti `inventory_location_id`, HPP / COGS, stock movement, dan journal posting.
- Menjadi pegangan implementasi sebelum domain accounting formal dipisah menjadi modul tersendiri.

## Prinsip Utama
- `sales` adalah owner transaksi penjualan.
- `inventory` adalah source of truth stok dan valuation.
- `finance` adalah owner journal governance dan posting accounting ringan.
- `reports` hanya membaca dan menyajikan output, bukan source of truth.
- `payments` adalah owner penerimaan/pembayaran dan allocation.

Artinya:
- sale tidak boleh menghitung saldo stok sendiri
- reports tidak boleh menjadi tempat logika costing
- inventory valuation harus berasal dari movement / stock balance
- accounting journal tidak boleh dibuat dari asumsi UI saja jika source transaksi sebenarnya ada di modul lain

## Boundary Modul

### `sales`
Owner untuk:
- draft sale
- finalize sale
- snapshot item/customer
- status sale
- void sale

Tidak menjadi owner untuk:
- saldo stok
- mutasi stok
- payment ledger
- perhitungan inventory valuation

### `inventory`
Owner untuk:
- location / warehouse
- stock balance
- stock movement
- average unit cost
- inventory value

Tidak menjadi owner untuk:
- nomor invoice sale
- payment allocation
- laporan accounting formal

### `finance`
Owner untuk:
- manual journal
- auto journal
- chart of accounts ringan
- draft vs posted
- period lock
- approval sensitif awal

### `payments`
Owner untuk:
- payment record
- payment allocation
- reconciliation status dasar

### `reports`
Owner untuk:
- trial balance
- general ledger
- balance sheet provisional
- profit/loss sederhana
- inventory vs GL reconciliation view

## Istilah Penting

### `inventory_location_id`
`inventory_location_id` adalah lokasi stok yang dipakai untuk menentukan:
- dari gudang mana stok dikurangi
- stock balance mana yang berubah
- moving average mana yang dipakai sebagai basis HPP

Secara bisnis, ini berarti:
- kalau sale menjual barang stockable, sistem perlu tahu stok keluar dari lokasi mana
- tanpa lokasi, sistem tidak tahu saldo dan valuation mana yang harus dipakai

Karena itu:
- `inventory_location_id` bukan field kosmetik
- ini adalah konteks operasional yang menentukan efek inventory dan HPP

### `moving average`
Moving average adalah biaya rata-rata per unit pada stock balance aktif.

Dipakai untuk:
- menghitung `unit_cost` movement keluar
- menghitung `movement_value` saat stok keluar
- menjadi basis jurnal HPP / COGS

### `COGS` / `HPP`
`COGS` adalah nilai biaya barang yang terjual.

Di sistem saat ini:
- COGS untuk sale stockable diambil dari movement inventory keluar
- bukan sekadar `product.cost_price` snapshot

## Cara Kerja Sale Saat Ini

### 1. Draft sale dibuat
Saat draft sale dibuat:
- sistem menyimpan item, total, dan snapshot dasar transaksi
- status masih `draft`
- belum ada journal revenue
- belum ada journal COGS
- belum ada stock movement keluar

Maknanya:
- draft sale masih aman diedit
- draft belum dianggap transaksi final accounting

### 2. Draft sale di-finalize
Saat sale di-finalize:
- status menjadi `finalized`
- snapshot customer/item diperbarui
- journal revenue dibuat di `finance`

Journal revenue saat ini:
- debit `AR`
- credit `SALES`
- jika ada discount: debit `SALES_DISC`
- jika ada tax: credit `SALES_TAX`

### 3. Jika sale membawa konteks `inventory_location_id`
Jika sale punya `inventory_location_id` di context transaksi:
- sistem cek item mana yang `track_stock = true`
- inventory membuat movement keluar (`direction = out`, `movement_type = sale_finalized`)
- valuation keluar memakai moving average stock aktif pada location tersebut
- journal `sale_cogs` dibuat

Journal COGS:
- debit `COGS`
- credit `INVENTORY`

Artinya:
- revenue dan HPP sekarang terpisah secara eksplisit
- inventory dan accounting tersambung lewat movement inventory, bukan lewat report estimate

### 4. Jika sale tidak punya `inventory_location_id`
Jika sale finalized tetapi tidak membawa `inventory_location_id`:
- sale tetap finalized
- journal revenue tetap dibuat
- stock tidak dikurangi
- journal COGS tidak dibuat

Ini disengaja untuk fase sekarang, supaya:
- starter flow non-inventory tetap bisa jalan
- transaksi jasa / non-stockable tidak dipaksa masuk inventory

Konsekuensinya:
- sale seperti ini belum lengkap untuk accounting persediaan
- laba rugi bisa fallback ke estimasi jika tidak ada COGS aktual

### 5. Jika draft sale atau purchase memakai `tax_rate_id`
Jika draft sale atau purchase memilih `tax_rate_id`:
- sistem membaca tax master aktif dari `finance_tax_rates`
- sistem menghitung `tax_total` otomatis di level header
- snapshot tax master disimpan ke `meta.tax`
- jurnal final tetap memakai `tax_total` transaksi yang sudah dihitung

Untuk fase sekarang:
- auto-apply tax hanya mendukung tax master `exclusive`
- tax master `inclusive` belum dipakai otomatis agar base revenue / purchase tidak salah
- jika tax master dipilih, tax per item di draft diabaikan dan header tax menjadi source of truth

## Aturan Praktis "Jika Ini Maka Ini"

### Jika workflow dokumen mewajibkan approval sebelum convert atau finalize
Maka:
- settings dokumen menjadi source of truth apakah approval wajib atau tidak
- untuk `quotation`, `sales order`, `purchase request`, dan `purchase order`, aksi convert akan mengikuti rule dokumen efektif pada scope company atau branch
- untuk `sale` dan `purchase`, finalize draft akan membuat approval request bila rule finalize diaktifkan
- approval request masuk ke layar `finance approvals` yang sudah ada

Artinya:
- requirement approval tidak lagi hardcoded permanen untuk semua tenant
- tenant bisa memilih dokumen mana yang butuh approval formal dulu
- branch bisa override rule company bila operasional cabang berbeda

### Jika produk tidak stockable
Jika `track_stock = false`:
- finalize sale tidak membuat stock movement
- tidak ada jurnal COGS dari inventory
- sale tetap bisa finalize dan tetap bisa punya journal revenue

### Jika produk stockable tapi sale tidak punya `inventory_location_id`
Maka:
- stok tidak bergerak
- COGS aktual tidak terbentuk
- transaksi masih valid untuk sales, tapi belum lengkap untuk inventory accounting

### Jika produk stockable dan sale punya `inventory_location_id`
Maka:
- stok keluar dari location itu
- movement value dihitung dari moving average location tersebut
- journal `sale_cogs` dibuat

### Jika stock adjustment di-finalize
Maka:
- inventory membuat movement sesuai arah `increase` atau `decrease`
- movement value mengikuti average cost / unit cost yang berlaku di stock balance
- finance membuat journal `stock_adjustment`
- kenaikan stock akan mem-post `debit INVENTORY` vs `credit INV_ADJ_GAIN`
- penurunan stock akan mem-post `debit INV_ADJ_LOSS` vs `credit INVENTORY`

Artinya:
- finalize stock adjustment sekarang berdampak ke stok dan GL sekaligus
- adjustment tidak lagi terlihat sebagai `Missing GL` selama journal berhasil diposting

### Jika opening stock diposting
Maka:
- inventory membuat movement `opening_stock`
- movement value mengikuti `unit_cost` opening atau fallback cost yang berlaku
- finance membuat journal `opening_stock`
- debit `INVENTORY`
- credit `OPENING_BAL_EQUITY`

Artinya:
- opening stock sekarang tidak hanya mengisi saldo stok, tetapi juga membentuk saldo accounting awal inventory
- opening stock tidak lagi terlihat sebagai `Missing GL` selama journal berhasil diposting

### Jika purchase sudah finalized lalu barang diterima
Maka:
- purchase finalized tetap membentuk kewajiban supplier dan parkir nilai barang di akun `PURCHASES`
- saat `purchase receipt` diposting, inventory membuat movement masuk berdasarkan item receipt aktual
- finance membuat journal `purchase_receipt_inventory`
- debit `INVENTORY`
- credit `PURCHASES`

Artinya:
- hutang supplier diakui saat dokumen purchase finalized
- nilai barang baru direklas ke persediaan saat fisik barang benar-benar diterima
- procurement flow menjadi lebih aman untuk kasus partial receiving karena journal inventory mengikuti receipt aktual, bukan asumsi seluruh PO sudah masuk

### Jika sale di-void
Saat ini void sale akan:
- membalik journal revenue menjadi `sale_void`
- membalik journal COGS menjadi `sale_cogs_void` jika jurnal COGS sebelumnya ada
- mengembalikan stok masuk lagi jika movement `sale_finalized` sebelumnya ada

Maknanya:
- efek accounting dan inventory dicoba dipulihkan secara simetris

### Jika sales return di-finalize
Saat ini finalize sales return akan:
- membuat journal `sale_return_finalized`
- membalik sisi revenue return ke `SALES_REFUND`
- membalik pajak keluaran dengan debit `SALES_TAX` jika return membawa tax
- mengurangi `AR` sebesar nilai return

Jika return butuh restock inventory dan movement inventory berhasil dibuat:
- sistem membuat journal `sale_return_inventory`
- debit `INVENTORY`
- credit `COGS`

Jika refund kas benar-benar diproses lewat payment:
- payment refund akan debit `AR`
- payment refund akan credit `CASH`

Maknanya:
- return dokumen, restock inventory, dan cash refund sekarang dipisahkan secara accounting
- finalize return mengakui reversal penjualan
- refund payment menyelesaikan arus kas, bukan mengakui reversal penjualan kedua kali

### Jika sale hanya draft atau hold cart POS
Maka:
- belum ada stock movement final
- belum ada journal revenue final
- belum ada journal COGS

## Hubungan Dengan POS
POS bukan owner stok dan bukan owner accounting final.

POS hanya:
- menyimpan cart sementara
- membentuk payload checkout
- membuat / update draft sale
- memproses payment
- meminta `sales` melakukan finalize

Implikasinya:
- pengurangan stok final tetap terjadi dari finalize sale
- bukan dari UI POS langsung

## Hubungan Dengan Payments
Payment tidak membuat COGS.

Payment berpengaruh ke:
- allocation ke sale / payable lain
- paid total
- balance due
- payment journal sesuai flow payment

Tetapi:
- payment bukan sumber valuation inventory

## Hubungan Dengan Cash & Bank Reconciliation
Reconciliation formal sekarang mulai dipisahkan jelas:
- `payments` tetap menjadi owner transaksi payment
- `finance` menjadi owner sesi reconciliation per account dan periode
- `payment method -> finance account` menjadi mapping yang menentukan payment masuk ke account reconciliation yang mana

Artinya:
- status `reconciled` seharusnya tidak lagi dianggap sekadar checkbox manual di payment
- source of truth reconciliation bergerak ke sesi reconciliation formal di `finance`
- struktur ini disiapkan agar nanti import mutasi bank dan matching otomatis bisa ditambah tanpa bongkar model dasar lagi

### Jika payment method dipetakan ke finance account
Maka:
- payment method itu dianggap masuk ke account kas/bank/e-wallet yang dipilih
- sesi reconciliation untuk account tersebut akan menarik candidate payment berdasarkan mapping itu
- payment yang tidak punya mapping account belum bisa ikut alur reconciliation formal account-based

### Jika sesi reconciliation dibuat
Maka:
- user memilih `finance_account`, periode, dan `statement ending balance`
- sistem menghitung `book closing balance` dari cashbook finance account sampai akhir periode
- sistem menampilkan candidate payment posted yang method-nya dipetakan ke account tersebut
- saat sesi di-complete, item reconciliation disimpan formal dan payment terpilih ditandai `reconciled`

Artinya:
- reconciliation sekarang punya jejak audit per sesi, bukan hanya status tunggal di payment
- desain tabel disiapkan polymorphic agar nanti bisa dilebarkan ke bank statement line atau finance transaction lain

### Jika bank statement diimport ke sesi reconciliation
Maka:
- file CSV/XLSX statement disimpan sebagai batch import
- setiap baris mutasi menjadi `statement line` terpisah
- import dasar sekarang menerima header tanggal yang umum seperti `transaction_date`, `date`, `posting_date`, `book_date`, atau `value_date`
- nilai mutasi bisa dibaca dari `amount` tunggal atau pasangan `debit` / `credit`
- sistem menandai `duplicate candidate` bila menemukan line yang sangat mirip di sesi reconciliation yang sama
- sistem mencoba membuat `suggested match` ke `payment` atau `finance transaction` yang amount-nya sama, lalu menaikkan skor jika arah kas, reference, notes, atau tanggalnya dekat
- suggested match belum mengubah payment sampai user menyelesaikan sesi reconciliation

Artinya:
- import statement adalah source data observasi dari bank
- payment tetap source transaksi operasional
- finance transaction juga bisa menjadi target jika mutasi memang berasal dari cash in/out operasional
- matching hanya menjadi jembatan antara statement line dan source transaksi pada sesi reconciliation yang sedang dikerjakan

### Jika suggested match tidak cocok
Maka:
- user bisa override per `statement line`
- target override saat ini bisa diarahkan ke `payment` atau `finance transaction`
- saat sesi reconciliation diselesaikan, statement line akan ditandai matched ke target yang dipilih user

Artinya:
- mutasi bank yang bukan payment murni tidak harus dipaksa cocok ke payment
- flow exception dasar sudah mulai ada untuk mutasi seperti cash in/out finance yang berasal dari modul `finance`

## Hubungan Dengan Tax Register Indonesia
Tax master dan tax register sekarang dipisahkan per peran:
- `finance_tax_rates` tetap menjadi master rule pajak
- `finance_tax_documents` menjadi register formal dokumen pajak per transaksi

Artinya:
- tax master tidak dipaksa menyimpan lifecycle dokumen
- sale atau purchase tetap owner transaksi dagang
- register pajak menjadi layer formal yang bisa diperluas ke e-Faktur atau export pajak nanti

### Jika tax master dipakai untuk Indonesia
Maka:
- tax master bisa membawa `tax_scope` seperti `PPN keluaran`, `PPN masukan`, atau `PPh`
- tax master bisa menyimpan metadata legal seperti `legal_basis`, `document_label`, dan kebutuhan nomor dokumen pajak
- tax master juga bisa menandai apakah NPWP/NIK lawan transaksi wajib ada

Artinya:
- struktur master tidak lagi hanya tarif dan akun
- sistem mulai siap menampung aturan perpajakan Indonesia tanpa mengubah boundary dokumen sumber

### Jika tax register dibuat dari sale atau purchase
Maka:
- user bisa memilih source document dari sale finalized atau purchase confirmed yang punya `tax_total`
- taxable base dan tax amount dasar dapat mengikuti dokumen sumber
- snapshot nama partner, NPWP, nama pajak, dan alamat pajak bisa ikut ditarik ke register

Artinya:
- register pajak menjadi jejak formal yang stabil walaupun master contact berubah setelahnya
- ini penting untuk faktur pajak, bukti potong, atau export perpajakan di fase berikutnya

### Jika sale atau purchase di-finalize dan punya tax
Maka:
- sistem sekarang mencoba membuat atau memperbarui `tax register` otomatis berdasarkan source document
- sale bertax masuk ke register sebagai `PPN keluaran`
- purchase bertax masuk ke register sebagai `PPN masukan`
- jika source document yang sama sudah pernah punya tax register, data register akan di-`update`, bukan dibuat dobel

Artinya:
- user tidak harus input register pajak manual untuk transaksi umum setiap kali finalize
- source of truth tetap ada di transaksi penjualan / pembelian, lalu tax register menjadi layer formal turunannya

### Jika tax register berubah dari `draft` ke status formal
Maka:
- jika `document_number` masih kosong, sistem sekarang mencoba generate nomor dokumen pajak otomatis
- numbering mengikuti fondasi `document_numbering_rules` yang sama dengan dokumen lain
- jika belum ada rule khusus, sistem memakai fallback sequence tax document internal per company/branch scope dan periode bulan

Artinya:
- tax register draft tetap fleksibel saat data perpajakan belum lengkap
- saat dokumen dianggap formal, identitas nomornya menjadi stabil dan siap dipakai untuk export atau proses lanjutan
- fallback sequence memakai scope key eksplisit agar unique constraint tetap aman di PostgreSQL / Supabase walaupun `branch_id` nullable

### Jika tax register bertipe PPh / withholding
Maka:
- document type bisa memakai `withholding`
- user bisa mengisi `withheld_amount` terpisah dari `tax_amount`
- tax master bisa diarahkan ke akun withholding khusus

Artinya:
- fondasi PPh dasar sudah ada
- automation jurnal dan export PPh masih bisa ditambahkan di batch berikutnya tanpa bentrok struktur

### Jika tax register mau diexport
Maka:
- sistem sekarang menyediakan `Export Register CSV` untuk register internal dan review operasional
- sistem juga menyediakan `Draft e-Faktur CSV` untuk dokumen `PPN keluaran`
- export e-Faktur saat ini masih draft structure, belum dianggap format final integrasi resmi

Artinya:
- tim finance sudah bisa mulai bekerja dengan struktur data export yang stabil
- refinement mapping kolom e-Faktur resmi masih bisa dilakukan di atas fondasi yang sama tanpa bongkar tax register

### Jika sistem menandai `duplicate candidate`
Maka:
- line itu tetap disimpan sebagai statement line biasa
- status line tidak otomatis dipaksa menjadi `exception`
- user tetap harus memutuskan apakah line itu valid, harus di-ignore, atau memang duplikat import

Artinya:
- sistem membantu memberi sinyal risiko import ganda
- keputusan final tetap ada di user finance agar tidak salah membuang mutasi yang kebetulan mirip

### Jika statement line memang tidak mau dimatch dulu
Maka:
- user bisa menandai line sebagai `exception` atau `ignored`
- user dapat menyimpan `resolution_reason` dan catatan singkat
- line itu tidak dipaksa ikut proses `complete reconciliation` sebagai matched item

Artinya:
- tim finance punya cara formal untuk menandai line bermasalah, duplikat, biaya bank, atau item investigasi
- status `unmatched` tidak lagi menjadi tempat semua kasus exception bercampur tanpa konteks

## Laba Rugi Saat Ini
Finance report sekarang membaca:
- revenue dari transaksi/journal yang relevan
- COGS aktual dari akun `COGS` di GL jika sudah ada
- fallback ke estimasi jika COGS aktual belum tersedia

Jadi:
- bila sale stockable sudah finalize dengan `inventory_location_id`, laba rugi bisa memakai COGS aktual
- bila belum, report masih bisa fallback ke estimasi snapshot

## Neraca Saat Ini
Balance sheet di report advanced sekarang:
- membaca saldo akun dari posted journal / trial balance
- mencoba memakai metadata `Chart of Accounts` bila tersedia
- memakai parent COA sebagai grouping report bila parent sudah disusun
- menampilkan subtotal per group agar struktur neraca lebih mudah direview
- membawa `current earnings` periode aktif ke equity sementara jika akun retained earnings formal belum ada
- bisa diexport ke CSV bersama trial balance dan general ledger

Artinya:
- neraca sekarang lebih dekat ke struktur accounting formal daripada sekadar heuristic kode akun
- jika periode sudah di-closing, retained earnings berasal dari journal closing permanen
- jika periode belum di-closing, current earnings masih dibawa sementara agar neraca provisional tetap terbaca
- reopen/reverse closing governance belum lengkap, jadi period closing harus dipakai hati-hati oleh user finance

## Period Closing Saat Ini
Period closing awal sekarang:
- membaca saldo akun laba/rugi dari posted journal sesuai periode
- memakai metadata COA `profit_loss` bila tersedia
- memakai fallback kode akun untuk data lama seperti `SALES`, `COGS`, `PURCHASES`, `SALES_DISC`, dan akun nominal lain yang umum
- membuat journal `period_closing`
- menutup akun revenue dan expense ke `RETAINED_EARNINGS`
- membuat period lock otomatis untuk periode yang sudah ditutup

Artinya:
- retained earnings tidak hanya simulasi report lagi setelah closing dibuat
- journal closing menjadi bukti formal perpindahan laba/rugi periode ke equity
- company-level closing mencakup semua branch, sedangkan branch-level closing hanya mencakup branch yang dipilih
- sistem mencegah closing periode yang sama pada scope company/branch yang konflik

## Rekonsiliasi Inventory vs GL
Finance report sekarang punya panel rekonsiliasi:
- inventory valuation dari `inventory_stocks.inventory_value`
- dibandingkan dengan saldo akun `INVENTORY` dari posted journal

Tujuan panel ini:
- melihat apakah valuation inventory dan GL masih selaras
- membantu mendeteksi gap implementasi atau transaksi yang belum mem-post inventory side secara lengkap

Catatan:
- ini masih rekonsiliasi agregat
- sekarang sudah ada detail per source document di finance report untuk membandingkan efek movement inventory vs impact akun `INVENTORY` di GL
- source yang belum punya impact GL inventory akan terlihat sebagai gap atau `Missing GL`
- gap ini membantu melihat area yang costing/accounting-nya belum lengkap seperti opening stock, stock adjustment, sales return, atau flow procurement tertentu
- purchase receipt yang berhasil diposting sekarang seharusnya tidak lagi muncul sebagai `Missing GL` untuk sisi inventory receipt

## Kapan `inventory_location_id` Harus Diisi
Isi `inventory_location_id` jika:
- sale menjual barang stockable
- Anda ingin stok benar-benar berkurang
- Anda ingin COGS aktual tercatat
- transaksi harus masuk ke alur accounting persediaan

Tidak wajib diisi jika:
- sale hanya jasa / non-stockable
- transaksi belum mau memengaruhi inventory
- flow bisnis memang belum siap menentukan gudang sumber

## Risiko Jika `inventory_location_id` Tidak Jelas
Kalau location tidak jelas, sistem bisa salah dalam:
- mengurangi gudang yang salah
- memakai average cost yang salah
- menghitung HPP yang salah
- membuat inventory vs GL mismatch

Karena itu aturan yang benar adalah:
- lebih baik lokasi eksplisit
- jangan mengandalkan tebakan gudang tanpa rule yang jelas

## Status Saat Ini
Sudah ada:
- draft sale
- finalize sale
- journal revenue
- inventory movement out dari sale ber-location
- journal COGS dari movement inventory
- void sale yang membalik revenue, COGS, dan restore stock
- finance report yang membaca COGS aktual dari GL
- inventory vs GL reconciliation agregat

Belum lengkap:
- enforcement yang lebih keras agar semua sale stockable wajib punya `inventory_location_id`
- drill-down reconciliation per document
- reversal journal formal dari layar finance umum untuk kasus kompleks lintas dokumen masih bisa diperdalam, tetapi reversal posted journal dasar sudah tersedia dari layar finance
- costing formal untuk seluruh edge case procurement/return/adjustment
- document flow quotation / sales order / purchase order
- import mutasi bank dan auto matching untuk reconciliation formal
- matching manual per line, matching ke finance transaction non-payment dasar, alias header import bank umum, duplicate candidate dasar, dan exception status dasar sudah mulai ada, tetapi exception flow lanjutan masih perlu diperdalam

## PostgreSQL / Supabase Notes
Untuk area fitur accounting yang dijelaskan di dokumen ini:
- query baru memakai query builder Laravel biasa
- tidak menambah SQL vendor-specific baru
- aman untuk PostgreSQL/Supabase production

Namun repo secara umum masih memiliki beberapa migration lama yang bersifat MySQL-specific.
Itu adalah concern terpisah dari logic accounting operasional di dokumen ini.

## Ringkasan Sederhana
- draft sale: belum mengubah stok, belum membuat accounting final
- finalize sale tanpa location: membuat revenue journal saja
- finalize sale dengan `inventory_location_id`: membuat revenue journal + stock out + COGS journal
- void sale: membalik revenue, membalik COGS, dan mengembalikan stock bila movement sebelumnya ada
- finance journal posted sekarang bisa direverse dari layar journal detail; sistem membuat journal reversal baru, menyimpan alasan, menghubungkan ke journal asal, dan tidak menghapus journal lama
- purchase finalized mengakui payable ke `AP`, lalu purchase receipt memindahkan nilai receipt aktual dari `PURCHASES` ke `INVENTORY`
- reconciliation account sekarang dimulai dari sesi formal di `finance`; payment method harus dipetakan ke finance account agar payment bisa ikut direkonsiliasi
- statement import sekarang masuk ke sesi reconciliation yang sama; sistem bisa membaca beberapa variasi header bank umum, memberi suggested match awal ke payment atau finance transaction, dan menandai kandidat duplikat
- jika suggested match salah, user sekarang bisa override line ke payment lain atau ke finance transaction yang relevan sebelum sesi diselesaikan
- reports membaca hasil transaksi/journal, bukan menjadi owner logika bisnis
