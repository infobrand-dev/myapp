# Accounting Product Line Plan

## Posisi
- `accounting` adalah nama product line dan bundle pricing, bukan industry dan bukan modul baru.
- Product line ini menggantikan family `commerce` lama di layer plan dan billing.
- Fase pertama memakai modul existing yang sudah ada di repo, bukan menunggu modul akuntansi formal baru.

## Bundle inti
Bundle inti `accounting` memakai modul existing berikut:
- `sales`
- `payments`
- `purchases`
- `finance`
- `point-of-sale`
- `reports`

Catatan dependency teknis:
- beberapa alur masih bergantung pada `products`, `inventory`, `contacts`, dan `discounts`
- dependency tersebut boleh tetap terikut secara teknis bila runtime membutuhkannya, tetapi bukan pesan utama paket

## Struktur paket
Tier awal mengikuti pola Omnichannel:
- `Accounting Starter`
- `Accounting Growth`
- `Accounting Scale`

Aturan tier:
- semua tier membawa core bundle yang sama
- pembeda utama ada di limit/capacity
- fase pertama tidak memakai unlock module antar tier

## Positioning copy
Copy paket harus menekankan:
- operasional penjualan dan pembelian
- pembayaran dan finance ringan
- POS
- reporting

Hindari klaim:
- COA formal
- general ledger
- closing buku
- integrasi Accurate/Zahir/Jurnal yang belum live

## Public flow
- fase pertama fokus ke plan dan harga internal
- onboarding public tetap `omnichannel` saja
- landing `accounting` boleh dipakai sebagai positioning, tetapi tidak menjadi self-serve checkout utama
