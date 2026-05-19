# Accounting Operational Flow

Dokumen ini menjelaskan cara kerja accounting operasional yang saat ini hidup di modul existing:
- `sales`
- `payments`
- `finance`
- `inventory`
- `reports`

Dokumen ini bukan roadmap. Fokusnya adalah perilaku sistem yang sedang berlaku sekarang, boundary antar modul, dan aturan praktis "jika ini maka ini".

---

## Tujuan

- Menjelaskan alur transaksi accounting yang benar-benar dipakai aplikasi saat ini.
- Mengurangi kebingungan di area seperti `inventory_location_id`, HPP / COGS, stock movement, dan journal posting.
- Menjadi pegangan implementasi sebelum domain accounting formal dipisah menjadi modul tersendiri.

---

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

---

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
- chart of accounts
- finance account (cashbook)
- finance category
- finance transaction
- draft vs posted
- period lock
- period closing
- approval sensitif awal
- tax master
- tax register
- bank reconciliation

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

---

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

### Moving Average

Moving average adalah biaya rata-rata per unit pada stock balance aktif di satu lokasi tertentu.

Dipakai untuk:
- menghitung `unit_cost` movement keluar
- menghitung `movement_value` saat stok keluar
- menjadi basis jurnal HPP / COGS

### COGS / HPP

COGS adalah nilai biaya barang yang terjual (Harga Pokok Penjualan).

Di sistem saat ini:
- COGS untuk sale stockable diambil dari movement inventory keluar
- bukan sekadar `product.cost_price` snapshot

### Chart of Accounts (COA)

Chart of Accounts adalah daftar formal seluruh akun buku besar (general ledger) yang dipakai perusahaan dalam sistem accounting.

Setiap akun COA punya:
- **Kode akun** — identifier unik seperti `SALES`, `AR`, `INVENTORY`, atau kode custom perusahaan
- **Nama akun** — label deskriptif
- **Tipe akun** — `asset`, `liability`, `equity`, `revenue`, atau `expense`
- **Normal balance** — `debit` atau `credit` sesuai sifat akun
- **Report section** — apakah akun masuk ke neraca (`balance_sheet`) atau laba rugi (`profit_loss`)
- **Parent akun** — untuk pengelompokan hierarki di laporan
- **Is postable** — apakah akun bisa langsung dipakai di journal line

Fungsi COA dalam sistem:
- mengontrol struktur laporan keuangan (neraca, laba rugi)
- menentukan cara period closing membaca nominal accounts
- menjadi pilihan saat input manual journal
- menjadi basis rekonsiliasi inventory vs GL

### Finance Account (Akun Cashbook)

Finance Account adalah akun operasional kas/bank/e-wallet yang dikelola di modul `finance` untuk mencatat arus kas harian bisnis.

Finance Account berbeda dari Chart of Accounts:
- COA adalah struktur buku besar formal untuk keperluan journal GL
- Finance Account adalah rekening operasional yang dipakai mencatat transaksi cash in/out harian di cashbook

Setiap Finance Account dipetakan ke satu akun di COA agar arus kas bisa ikut rekonsiliasi GL.

Contoh: akun "Kas Kantor Pusat" dipetakan ke COA `CASH`, akun "BCA Giro" dipetakan ke COA `BANK`.

### Finance Category

Finance Category adalah klasifikasi/label yang dipakai untuk mengelompokkan transaksi cashbook (finance transaction) berdasarkan tujuan bisnis.

Contoh kategori: "Gaji", "Biaya Operasional", "Pembelian ATK", "Pendapatan Lain-lain".

Finance Category tidak secara langsung memengaruhi akun GL — itu hanya penanda operasional untuk mempermudah filtering dan laporan internal.

### Finance Transaction

Finance Transaction adalah pencatatan arus kas masuk atau keluar yang diinput secara manual di cashbook finance.

Finance Transaction berbeda dari Auto Journal:
- Auto Journal dibuat sistem secara otomatis dari event transaksi (finalize sale, finalize purchase, dll)
- Finance Transaction adalah pencatatan manual oleh staf finance untuk transaksi kas yang tidak berasal dari sales/purchase, seperti: pembayaran operasional kantor, penarikan kas, atau penerimaan non-sale

### Period Lock

Period Lock adalah kunci akuntansi yang mencegah journal baru diposting pada rentang tanggal tertentu.

Period lock dipakai untuk:
- melindungi periode yang sudah selesai dari perubahan tidak sengaja
- memastikan laporan keuangan periode lampau tidak berubah setelah diterbitkan
- menjadi prasyarat sebelum menutup periode secara formal

Period Lock bisa:
- dibuat manual oleh user finance dengan akses `finance.manage-period-locks`
- dibuat otomatis oleh sistem saat period closing dijalankan
- dilepas (release) jika memang perlu koreksi di periode lama

### Period Closing

Period Closing adalah proses formal menutup akun laba/rugi pada akhir periode (bulan, kuartal, atau tahun) dengan memindahkan saldo net income ke akun Retained Earnings.

Period Closing melakukan:
1. Membaca semua akun revenue dan expense dari posted journal periode tersebut
2. Menghitung net income (total revenue dikurangi total expense)
3. Membuat journal `period_closing` yang menutup semua akun nominal ke `RETAINED_EARNINGS`
4. Membuat Period Lock otomatis untuk periode yang sudah ditutup

Setelah Period Closing:
- akun revenue dan expense periode tersebut tereset ke nol (secara accounting)
- net income sudah tersimpan permanen di `RETAINED_EARNINGS`
- balance sheet bisa membaca equity aktual, bukan hanya estimasi current earnings

### Bank Reconciliation

Bank Reconciliation adalah proses mencocokkan saldo kas/bank yang tercatat di sistem dengan mutasi yang sebenarnya terjadi di rekening bank (berdasarkan statement bank).

Tujuannya: memastikan tidak ada selisih antara buku internal sistem dan catatan bank eksternal.

### Tax Register (Tax Document)

Tax Register adalah register formal dokumen pajak per transaksi — terpisah dari transaksi penjualan/pembelian itu sendiri.

Tax Register dipakai untuk:
- mencatat nomor faktur pajak (Faktur Pajak Keluaran / Masukan)
- menyimpan data identitas perpajakan lawan transaksi (NPWP/NIK)
- menjadi basis export e-Faktur atau laporan perpajakan

---

## Chart of Accounts — Cara Kerja

### Cara membuat akun COA baru

1. Buka **Finance → Chart of Accounts**.
2. Sistem otomatis menyediakan akun default saat halaman pertama kali dibuka (provisioning awal).
3. Klik **Tambah Akun** untuk membuat akun baru.
4. Isi:
   - **Kode** — kode unik akun (huruf besar disarankan, contoh: `CASH`, `AR`, `SALES`)
   - **Nama** — nama akun dalam bahasa Indonesia atau Inggris
   - **Tipe Akun** — pilih sesuai sifat: `asset`, `liability`, `equity`, `revenue`, `expense`
   - **Normal Balance** — biasanya `debit` untuk asset/expense, `credit` untuk liability/equity/revenue
   - **Report Section** — `balance_sheet` untuk neraca, `profit_loss` untuk laba rugi
   - **Parent** — opsional, untuk pengelompokan hierarki di laporan
   - **Is Postable** — centang jika akun ini bisa dipakai di journal line
5. Simpan.

### Aturan penghapusan akun COA

Akun COA tidak bisa dihapus jika:
- masih punya child account di bawahnya
- sudah pernah dipakai di journal line manapun

Jika salah satu kondisi di atas terpenuhi, sistem akan menolak penghapusan dan memunculkan pesan error.

---

## Finance Account (Cashbook) — Cara Kerja

### Cara membuat Finance Account baru

1. Buka **Finance → Akun Keuangan**.
2. Klik **Tambah Akun**.
3. Isi nama akun (misal: "Kas Kecil Pusat", "BCA Giro Operasional").
4. Pilih **akun COA** yang sesuai sebagai mapping ke general ledger.
5. Simpan.

Finance Account yang sudah dipetakan ke COA akan menjadi kandidat di sesi reconciliation bank.

---

## Finance Categories — Cara Kerja

### Cara membuat kategori baru

1. Buka **Finance → Kategori Transaksi**.
2. Klik **Tambah Kategori**.
3. Isi nama kategori dan tipe (`income` atau `expense`).
4. Simpan.

Kategori dipakai saat input Finance Transaction untuk menandai tujuan transaksi.

---

## Finance Transaction / Cashbook — Cara Kerja

Finance Transaction adalah pencatatan manual arus kas di cashbook yang tidak berasal dari sales atau purchase.

### Cara mencatat transaksi kas manual

1. Buka **Finance → Transaksi Keuangan**.
2. Klik **Tambah Transaksi**.
3. Isi:
   - **Tanggal transaksi**
   - **Tipe** — `income` (kas masuk) atau `expense` (kas keluar)
   - **Akun keuangan** — pilih Finance Account yang terlibat
   - **Kategori** — pilih Finance Category sesuai tujuan
   - **Jumlah**
   - **Keterangan** (opsional)
   - **Bukti transaksi** (lampiran, opsional)
4. Simpan.

### Cashbook view

Halaman **Finance → Cashbook** menampilkan ringkasan transaksi per akun keuangan, lengkap dengan saldo awal, mutasi, dan saldo akhir untuk rentang tanggal yang dipilih.

### Catatan penting

- Finance Transaction yang diinput manual juga bisa menjadi target matching di sesi bank reconciliation, karena kadang mutasi bank berasal dari kas keluar operasional yang diinput lewat cashbook, bukan dari payment modul `payments`.
- Period lock aktif akan memblokir finance transaction yang tanggalnya jatuh di periode yang sudah dikunci.

---

## Manual Journal — Cara Kerja

Manual Journal adalah jurnal akuntansi yang dibuat secara manual oleh staf finance, bukan oleh otomasi sistem.

Dipakai untuk:
- koreksi kesalahan posting
- jurnal penyesuaian akhir periode (accrual, prepaid, depresiasi)
- jurnal yang tidak bisa di-generate otomatis dari transaksi operasional

### Cara membuat manual journal

1. Buka **Finance → Jurnal Akuntansi**.
2. Klik **Buat Manual Journal**.
3. Isi:
   - **Tanggal entri** — tanggal efektif jurnal
   - **Deskripsi** — keterangan singkat tujuan jurnal
   - **Branch** (opsional) — jika jurnal spesifik satu cabang
   - **Baris jurnal** — tambahkan minimal dua baris, satu debit satu kredit; total debit harus sama dengan total kredit
4. Pilih status:
   - **Draft** — disimpan tapi belum berdampak ke GL
   - **Posted** — langsung diposting ke GL (tidak bisa diedit lagi)
5. Jika disimpan sebagai Draft, buka detail jurnal tersebut lalu klik **Post** untuk memposting ke GL.

### Aturan manual journal

- Journal berstatus `draft` bisa diedit.
- Journal berstatus `posted` tidak bisa diedit — hanya bisa di-reverse.
- Total debit dan kredit di semua baris harus balance sebelum bisa diposting.
- Journal type `period_closing` tidak dibuat manual — hanya dibuat otomatis oleh sistem saat Period Closing dijalankan.

---

## Journal Reversal — Cara Kerja

Journal Reversal adalah fitur untuk membatalkan efek sebuah journal yang sudah posted, dengan cara membuat jurnal baru yang membalik seluruh baris (debit jadi kredit, kredit jadi debit).

Reversal tidak menghapus journal lama — journal asal tetap ada dengan status `posted`, dan journal reversal baru dibuat sebagai dokumen terpisah yang terhubung ke journal asal.

### Kapan reversal dipakai

- Koreksi jurnal manual yang salah diposting
- Pembatalan pencatatan yang tidak seharusnya ada
- Penyesuaian periode (misalnya: jurnal accrual bulan lalu perlu dibalik di bulan baru)

### Cara mereverse journal

1. Buka **Finance → Jurnal Akuntansi**.
2. Klik journal yang ingin di-reverse (status harus `posted`).
3. Di halaman detail journal, klik **Reverse Journal**.
4. Isi:
   - **Tanggal reversal** — tanggal efektif journal reversal (boleh berbeda dari journal asal)
   - **Alasan reversal** — wajib diisi, tercatat sebagai audit trail
5. Konfirmasi.
6. Sistem membuat journal baru dengan tipe `reversal`, membalik semua baris, dan menghubungkannya ke journal asal.

### Catatan reversal

- Journal auto (dari sale, purchase, opname, dll) juga bisa di-reverse, tapi disarankan hati-hati karena efek inventory/payment tidak otomatis ikut dibalik.
- Journal `period_closing` tidak bisa di-reverse dari layar journal langsung — harus melalui alur reopen period yang terpisah (belum sepenuhnya ada saat ini).
- Setelah di-reverse, journal asal tetap terlihat di daftar journal dengan penanda bahwa sudah ada reversalnya.

---

## Accounting Approval — Cara Kerja

Accounting Approval adalah mekanisme persetujuan formal sebelum transaksi tertentu bisa difinalisasi. Dirancang agar transaksi sensitif tidak bisa langsung posting tanpa review atasan finance.

### Dokumen yang bisa diwajibkan approval

- Sale (finalize)
- Purchase (finalize)
- Quotation (convert)
- Sales Order (convert)
- Purchase Request (convert)
- Purchase Order (convert)

Pengaturan per dokumen dikontrol di **Settings dokumen** di level company atau branch.

### Cara kerja approval

1. Jika rule approval aktif untuk dokumen tersebut, aksi finalize/convert akan membuat **Approval Request** alih-alih langsung memproses.
2. User dengan permission `finance.approve-sensitive-transactions` membuka **Finance → Approvals**.
3. Layar approvals menampilkan semua permintaan yang pending.
4. Approver bisa:
   - **Approve** — dokumen dilanjutkan ke proses finalize/convert
   - **Reject** — dokumen dikembalikan dengan alasan penolakan
5. Setelah di-approve, dokumen diproses oleh sistem seperti biasa.

### Jika approval matrix lintas modul dipakai

Maka:
- finance bisa membuat `approval matrix rule` per `module + action`
- rule bisa dibedakan per company level atau branch tertentu
- rule bisa memakai `min_amount` sebagai threshold nominal
- rule bisa meminta lebih dari satu approver lewat `required_approvals`
- setiap keputusan approver disimpan terpisah sebagai jejak audit

Artinya:
- approval tidak lagi hanya model `ya/tidak` global
- transaksi dengan nominal lebih besar bisa meminta approver lebih banyak
- flow approval mulai bisa dipakai lintas `sales`, `purchases`, `payments`, dan `finance transaction`

### Jika maker-checker formal dipakai

Maka:
- rule approval matrix bisa menandai `maker_checker_required`
- maker transaksi atau maker dokumen sensitif tidak boleh mengaplikasikan aksi sendiri setelah approval
- aksi sensitif seperti `post manual journal`, `reverse journal`, `reopen period closing`, `void`, dan edit/delete transaction dapat membawa `maker ids`
- jika rule meminta maker-checker, request boleh disetujui checker, tetapi aksi final harus dijalankan user yang berbeda dari maker
- rule juga bisa membawa `max_backdate_days` agar aksi pada tanggal terlalu lama masuk jalur governance yang lebih ketat

Artinya:
- approval tidak lagi hanya persetujuan administratif
- separation of duties dasar mulai berlaku untuk aksi accounting yang paling berisiko
- fondasi control backdate dan control posting sudah mulai masuk tanpa membongkar struktur approval yang ada

### Catatan penting

- Requirement approval tidak hardcoded untuk semua tenant.
- Setiap tenant bisa memilih dokumen mana yang butuh approval formal.
- Branch bisa override rule company jika operasional cabang membutuhkan aturan berbeda.
- jika tidak ada rule matrix yang cocok, flow lama tetap berlaku
- jika ada rule matrix yang cocok, request harus memenuhi jumlah approver minimum
- peminta request tidak boleh menyetujui request miliknya sendiri
- assignment approver per role/jabatan khusus belum lengkap; fondasi saat ini fokus pada threshold nominal, jumlah approver, dan audit keputusan
- maker-checker saat ini masih fondasi dasar; auto-routing checker per jabatan/role khusus belum lengkap

---

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

---

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
- kenaikan stock: debit `INVENTORY` vs credit `INV_ADJ_GAIN`
- penurunan stock: debit `INV_ADJ_LOSS` vs credit `INVENTORY`
- jika adjustment berasal dari stock opname, metadata journal dan movement membawa `source_opname_id` serta `source_opname_code`

Artinya:
- finalize stock adjustment sekarang berdampak ke stok dan GL sekaligus
- adjustment tidak lagi terlihat sebagai `Missing GL` selama journal berhasil diposting
- audit trail bisa ditarik dari stock opname ke adjustment, movement, lalu journal accounting

### Jika stock opname di-finalize

Maka:
- sistem wajib memastikan semua item sudah punya stock fisik
- sistem mengecek period lock pada tanggal opname walaupun tidak ada selisih
- sistem menghitung ulang final system quantity saat finalize, bukan hanya memakai snapshot awal
- jika ada selisih, sistem membuat stock adjustment otomatis dengan source `stock_opname`
- jika tidak ada selisih, opname tetap finalized tanpa movement/journal dan menyimpan audit summary

Artinya:
- stock opname final tanpa selisih tetap punya status audit yang jelas
- stock opname dengan selisih punya jalur formal `opname -> adjustment -> movement -> journal`
- user bisa membuka adjustment dan journal dari detail opname

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

### Jika stock transfer dikirim

Maka:
- inventory membuat movement `transfer_out` dari source location
- valuation keluar memakai average cost source location
- finance membuat journal `inventory_transfer_out`
- debit `INVENTORY_IN_TRANSIT`
- credit `INVENTORY`

Artinya:
- nilai barang tidak langsung hilang dari neraca saat keluar dari gudang asal
- barang yang sedang dalam perjalanan tetap terlihat sebagai asset `Inventory In Transit`
- jika transfer dikirim di periode berbeda dari penerimaan, rekonsiliasi inventory vs GL tetap punya jejak formal

### Jika stock transfer diterima

Maka:
- inventory membuat movement `transfer_in` ke destination location
- unit cost mengikuti movement transfer out bila tersedia
- finance membuat journal `inventory_transfer_in`
- debit `INVENTORY`
- credit `INVENTORY_IN_TRANSIT`

Artinya:
- nilai barang kembali dari in transit ke inventory fisik saat diterima gudang tujuan
- total inventory company tetap terjaga, tetapi posisi antar location tetap bisa diaudit

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

---

## Period Lock — Cara Kerja

### Cara membuat period lock manual

1. Buka **Finance → Period Locks**.
2. Klik **Tambah Lock**.
3. Isi:
   - **Tanggal mulai** — awal rentang yang dikunci
   - **Tanggal akhir** — akhir rentang yang dikunci
   - **Branch** — opsional; jika kosong, lock berlaku untuk seluruh company
   - **Catatan** — alasan penguncian
4. Simpan.

### Cara melepas period lock

1. Buka **Finance → Period Locks**.
2. Klik tombol **Hapus / Release** pada lock yang ingin dilepas.
3. Lock dilepas dan rentang tanggal tersebut kembali bisa menerima posting journal.

### Kapan period lock dibuat otomatis

Period lock dibuat otomatis oleh sistem saat **Period Closing** dijalankan. Artinya, setelah closing berhasil, periode tersebut langsung terkunci tanpa perlu input manual.

### Aturan period lock

- Satu periode bisa punya lebih dari satu lock jika rentang berbeda atau scope berbeda (company vs branch).
- Period lock di level company akan memblokir semua branch di company tersebut.
- Period lock di level branch hanya memblokir branch yang bersangkutan.
- Transaksi apapun (sale, purchase, journal, finance transaction) yang tanggalnya jatuh di rentang terkunci akan ditolak sistem.

---

## Period Closing — Cara Kerja

### Prasyarat sebelum period closing

- Pastikan semua transaksi periode tersebut sudah finalized dan journal-nya posted.
- Pastikan tidak ada period lock aktif yang overlap dengan periode yang ingin ditutup (jika ada, lepas dulu period lock tersebut sebelum closing).
- Pastikan akun `RETAINED_EARNINGS` sudah ada di Chart of Accounts.

### Cara menjalankan period closing

1. Buka **Finance → Period Closings**.
2. Klik **Closing Periode Baru**.
3. Isi:
   - **Tanggal mulai periode** — awal bulan/kuartal/tahun yang ingin ditutup
   - **Tanggal akhir periode** — akhir periode
   - **Branch** — opsional; jika kosong, closing berlaku untuk seluruh company (mencakup semua branch)
   - **Catatan** — alasan atau keterangan closing
4. Klik **Proses Closing**.
5. Sistem akan:
   - Membaca semua akun revenue dan expense dari posted journal periode tersebut
   - Menghitung net income
   - Membuat journal `period_closing` yang menutup semua nominal accounts ke `RETAINED_EARNINGS`
   - Membuat period lock otomatis untuk rentang tersebut

### Aturan period closing

- Satu scope (company atau branch tertentu) tidak bisa di-closing dua kali untuk periode yang sama.
- Jika sudah ada period lock aktif yang overlap, sistem akan menolak dan meminta period lock dilepas dulu.
- Jika tidak ada akun laba/rugi yang memiliki saldo di periode tersebut, sistem akan menolak dengan pesan "tidak ada akun nominal yang perlu di-closing".
- Company-level closing mencakup semua branch; branch-level closing hanya mencakup branch yang dipilih.
- Setelah period closing berhasil, periode langsung terkunci dan tidak bisa diposting journal baru.

### Jika period closing perlu di-reopen

Maka:
- user finance bisa menjalankan aksi `reopen` dari daftar period closings
- sistem akan `release` auto period lock milik closing tersebut lebih dulu
- sistem membuat `reversal journal` untuk closing journal dengan alasan reopen
- record closing tetap disimpan, tetapi status berubah menjadi `reopened`
- audit trail menyimpan `reopened_at`, `reopened_by`, alasan reopen, dan link ke reversal journal

Artinya:
- reopen tidak menghapus bukti closing lama
- retained earnings dari closing dibalik secara formal di GL
- periode bisa dibuka kembali dengan jejak audit yang jelas, bukan lewat ubah data diam-diam

### Apa yang terjadi pada neraca setelah closing

- Sebelum closing: balance sheet membawa `current earnings` dari akun laba/rugi sebagai estimasi sementara di sisi equity.
- Setelah closing: retained earnings sudah berasal dari journal closing formal, saldo akun nominal sudah nol untuk periode tersebut.

### Catatan penting

- Reopen period closing sekarang hanya berlaku untuk closing yang masih punya closing journal asli dan belum pernah direverse sebelumnya.
- Jika ada period lock lain yang masih overlap setelah auto lock closing dilepas, reversal reopen tetap akan ditolak sampai lock lain itu ditangani.
- Period closing tetap harus dijalankan hati-hati; reopen adalah jalur koreksi formal, bukan pengganti disiplin closing.

---

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

---

## Hubungan Dengan Payments

Payment tidak membuat COGS.

Payment berpengaruh ke:
- allocation ke sale / payable lain
- paid total
- balance due
- payment journal sesuai flow payment

Tetapi:
- payment bukan sumber valuation inventory

---

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

### Cara membuat sesi reconciliation

1. Buka **Finance → Rekonsiliasi Bank**.
2. Klik **Buat Sesi Rekonsiliasi**.
3. Isi:
   - **Finance Account** — pilih akun kas/bank yang ingin direkonsiliasi
   - **Periode** — tanggal mulai dan akhir rekonsiliasi
   - **Statement Ending Balance** — saldo akhir sesuai statement bank
4. Simpan. Sistem akan menghitung `book closing balance` dari cashbook finance account sampai akhir periode dan menampilkan candidate payment yang method-nya dipetakan ke account tersebut.

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

---

## Hubungan Dengan Tax Register Indonesia

Tax master dan tax register sekarang dipisahkan per peran:
- `finance_tax_rates` tetap menjadi master rule pajak
- `finance_tax_documents` menjadi register formal dokumen pajak per transaksi

Artinya:
- tax master tidak dipaksa menyimpan lifecycle dokumen
- sale atau purchase tetap owner transaksi dagang
- register pajak menjadi layer formal yang bisa diperluas ke e-Faktur atau export pajak nanti

### Tax Master — Cara membuat tax rate baru

1. Buka **Finance → Pajak (Tax Rates)**.
2. Klik **Tambah Pajak**.
3. Isi:
   - **Nama pajak** — misal "PPN 11%", "PPh 23 2%"
   - **Rate (%)** — tarif pajak
   - **Tipe kalkulasi** — `exclusive` (pajak di luar harga) atau `inclusive` (pajak sudah termasuk harga)
   - **Tax Scope** — `PPN keluaran`, `PPN masukan`, `PPh`, atau `withholding`
   - **Akun debit/kredit** — akun GL yang dipakai untuk jurnal pajak
   - **Legal basis** — dasar hukum (opsional, untuk referensi)
   - **Wajib NPWP/NIK** — centang jika transaksi menggunakan pajak ini wajib punya identitas perpajakan lawan transaksi
4. Simpan.

### Tax Register — Cara membuat manual

Tax register biasanya dibuat otomatis saat sale/purchase finalized. Jika perlu membuat manual:

1. Buka **Finance → Tax Register**.
2. Klik **Tambah Dokumen Pajak**.
3. Isi:
   - **Source document** — pilih sale finalized atau purchase yang punya `tax_total`
   - **Tax rate** — jenis pajak yang berlaku
   - **Taxable base** dan **Tax amount** — diisi otomatis dari source dokumen, bisa dioverride
   - **Nama dan NPWP** lawan transaksi — snapshot identitas
4. Simpan sebagai `draft`.

### Jika tax master dipakai untuk Indonesia

Maka:
- tax master bisa membawa `tax_scope` seperti `PPN keluaran`, `PPN masukan`, atau `PPh`
- tax master bisa menyimpan metadata legal seperti `legal_basis`, `document_label`, dan kebutuhan nomor dokumen pajak
- tax master juga bisa menandai apakah NPWP/NIK lawan transaksi wajib ada

### Jika tax register dibuat dari sale atau purchase

Maka:
- user bisa memilih source document dari sale finalized atau purchase confirmed yang punya `tax_total`
- taxable base dan tax amount dasar dapat mengikuti dokumen sumber
- snapshot nama partner, NPWP, nama pajak, dan alamat pajak bisa ikut ditarik ke register

### Jika sale atau purchase di-finalize dan punya tax

Maka:
- sistem sekarang mencoba membuat atau memperbarui `tax register` otomatis berdasarkan source document
- sale bertax masuk ke register sebagai `PPN keluaran`
- purchase bertax masuk ke register sebagai `PPN masukan`
- jika source document yang sama sudah pernah punya tax register, data register akan di-`update`, bukan dibuat dobel

### Jika tax register berubah dari `draft` ke status formal

Maka:
- jika `document_number` masih kosong, sistem sekarang mencoba generate nomor dokumen pajak otomatis
- numbering mengikuti fondasi `document_numbering_rules` yang sama dengan dokumen lain
- jika belum ada rule khusus, sistem memakai fallback sequence tax document internal per company/branch scope dan periode bulan
- jika tax master mewajibkan nomor pajak, dokumen tidak boleh menjadi formal tanpa `document_number`
- jika tax master mewajibkan NPWP/NIK lawan transaksi, dokumen tidak boleh menjadi formal tanpa `counterparty_tax_id`

### Jika tax register di-cancel atau diganti

Maka:
- status `cancelled` dan `replaced` wajib punya `status_reason`
- status `replaced` wajib menunjuk dokumen pajak formal yang diganti
- sistem menyimpan timestamp lifecycle seperti `issued_at`, `replaced_at`, dan `cancelled_at`
- export register internal ikut membawa nomor dokumen yang diganti dan alasan status
- dokumen formal tidak boleh dikembalikan ke draft
- status terminal `cancelled` dan `replaced` tidak boleh diubah ke status lain

### Jika source sale atau purchase bertax tersinkron ulang

Maka:
- tax register yang masih `draft` boleh di-update dari source document
- tax register yang sudah formal tidak akan di-downgrade atau ditimpa otomatis oleh source sync
- sistem hanya mencatat bahwa sync source dilewati karena register sudah formal

### Jika tax register bertipe PPh / withholding

Maka:
- document type bisa memakai `withholding`
- user bisa mengisi `withheld_amount` terpisah dari `tax_amount`
- tax master bisa diarahkan ke akun withholding khusus

### Jika tax register mau diexport

Maka:
- sistem sekarang menyediakan `Export Register CSV` untuk register internal dan review operasional
- sistem sekarang juga menyediakan `Draft Bukti Potong CSV` untuk dokumen `PPh / withholding`
- sistem juga menyediakan `Draft e-Faktur CSV` untuk dokumen `PPN keluaran`
- export e-Faktur saat ini masih draft structure, belum dianggap format final integrasi resmi

Artinya:
- tim finance sudah bisa mulai bekerja dengan struktur data export yang stabil
- draft bukti potong internal sudah bisa dipakai untuk review direction potong, dasar pengenaan, nilai potong, dan source document

---

## Laba Rugi Saat Ini

Finance report sekarang membaca:
- revenue dari transaksi/journal yang relevan
- COGS aktual dari akun `COGS` di GL jika sudah ada
- fallback ke estimasi jika COGS aktual belum tersedia

Jadi:
- bila sale stockable sudah finalize dengan `inventory_location_id`, laba rugi bisa memakai COGS aktual
- bila belum, report masih bisa fallback ke estimasi snapshot

---

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

---

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
- stock transfer out/in sekarang ikut rekonsiliasi karena sudah punya journal inventory/in-transit formal
- stock opname yang membuat adjustment sekarang punya metadata source agar audit trail ke journal lebih jelas

---

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

---

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
- chart of accounts dengan provisioning default dan hierarki parent
- finance account (cashbook) dengan mapping ke COA
- finance category untuk klasifikasi transaksi operasional
- finance transaction / cashbook manual
- manual journal (draft, post, edit draft)
- journal reversal formal dengan alasan dan link ke journal asal
- accounting approval per tipe dokumen, configurable per tenant/branch
- period lock manual dan release
- period closing otomatis dengan journal closing dan auto period lock
- bank reconciliation sesi formal per akun per periode
- import bank statement dengan alias header umum, suggested match ke payment dan finance transaction, duplicate candidate, dan exception status
- override match manual per statement line ke payment atau finance transaction
- tax register otomatis dari sale/purchase finalized
- tax register draft yang bisa disinkron ulang dari source
- tax register lifecycle formal (issued, cancelled, replaced) dengan alasan dan timestamp
- auto-numbering tax document dengan fallback sequence
- export tax register CSV, draft bukti potong CSV, dan draft e-Faktur CSV
- approval matrix lintas modul dasar dengan threshold nominal dan multi-approver
- maker-checker dasar untuk post/reverse/reopen/void/edit-delete transaksi sensitif

Belum lengkap:
- enforcement yang lebih keras agar semua sale stockable wajib punya `inventory_location_id`
- drill-down reconciliation per document
- reopen / reversal journal formal untuk period closing
- costing formal untuk seluruh edge case procurement/return/adjustment
- document flow quotation / sales order / purchase order end-to-end
- auto matching lanjutan untuk reconciliation bank (rule-based matching)
- exception flow reconciliation lanjutan
- format e-Faktur final yang sesuai spesifikasi resmi DJP
- automation jurnal dan export PPh/withholding lanjutan

---

## PostgreSQL / Supabase Notes

Untuk area fitur accounting yang dijelaskan di dokumen ini:
- query baru memakai query builder Laravel biasa
- tidak menambah SQL vendor-specific baru
- aman untuk PostgreSQL/Supabase production

Namun repo secara umum masih memiliki beberapa migration lama yang bersifat MySQL-specific.
Itu adalah concern terpisah dari logic accounting operasional di dokumen ini.

---

## Ringkasan Sederhana

- **draft sale**: belum mengubah stok, belum membuat accounting final
- **finalize sale tanpa location**: membuat revenue journal saja
- **finalize sale dengan `inventory_location_id`**: membuat revenue journal + stock out + COGS journal
- **void sale**: membalik revenue, membalik COGS, dan mengembalikan stock bila movement sebelumnya ada
- **manual journal**: bisa dibuat draft lalu dipost, atau langsung posted; tidak bisa diedit setelah posted
- **journal reversal**: posted journal bisa direverse dari halaman detail; sistem membuat journal baru yang membalik semua baris, menyimpan alasan, dan menghubungkan ke journal asal
- **period lock**: mengunci rentang tanggal agar tidak ada posting baru; bisa manual atau otomatis dari period closing
- **period closing**: menutup akun revenue/expense ke retained earnings, membuat journal closing formal, dan membuat period lock otomatis; irreversible saat ini
- **purchase finalized**: mengakui payable ke `AP`; purchase receipt kemudian memindahkan nilai receipt aktual dari `PURCHASES` ke `INVENTORY`
- **reconciliation account**: dimulai dari sesi formal di `finance`; payment method harus dipetakan ke finance account agar payment bisa ikut direkonsiliasi
- **statement import**: masuk ke sesi reconciliation yang sama; sistem bisa membaca beberapa variasi header bank umum, memberi suggested match awal ke payment atau finance transaction, dan menandai kandidat duplikat; jika suggested match salah, user bisa override ke payment lain atau finance transaction sebelum sesi diselesaikan
- **tax register**: dibuat otomatis saat finalize sale/purchase bertax; lifecycle formal mulai draft sampai issued/cancelled/replaced; export CSV dan draft e-Faktur tersedia
- **chart of accounts**: dipakai sebagai struktur GL, basis period closing, dan pilihan di manual journal; punya provisioning default saat pertama kali dibuka
- **finance account / cashbook**: akun operasional kas/bank yang dipetakan ke COA; dipakai di finance transaction dan menjadi kandidat di bank reconciliation
- **reports**: membaca hasil transaksi/journal, bukan menjadi owner logika bisnis
