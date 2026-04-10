# Accounting Product Line Plan

## Posisi
- `accounting` adalah nama product line dan bundle pricing, bukan industry dan bukan modul baru.
- Product line ini menggantikan family `commerce` lama di layer plan dan billing.
- Fase pertama memakai modul existing yang sudah ada di repo, bukan menunggu modul akuntansi formal baru.

## Bundle inti
Bundle inti `accounting` yang dipakai saat ini:
- `sales`
- `payments`
- `finance`
- `reports`
- `products`
- `contacts`

Tier lanjutan:
- `Accounting Growth` menambahkan `purchases` dan `inventory`
- `Accounting Scale` memakai capability yang sama dengan kapasitas lebih besar

Add-on:
- `point-of-sale`
- `discounts` mengikuti kebutuhan POS

## Struktur paket
Tier awal mengikuti pola Omnichannel:
- `Accounting Starter`
- `Accounting Growth`
- `Accounting Scale`

Aturan tier:
- `Starter` ditujukan untuk UMKM yang belum butuh pembelian supplier, stok, atau POS
- `Growth` dan `Scale` membuka workflow operasional yang lebih lengkap
- pembeda tier ada di kombinasi capability dan limit/capacity
- POS tetap diposisikan sebagai add-on, bukan paket inti semua bisnis

## Positioning copy
Copy paket harus menekankan:
- operasional penjualan, pembayaran, dan finance ringan
- products dan contacts sebagai data utama
- pembelian dan stok tersedia saat bisnis sudah membutuhkannya
- reporting

Hindari klaim:
- COA formal
- general ledger
- closing buku
- integrasi Accurate/Zahir/Jurnal yang belum live

## Public flow
- `accounting` adalah jalur utama yang aktif dijual lewat `/onboarding`
- default public signup harus mengarahkan user ke paket `accounting`
- `omnichannel` tetap ada sebagai product line berikutnya, tetapi bukan jalur utama sampai siap dijual penuh

## Referensi roadmap
- detail backlog fitur bisnis accounting ada di `docs/product/accounting-feature-roadmap.md`
