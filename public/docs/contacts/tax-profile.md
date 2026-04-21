# Profil Pajak & NPWP

Setiap kontak di Meetra bisa dilengkapi dengan informasi perpajakan. Data ini digunakan untuk keperluan faktur pajak, rekap pajak, dan kepatuhan terhadap kewajiban perpajakan bisnis Anda.

---

## Data Pajak yang Bisa Disimpan

| Field | Keterangan |
|-------|-----------|
| **NPWP** | Nomor Pokok Wajib Pajak perusahaan atau perorangan |
| **Nama Pajak** | Nama yang tertera di NPWP (bisa berbeda dengan nama usaha) |
| **Alamat Pajak** | Alamat yang terdaftar di NPWP |
| **Status PKP** | Apakah kontak ini Pengusaha Kena Pajak (PKP) atau bukan |

---

## Mengisi Data Pajak Kontak

1. Buka **Contacts** dan pilih kontak yang ingin diupdate
2. Klik **Edit**
3. Scroll ke bagian **Informasi Pajak**
4. Isi NPWP, nama pajak, dan alamat pajak
5. Centang **PKP** jika kontak tersebut adalah Pengusaha Kena Pajak
6. Simpan

---

## Kenapa Status PKP Penting?

Status PKP menentukan apakah transaksi dengan kontak ini kena PPN atau tidak:

- **Customer PKP** — berhak mendapatkan faktur pajak dari Anda (jika Anda juga PKP)
- **Supplier PKP** — bisa memberikan faktur pajak kepada Anda sebagai pajak masukan
- **Non-PKP** — transaksi umumnya tidak menggunakan mekanisme PPN

---

## Rekap Pajak

Untuk melihat ringkasan pajak dari semua transaksi dalam periode tertentu:

1. Buka **Finance → Pajak** atau **Reports → Rekap Pajak**
2. Pilih periode
3. Laporan menampilkan total pajak keluaran (dari penjualan) dan pajak masukan (dari pembelian)

---

## Master Pajak

Selain profil pajak per kontak, Meetra juga menyediakan master pajak untuk mengatur tarif pajak yang berlaku:

1. Buka **Finance → Master Pajak**
2. Tambahkan atau edit tarif pajak (contoh: PPN 11%, PPh 23 2%)
3. Setiap tarif pajak terhubung ke akun jurnal pajak yang sesuai

Master pajak ini digunakan saat menambahkan pajak ke invoice atau purchase.

---

## Tips

- Isi NPWP customer sebelum membuat invoice jika Anda perlu menerbitkan faktur pajak
- Pastikan status PKP customer sudah benar sebelum menerapkan PPN di invoice
- Gunakan rekap pajak setiap bulan untuk menyiapkan laporan SPT Masa PPN
