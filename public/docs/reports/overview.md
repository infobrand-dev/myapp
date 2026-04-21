# Reports — Laporan Keuangan & Analitik

Modul Reports menyediakan semua laporan untuk memahami kondisi keuangan dan performa bisnis Anda. Semua laporan bersifat **read-only** — data diambil dari transaksi di modul lain secara real-time.

---

## Daftar Laporan

### Laporan Keuangan Formal

| Laporan | Fungsi |
|---------|--------|
| **Laba Rugi** | Pendapatan, beban, dan laba/rugi bersih dalam periode tertentu |
| **Neraca** | Posisi keuangan (aset, kewajiban, ekuitas) pada tanggal tertentu |
| **Arus Kas** | Aliran kas masuk dan keluar berdasarkan kategori |
| **Trial Balance** | Ringkasan saldo semua akun — verifikasi keseimbangan pembukuan |
| **Buku Besar** | Riwayat mutasi per akun dengan drill-down ke transaksi |

### Laporan Piutang & Hutang

| Laporan | Fungsi |
|---------|--------|
| **Aging Piutang** | Piutang customer dikelompokkan per umur tagihan |
| **Aging Hutang** | Hutang supplier dikelompokkan per umur tagihan |

### Laporan Penjualan & Pembelian

| Laporan | Fungsi |
|---------|--------|
| **Penjualan per Customer** | Total penjualan dan pembayaran dikelompokkan per customer |
| **Pembelian per Supplier** | Total pembelian dan hutang dikelompokkan per supplier |
| **Margin per Produk** | Harga jual vs harga pokok — identifikasi produk paling menguntungkan |

### Laporan Inventori

| Laporan | Fungsi |
|---------|--------|
| **Valuasi Stok** | Nilai total persediaan berdasarkan harga pokok moving average |

---

## Membuka Laporan

1. Buka **Reports** dari menu navigasi
2. Pilih laporan yang diinginkan
3. Atur filter (minimal pilih periode)
4. Klik **Tampilkan**

**Filter umum yang tersedia:**
- Rentang tanggal atau bulan
- Perusahaan (jika multi-entitas)
- Cabang (jika multi-lokasi)

---

## Laporan Laba Rugi

Menampilkan performa keuangan dalam periode tertentu:

```
  Pendapatan Penjualan             Rp xxx
− Retur & Diskon                   Rp xxx
= Pendapatan Bersih                Rp xxx

− Harga Pokok Penjualan (HPP)      Rp xxx
= Laba Kotor                       Rp xxx

− Beban Operasional                Rp xxx
  (Gaji, Sewa, Utilitas, dll)
= Laba Operasional                 Rp xxx

+/− Pendapatan / Beban Lain-lain   Rp xxx
= Laba Bersih                      Rp xxx
```

**Yang perlu diperhatikan:**
- **Laba Kotor negatif** → harga jual di bawah harga pokok, perlu review harga
- **Beban operasional terlalu besar** → identifikasi kategori beban terbesar
- **Bandingkan antar periode** untuk melihat tren

---

## Aging Piutang

Menampilkan semua piutang yang belum lunas, dikelompokkan berdasarkan berapa lama sudah jatuh tempo:

| Kolom | Arti |
|-------|------|
| **Belum Jatuh Tempo** | Invoice aktif yang belum lewat due date |
| **1–30 Hari** | Terlambat 1 sampai 30 hari |
| **31–60 Hari** | Terlambat 31 sampai 60 hari |
| **61–90 Hari** | Terlambat 61 sampai 90 hari |
| **> 90 Hari** | Terlambat lebih dari 90 hari — risiko tidak tertagih tinggi |

> Prioritaskan penagihan ke customer di kolom paling kanan. Semakin lama piutang tidak dibayar, semakin besar risiko macet.

---

## Margin per Produk

Laporan ini membandingkan harga jual rata-rata dengan harga pokok untuk setiap produk dalam periode yang dipilih:

| Kolom | Arti |
|-------|------|
| Produk | Nama produk |
| Qty Terjual | Jumlah unit yang terjual |
| Total Pendapatan | Harga jual × qty |
| Total HPP | Harga pokok × qty |
| Laba Kotor | Pendapatan − HPP |
| Margin % | (Laba Kotor ÷ Pendapatan) × 100 |

Gunakan laporan ini untuk mengidentifikasi produk mana yang paling menguntungkan dan mana yang margin-nya terlalu tipis.

---

## Arus Kas

Laporan arus kas menampilkan semua pergerakan kas dan bank dalam periode tertentu, dibagi per kategori:

- **Operasional** — kas dari penjualan, pembayaran beban
- **Investasi** — pembelian aset
- **Pendanaan** — setoran modal, pinjaman

Berguna untuk memahami dari mana uang datang dan ke mana perginya, terpisah dari laporan laba rugi yang berbasis akrual.

---

## Ekspor Laporan

Semua laporan bisa diekspor ke **Excel**. Klik tombol **Ekspor** di pojok kanan atas.

Gunakan ekspor untuk:
- Laporan ke investor atau bank
- Analisis pivot di Excel
- Arsip laporan bulanan/tahunan
