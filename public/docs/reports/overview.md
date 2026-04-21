# Reports — Laporan Keuangan

Modul Reports menyediakan seluruh laporan keuangan dan analitik bisnis Anda — dari laporan operasional harian hingga laporan keuangan formal.

---

## Laporan yang Tersedia

### Laporan Keuangan

| Laporan | Fungsi |
|---------|--------|
| [Laba Rugi](profit-loss.md) | Pendapatan, beban, dan keuntungan dalam periode tertentu |
| [Neraca](../accounting/balance-sheet.md) | Posisi keuangan pada tanggal tertentu |
| [Arus Kas](cash-flow.md) | Aliran kas masuk dan keluar |
| [Trial Balance](../accounting/trial-balance.md) | Ringkasan saldo semua akun |
| [Buku Besar](../accounting/general-ledger.md) | Riwayat mutasi per akun |

### Laporan Piutang & Hutang

| Laporan | Fungsi |
|---------|--------|
| [Aging Piutang](aging-receivable.md) | Piutang customer dikelompokkan per umur tagihan |
| [Aging Hutang](aging-payable.md) | Hutang supplier dikelompokkan per umur tagihan |

### Laporan Penjualan & Pembelian

| Laporan | Fungsi |
|---------|--------|
| [Penjualan per Customer](sales-by-customer.md) | Total penjualan dikelompokkan per customer |
| [Pembelian per Supplier](purchase-by-supplier.md) | Total pembelian dikelompokkan per supplier |
| [Margin per Produk](product-margin.md) | Perbandingan harga jual vs harga pokok per produk |

### Laporan Inventori

| Laporan | Fungsi |
|---------|--------|
| [Valuasi Stok](../inventory/valuation.md) | Nilai total persediaan berdasarkan harga pokok |

---

## Cara Membuka Laporan

1. Buka **Reports** dari menu navigasi
2. Pilih laporan yang diinginkan
3. Tentukan **periode** (tanggal mulai dan tanggal akhir)
4. Opsional: filter berdasarkan perusahaan, cabang, atau dimensi lain
5. Klik **Tampilkan**

---

## Filter yang Tersedia

Sebagian besar laporan mendukung filter berikut:

- **Periode** — bulan, kuartal, tahun, atau rentang tanggal custom
- **Perusahaan** — jika Anda mengelola beberapa entitas
- **Cabang** — jika bisnis Anda memiliki beberapa lokasi

---

## Ekspor Laporan

Semua laporan bisa diekspor ke **Excel** untuk keperluan:
- Pelaporan ke stakeholder atau investor
- Analisis lebih lanjut
- Pengarsipan

Klik tombol **Ekspor** atau **Download** di pojok kanan atas halaman laporan.

---

## Laporan Aging — Panduan Cepat

### Aging Piutang

Menampilkan piutang yang belum dibayar, dikelompokkan berdasarkan berapa lama sudah jatuh tempo:

| Kolom | Artinya |
|-------|---------|
| Belum Jatuh Tempo | Invoice yang belum jatuh tempo |
| 1–30 Hari | Terlambat 1–30 hari |
| 31–60 Hari | Terlambat 31–60 hari |
| 61–90 Hari | Terlambat 61–90 hari |
| > 90 Hari | Terlambat lebih dari 90 hari |

Prioritaskan penagihan ke customer di kolom paling kanan — semakin lama tidak dibayar, semakin besar risiko tidak tertagih.

### Aging Hutang

Sama seperti aging piutang, tapi menampilkan hutang Anda ke supplier. Gunakan laporan ini untuk merencanakan pembayaran agar tidak ada hutang yang terlewat jatuh tempo.

---

## Laporan Laba Rugi — Cara Membaca

Laporan Laba Rugi menampilkan:

```
Pendapatan Penjualan
- Harga Pokok Penjualan (HPP)
= Laba Kotor

- Beban Operasional
= Laba Operasional

+/- Pendapatan / Beban Lain-lain
= Laba Bersih
```

**Laba Kotor** menunjukkan efisiensi produksi atau pembelian barang.
**Laba Bersih** menunjukkan keuntungan setelah semua biaya operasional.

---

## Tips Menggunakan Laporan

- **Bandingkan antar periode** — Lihat laba rugi bulan ini vs bulan lalu untuk mendeteksi tren
- **Pantau aging piutang mingguan** — Jangan tunggu akhir bulan untuk tahu piutang mana yang menunggak
- **Gunakan margin per produk** — Identifikasi produk mana yang paling menguntungkan dan mana yang margin-nya tipis
- **Rekonsiliasi sebelum tutup buku** — Sebelum akhir periode, pastikan trial balance seimbang dan neraca tidak ada anomali
