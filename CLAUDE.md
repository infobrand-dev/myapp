# CLAUDE.md — UI/UX Guidelines for This Project

Panduan ini **wajib diikuti** setiap kali membuat atau memodifikasi tampilan (blade views).
Tujuannya: semua halaman terlihat konsisten, tidak ambigu, dan modern.

---

## Stack & Framework

- **Primary UI**: Tabler (Bootstrap 5) — gunakan class Tabler untuk semua halaman admin.
- **Secondary**: Tailwind utility — hanya untuk halaman auth (`/login`, `/register`, dll).
- **Icons**: Tabler Icons via `ti ti-*` class (CDN). Gunakan ini, bukan SVG inline kecuali sudah ada sebelumnya.
- **JS utilities**: `window.AppToast.success/error/warning/info()` untuk notifikasi, `data-confirm="..."` untuk konfirmasi delete.
- **Jangan** tambahkan framework CSS baru atau `<style>` block di dalam blade file. Semua custom CSS masuk ke `resources/css/theme.css`.

---

## Struktur Halaman Admin (Standar)

Setiap halaman admin mengikuti pola ini:

```blade
@extends('layouts.admin')

@section('title', 'Judul Halaman')  {{-- opsional, default = config('app.name') --}}

@section('content')

{{-- 1. Page Header --}}
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <div class="page-pretitle">Kategori / Breadcrumb</div>
            <h2 class="page-title">Judul Halaman</h2>
            {{-- Deskripsi opsional — pakai <p> bukan <div>, mb-0 WAJIB --}}
            <p class="text-muted mb-0">Deskripsi singkat halaman ini.</p>
        </div>
        {{-- Tombol aksi (jika ada) --}}
        <div class="col-auto">
            @can('resource.create')
                <a href="{{ route('resource.create') }}" class="btn btn-primary">
                    <i class="ti ti-plus me-1"></i>Tambah X
                </a>
            @endcan
        </div>
    </div>
</div>

{{-- 2. Konten utama --}}

@endsection
```

**Aturan page-header:**
- WAJIB gunakan `<div class="page-header">` + `<div class="row align-items-center">` + `<div class="col">` + `<div class="col-auto">`.
- **JANGAN** pakai `d-flex justify-content-between` langsung di `.page-header` — Tabler sudah punya `flex-wrap: wrap` di `.page-header`, sehingga button bisa turun ke bawah jika ada deskripsi 3 baris.
- `page-pretitle` = konteks/kategori (misal: "Administrasi", "Konfigurasi", "Modul")
- `page-title` menggunakan `<h2>`, bukan `<h1>` — kecuali halaman platform yang memang `<h1>`
- Deskripsi di bawah title: gunakan `<p class="text-muted mb-0">` — bukan `<div class="text-muted small mt-1">`. `mb-0` wajib agar tidak ada gap berlebih.
- Tombol aksi: primary = `btn btn-primary`, secondary = `btn btn-outline-secondary`
- Jika ada banyak tombol: `<div class="col-auto d-flex gap-2 flex-wrap">`
- Jika tidak ada tombol: hapus `<div class="col-auto">` sama sekali

---

## Halaman Index (List/Tabel)

### Struktur Standar

```blade
<div class="card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-vcenter table-hover">
                <thead>
                    <tr>
                        <th>Kolom A</th>
                        <th>Kolom B</th>
                        <th class="w-1"></th>  {{-- kolom aksi --}}
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    <tr>
                        <td>{{ $item->field }}</td>
                        <td>{{ $item->field2 }}</td>
                        <td class="text-end align-middle">
                            <div class="table-actions">
                                {{-- tombol aksi --}}
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="N" class="text-center py-5">
                            {{-- empty state --}}
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer">
        {{ $items->links() }}
    </div>
</div>
```

**Aturan tabel:**
- Selalu gunakan `card-body p-0` sebagai wrapper `table-responsive` — ini menghilangkan padding berlebih di sisi tabel.
- Selalu gunakan `table table-vcenter` sebagai class tabel. Tambahkan `table-hover` jika baris bisa diklik/diedit.
- Kolom aksi: `<th class="w-1"></th>` + `<td class="text-end align-middle">`.
- Pagination selalu di `card-footer`, bukan di dalam `card-body`.

### Tombol Aksi di Tabel

Gunakan **icon-only buttons** untuk aksi di tabel (konsisten di semua halaman):

```blade
{{-- Edit --}}
<a href="{{ route('resource.edit', $item) }}" class="btn btn-icon btn-sm btn-outline-primary" title="Edit">
    <i class="ti ti-pencil"></i>
</a>

{{-- Delete --}}
<form class="d-inline-block m-0" method="POST" action="{{ route('resource.destroy', $item) }}">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-icon btn-sm btn-outline-danger" title="Hapus" data-confirm="Hapus {{ $item->name }}?">
        <i class="ti ti-trash"></i>
    </button>
</form>

{{-- View/Detail --}}
<a href="{{ route('resource.show', $item) }}" class="btn btn-icon btn-sm btn-outline-secondary" title="Lihat Detail">
    <i class="ti ti-eye"></i>
</a>
```

**Aturan tombol aksi tabel:**
- Selalu `btn-icon btn-sm` + `btn-outline-{warna}`.
- Edit = `btn-outline-primary`, Delete = `btn-outline-danger`, View = `btn-outline-secondary`.
- Selalu tambahkan `title="..."` untuk tooltip aksesibilitas.
- Gunakan icon Tabler (`ti ti-pencil`, `ti ti-trash`, dll) — **jangan SVG inline** untuk tombol baru.
- Urutan: View → Edit → Delete (dari kiri ke kanan).
- Jangan campur icon-only dan text-only di halaman yang sama.

### Empty State

```blade
<tr>
    <td colspan="N" class="text-center py-5">
        <i class="ti ti-{icon-relevan} text-muted d-block mb-2" style="font-size:2rem;"></i>
        <div class="text-muted mb-2">Belum ada data.</div>
        @can('resource.create')
            <a href="{{ route('resource.create') }}" class="btn btn-sm btn-primary">Tambah Pertama</a>
        @endcan
    </td>
</tr>
```

---

## Halaman Create & Edit (Form)

### Struktur Standar

```blade
@extends('layouts.admin')

@section('content')

<div class="page-header d-flex align-items-center justify-content-between">
    <div>
        <div class="page-pretitle">Kategori</div>
        <h2 class="page-title">Tambah / Edit X</h2>
    </div>
    <a href="{{ route('resource.index') }}" class="btn btn-outline-secondary">
        <i class="ti ti-arrow-left me-1"></i>Kembali
    </a>
</div>

<form method="POST" action="{{ route(...) }}">
    @csrf
    @isset($item) @method('PUT') @endisset

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Informasi X</h3>
        </div>
        <div class="card-body">
            <div class="row g-3">
                {{-- field-field form --}}
            </div>
        </div>
        <div class="card-footer d-flex justify-content-end gap-2">
            <a href="{{ route('resource.index') }}" class="btn btn-outline-secondary">Batal</a>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i>Simpan
            </button>
        </div>
    </div>

</form>

@endsection
```

### Field Form

```blade
{{-- Input text --}}
<div class="col-md-6">
    <label class="form-label">Label <span class="text-danger">*</span></label>
    <input type="text" name="field" class="form-control @error('field') is-invalid @enderror"
           value="{{ old('field', $item->field ?? '') }}">
    @error('field')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    <div class="form-hint">Teks bantuan opsional di sini.</div>
</div>

{{-- Select --}}
<div class="col-md-6">
    <label class="form-label">Label</label>
    <select name="field" class="form-select @error('field') is-invalid @enderror">
        <option value="">- Pilih -</option>
    </select>
    @error('field') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>

{{-- Textarea --}}
<div class="col-12">
    <label class="form-label">Deskripsi</label>
    <textarea name="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $item->description ?? '') }}</textarea>
    @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
```

**Aturan form:**
- `form-label` selalu ada di atas field.
- Field wajib diberi `<span class="text-danger">*</span>` di label.
- Error tampilkan dengan `@error` + `is-invalid` + `invalid-feedback`.
- Hint/bantuan (bukan error) gunakan `<div class="form-hint">`.
- Layout gunakan `row g-3` dengan `col-md-6` atau `col-12` sesuai kebutuhan.
- Tombol submit di `card-footer`: kanan, urutan Batal → Simpan.
- Simpan = `btn btn-primary` + icon `ti-device-floppy`.
- Batal = `btn btn-outline-secondary` (link ke index, bukan `<button type="button">`).

---

## Dashboard & KPI Cards

Dashboard **tenant** (halaman `/dashboard`) menggunakan custom classes dari `theme.css`:
- Hero: `dashboard-hero dashboard-hero--{pagi|siang|sore|malam}`
- KPI cards: `dashboard-kpi`
- Panel bawah: `dashboard-panel`
- Timeline item: `dashboard-timeline-item`
- Mini stat item: `dashboard-mini-stat`
- Badge kecil: `dashboard-chip`

Dashboard **platform** (`/platform/dashboard`) menggunakan standard Tabler `card` dengan `card-body`.

**Aturan:**
- Jangan gunakan `dashboard-*` classes di luar halaman dashboard tenant.
- Jangan gunakan standard `card` di dashboard tenant — gunakan custom classes yang sudah ada.
- KPI card pattern: label uppercase small muted di atas + angka besar `fs-1 fw-bold` + subtitle muted kecil di bawah.
- Jika menambah KPI card baru di dashboard tenant, gunakan `col-12 col-sm-6 col-xl-3` dan class `dashboard-kpi`.
- **Icon di KPI card**: JANGAN pakai `<span class="text-{color}"><i class="ti ti-..."></i></span>` — Tabler v2 menyuntikkan `--tblr-text-opacity` sebagai CSS custom property yang bisa bleed ke sibling elements dan mengubah warna subtitle. Gunakan inline style langsung di elemen icon:
  ```blade
  <i class="ti ti-{icon}" style="font-size:1.3rem; color:var(--tblr-{color});"></i>
  ```

---

## Badges & Status Label

```blade
{{-- Status aktif/tidak --}}
<span class="badge bg-green-lt text-green">Aktif</span>
<span class="badge bg-red-lt text-red">Nonaktif</span>

{{-- Status warning --}}
<span class="badge bg-orange-lt text-orange">Perlu Perhatian</span>

{{-- Info/neutral --}}
<span class="badge bg-azure-lt text-azure">Info</span>
<span class="badge bg-secondary-lt text-secondary">Default</span>

{{-- Tag/label di tabel --}}
<span class="badge bg-blue-lt text-blue">Tag</span>
```

**Pola**: selalu `bg-{color}-lt text-{color}` — bukan `bg-{color}` solid (terlalu mencolok untuk data di tabel).

---

## Warna & Kontras

### Palet semantik — gunakan warna sesuai makna, jangan acak

| Makna | Class badge | Class teks | Class ikon | Contoh konteks |
|---|---|---|---|---|
| Sukses / Aktif / OK | `bg-green-lt text-green` | `text-green` | `text-green` | Status aktif, berhasil, terverifikasi |
| Bahaya / Error / Hapus | `bg-red-lt text-red` | `text-danger` | `text-red` | Error, nonaktif kritis, tombol hapus |
| Peringatan / Hampir habis | `bg-orange-lt text-orange` | `text-orange` | `text-orange` | Perlu perhatian, near-limit, draft |
| Info / AI / Neutral-positif | `bg-azure-lt text-azure` | `text-azure` | `text-azure` | Info, AI credits, tanpa batas |
| Netral / Tidak aktif | `bg-secondary-lt text-secondary` | `text-muted` | `text-muted` | OFF, nonaktif, belum ada data |
| Tipe / Kategori / Tag | `bg-blue-lt text-blue` | `text-blue` | `text-blue` | Label modul, kategori |
| Spesial / Mode lanjut | `bg-indigo-lt text-indigo` | `text-indigo` | `text-indigo` | Mode kombinasi, advanced feature |
| Rekam / Sync / Live | `bg-cyan-lt text-cyan` | `text-cyan` | `text-cyan` | Mirror, sync, live channel |

### Aturan kontras — jangan nabrak

1. **Jangan pakai warna solid di badge dalam tabel** — `bg-green`, `bg-red` (solid) membuat tampilan terlalu ramai. Selalu pakai `-lt` (light variant).
2. **Jangan pakai dua warna panas berdampingan** — misal `text-red` di samping `text-orange` dalam satu baris tabel. Salah satu harus netral.
3. **Jangan pakai warna untuk dekorasi** — warna hanya untuk menyampaikan makna (sukses, error, warning, info). Jika tidak ada makna, pakai `text-muted` atau `bg-secondary-lt`.
4. **Teks biasa di tabel = tidak berwarna** — data seperti nama, email, tanggal, angka harus `text-body` (default) atau `text-muted small`. Jangan diberi warna tanpa alasan.
5. **Jangan pakai `text-bg-*`** (Bootstrap 5 utility) — ini menghasilkan warna solid. Gunakan pola Tabler `bg-*-lt text-*` yang lebih lembut dan kontras aman.
6. **Icon di KPI card** — warna icon harus sesuai makna kolomnya (sukses = `text-green`, error = `text-red`, dll), bukan semua satu warna.
7. **Tombol di card-footer** — hanya dua tombol: Batal (`btn-outline-secondary`, abu-abu) dan Simpan (`btn-primary`, warna brand). Jangan tambah warna lain di sini.
8. **Alert / notice** — `alert-azure` untuk info netral, `alert-warning` untuk perhatian, `alert-danger` untuk error kritis, `alert-success` untuk konfirmasi. Jangan pakai `alert-primary` untuk info biasa.

### Warna yang TIDAK BOLEH dicampur dalam satu elemen

- `text-red` + background merah → kontras hilang
- `bg-green` (solid) di dalam tabel yang sudah ramai dengan data
- `text-warning` (kuning Bootstrap) → pakai `text-orange` Tabler, lebih terbaca
- `text-info` (cyan Bootstrap) → pakai `text-azure` atau `text-cyan` Tabler
- Dua badge warna berbeda dalam satu `<td>` tanpa spasi/pemisah yang jelas

---

## Notifikasi & Feedback

- Flash message (redirect) → otomatis ditampilkan oleh layout via `session('success')`, `session('error')`, dll. Cukup `return redirect()->with('success', '...')`.
- Toast real-time (AJAX/JS) → `window.AppToast.success('Berhasil disimpan')`.
- Konfirmasi delete → `data-confirm="Yakin hapus X?"` pada button/form submit.
- Jangan buat modal konfirmasi custom untuk operasi delete standar — gunakan `data-confirm`.

---

## Konsistensi Lintas Halaman — Checklist

Sebelum selesai mengerjakan halaman baru atau modifikasi, verifikasi:

- [ ] `page-header` mengikuti struktur standar (pretitle + title + aksi kanan)
- [ ] Tabel pakai `card > card-body p-0 > table-responsive > table table-vcenter`
- [ ] Tombol aksi tabel: icon-only, `btn-icon btn-sm btn-outline-{warna}`, ada `title`
- [ ] Urutan tombol tabel: View → Edit → Delete
- [ ] Empty state ada icon + teks muted + CTA button (jika relevan)
- [ ] Form ada di dalam `card` dengan `card-footer` berisi tombol Batal + Simpan
- [ ] Semua icon menggunakan `ti ti-*` (Tabler Icons) — bukan SVG inline untuk elemen baru
- [ ] Tidak ada `<style>` block di blade file
- [ ] Nama aplikasi pakai `config('app.name')` bukan hardcode
- [ ] Error validation pakai `@error` + `is-invalid` + `invalid-feedback`
- [ ] Pagination ada di `card-footer`
- [ ] `@section('title', '...')` diisi di setiap halaman

---

## Hal yang Dilarang

- Hardcode nama aplikasi ("MyApp", "SIPD", dll) di blade — pakai `config('app.name')`
- Tambah `<style>` block di blade file — semua CSS ke `theme.css`
- Gunakan Tailwind utility di halaman admin (hanya untuk auth pages)
- Campur icon-only buttons dan text buttons dalam satu tabel
- Buat modal konfirmasi custom untuk operasi delete yang bisa ditangani `data-confirm`
- Gunakan `btn btn-danger` (solid merah) di tabel — pakai `btn-outline-danger`
- Tambahkan framework CSS atau JS baru tanpa diskusi terlebih dahulu
