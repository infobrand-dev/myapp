# Platform Core Boundary Policy

Dokumen ini membekukan boundary transisional antara `core` dan `module`.

Status saat ini:

- audit boundary aktif
- approved transitional reference: `0`
- migration touch historis yang masih dibekukan: tetap diawasi terpisah sampai dipindah penuh ke owner module

## Prinsip

- `core` hanya boleh berisi primitive SaaS yang tetap relevan walau semua business module dimatikan
- `module` tetap memiliki schema, flow bisnis, adapter vendor, dan domain behavior miliknya sendiri
- dependency `core -> App\Modules\*` dianggap pelanggaran kecuali masuk daftar exception transisional yang dibekukan

## Exception Transisional

Exception saat ini disimpan di `config/platform-core.php` pada key `boundary.approved_module_references`.

Per 2026-06-07, daftar exception reference class sudah kosong. Key ini tetap dipertahankan agar compatibility bridge masa depan harus melalui approval eksplisit, bukan lewat import diam-diam.

Aturan exception:

- hanya untuk compatibility bridge yang belum selesai dipisahkan
- harus spesifik per file dan per class reference
- tidak boleh dipakai untuk menambah coupling baru secara diam-diam
- setiap exception baru harus disertai alasan dan target refactor keluar dari core

## Audit

Gunakan:

```bash
php artisan modules:audit-boundaries
```

Hasil yang diterima:

- `approved_core_module_reference`: masih diizinkan sementara
- `approved_core_module_migration_touch`: migration lama masih ditoleransi sementara sambil dipindahkan ke module owner
- `core_depends_on_module_class`: pelanggaran baru atau dependency yang belum disetujui
- `core_migration_touches_module_table`: schema ownership bocor ke core

## Aturan Praktis

- bila `core` perlu berinteraksi dengan capability optional, gunakan contract, registry, atau service abstraction
- bila sebuah flow hanya relevan jika module aktif, logic utamanya pindah ke module owner
- migration yang membuat atau mengubah tabel milik module harus hidup di module owner

## Contoh Refactor Yang Diizinkan

- `core` butuh finalize draft order dari module `Sales`
  - salah: inject langsung `FinalizeSaleAction`
  - benar: definisikan contract `App\Contracts\CommerceDraftFinalizer`, lalu module `Sales` bind adapter implementasinya
- `core` butuh checkout payment vendor
  - salah: driver core import langsung `MidtransService`, `TripayService`, atau `XenditService`
  - benar: driver core pakai contract gateway, module owner bind adapter vendor
- `core` butuh tahu daftar entity yang bisa dijadikan accounting source
  - salah: service core import semua model module
  - benar: registry dipindah ke config/manifest dan dibaca generic oleh core
