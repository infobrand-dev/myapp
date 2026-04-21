# Valuasi Stok

Valuasi stok adalah perhitungan nilai total persediaan barang yang Anda miliki berdasarkan harga pokok. Meetra menggunakan metode **Moving Average** (rata-rata bergerak) untuk semua produk.

---

## Metode Moving Average

Dengan metode ini, harga pokok rata-rata diperbarui setiap kali ada penerimaan barang baru. Rumusnya:

```
Harga Pokok Baru =
  (Nilai Stok Lama + Nilai Penerimaan Baru)
  ÷
  (Jumlah Stok Lama + Jumlah Terima Baru)
```

**Contoh:**

| Kejadian | Qty | Harga/Unit | Nilai |
|----------|-----|-----------|-------|
| Stok awal | 100 | Rp 10.000 | Rp 1.000.000 |
| Terima dari supplier | 50 | Rp 12.000 | Rp 600.000 |
| **Setelah penerimaan** | **150** | **Rp 10.667** | **Rp 1.600.000** |

Saat menjual 10 unit setelah ini, harga pokok yang digunakan adalah Rp 10.667 per unit.

---

## Melihat Laporan Valuasi Stok

1. Buka **Reports → Valuasi Stok** atau **Inventory → Laporan Valuasi**
2. Pilih tanggal atau periode
3. Laporan menampilkan:
   - Nama produk
   - Jumlah stok saat ini
   - Harga pokok rata-rata per unit
   - Total nilai persediaan

---

## Kolom di Laporan Valuasi

| Kolom | Keterangan |
|-------|-----------|
| Produk | Nama dan kode produk |
| Stok | Jumlah unit yang tersedia saat ini |
| Harga Pokok/Unit | Harga pokok rata-rata per unit (moving average) |
| Nilai Total | Stok × Harga Pokok — nilai persediaan di neraca |

---

## Hubungan dengan Neraca

Nilai total persediaan dari laporan ini seharusnya sama dengan saldo akun **Persediaan** di Neraca. Jika ada selisih, periksa apakah ada penyesuaian stok yang belum dicatat atau ada transaksi yang belum diposting.

---

## Dampak ke Harga Pokok Penjualan (HPP)

Setiap kali Anda menjual barang dan invoice di-finalize, Meetra menggunakan harga pokok moving average saat itu untuk menghitung HPP. Ini artinya:

- HPP per unit bisa berbeda antara penjualan di awal bulan dan akhir bulan jika ada penerimaan barang di antaranya
- HPP yang tercatat di jurnal akuntansi selalu mencerminkan harga pokok rata-rata yang paling terkini

---

## Tips

- Pastikan **stok awal** diinput dengan harga pokok yang benar sebelum ada transaksi
- Jika ada penyesuaian harga pokok (misalnya setelah audit), gunakan fitur **Penyesuaian Stok** dengan mencantumkan harga pokok yang dikoreksi
- Cek laporan valuasi secara rutin dan bandingkan dengan saldo akun Persediaan di Neraca untuk memastikan tidak ada selisih
