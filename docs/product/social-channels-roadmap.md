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
- Kebutuhan minimal:
  - account connect/token flow
  - inbound event parser native
  - outbound reply adapter
  - normalized mapping ke conversations
  - plan limit + storage + audit

### Threads
- Anggap sebagai channel baru, bukan turunan langsung dari Instagram.
- Jangan asumsikan support Instagram otomatis mencakup Threads DM.
- Saat ini di codebase sudah ada scaffold internal `threads`, tapi masih status research.
- Kebutuhan teknis sama seperti channel baru lain: auth, inbound parser, outbound adapter, limits, audit.

### X
- Prioritas bisnis lebih rendah untuk SMB Indonesia dibanding TikTok/Meta.
- Tetap relevan untuk use case public brand response atau segmen tertentu.
- Saat ini di codebase sudah ada scaffold internal `x`, tapi connector tenant belum dibuka.
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
