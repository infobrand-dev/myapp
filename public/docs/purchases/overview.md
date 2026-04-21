# Purchases — Pembelian & Supplier

Modul Purchases mengelola transaksi pembelian dari supplier — mulai dari pencatatan pembelian, penerimaan barang parsial, biaya tambahan, hingga pelunasan hutang.

---

## Alur Pembelian

```
Buat Purchase  →  Finalize  →  Terima Barang  →  Bayar Supplier  →  Lunas
   (Draft)                      (Receiving)       (Payments)

                   ↓                  ↓
          Hutang Usaha tercatat   Stok bertambah
          Auto Journal di GL      Harga pokok diupdate
```

---

## Membuat Purchase

1. Buka **Purchases → Buat Purchase**
2. Pilih **supplier** dari Contacts
3. Isi **tanggal purchase** dan **estimasi tanggal terima** (opsional — berguna untuk monitoring)
4. Tambahkan item yang dibeli:
   - Pilih produk
   - Isi kuantitas dan harga beli
5. Tambahkan biaya tambahan jika ada (lihat Landed Cost di bawah)
6. Lampirkan dokumen (PO, surat penawaran) jika perlu
7. Pilih **Simpan Draft** atau **Finalize**

---

## Status Purchase

| Status | Arti |
|--------|------|
| **Draft** | Tersimpan, belum memengaruhi hutang atau stok |
| **Finalized** | Aktif, hutang supplier tercatat di GL |
| **Partially Received** | Sebagian item sudah diterima |
| **Received** | Semua item sudah diterima |
| **Partially Paid** | Sudah ada pembayaran, belum lunas |
| **Paid** | Lunas |
| **Overdue** | Melewati jatuh tempo, belum lunas |

---

## Penerimaan Barang (Receiving)

Meetra mendukung **partial receiving** — Anda bisa menerima barang sebagian demi sebagian jika supplier mengirim dalam beberapa tahap.

**Cara menerima barang:**
1. Buka detail purchase yang sudah di-finalize
2. Klik **Terima Barang**
3. Isi jumlah yang diterima untuk setiap item (tidak harus semua sekaligus)
4. Konfirmasi

Setiap penerimaan:
- Menambah stok barang di Inventory
- Memperbarui harga pokok rata-rata (moving average)
- Status purchase berubah ke Partially Received atau Received

Jika barang diterima dalam 3 termin, cukup lakukan proses Terima Barang sebanyak 3 kali dengan jumlah masing-masing.

---

## Landed Cost (Biaya Tambahan)

Biaya pengadaan selain harga beli — seperti ongkos kirim, bea masuk, biaya handling — bisa ditambahkan ke purchase agar masuk ke harga pokok barang.

**Cara menambahkan:**
1. Di form purchase, scroll ke bagian **Biaya Tambahan**
2. Klik **Tambah Biaya**
3. Pilih jenis biaya dan isi nominal
4. Biaya ini akan ditambahkan ke total purchase dan dialokasikan ke harga pokok item

Contoh: Beli 100 unit @ Rp 10.000 + ongkir Rp 500.000 → harga pokok per unit menjadi Rp 10.500.

---

## Supplier Bill Tracking

Setiap purchase yang di-finalize otomatis membuat **hutang supplier** yang bisa dipantau. Status hutang ditampilkan di halaman detail purchase:

- **Belum dibayar** — total hutang masih penuh
- **Dibayar sebagian** — ada pembayaran tapi belum lunas, lengkap dengan sisa yang masih terutang
- **Lunas** — semua hutang sudah dilunasi

---

## Memantau Hutang Supplier

### Indikator Overdue

Purchase yang melewati jatuh tempo ditandai label **Overdue**. Filter daftar dengan status Overdue untuk melihat tagihan yang perlu segera dibayar.

### Aging Hutang

Untuk ringkasan hutang ke semua supplier dikelompokkan berdasarkan umurnya, buka **Reports → Aging Hutang**.

---

## Riwayat Harga Beli

Meetra menyimpan riwayat harga beli setiap produk dari setiap supplier. Berguna untuk:
- Membandingkan harga antar supplier sebelum memutuskan pembelian
- Melihat tren kenaikan harga bahan baku
- Mengisi harga di purchase baru dengan referensi harga terakhir

Akses di **Products → detail produk → Riwayat Harga**.
