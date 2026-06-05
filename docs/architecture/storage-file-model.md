# Storage File Model - Internal Technical Note

Status: internal engineering documentation  
Audience: backend engineers, platform engineers, security reviewers, owners  
Exposure: this file lives outside `public/` and is not intended as tenant-facing or public product documentation

## Purpose

Dokumen ini menjelaskan model teknis file storage yang berlaku di aplikasi saat ini:
- klasifikasi file
- routing storage owner-managed
- akses private vs public
- signed sharing untuk channel/provider
- inbound media capture
- audit trail, remediation, dan residual legacy risk

Ini adalah dokumen teknis internal. Ringkasan singkat boleh muncul di `README.md` atau `ARCHITECTURE.md`, tetapi detail operasional harus dirawat di sini.

## Non-public boundary

Secara runtime aplikasi:
- file ini berada di `docs/architecture/`, bukan di `public/`
- tidak ada route aplikasi yang mengekspose isi `docs/`
- dokumen ini hanya terbaca oleh developer/operator yang punya akses repository atau filesystem server

Catatan:
- jika repository dijadikan public, maka dokumen ini ikut public di level source control
- jadi status "internal" di sini berarti non-public dari sisi web app/runtime, bukan otomatis private terhadap distribusi repository

## Design goals

Target model storage:
- `private-first`
- `share-when-needed`
- owner-managed storage routing
- provenance and auditability by default
- historical reads remain valid after storage profile changes
- no silent fallback from sensitive files to public permanent URLs

## File classes

### `public_asset`

Contoh:
- avatar user
- brand logo
- storefront/product media publik

Rules:
- boleh memakai public-capable storage profile
- boleh memakai direct public URL
- tidak perlu authenticated download route

### `private_document`

Contoh:
- finance attachment
- payment proof
- bank statement
- sales attachment

Rules:
- wajib disimpan private
- akses hanya lewat route/controller/service yang terotorisasi
- download dan preview harus tercatat di `stored_file_access_logs`
- tidak boleh fallback ke public permanent URL

### `channel_shared_media`

Contoh:
- outgoing media yang perlu di-fetch provider atau penerima
- WhatsApp/media social yang dikirim keluar dari sistem
- WA template header media yang butuh provider fetch

Rules:
- object tetap private
- akses keluar memakai signed URL singkat
- signed URL harus dicatat sebagai event audit
- jika provider punya `provider_media_id`, itu lebih diutamakan daripada signed URL

### `channel_inbound_evidence`

Contoh:
- inbound media dari WhatsApp Cloud
- inbound attachment dari Instagram/Facebook DM

Rules:
- default private
- provenance provider wajib disimpan
- jika media berhasil dicopy ke internal storage, message payload harus menyimpan `stored_file_id`
- policy saat ini: `copy important only`

### `ephemeral_export`

Contoh:
- export CSV/PDF yang hanya untuk download user

Rules:
- default `streamDownload()`
- simpan object hanya jika memang dibutuhkan untuk retry, audit, atau workflow khusus

## Persistent metadata

### `stored_files`

Metadata inti yang sekarang relevan:
- `storage_profile_id`
- `disk`
- `path`
- `visibility`
- `availability_status`
- `category`
- `access_class`
- `share_strategy`
- `retention_class`
- `provider_origin`
- `provider_media_id`
- `provider_media_url`
- `expires_at`
- `storage_snapshot`
- provenance umum seperti `origin_system`, `origin_owner`, `source_module`, `source_context`

### `stored_file_access_logs`

Minimal event yang harus bisa dibedakan:
- `download`
- `preview`
- `signed_url_issued`
- `signed_url_accessed`
- `provider_fetch`
- `forbidden`
- `missing_object`
- `unreachable`

## Routing model

### Control plane

Owner mengelola:
- `storage_profiles`
- status aktif/nonaktif
- default/fallback profile

Tenant tidak memilih storage profile secara manual.

### Data plane

Upload flow:
1. caller memilih `category`
2. `StoredFileService` memanggil `StorageRoutingService`
3. router resolve topology aktif + storage profile yang cocok
4. file disimpan ke destination aktual
5. snapshot lokasi aktual disimpan ke `stored_files`

Historical read flow:
1. app membaca row `stored_files`
2. `StorageAccessService` memakai snapshot file tersebut
3. object dibaca dari profile/credential yang sesuai
4. jika object hilang atau profile unreachable, result harus controlled, bukan 500

## Access model

### Public asset

Resolver:
- `StorageAccessService::publicUrlFromPath()`

Allowed:
- direct public URL

### Private document

Resolvers:
- `stored-files.download`
- `stored-files.preview`
- legacy secure bridge `stored-files.legacy-download`

Allowed:
- authenticated access only

### Shared media

Resolver:
- `SharedFileAccessService::issueShareUrl()`

Allowed:
- signed short-lived external access for provider/recipient/internal-preview use case

Not allowed:
- converting sensitive object into open `/storage/...` link

## Provider URL policy

Jangan asumsi semua provider memberi URL public permanen.

Provider pattern yang didukung:

### `provider_media_id`

Contoh:
- WhatsApp Cloud media

Meaning:
- provider memberi identifier object
- binary diambil belakangan via provider API

### `provider_media_url`

Contoh:
- Meta attachment payload tertentu

Meaning:
- provider memberi URL fetchable
- URL bisa temporary atau provider-controlled

### app-issued signed URL

Contoh:
- outbound media saat provider perlu pull object dari sistem kita

Meaning:
- object tetap private
- sistem memberi akses singkat hanya untuk fetch

## Explicit outbound fallback order

Urutan yang harus dipertahankan:
1. provider-hosted object / `provider_media_id`
2. signed short-lived URL dari sistem kita
3. fail closed

Jangan fallback ke:
- public permanent URL
- `asset('storage/...')`
- object private yang dipaksa menjadi public hanya agar provider bisa fetch

## Inbound media capture

Owner service:
- `App\Modules\Conversations\Services\InboundMediaCaptureService`

Tanggung jawab:
- fetch provider media
- copy ke category `channel_inbound_evidence`
- update `ConversationMessage.payload`
- set `stored_file_id`, `storage_path`, `content_hash`, `copied_locally`, `fetched_at`
- ganti `media_url` message ke preview route internal

### Current implementation

Saat ini aktif untuk:
- WhatsApp Cloud `media_id`
- Meta social attachment URL

### Current non-goals

Belum di-rollout penuh untuk:
- provider yang hanya memberi opaque attachment key tanpa fetch API yang sudah dipetakan
- semua channel lain yang belum punya downloader/auth flow stabil

Dalam kasus itu:
- provenance tetap harus disimpan
- `copied_locally` boleh tetap `false`

## Conversation payload contract

Untuk message yang berhubungan dengan media, payload internal sebaiknya dapat membawa:
- `provider_origin`
- `provider_media_id`
- `provider_media_url`
- `media_url_reference`
- `media_url_source`
- `copied_locally`
- `stored_file_id`
- `storage_disk`
- `storage_path`
- `content_hash`
- `capture_status`
- `fetched_at`
- `copy_policy`

## Legacy compatibility

Masih ada file lama yang berasal dari public path historis.

Aturan kompatibilitas:
- jangan expose ulang sebagai public URL baru
- bila perlu dibuka, materialize sebagai secure legacy reference
- tandai exposure itu di metadata
- surface lewat `storage:audit-profiles`

Status yang dipakai saat ini:
- `availability_status = legacy_exposed`
- `meta.legacy_public_exposed = true`

## Security expectations

### Minimum controls

- sensitive file tidak boleh bergantung pada `public/storage`
- authorization tidak boleh hanya mengandalkan obscured path
- access private harus tercatat
- inactive/revoked storage profile tidak boleh dipilih untuk write baru
- historical file read harus fail closed bila backend object tidak tersedia

### Incident response support

Sistem harus membantu menjawab:
- file ini berasal dari flow apa
- siapa yang mengunggah
- provider/source apa yang terlibat
- siapa yang mengunduh atau meminta share URL
- profile storage mana yang dipakai
- apakah object masih ada atau sudah unreachable

## Operational commands

### Audit storage profile

```bash
php artisan storage:audit-profiles
```

Saat ini command dipakai untuk:
- melihat profile aktif/inaktif
- melihat file yang statusnya tidak sehat
- melihat legacy public-sensitive exposure yang perlu remediation

## Testing contract

Minimal test yang harus dipertahankan:
- storage routing new upload
- inactive profile fallback behavior
- private download authorization
- signed share issuance and access
- legacy secure download for sensitive files
- inbound provenance persistence
- inbound media capture into stored file

## Files and services to know

Core files:
- `app/Services/StoredFileService.php`
- `app/Services/StorageRoutingService.php`
- `app/Services/StorageAccessService.php`
- `app/Services/SharedFileAccessService.php`
- `app/Http/Controllers/StoredFileController.php`
- `app/Console/Commands/AuditStorageProfilesCommand.php`

Conversation/channel files:
- `app/Modules/Conversations/Services/ConversationInboxIngester.php`
- `app/Modules/Conversations/Services/InboundMediaCaptureService.php`
- `app/Modules/SocialMedia/Http/Controllers/SocialWebhookController.php`
- `app/Modules/SocialMedia/Services/MetaWebhookPayloadParser.php`
- `app/Modules/WhatsAppApi/Http/Controllers/WebhookController.php`
- `app/Modules/WhatsAppApi/Jobs/SendWhatsAppMessage.php`
- `app/Modules/SocialMedia/Jobs/SendSocialMessage.php`

## Change discipline

Jika ada perubahan pada:
- klasifikasi file
- access strategy
- provider media flow
- audit event naming
- inbound capture policy

maka dokumen ini harus ikut diupdate dalam perubahan yang sama.
