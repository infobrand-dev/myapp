# Purchases — Pembelian

Modul Purchases mengelola transaksi pembelian dari supplier — mulai dari pencatatan pembelian, penerimaan barang, hingga pembayaran hutang.

---

## Alur Pembelian di Meetra

```
Buat Purchase (Draft)
        ↓
   Finalize Purchase
        ↓
  Terima Barang (Receiving)
        ↓
  Bayar Supplier
        ↓
     Lunas
```

Purchase yang di-finalize mencatat hutang ke supplier. Saat barang diterima, stok otomatis bertambah.

---

## Membuat Purchase Baru

1. Buka **Purchases → Buat Purchase**
2. Pilih **supplier**
3. Isi **tanggal purchase** dan **tanggal estimasi terima** (opsional)
4. Tambahkan item yang dibeli:
   - Pilih produk
   - Isi kuantitas dan harga beli
5. Tambahkan **biaya tambahan** seperti ongkir atau biaya import (landed cost) jika ada
6. Isi catatan untuk supplier jika perlu
7. Lampirkan dokumen (PO, surat penawaran supplier) jika ada
8. Klik **Simpan Draft** atau **Finalize**

---

## Status Purchase

| Status | Artinya |
|--------|---------|
| **Draft** | Tersimpan, belum memengaruhi hutang atau stok |
| **Finalized** | Aktif, hutang supplier tercatat |
| **Partially Received** | Sebagian barang sudah diterima |
| **Received** | Semua barang sudah diterima |
| **Partially Paid** | Sudah ada pembayaran tapi belum lunas |
| **Paid** | Lunas |
| **Overdue** | Sudah lewat jatuh tempo, belum lunas |

---

## Penerimaan Barang (Receiving)

Saat barang dari supplier tiba:
1. Buka purchase yang bersangkutan
2. Klik **Terima Barang**
3. Periksa daftar item dan isi **jumlah yang diterima** (bisa sebagian jika pengiriman partial)
4. Konfirmasi penerimaan

Stok barang yang diterima langsung bertambah dan harga pokok persediaan diperbarui otomatis menggunakan metode **moving average**.

---

## Biaya Tambahan (Landed Cost)

Biaya tambahan seperti ongkos kirim, bea masuk, atau biaya handling bisa ditambahkan ke purchase:
- Buka form purchase, scroll ke bagian **Biaya Tambahan**
- Isi jenis biaya dan nominalnya
- Biaya ini akan ditambahkan ke total purchase dan memengaruhi harga pokok barang yang diterima

---

## Memantau Hutang Supplier

### Indikator Overdue

Purchase yang sudah melewati jatuh tempo pembayaran ditandai dengan label **Overdue**. Filter daftar purchase dengan status Overdue untuk melihat tagihan yang perlu segera dibayar.

### Aging Hutang

Untuk melihat ringkasan hutang ke semua supplier berdasarkan umur hutang, buka **Reports → Aging Hutang**.

---

## Riwayat Harga Beli

Meetra menyimpan riwayat harga beli untuk setiap produk dari setiap supplier. Ini berguna untuk:
- Membandingkan harga antar supplier
- Melihat tren kenaikan harga bahan baku
- Mengisi harga beli di purchase baru dengan referensi harga sebelumnya

Buka **Products → detail produk → Riwayat Harga** untuk melihatnya.

---

## Supplier Default per Produk

Setiap produk bisa dikonfigurasi supplier defaultnya. Saat membuat purchase dan memilih produk tersebut, supplier akan terisi otomatis — mengurangi potensi salah pilih supplier.

Atur di **Products → edit produk → Supplier Default**.
