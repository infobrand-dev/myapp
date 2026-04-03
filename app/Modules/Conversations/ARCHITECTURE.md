# Conversations Architecture

`Conversations` adalah domain shared inbox yang netral terhadap channel.
Ia menyimpan percakapan, pesan, participant, dan activity log untuk berbagai adapter channel seperti `WhatsAppApi`, `WhatsAppWeb`, dan `SocialMedia`.

## Tujuan desain

- `Conversations` memiliki storage dan alur inbox bersama.
- Module channel bertugas sebagai adapter/integration layer.
- Logic khusus provider tidak ditaruh di core app path.
- Rule khusus channel tidak di-hardcode di inbox module jika masih bisa diregistrasikan dari module channel.

## Prinsip utama

- Inbound masuk ke `Conversations` melalui contract bersama, bukan lewat `Conversation::create()` langsung dari module channel.
- Outbound dikirim melalui dispatcher registry, bukan `if/else` channel di controller inbox.
- Access policy tambahan diregistrasikan oleh module channel.
- Capability channel untuk send flow dan UI diregistrasikan oleh module channel.
- Unread utama untuk agent disimpan per participant/user. `conversations.unread_count` tetap dipakai sebagai fallback queue-level counter saat conversation belum punya state participant yang spesifik.

## Integration seams saat ini

### 1. Inbound ingestion

- Contract: `App\Modules\Conversations\Contracts\InboxMessageIngester`
- DTO: `App\Modules\Conversations\Data\InboxMessageEnvelope`
- Service: `App\Modules\Conversations\Services\ConversationInboxIngester`

Alur yang diharapkan:

1. Module channel memvalidasi webhook atau payload provider di modulnya sendiri.
2. Module channel me-resolve account, instance, atau token provider di modulnya sendiri.
3. Module channel memetakan payload ke `InboxMessageEnvelope`.
4. Module channel memanggil contract ingestion.

Catatan:

- Realtime ingestion, history sync, dan backfill dibedakan dengan `ingestionMode`.
- `Conversations` tidak perlu tahu bentuk asli payload vendor selama envelope yang dikirim sudah lengkap.

### 2. Outbound dispatch

- Contract: `App\Modules\Conversations\Contracts\ConversationOutboundDispatcher`
- Service: `App\Modules\Conversations\Services\ConversationOutboundRegistry`

`Conversations` hanya memanggil dispatcher.
Setiap module channel mendaftarkan sender atau job miliknya sendiri dari service provider modul tersebut.

### 2b. AI assistant resolution

- Contract: `App\Modules\Conversations\Contracts\ConversationAiAssistantRegistry`
- Service: `App\Modules\Conversations\Services\ConversationAiAssistantManager`

`Conversations` tidak boleh mengimpor model akun AI dari module automation secara langsung.
Module seperti `Chatbot` mendaftarkan resolver account-nya sendiri.

### 3. Access policy

- Contract: `App\Modules\Conversations\Contracts\ConversationAccessRegistry`
- Service: `App\Modules\Conversations\Services\ConversationAccessManager`

Baseline access yang tetap dimiliki `Conversations`:

- `Super-admin`
- owner conversation
- participant conversation

Access tambahan yang bersifat channel-specific diregistrasikan dari module channel.
Contoh:

- Admin boleh melihat conversation `wa_web`
- User yang terhubung ke instance boleh melihat conversation `wa_api`

### 4. Channel capabilities

- Contract: `App\Modules\Conversations\Contracts\ConversationChannelManager`
- Service: `App\Modules\Conversations\Services\ConversationChannelRegistry`

Registry ini dipakai untuk kebutuhan channel-specific yang masih berhubungan dengan shared inbox, misalnya:

- preflight send check
- aturan apakah text atau media boleh dikirim
- dukungan template
- template lookup dan payload builder
- dukungan AI structured reply jika channel memang mendukung payload non-text
- default persistence status seperti `queued`
- flag UI seperti menampilkan template composer atau media composer

### 5. Detail hooks

Detail panel dan badge integrasi yang benar-benar channel/domain-specific tidak lagi di-hardcode di controller inbox.
Gunakan hook view seperti:

- `conversations.index.integration_badges`
- `conversations.show.detail_rows`

Dengan pola ini, module seperti `WhatsAppApi` atau `Contacts` bisa menambahkan UI detail sendiri tanpa membuat `Conversations` mengimpor class mereka.

## Aturan praktis saat menambah channel baru

Saat menambah module channel baru:

1. Tambahkan `requires: ["conversations"]` di `module.json`.
2. Simpan seluruh logic webhook, auth, signature, token, dan provider API di module channel.
3. Map inbound payload ke `InboxMessageEnvelope`.
4. Daftarkan outbound dispatcher dari service provider module channel.
5. Daftarkan access rule tambahan hanya jika memang dibutuhkan.
6. Daftarkan channel capability untuk send policy atau UI jika diperlukan.
7. Hindari menambah `if ($conversation->channel === 'new_channel')` di module `Conversations` kecuali perilakunya benar-benar shared inbox behavior.

## Batas yang sengaja dipertahankan

- `Conversations` tetap boleh menyimpan state inbox umum seperti unread count, owner, participant, dan activity log.
- Module channel tetap boleh menjalankan logic khususnya sendiri setelah ingestion berhasil, misalnya auto-assign, chatbot trigger, atau provider status update.
- Tidak semua perbedaan channel harus dipaksa masuk ke `Conversations`. Jika perbedaannya murni adapter/provider concern, letakkan di module channel.

## Langkah lanjutan yang disarankan

- Tambah feature test untuk adapter `WhatsAppApi` dan `WhatsAppWeb`.
- Jika UI channel-specific makin banyak, pertimbangkan view model atau view partial yang diregistrasikan per channel.
- Jika history sync/backfill makin kompleks, tambahkan logging atau sync run record yang eksplisit.
