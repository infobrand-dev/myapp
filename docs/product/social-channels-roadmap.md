# Social Channels Roadmap

## Current Public Scope
- `Social Media` saat ini berarti `Instagram Business DM` dan `Facebook Messenger`.
- Jangan jual sebagai inbox sosial media lengkap sampai channel baru benar-benar live.

## Current Priority
1. Stabilkan parser webhook native Meta.
2. Tambahkan outbound media/attachment untuk Instagram/Facebook.
3. Perkuat observability, retry, dan rate/cost guard.

## Next Channel Candidates
### TikTok
- Prioritas bisnis paling tinggi setelah Meta untuk pasar Indonesia.
- Cocok untuk inbound commerce dan brand interaction.
- Saat ini di codebase sudah ada scaffold internal `tiktok`, tapi belum ada connector tenant-facing.
- Urutan kerja sampai siap publish:
  1. Validasi capability resmi yang benar-benar tersedia untuk tenant.
     - gunakan hanya API resmi TikTok yang memang dibuka untuk partner/developer
     - jangan asumsikan DM/business inbox tersedia kalau docs publik belum ada
  2. Siapkan OAuth / Login Kit connector tenant.
     - connect account TikTok harus lewat platform OAuth, bukan token manual
  3. Siapkan account metadata model.
     - simpan user/account ID, display name, avatar, dan token refresh lifecycle
  4. Tentukan event ingress resmi.
     - jika ada webhook/event resmi untuk messaging atau komentar, parser harus native
     - jika tidak ada, jangan jual sebagai inbox omnichannel penuh
  5. Tambahkan outbound adapter hanya untuk aksi yang memang didukung official API.
  6. Normalisasi ke `conversations` hanya jika ada inbound/outbound nyata.
  7. Tambahkan plan limits, storage, audit, dan health observability.
  8. Baru buka ke landing page dan tenant umum setelah `connect + inbound + outbound + audit` nyata.
- Blocker saat ini:
  - saya belum menemukan dokumentasi publik resmi TikTok yang cukup jelas untuk `DM/business messaging inbox` setara Meta/X.
  - yang jelas tersedia secara publik justru area seperti Login Kit/OAuth dan Content Posting API.
  - artinya TikTok bisa disiapkan dari sisi auth/foundation, tetapi belum jujur kalau langsung dijual sebagai channel DM omnichannel.

### Threads
- Anggap sebagai channel baru, bukan turunan langsung dari Instagram.
- Jangan asumsikan support Instagram otomatis mencakup Threads DM.
- Saat ini di codebase sudah ada scaffold internal `threads`, tapi masih status research.
- Kebutuhan teknis sama seperti channel baru lain: auth, inbound parser, outbound adapter, limits, audit.

### X
- Prioritas bisnis lebih rendah untuk SMB Indonesia dibanding TikTok/Meta.
- Tetap relevan untuk use case public brand response atau segmen tertentu.
- Saat ini di codebase sudah ada scaffold internal `x`, tapi connector tenant belum dibuka.
- Fondasi internal saat ini sudah mencakup registry platform dan client boundary untuk DM send berdasarkan X API v2.
- Fondasi internal sekarang juga sudah mencakup:
  - helper CRC/signature webhook
  - parser payload Account Activity untuk event `MessageCreate`
- Endpoint resmi yang jadi acuan:
  - `POST /2/dm_conversations/with/:participant_id/messages`
  - `POST /2/dm_conversations/:dm_conversation_id/messages`
- Scope resmi yang dibutuhkan:
  - `dm.write`
  - `dm.read`
  - `tweet.read`
  - `users.read`
- Cocok dipertimbangkan setelah Meta parser stabil dan roadmap TikTok lebih jelas.

## Shared Architecture For Any New Social Channel
- satu account record per channel/account yang benar-benar aktif
- native webhook parser, bukan payload pseudo-flat
- outbound adapter terpisah dari normalized conversation flow
- tenant-aware plan limits
- audit log dan error log
- storage quota untuk attachment lokal
- status capability yang jujur di UI dan landing page

## Public Messaging Rule
- hanya channel yang `connect + inbound + outbound + audit` sudah nyata yang boleh tampil di landing page
- channel yang belum siap cukup masuk roadmap internal
