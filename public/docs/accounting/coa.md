# Chart of Accounts (Bagan Akun)

Chart of Accounts (COA) atau Bagan Akun adalah daftar semua akun keuangan yang digunakan dalam pembukuan bisnis Anda. Setiap transaksi di Meetra akan dicatat ke salah satu akun ini.

---

## Struktur Akun

Akun dikelompokkan berdasarkan jenisnya:

| Kelompok | Contoh Akun | Posisi di Laporan |
|----------|-------------|-------------------|
| **Aset** | Kas, Bank, Piutang Usaha, Persediaan | Neraca |
| **Kewajiban** | Hutang Usaha, Hutang Pajak | Neraca |
| **Ekuitas** | Modal Pemilik, Laba Ditahan | Neraca |
| **Pendapatan** | Pendapatan Penjualan, Pendapatan Lain | Laba Rugi |
| **Beban** | HPP, Beban Operasional, Beban Pajak | Laba Rugi |

---

## Akun Default Meetra

Saat workspace dibuat, Meetra sudah menyediakan akun-akun standar yang siap dipakai:

- **1100** — Kas
- **1110** — Bank
- **1200** — Piutang Usaha
- **1300** — Persediaan
- **2100** — Hutang Usaha
- **3100** — Modal
- **4100** — Pendapatan Penjualan
- **5100** — Harga Pokok Penjualan
- **5200** — Beban Operasional

Anda bisa menggunakan akun-akun ini langsung tanpa perubahan, atau menyesuaikannya sesuai struktur bisnis Anda.

---

## Mengelola Chart of Accounts

### Melihat Daftar Akun

1. Buka **Finance → Chart of Accounts** (tersedia di mode Advanced)
2. Daftar akun akan muncul dikelompokkan berdasarkan jenis
3. Gunakan kolom pencarian untuk menemukan akun tertentu

### Menambah Akun Baru

1. Klik tombol **Tambah Akun**
2. Isi:
   - **Kode Akun** — gunakan format numerik yang konsisten (misal: 1xxx untuk aset, 2xxx untuk kewajiban)
   - **Nama Akun** — nama yang jelas dan deskriptif
   - **Jenis** — pilih jenis akun sesuai kelompoknya
   - **Akun Induk** — opsional, jika akun ini merupakan sub-akun dari akun lain
3. Klik **Simpan**

### Mengubah Akun

Klik ikon edit di samping akun yang ingin diubah. Beberapa akun bawaan sistem tidak bisa dihapus karena digunakan oleh proses otomatis.

---

## Tips Penamaan Akun

- Gunakan nama yang spesifik: **Bank BCA - Operasional** lebih baik dari sekadar **Bank**
- Jika punya beberapa rekening bank, buat akun terpisah untuk masing-masing
- Hindari membuat akun duplikat dengan nama berbeda untuk tujuan yang sama

---

## Hubungan dengan Transaksi

Setiap kali Anda membuat transaksi, Meetra secara otomatis menentukan akun yang tepat:

- **Invoice penjualan** → Debit Piutang Usaha / Kredit Pendapatan
- **Pembayaran dari customer** → Debit Bank/Kas / Kredit Piutang Usaha
- **Pembelian dari supplier** → Debit Persediaan atau Beban / Kredit Hutang Usaha
- **Bayar hutang supplier** → Debit Hutang Usaha / Kredit Bank/Kas

Untuk melihat akun mana yang terlibat dalam suatu transaksi, buka detail transaksi dan klik **Lihat Jurnal**.
