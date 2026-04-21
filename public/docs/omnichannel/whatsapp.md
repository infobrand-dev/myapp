# WhatsApp

Meetra terhubung ke WhatsApp menggunakan **WhatsApp Cloud API** resmi dari Meta — bukan WhatsApp Web atau aplikasi pihak ketiga. Ini memastikan koneksi yang stabil, aman, dan sesuai kebijakan WhatsApp.

---

## Apa Itu WhatsApp Cloud API?

WhatsApp Cloud API adalah jalur resmi dari Meta untuk bisnis yang ingin menggunakan WhatsApp secara programatik. Dengan ini, Anda bisa:

- Menerima dan membalas pesan dari nomor WhatsApp bisnis
- Mengirim template pesan (pesan terstruktur yang disetujui Meta)
- Mengelola banyak percakapan dari satu dashboard
- Menghubungkan lebih dari satu nomor WhatsApp dalam satu workspace

---

## Menghubungkan Nomor WhatsApp

Sebelum menghubungkan, pastikan Anda sudah memiliki:
- Akun **Meta Business Manager** yang terverifikasi
- Nomor telepon yang didaftarkan sebagai **WhatsApp Business**
- Akses ke **WhatsApp Business Platform** di Meta for Developers

Langkah menghubungkan:
1. Buka **Pengaturan → Channels → WhatsApp**
2. Klik **Tambah Akun WhatsApp**
3. Ikuti proses autentikasi dengan akun Meta Anda
4. Pilih nomor WhatsApp yang ingin dihubungkan
5. Selesai — pesan masuk akan mulai muncul di Conversations

---

## Template Pesan

WhatsApp membatasi pengiriman pesan pertama (outbound) ke pelanggan — Anda hanya bisa memulai percakapan menggunakan **template pesan** yang sudah disetujui Meta.

Template berguna untuk:
- Konfirmasi order
- Notifikasi pengiriman
- Pengingat pembayaran
- Pesan promosi (dengan kategori Marketing)

### Membuat Template

1. Buka **WhatsApp → Template**
2. Klik **Buat Template Baru**
3. Isi nama, kategori, bahasa, dan isi pesan
4. Submit untuk review ke Meta (proses review biasanya 1–24 jam)
5. Setelah disetujui, template siap digunakan

### Menggunakan Template

Saat ingin memulai percakapan baru ke nomor yang belum pernah menghubungi Anda, atau memulai ulang setelah jendela 24 jam:
1. Buka percakapan atau buat percakapan baru
2. Pilih **Kirim Template**
3. Pilih template yang sudah disetujui
4. Isi variabel jika ada (misal: nama customer, nomor order)
5. Kirim

---

## Jendela Percakapan 24 Jam

WhatsApp memberlakukan aturan **jendela percakapan 24 jam**: setelah pelanggan mengirim pesan ke Anda, Anda punya waktu 24 jam untuk membalas dengan pesan bebas. Setelah 24 jam, Anda hanya bisa menggunakan template pesan.

Meetra menampilkan indikator waktu tersisa di setiap percakapan agar tim Anda tidak melewatkan jendela tersebut.

---

## Mengelola Beberapa Nomor

Jika bisnis Anda menggunakan lebih dari satu nomor WhatsApp (misalnya untuk departemen berbeda atau cabang berbeda), Anda bisa menghubungkan beberapa nomor ke satu workspace. Setiap percakapan akan menampilkan dari nomor mana pesan diterima.

---

## Pertanyaan Umum

**Apakah bisa pakai nomor WhatsApp Personal?**
Tidak. WhatsApp Cloud API hanya untuk nomor yang terdaftar sebagai WhatsApp Business. Nomor personal tidak bisa digunakan.

**Apakah ada biaya penggunaan WhatsApp?**
Meta mengenakan biaya per percakapan berdasarkan kategori dan negara tujuan. Biaya ini terpisah dari biaya langganan Meetra. Cek tarif terkini di halaman resmi Meta for Developers.

**Apakah pesan lama bisa diimport?**
Riwayat pesan sebelum akun dihubungkan ke Meetra tidak bisa diimport. Hanya pesan yang masuk setelah koneksi aktif yang akan muncul di Conversations.
