# CRM Suite Blueprint

Dokumen ini menjawab kebutuhan CRM sebagai business suite yang bisa dijual sendiri, tetap bernilai tanpa Omnichannel, dan menjadi lebih kuat saat terhubung ke Omnichannel, Accounting, serta Automation.

## 1. CRM Architecture

CRM diposisikan sebagai `relationship system of record`, bukan channel engine. Boundary utamanya:

- `crm`: lead, opportunity, pipeline, follow-up plan, sales activity, customer timeline, dashboard, onboarding.
- `contacts`: customer master, company, PIC, identitas dan data dasar pihak eksternal.
- `task_management`: task internal generik lintas fungsi. CRM boleh reuse untuk task umum, tetapi follow-up sales yang sangat kontekstual sebaiknya tetap punya model CRM sendiri.
- `conversations`, `whatsapp_api`, `whatsapp_web`, `social_media`, `email_inbox`: sumber aktivitas komunikasi, bukan sumber kebenaran relasi customer.
- `sales`, `payments`, `finance`: sumber quotation, invoice, payment, dan outstanding.

Prinsip runtime:

- CRM tetap berjalan penuh hanya dengan `crm + contacts`.
- Integrasi channel masuk ke `crm_activities` atau timeline bridge, bukan memindahkan logic channel ke CRM.
- Customer 360 dibangun di atas timeline terpusat yang menerima event dari CRM sendiri lebih dulu, lalu diperluas oleh suite lain.
- Untuk rollout sekarang, `crm_leads` tetap menjadi model operational tunggal untuk prospek dan deal ringan agar implementasi kecil dan reversible.
- Saat kebutuhan bertambah, pecah menjadi `crm_leads`, `crm_opportunities`, `crm_tasks`, dan `crm_timeline_events` tanpa mengubah boundary suite.

## 2. Module Structure

Struktur yang direkomendasikan untuk tahap sekarang:

- `app/Modules/Crm`
- `Database/Migrations`
- `Models/CrmLead.php`
- `Models/CrmActivity.php`
- `Http/Controllers/CrmLeadController.php`
- `Support/CrmStageCatalog.php`
- `Support/CrmSourceCatalog.php`
- `Support/CrmActivityLogger.php`
- `resources/views`

Struktur target growth:

- `Models/CrmOpportunity.php`
- `Models/CrmPipeline.php`
- `Models/CrmPipelineStage.php`
- `Models/CrmTask.php`
- `Models/CrmAutomationRule.php`
- `Services/Customer360TimelineBuilder.php`
- `Services/CrmOmnichannelBridge.php`
- `Services/CrmAccountingBridge.php`

Catatan desain:

- Jangan buat modul `crm` mengelola session WhatsApp, SMTP, atau akun media sosial.
- Timeline event sebaiknya generik dan typed agar Accounting/Omnichannel bisa publish event tanpa coupling ke view CRM.

## 3. Database Design Recommendation

MVP sekarang:

- `contacts`
  - company dan person tetap hidup di domain `contacts`
- `crm_leads`
  - tambah `qualification_status`, `lead_score`, `expected_close_date`
- `crm_activities`
  - foundation Customer 360 timeline

Growth design:

- `crm_pipelines`
  - `id`, `tenant_id`, `name`, `is_default`, `meta`
- `crm_pipeline_stages`
  - `pipeline_id`, `name`, `code`, `position`, `probability_default`, `is_closed_won`, `is_closed_lost`
- `crm_opportunities`
  - `contact_id`, `company_contact_id`, `pipeline_id`, `stage_id`, `owner_user_id`, `value`, `probability`, `expected_close_date`
- `crm_tasks`
  - `related_type`, `related_id`, `subject`, `due_at`, `status`, `priority`, `sequence_no`
- `crm_contact_profiles`
  - optional overlay di atas `contacts` untuk customer-specific CRM preferences, score snapshot, lifecycle stage, last engagement

Indexing minimum:

- `(tenant_id, stage, is_archived)`
- `(tenant_id, owner_user_id, stage)`
- `(tenant_id, next_follow_up_at)`
- `(tenant_id, expected_close_date)`
- `(tenant_id, lead_id, occurred_at desc)` untuk timeline
- `(tenant_id, contact_id, occurred_at desc)` untuk Customer 360

## 4. UX Flow

Flow inti user:

1. Import contact atau tambah contact manual.
2. Tambah lead dari contact, source, atau manual.
3. Assign owner.
4. Jadwalkan follow-up pertama.
5. Geser stage di pipeline.
6. Lihat timeline customer sebelum follow-up.
7. Saat ada quotation/invoice/komunikasi, semua muncul di Customer 360.
8. Deal dimenangkan atau ditutup lost dengan reason.

Halaman utama yang dijual:

- `CRM Dashboard`
- `Leads / Deals`
- `Customer 360`
- `Follow-Up Queue`
- `Pipeline Board`
- `Source Performance`

## 5. Permission Matrix

Minimum:

| Permission | Sales | CS | Admin |
| --- | --- | --- | --- |
| `crm.view` | Yes | Yes | Yes |
| `crm.create` | Yes | Yes | Yes |
| `crm.update` | Yes | Yes | Yes |
| `crm.delete` | No | No | Yes |
| future `crm.assign` | Optional | Optional | Yes |
| future `crm.export` | Optional | Optional | Yes |
| future `crm.manage_pipeline` | No | No | Yes |
| future `crm.view_all` | Optional | Optional | Yes |

Rule penting:

- Sales default melihat record miliknya sendiri jika tenant mengaktifkan restricted visibility.
- Admin selalu bisa melihat seluruh tenant.
- Integrasi event dari Omnichannel/Accounting tidak memberi hak akses CRUD ke suite asal.

## 6. Feature Matrix

| Capability | CRM Only | + Omnichannel | + Accounting | + Automation |
| --- | --- | --- | --- | --- |
| Contact & company management | Yes | Yes | Yes | Yes |
| Lead / deal pipeline | Yes | Yes | Yes | Yes |
| Follow-up planning | Yes | Yes | Yes | Yes |
| Customer 360 internal activity | Yes | Yes | Yes | Yes |
| WhatsApp/email/call timeline | No | Yes | No | No |
| Quotation/invoice/payment timeline | No | No | Yes | No |
| Auto assign / auto task / reminders | Basic | Basic | Basic | Yes |

## 7. MVP Features

Yang layak dijual sekarang:

- Contact dan company management berbasis modul `contacts`
- Lead/deal board
- Owner assignment
- Lead source
- Qualification dan score
- Expected close date
- Follow-up date
- CRM activity timeline internal
- Dashboard operasional: leads, contacts, active deals, pipeline value, conversion rate, due today, overdue, top sales, source performance
- Import contacts

## 8. Growth Features

- Multiple pipelines
- Custom stages per tenant
- Lost reason
- Follow-up sequence `1, 2, 3, ...`
- Task template per pipeline stage
- Rule-based auto task
- Customer lifecycle stages
- Team performance dashboard
- Saved filters
- WhatsApp-ready activity bridge

## 9. Enterprise Features

- Multi-pipeline by business unit
- SLA for response/follow-up
- Territory / account ownership rules
- Approval on discount / deal exception
- Forecasting
- Revenue attribution by source/campaign
- Audit trail export
- Dedicated tenant topology and archive policy

## 10. Landing Page Selling Points

Pesan penjualan yang layak untuk market Indonesia:

- `Customer 360`: semua riwayat customer dalam satu layar
- `Pipeline yang tidak bikin prospek hilang`
- `Follow-up management untuk tim sales yang sibuk`
- `Siap WhatsApp` tanpa memaksa tenant beli Omnichannel dulu
- `Siap invoice dan payment visibility` saat Accounting aktif
- `Automation-ready` untuk assign task, reminder, dan stage movement

Hindari positioning ini:

- hanya buku kontak
- hanya board kanban
- pseudo-omnichannel di dalam CRM

## 11. Onboarding Flow

Wizard aktivasi CRM:

1. Import Contacts
2. Buat Pipeline Default
3. Tambah Sales Team
4. Buat First Deal
5. Jadwalkan Follow-Up Pertama

Metrik sukses onboarding:

- tenant punya minimal 10 contact
- tenant punya minimal 1 lead aktif
- tenant punya minimal 1 follow-up due
- tenant melihat dashboard sebelum keluar wizard

## 12. Automation Opportunities

Trigger minimum:

- Lead Created
- Lead Assigned
- Deal Created
- Deal Won
- Deal Lost
- Task Completed
- Follow-Up Missed

Action minimum:

- Create Task
- Assign User
- Change Stage
- Add Tag
- Create Activity
- Create Reminder

Rekomendasi implementasi:

- event publish dari CRM harus typed dan kecil
- automation engine membaca event, bukan query bebas ke seluruh tabel CRM

## 12A. API And Lead Capture Integration

Endpoint yang sekarang disiapkan:

- `POST /api/crm/leads`
  - untuk integrasi tenant-authenticated berbasis `auth:sanctum`
- `POST /crm/webhooks/lead-capture`
  - untuk webhook publik generik dengan header `X-Lead-Capture-Token`
- `POST /crm/webhooks/meta-leads`
  - untuk payload bergaya Meta Lead Ads dengan header `X-Lead-Capture-Token`

Payload generik minimum:

```json
{
  "title": "Lead Budi - Campaign Jakarta",
  "name": "Budi Santoso",
  "email": "budi@example.com",
  "mobile": "08123456789",
  "lead_source": "meta_ads",
  "provider": "meta_ads",
  "external_reference": "leadgen_12345",
  "campaign_name": "Jakarta Juni",
  "adset_name": "Lookalike 3%",
  "form_name": "Form Promo",
  "estimated_value": 2500000,
  "currency": "IDR",
  "next_follow_up_at": "2026-06-09 10:00:00"
}
```

Perilaku ingestion:

- contact akan dibuat atau di-merge dari email/phone
- lead akan di-merge bila `external_reference` sama
- owner bisa diisi langsung atau ditentukan lewat routing rule
- CRM membuat timeline event `external_lead_captured`
- receipt webhook disimpan di `platform_webhook_receipts`
- authenticated API dan webhook publik sama-sama masuk ke service ingestion yang sama, jadi dedupe, routing owner, pipeline default, dan follow-up queue tetap konsisten

Routing owner:

- format rule dasar: `keyword|owner_user_id`
- format rule terarah:
  - `source:meta_ads|12`
  - `campaign:jakarta|8`
  - `adset:lookalike|5`
  - `form:promo|4`
  - `title:enterprise|7`

Replay webhook:

- receipt webhook CRM sekarang bisa di-replay dari halaman `CRM > Settings`
- replay tetap memakai parser endpoint asal, jadi payload Meta tidak diperlakukan sebagai payload generik

## 12B. Accounting And Sales Bridge

Bridge launch yang sekarang sudah disiapkan:

- `Deal Won -> quotation`
  - bila module `sales` aktif dan tenant punya feature `accounting`
- `Deal Won -> draft sale`
  - bila module `sales` aktif dan tenant punya feature `accounting` atau `commerce`
- `Deal Won -> finalize invoice`
  - optional, hanya bila setting `finalize_draft_sale` aktif dan tenant punya feature `accounting`
- `Payment posted -> Customer 360 timeline`
  - hanya publish untuk payable type `sale`

Contract fail-closed:

- bila module owner belum aktif, CRM tidak mencoba memaksa write ke tabel suite lain
- bila feature plan belum aktif, automation hanya mencatat `won_automation_processed` dengan alasan skip
- bila product default belum diset, quotation/sale tidak dibuat

Timeline bridge yang sekarang dipublish ke Customer 360:

- `sales_quotation_created`
- `sales_quotation_converted`
- `sales_invoice_finalized`
- `sales_invoice_voided`
- `payment_posted`

Metadata penting yang dibawa:

- `crm.lead_id`
- `crm.lead_title`
- `quotation_number`
- `sale_number`
- `payment_number`
- `grand_total`
- `balance_due`
- `payment_status`

## 13. Refactor Risks

- `crm_leads` saat ini masih menyatukan lead dan deal. Ini aman untuk MVP, tapi akan membatasi multiple pipeline dan stage governance bila dibiarkan terlalu lama.
- `contacts` saat ini memuat company + individual sekaligus. Itu cocok untuk sekarang, tetapi Customer 360 enterprise mungkin butuh overlay CRM sendiri agar tidak memaksa domain `contacts` menanggung semua kebutuhan sales.
- `task_management` dan follow-up CRM bisa tumpang tindih. Boundary harus jelas: task generic tetap di modul task, task sales-contextual tetap di CRM.

## 14. Technical Debt Risks

- Dashboard aggregate bisa mahal bila semua metrik terus dihitung real-time dari dataset besar tanpa materialized summary atau cache terkontrol.
- Timeline akan cepat tumbuh; perlu retention, pagination, dan archive strategy untuk tenant besar.
- Banyak integrasi nanti akan menulis ke timeline. Tanpa event contract yang disiplin, `crm_activities.payload` bisa berubah menjadi JSON liar.
- Jika multiple pipeline ditambah tanpa stage owner model yang jelas, migrasi dari stage enum ke stage table akan lebih mahal.

## 15. Detailed Development Plan

Fase 1, sekarang:

1. Tambah field operasional lead: qualification, score, expected close date.
2. Tambah `crm_activities` sebagai fondasi Customer 360.
3. Log event internal: lead created, updated, stage changed, follow-up scheduled.
4. Naikkan dashboard CRM agar fokus ke revenue action, bukan hanya angka mentah.

Fase 2:

1. Tambah `crm_pipelines` dan `crm_pipeline_stages`.
2. Pisahkan queue `Follow-Up` dari list lead.
3. Tambah lost reason dan forecast sederhana.

Fase 3:

1. Bridge event dari Omnichannel ke timeline CRM.
2. Bridge quotation, invoice, payment dari Accounting.
3. Tambah automation trigger/action.

Fase 4:

1. Multiple pipeline per tenant.
2. Team performance dan manager visibility.
3. Archival, retention, dan reporting hardening.

## Kritik Dan Alternatif Sederhana

Beberapa permintaan di prompt terlalu besar bila dipaksa sekaligus sekarang:

- `Multiple pipelines + custom stages + full opportunity split + full automation + omnichannel/accounting bridge` sekaligus akan membuat rollout lambat dan risk tinggi.
- Untuk tahap sekarang, lebih sederhana dan lebih aman mempertahankan `crm_leads` sebagai model operasional tunggal, lalu menambah timeline dan insight dashboard lebih dulu.
- Customer 360 tidak perlu menunggu semua integrasi siap. Mulai dari internal CRM events sekarang sudah memberi value nyata dan menjaga arah arsitektur tetap benar.
