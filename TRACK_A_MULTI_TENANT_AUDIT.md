# Track A - Multi-tenant Scope Audit

Tanggal audit: 2026-03-21

## Tujuan
Track A dipakai untuk memetakan gap scope enforcement sebelum lanjut ke implementasi hardening berikutnya.
Fokus audit:
1. Sales read/write scope
2. Payments read/write scope
3. Reports legacy scope patterns

---

## Ringkasan prioritas

### P0 - Kritikal
1. **Sales detail/edit/invoice memakai route model binding tanpa scope guard eksplisit**
   - `SaleController@show`, `edit`, dan `invoice` menerima `Sale $sale` lalu membaca model tersebut tanpa re-query scoped lebih dulu.
   - `SaleRepository::findForDetail()` dan `findForEdit()` saat ini hanya `load(...)`, bukan `findOrFail()` dalam query yang dibatasi `tenant_id + company_id + branch`.
   - Dampak: user yang tahu ID sale berpotensi mencoba akses detail lintas scope bila route binding berhasil resolve model.

2. **Sebagian besar report service masih belum mengikuti active runtime context**
   - `PaymentReportService`, `FinanceReportService`, `PosReportService` masih memakai pola legacy `applyOutlet(...)`.
   - `InventoryReportService` dan `PurchaseReportService` bahkan masih membangun query agregasi tanpa guard `tenant/company/branch` yang jelas.
   - `DashboardReportService` mewarisi risiko ini karena hanya mendelegasikan ke service-service report tersebut.

### P1 - Tinggi
3. **Payment lookup untuk sale return belum branch-aware**
   - `PaymentLookupService::saleReturnOptions()` membatasi `tenant_id + company_id`, tetapi belum menerapkan `BranchContext::applyScope(...)`.
   - Ini bisa menampilkan opsi refund return lintas branch saat company sama.

4. **Search payment allocation ke SaleReturn belum branch-aware**
   - `PaymentRepository::applyFilters()` menerapkan branch scope pada morph `Sale`, tetapi tidak pada morph `SaleReturn`.
   - Risiko utamanya ada di hasil pencarian list payment yang bisa mencocokkan dokumen refund dari branch lain dalam company yang sama.

### P2 - Menengah
5. **Receivers di Payments masih tenant-wide**
   - `PaymentLookupService::receivers()` mengambil semua user dalam tenant.
   - Ini belum tentu bug, tetapi perlu keputusan eksplisit: apakah penerima payment cukup tenant-level, atau harus dibatasi membership company/branch aktif.

6. **Sales lookup untuk sellables belum menunjukkan boundary company/branch**
   - `SaleLookupService::sellables()` memuat produk aktif tanpa scope company/branch.
   - Bisa valid jika catalog produk memang tenant-shared, tetapi perlu dikonfirmasi sebagai aturan domain, bukan asumsi implisit.

---

## Temuan detail per area

## A1. Sales scope audit

### Yang sudah baik
- List/index sales sudah memfilter `tenant_id + company_id` dan memakai `BranchContext::applyScope(...)`.
- `saleOptions()` di lookup service juga sudah mengikuti company + branch aktif.
- Flow write sensitif seperti update/finalize/void/cancel sudah mengarah ke action yang melakukan re-query scoped.

### Gap
- **Show/edit/invoice belum memakai repository query yang scoped dari awal.**
- Repository detail/edit hanya melakukan eager loading pada model hasil route model binding.
- Artinya pola scope read belum konsisten dengan pola scope write.

### Task lanjutan yang disarankan
- **A1-T1:** Tambahkan scoped finder untuk detail/edit/invoice (`findForDetailOrFail`, `findForEditOrFail`) dan pakai itu di controller.
- **A1-T2:** Tambahkan feature test untuk memastikan sale lintas company/branch menghasilkan 404/forbidden pada show/edit/invoice.
- **A1-T3:** Putuskan dan dokumentasikan apakah product catalog di Sales memang tenant-shared atau perlu future company/branch scoping.

---

## A2. Payments scope audit

### Yang sudah baik
- Payment list/index sudah memfilter `tenant_id + company_id + branch`.
- Detail payment memakai repository scoped finder.
- Create payment request sudah memiliki validasi membership branch.
- Flow policy untuk `show` dan `void` sudah dipanggil sebelum aksi.

### Gap
- **`saleReturnOptions()` belum branch-aware.**
- **Search pada `SaleReturn` di `PaymentRepository` belum branch-aware.**
- **Daftar receiver masih tenant-wide** dan perlu keputusan boundary yang eksplisit.

### Task lanjutan yang disarankan
- **A2-T1:** Tambahkan `BranchContext::applyScope(...)` ke `saleReturnOptions()`.
- **A2-T2:** Tambahkan branch scope pada morph query `SaleReturn` di pencarian payment.
- **A2-T3:** Putuskan boundary untuk `receivers()` lalu sesuaikan query dan UX bila memang harus membership-aware.
- **A2-T4:** Tambahkan regression test untuk payment search dan lookup option lintas branch.

---

## A3. Reports scope audit

### Temuan utama
- `BaseReportService` sekarang sudah memiliki helper `applyTenantCompanyBranchScope(...)`, tetapi helper ini baru dipakai oleh `SalesReportService`.
- `DashboardReportService` memanggil seluruh service report lain, sehingga dashboard masih ikut terpapar gap dari report service yang belum dimigrasikan.

### Status per service
- **SalesReportService**
  - Sudah mulai mengikuti active `tenant + company + optional branch`.
  - Perlu diperlakukan sebagai referensi awal, bukan final abstraction untuk semua report.

- **PaymentReportService**
  - Masih memakai `applyOutlet(...)`.
  - Belum ada guard tenant/company/branch pada base query.

- **FinanceReportService**
  - Masih memakai `applyOutlet(...)`.
  - Belum ada guard tenant/company/branch pada base query.

- **PosReportService**
  - Masih memakai `applyOutlet(...)`.
  - Padahal domain cash session di arsitektur sudah company-aware dan branch-aware.

- **InventoryReportService**
  - Query stock/movement/adjustment/opname belum menunjukkan guard tenant/company/branch yang konsisten.
  - Ini paling rawan karena query read agregasi tersebar di beberapa tabel.

- **PurchaseReportService**
  - Query purchase report belum menerapkan active context resolver.
  - `receivedVsPending()` juga belum dibatasi tenant/company/branch.

### Task lanjutan yang disarankan
- **A3-T1:** Audit kolom scope nyata per report source table (`tenant_id`, `company_id`, `branch_id`, atau `inventory_location.company_id/branch_id`) sebelum refactor service.
- **A3-T2:** Migrate `PaymentReportService` ke active runtime context.
- **A3-T3:** Migrate `PosReportService` ke active runtime context.
- **A3-T4:** Migrate `FinanceReportService` ke active runtime context.
- **A3-T5:** Migrate `PurchaseReportService` ke active runtime context.
- **A3-T6:** Migrate `InventoryReportService` dengan perhatian khusus ke relasi lokasi dan movement source.
- **A3-T7:** Tambahkan regression tests per service: company-level (`branch_id = null`) dan branch-level.

---

## Rekomendasi urutan eksekusi setelah Track A
1. **A1-T1 + A1-T2** — tutup potensi read leak di Sales detail/edit/invoice.
2. **A2-T1 + A2-T2** — rapikan lookup dan search Payments lintas branch.
3. **A3-T1** — petakan kolom scope riil untuk seluruh report source.
4. **A3-T2 s/d A3-T7** — migrasi report services satu per satu.
5. **A2-T3** — putuskan boundary business untuk receiver list.
6. **A1-T3** — finalisasi keputusan domain untuk catalog visibility di Sales.

---

## Cara pakai backlog ini
Mulai task berikutnya dengan format:
- `Start A1-T1`
- `Start A2-T2`
- `Start A3-T1`

Disarankan mulai dari:
- **`Start A1-T1`** bila ingin menutup potensi kebocoran read access tercepat.
- **`Start A3-T1`** bila ingin menyiapkan migrasi reports secara sistematis.
