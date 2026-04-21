# Inventory — Stok & Persediaan

Modul Inventory mengelola stok barang secara real-time — setiap transaksi penjualan atau pembelian otomatis memengaruhi jumlah stok di sistem.

---

## Cara Kerja Stok di Meetra

Stok bergerak otomatis berdasarkan transaksi:

| Transaksi | Efek ke Stok |
|-----------|--------------|
| Penerimaan barang dari supplier | Stok **bertambah** |
| Invoice penjualan di-finalize | Stok **berkurang** |
| Retur dari customer | Stok **bertambah** |
| Retur ke supplier | Stok **berkurang** |
| Penyesuaian stok (adjustment) | Bertambah atau berkurang sesuai input |
| Transfer antar gudang | Stok berpindah lokasi |

---

## Melihat Stok Saat Ini

1. Buka **Inventory → Stok**
2. Tampil daftar semua produk beserta jumlah stok saat ini
3. Klik produk untuk melihat detail termasuk riwayat mutasi

---

## Stok Awal (Opening Stock)

Saat pertama kali menggunakan Meetra, stok awal perlu diinput manual:

1. Buka **Inventory → Stok Awal**
2. Klik **Tambah Stok Awal**
3. Pilih produk, isi jumlah, harga pokok per unit, dan tanggal efektif
4. Konfirmasi

Stok awal sebaiknya diinput di awal sebelum ada transaksi penjualan atau pembelian, agar perhitungan harga pokok berjalan dengan benar.

---

## Penyesuaian Stok (Adjustment)

Jika ada selisih stok karena barang rusak, hilang, atau hasil opname:

1. Buka **Inventory → Penyesuaian**
2. Pilih produk dan isi **jumlah aktual** saat ini
3. Sistem akan menghitung selisih antara stok tercatat dan jumlah aktual
4. Tambahkan keterangan alasan penyesuaian
5. Konfirmasi

---

## Transfer Stok

Jika Anda memiliki lebih dari satu gudang atau lokasi:

1. Buka **Inventory → Transfer**
2. Pilih **gudang asal** dan **gudang tujuan**
3. Pilih produk dan jumlah yang dipindah
4. Konfirmasi transfer

Stok di gudang asal berkurang dan di gudang tujuan bertambah secara bersamaan.

---

## Minimum Stok & Reorder Point

Untuk setiap produk, Anda bisa mengatur:

**Minimum Stok** — batas stok terendah yang seharusnya ada. Produk dengan stok di bawah batas ini akan ditandai di daftar.

**Reorder Point** — titik di mana Anda perlu mulai memesan lagi ke supplier.

Atur di **Products → edit produk → Pengaturan Stok**.

---

## Riwayat Mutasi Stok

Untuk melihat semua pergerakan stok suatu produk:

1. Buka **Inventory → Mutasi Stok**
2. Filter berdasarkan produk, tanggal, atau jenis mutasi
3. Setiap baris menampilkan: tanggal, jenis mutasi, jumlah, dan saldo stok setelah mutasi

---

## Valuasi Stok

Meetra menggunakan metode **Moving Average** untuk menghitung harga pokok persediaan. Setiap penerimaan barang akan memperbarui harga pokok rata-rata secara otomatis.

[Pelajari lebih lanjut tentang Valuasi Stok →](valuation.md)
