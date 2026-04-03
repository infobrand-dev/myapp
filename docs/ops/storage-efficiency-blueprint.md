# Storage Efficiency Blueprint

## Tujuan

Dokumen ini menjadi acuan efisiensi infrastruktur untuk area yang paling cepat membengkak:

- `conversations`
- `whatsapp_api`
- `social_media`
- dokumen/asset `chatbot`

Targetnya bukan optimasi teoritis, tetapi pengurangan biaya storage, I/O, dan payload processing tanpa merusak pengalaman tenant.

## Prinsip

- Optimasi harus aman untuk produksi dan bisa diterapkan bertahap.
- Prioritaskan pengurangan duplikasi dan data yang tidak perlu disimpan lama.
- Pisahkan optimasi `file storage` dari `database size`.
- Jangan mengandalkan kompresi sebagai satu-satunya strategi.
- Jangan menyimpan payload provider lengkap selamanya jika nilai operasionalnya rendah.

## Prioritas Utama

### 1. Retention dan archival

Penyebab pembengkakan paling umum bukan hanya upload besar, tetapi data yang tidak pernah dibersihkan.

Langkah:

- Tetapkan `hot retention` untuk media dan payload operasional, misalnya 30-90 hari.
- Tetapkan `warm retention` untuk arsip yang masih bisa diakses jika perlu.
- Tetapkan `cold policy` untuk data lama:
  - simpan metadata inti
  - hapus payload mentah
  - pertahankan referensi audit minimum

Rekomendasi awal:

- `webhook raw payload`: TTL pendek
- `conversation attachment`: simpan lebih lama, tapi evaluasi arsip
- `provider debug payload`: jangan permanen

### 2. File deduplication berbasis hash

Jika file yang sama diupload berulang, sistem tidak boleh menyimpan salinan fisik berkali-kali.

Struktur yang disiapkan:

- `content_hash`
- `size_bytes`
- `mime_type`
- `storage_disk`
- `storage_path`
- `tenant_id`

Strategi:

- hitung hash saat ingest
- cek apakah file identik sudah ada
- jika ada, gunakan referensi file yang sama
- hapus file fisik hanya jika tidak ada referensi aktif

Ini paling penting untuk:

- attachment conversation
- media template WhatsApp
- media sosial yang disimpan lokal
- dokumen knowledge chatbot

### 3. Image normalization

Image sering menjadi sumber pembengkakan storage karena:

- dimensi terlalu besar
- metadata EXIF tidak diperlukan
- format tidak efisien

Kebijakan aman:

- strip metadata EXIF
- resize ke batas maksimal yang masuk akal
- buat thumbnail/preview
- simpan original hanya bila memang diperlukan oleh provider/channel

Catatan:

- jangan transcode video secara default pada phase 1 karena mahal di CPU
- jangan recompress PDF atau file binary lain secara buta

### 4. Payload minimization

Banyak webhook dan respons provider jauh lebih besar dari kebutuhan operasional sebenarnya.

Simpan permanen hanya:

- ID provider
- direction
- sender/recipient reference
- body ter-normalisasi
- attachment metadata penting
- status penting
- error penting

Jangan simpan permanen:

- raw event JSON penuh jika hanya berguna untuk debug sesaat
- payload provider berulang yang bisa direkonstruksi dari metadata inti

Strategi:

- payload mentah disimpan sementara
- atau disimpan dengan TTL
- atau diringkas saat ingest

## Fokus per Area

### Conversations

Masalah utama:

- attachment lokal
- histori message yang tumbuh terus
- payload/debug channel

Langkah phase 1:

- dedupe attachment message
- batasi ukuran upload per file
- batasi tipe file yang diterima
- TTL untuk raw payload debug
- lazy-load histori lama

Phase berikutnya:

- archive table atau partition untuk message lama
- summary snapshot untuk conversation lama

### WhatsApp API

Masalah utama:

- media template
- attachment/media conversation dari channel WA
- webhook events/log

Langkah phase 1:

- dedupe media template
- cleanup file lama yang sudah tidak direferensikan template aktif
- TTL untuk webhook raw payload
- retention untuk log event yang hanya observability

### Social Media

Masalah utama:

- media inbound/outbound lokal
- event payload Meta/X
- file upload balasan agent

Langkah phase 1:

- dedupe attachment sosial
- simpan metadata event ter-normalisasi, bukan raw payload penuh selamanya
- image normalization untuk upload agent

### Chatbot

Masalah utama:

- dokumen knowledge
- chunking asset
- file RAG yang diunggah tenant

Langkah phase 1:

- dedupe dokumen knowledge
- simpan satu source file, bukan duplikasi per proses
- cleanup file knowledge yang sudah tidak direferensikan

## Tabel Shared yang Disarankan

Untuk jangka menengah, siapkan tabel shared seperti `stored_files`.

Kolom minimal:

- `id`
- `tenant_id`
- `disk`
- `path`
- `content_hash`
- `size_bytes`
- `mime_type`
- `original_name`
- `width`
- `height`
- `duration_seconds`
- `variant`
- `created_by`
- `last_referenced_at`
- `deleted_at`

Fungsi:

- dedupe
- quota counting
- safe delete
- retention cleanup
- audit file ownership

Catatan:

- Ini bukan wajib untuk go-live awal.
- Tapi ini fondasi terbaik kalau nanti storage mulai tumbuh cepat.

## Enkripsi

Enkripsi tetap penting untuk keamanan, tetapi bukan alat utama penghematan biaya.

Posisi yang benar:

- gunakan enkripsi jika dibutuhkan untuk compliance atau data sensitif
- jangan menjadikannya strategi pengurangan storage

## Database Size

Ukuran database memang memakan storage, tetapi untuk phase 1 jangan dihitung sebagai `tenant storage quota`.

Alasannya:

- shared database sulit dipecah presisi per tenant
- perhitungan bisa mahal
- hasilnya mudah membingungkan tenant

DB cost sebaiknya dikontrol lewat limit lain:

- `max_users`
- `max_contacts`
- `max_conversations`
- `max_messages`

## Batasan Sistem yang Disarankan

Selain quota total storage, tambahkan batas teknis:

- `max_attachment_size_per_file`
- `max_attachments_per_message`
- `max_media_uploads_per_day_per_tenant`
- `max_raw_payload_retention_days`
- `max_debug_log_retention_days`

Ini lebih efektif menjaga infra daripada hanya mengandalkan satu storage quota besar.

## Urutan Implementasi Aman

### Phase 1

- retention raw payload
- image normalization untuk upload image baru
- file dedupe untuk attachment lokal baru
- cleanup job file orphan

### Phase 2

- shared file registry `stored_files`
- archive message lama
- cleanup policy per channel/module

### Phase 3

- object storage tiering
- archive storage class
- partitioning atau cold archive untuk histori besar

## Hal yang Sengaja Ditunda

Belum disarankan untuk fase go-live:

- transcode video default
- hitung exact database size per tenant
- kompres semua file biner secara paksa
- rewrite total semua modul ke satu file abstraction dalam satu langkah

## Checklist Go-Live

- tentukan retention policy raw payload
- tentukan batas upload per file dan per message
- tentukan apakah image di-normalisasi saat upload
- tentukan job cleanup orphan file
- pastikan observability file cleanup ada
- pastikan quota tenant dihitung dari file yang benar-benar ada

## Kesimpulan

Penghematan terbesar untuk inbox dan DM biasanya datang dari:

- menghapus data yang tidak perlu disimpan lama
- menghindari duplikasi file
- menormalkan image besar
- merapikan payload mentah provider

Kompresi dan enkripsi tetap berguna, tetapi bukan pusat strategi efisiensi. Fokus utama yang paling aman untuk tahap sekarang adalah `retention + dedupe + normalization + cleanup`.
