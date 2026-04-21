# Chart of Accounts (Bagan Akun)

Chart of Accounts (COA) atau Bagan Akun adalah daftar terstruktur semua akun keuangan yang digunakan dalam pembukuan. Setiap transaksi di Meetra — baik otomatis maupun manual — dicatat ke salah satu akun di sini.

> Tersedia di **Mode Advanced** → **Finance → Chart of Accounts**.

---

## Kelompok Akun

Semua akun dibagi menjadi lima kelompok utama:

| Kelompok | Kode | Posisi Normal | Contoh Akun |
|----------|------|--------------|-------------|
| **Aset** | 1xxx | Debit | Kas, Bank, Piutang, Persediaan |
| **Kewajiban** | 2xxx | Kredit | Hutang Usaha, Hutang Pajak |
| **Ekuitas** | 3xxx | Kredit | Modal Pemilik, Laba Ditahan |
| **Pendapatan** | 4xxx | Kredit | Pendapatan Penjualan |
| **Beban** | 5xxx | Debit | HPP, Beban Gaji, Beban Sewa |

---

## Akun Default Meetra

Saat workspace dibuat, Meetra sudah menyediakan akun-akun dasar yang siap dipakai:

**Aset**
- `1100` — Kas
- `1110` — Bank
- `1200` — Piutang Usaha
- `1300` — Persediaan Barang

**Kewajiban**
- `2100` — Hutang Usaha
- `2200` — Hutang Pajak PPN

**Ekuitas**
- `3100` — Modal Pemilik
- `3200` — Laba Ditahan

**Pendapatan**
- `4100` — Pendapatan Penjualan
- `4900` — Pendapatan Lain-lain

**Beban**
- `5100` — Harga Pokok Penjualan (HPP)
- `5200` — Beban Gaji
- `5300` — Beban Sewa
- `5400` — Beban Utilitas
- `5900` — Beban Lain-lain

---

## Mengelola COA

### Melihat Daftar Akun

1. Buka **Finance → Chart of Accounts**
2. Akun ditampilkan dikelompokkan per jenis
3. Gunakan kolom pencarian untuk menemukan akun tertentu
4. Klik nama akun untuk melihat ringkasan saldo dan link ke jurnal terkait

### Menambah Akun Baru

1. Klik **Tambah Akun**
2. Isi:
   - **Kode Akun** — numerik, ikuti konvensi kelompok (1xxx aset, 2xxx kewajiban, dst)
   - **Nama Akun** — spesifik dan deskriptif
   - **Jenis** — Aset, Kewajiban, Ekuitas, Pendapatan, atau Beban
   - **Akun Induk** — opsional, jika ini sub-akun dari akun lain
3. Simpan

### Mengubah Akun

Klik ikon edit di samping akun. Catatan: akun yang sudah memiliki transaksi tidak bisa diubah jenisnya karena akan memengaruhi laporan historis.

### Navigasi ke Jurnal & Laporan

Dari halaman COA, klik akun mana saja untuk langsung melihat:
- Saldo akun saat ini
- Link ke Buku Besar akun tersebut
- Riwayat jurnal yang melibatkan akun ini

---

## Tips Penamaan Akun

**Beri nama yang spesifik:**
- ✅ `Bank BCA — Operasional`
- ❌ `Bank 1`

**Pisahkan rekening yang berbeda:**
- `Bank BCA — Operasional`
- `Bank Mandiri — Tabungan`
- `Kas Toko`

**Gunakan sub-akun untuk detail:**
- `5200 — Beban Gaji` (induk)
  - `5201 — Gaji Pokok`
  - `5202 — Tunjangan`
  - `5203 — BPJS Ketenagakerjaan`

---

## Akun yang Tidak Bisa Dihapus

Beberapa akun sistem tidak bisa dihapus karena digunakan oleh proses otomatis Meetra — misalnya akun Piutang Usaha yang terhubung ke modul Sales, atau akun Hutang Usaha yang terhubung ke Purchases. Akun-akun ini bisa diganti namanya tapi tidak bisa dihapus.
