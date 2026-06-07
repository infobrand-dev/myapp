# Platform Core Milestones 2026-06

Dokumen ini memecah audit platform core 2026-06 menjadi milestone implementasi yang bisa dijadikan basis PR terpisah.

## Milestone 1 - Boundary & Tenancy Baseline

Status: baseline selesai

Scope:

- audit boundary aktif di `php artisan modules:audit-boundaries`
- seluruh approved transitional reference `core -> module` dibersihkan dari area core yang diaudit
- migration historis yang menyentuh tabel module sudah dipindah ke owner module
- `tenant:health-check` dan `tenant:query-readiness-audit` lulus di environment dev utama

Acceptance:

- `php artisan modules:audit-boundaries` lulus
- `Approved transitional references: 0`
- `approved_module_table_touches: 0`
- `php artisan tenant:health-check` lulus

## Milestone 2 - Core Foundation Contracts

Status: baseline selesai, coverage masih perlu diperluas

Scope:

- entitlement snapshot/service
- platform audit log v1
- platform activity foundation v1
- platform outbox event baseline
- webhook receipt baseline
- API response contract v1
- global search foundation baseline
- notification policy layer
- file post-processing pipeline

Acceptance baseline:

- primitive core tersedia dan bisa dipakai tanpa import class bisnis module langsung dari core
- minimal satu endpoint API platform aktif
- minimal satu webhook flow memakai receipt log generik
- boundary tetap bersih setelah primitive baru ditambahkan

## Milestone 3 - Runtime Adoption

Status: in progress

Scope:

- perluas penggunaan audit log ke flow sensitif tambahan
- perluas activity foundation ke entity timeline lintas modul
- perluas event outbox ke event penting lain
- perluas endpoint API v1 yang mengikuti contract baru
- perluas search index agar validasi multi-entity benar-benar terbukti oleh data dev/staging

Acceptance target:

- satu flow sensitif tambahan tercatat penuh di audit log
- satu timeline lintas modul bisa dirender dari foundation yang sama
- satu event penting bisa dipublish, diproses ulang, dan dedupe
- global search mengembalikan minimal 3 entity type dengan data nyata

## Milestone 4 - Growth Operations

Status: belum mulai

Scope:

- retention job untuk tabel growth-heavy
- archive strategy awal
- observability minimum untuk queue backlog dan slow query watchlist
- review report berat ke summary/read-model

Acceptance target:

- daftar tabel growth-heavy punya retention owner yang jelas
- report berat baru tidak langsung query OLTP mentah tanpa review

## Milestone 5 - Future Scope & Enterprise Hooks

Status: belum mulai

Scope:

- access scope assignment hook untuk department/team/division/owner
- selective isolation hook untuk tenant besar
- legal hold/compliance extension point

Acceptance target:

- scope baru tidak menambah kolom ad hoc tersebar di banyak tabel
- hook enterprise siap dipakai tanpa membongkar ulang boundary core
