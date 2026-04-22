# Accounting Document Numbering

Dokumen ini menjelaskan fondasi numbering dokumen accounting agar bisa diperluas tanpa bentrok saat fitur bertambah.

## Tujuan
- satu struktur numbering untuk banyak jenis dokumen
- bisa di-scope per `company`, lalu dioverride per `branch`
- aman untuk PostgreSQL / Supabase dan tetap mudah diperluas
- dokumen baru cukup menambah `document_type`, bukan menambah kolom baru satu per satu

## Struktur Data

### `document_settings`
Tetap dipakai untuk:
- `document_header`
- `document_footer`
- `receipt_footer`
- `notes`

Kolom invoice lama tetap dipertahankan untuk kompatibilitas bertahap, tetapi source of truth numbering jangka panjang bukan lagi di sini.

### `document_numbering_rules`
Tabel baru ini adalah source of truth numbering lintas dokumen.

Kolom penting:
- `tenant_id`
- `company_id`
- `branch_id`
- `scope_key`
- `document_type`
- `prefix`
- `number_format`
- `padding`
- `next_number`
- `last_period`
- `reset_period`

## Kenapa pakai `scope_key`
Untuk PostgreSQL, unique index dengan kolom nullable seperti `branch_id = null` bisa membiarkan duplikasi row company-level.

Karena itu row numbering memakai:
- `scope_key = company`
- `scope_key = branch:{id}`

Dengan ini unique rule per dokumen jadi stabil di MySQL maupun PostgreSQL/Supabase.

## Prioritas Rule
Saat sistem generate nomor:

1. cari rule branch aktif untuk `document_type`
2. jika tidak ada, pakai rule company
3. jika belum ada juga, fallback ke generator sequence lama

Jadi branch override menjadi source of truth hanya untuk branch tersebut, bukan untuk semua branch.

## Dokumen yang Sudah Masuk Fondasi
- `sale`
- `sale_quotation`
- `sale_order`
- `sale_return`
- `purchase`
- `purchase_request`
- `purchase_order`
- `purchase_receipt`
- `payment`
- `tax_output_vat`
- `tax_input_vat`
- `tax_withholding`

Kalau nanti ditambah misalnya:
- `purchase_request`
- `credit_note`
- `debit_note`
- `journal`

cukup tambahkan `document_type` baru di definisi aplikasi dan tampilkan di settings.

## Format Nomor
Field `number_format` mendukung token:
- `{PREFIX}`
- `{YYYY}`
- `{YY}`
- `{MM}`
- `{DD}`
- `{YYYYMM}`
- `{YYYYMMDD}`
- `{SEQ}`

Contoh:
- `{PREFIX}-{YYYYMMDD}-{SEQ}` menjadi `SAL-20260422-00001`
- `{PREFIX}/{YYYY}/{MM}/{SEQ}` menjadi `INV/2026/04/00001`

## Reset Counter
- `never`: counter terus naik
- `monthly`: reset saat periode bulan berubah
- `yearly`: reset saat tahun berubah

Kolom `last_period` dipakai untuk mendeteksi kapan counter harus diulang dari `1`.

## Logika Operasional

### Saat draft dibuat
Nomor dokumen langsung digenerate sesuai rule dokumen tersebut. Jadi draft sudah punya identitas dokumen internal yang konsisten.

### Saat finalisasi
Nomor tidak diganti lagi kecuali memang ada flow khusus yang nanti sengaja dibuat terpisah.

### Pengecualian untuk tax register
Untuk `tax_output_vat`, `tax_input_vat`, dan `tax_withholding`:
- draft register boleh belum punya nomor
- nomor akan digenerate saat status register naik dari `draft` ke status formal seperti `issued`, `replaced`, atau `cancelled`

Tujuannya:
- draft tetap fleksibel saat data perpajakan masih dilengkapi
- nomor dokumen pajak baru menjadi identitas final saat dokumennya benar-benar dianggap formal

### Jika branch override ada
Nomor branch itulah yang dipakai.

### Jika branch override tidak ada
Nomor mengikuti company.

## Catatan Pengembangan
- jangan tambah kolom baru seperti `quotation_prefix`, `po_prefix`, `payment_prefix` ke `document_settings`
- dokumen baru harus masuk ke daftar `document_type`
- query generator harus tetap scoped ke `tenant + company + optional branch`
- jika butuh approval atau lifecycle lebih dalam, numbering tidak perlu dirombak lagi, cukup dokumennya yang dilebarkan
