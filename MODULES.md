# MODULES.md - Module Overview

This file is a quick catalog. The authoritative metadata for each module lives in its own `app/Modules/<Module>/module.json`.

## Current module direction

### Already present
- `products`: product master data, variants, options, units, media, pricing structure.
- `inventory`: stock balances, movements, opening stock, adjustments, transfers, opname. Requires `products`.
- `discounts`: discount engine and voucher rules. Requires `products`.
- `sales`: sales workflow and returns. Requires `products`, `contacts`.
- `payments`: payment records, methods, allocations, void flow. Requires `sales`, `purchases`, `point-of-sale`.
- `purchases`: draft/finalized purchase flow and goods receiving. Requires `products`, `contacts`, `inventory`, `payments`.
- `finance`: finance-related services and permissions used by the commerce stack.
- `point-of-sale`: POS cart, checkout, cash session, receipt flow. Requires `products`, `contacts`, `sales`, `payments`, `discounts`.
- `midtrans`: Midtrans payment gateway integration for online payment flow. Requires `payments`, `sales`, `point-of-sale`.
- `reports`: dashboard and module-level reports for sales, payments, inventory, purchases, finance, POS, and products.
- `conversations`: shared inbox domain, message ingestion contracts, activity log, and conversation UI.
- `live_chat`: embeddable website live chat widget that routes visitor chat into `conversations`.
- `whatsapp_api`: WhatsApp Cloud/API-oriented messaging, instances, templates, flows, blast, webhook ingestion. Requires `conversations`.
- `whatsapp_web`: WhatsApp Web bridge with QR auth, chat sync, and Node bridge runtime. Requires `conversations`.
- `social_media`: social account integrations and webhook-driven inbox flow. Requires `conversations`.
- `email_marketing`: campaigns, recipients, attachment templates, unsubscribe flow. Requires `contacts`.
- `email_inbox`: mailbox account, inbound sync, outbound send, folder/message storage, and operational email workspace.
- `chatbot`: bot accounts, playground, knowledge base, conversation mirroring, and automation modes. Requires `conversations`.
- `crm`: pipeline lead, owner assignment, follow-up, dan kanban/list view berbasis `contacts`. Requires `contacts`.
- `contacts`: contact directory and merge/import flows.
- `task_management`: internal memo, task, subtask, and task template management.
- `shortlink`: short URL management, multi-code support, click tracking, redirect endpoint.
- `sample_data`: sample/demo data entry points for local testing and demos.

### Needs deeper scope or clearer boundary
- `finance`: tetap finance operasional ringan; bila kebutuhan akuntansi formal membesar, pecah domainnya dengan sengaja.
- `reports`: perlu terus mengikuti scope modul owner, bukan menjadi tempat logika bisnis utama.
- `conversations`: harus dijaga sebagai inbox dan activity backbone lintas channel, bukan hanya penyimpanan chat mentah.
- `social_media`: masih cocok sebagai integrasi inbox sosial, tetapi belum setara social marketing penuh.
- `email_marketing`: masih campaign-oriented; otomatisasi marketing lintas channel belum lengkap.
- `chatbot`: knowledge dan automation sudah ada, tetapi jangan dipakai untuk menutup gap modul lain yang seharusnya berdiri sendiri.
- `task_management`: masih internal task/memo; jangan diasumsikan otomatis menutup project planning, service desk, scheduling, atau timesheet.
- `crm`: perlu dijaga fokus pada pipeline dan follow-up; jangan menumpuk area service/project ke dalam CRM tanpa boundary jelas.

### Good candidates for new modules or major additions
- `subscriptions`: subscription pelanggan/kontrak berulang yang dibedakan dari billing plan platform internal.
- `documents`: document management lintas sales, finance, approval, dan lampiran operasional.
- `approvals`: approval workflow generik lintas modul.
- `helpdesk`: ticketing dan SLA operasional yang terpisah dari `task_management`.
- `project_management`: project, milestone, resource planning, dan kemungkinan timesheet bila memang dibutuhkan.
- `website` / `cms`: publishing, landing page, atau site builder jika produk bergerak ke web presence.
- `ecommerce`: katalog publik, cart, checkout, dan order storefront jika kanal penjualan web ingin dibuka.
- `knowledge_base`: knowledge workspace umum jika kebutuhan dokumentasi melampaui knowledge untuk `chatbot`.
- `survey`: form/survey/feedback jika kebutuhan lead capture atau CSAT mulai formal.
- `appointments`: booking/jadwal meeting atau layanan jika scheduling external mulai dibutuhkan.

## Commerce
- `products`: product master data, variants, options, units, media, pricing structure.
- `inventory`: stock balances, movements, opening stock, adjustments, transfers, opname. Requires `products`.
- `discounts`: discount engine and voucher rules. Requires `products`.
- `sales`: sales workflow and returns. Requires `products`, `contacts`.
- `payments`: payment records, methods, allocations, void flow. Requires `sales`, `purchases`, `point-of-sale`.
- `purchases`: draft/finalized purchase flow and goods receiving. Requires `products`, `contacts`, `inventory`, `payments`.
- `finance`: finance-related services and permissions used by the commerce stack.
- `point-of-sale`: POS cart, checkout, cash session, receipt flow. Requires `products`, `contacts`, `sales`, `payments`, `discounts`.
- `midtrans`: Midtrans payment gateway integration for online payment flow. Requires `payments`, `sales`, `point-of-sale`.

Planned boundary note:
- `finance` saat ini tetap berada di `commerce` karena fungsinya masih cash flow operasional ringan, bukan akuntansi formal.

## Accounting
Saat ini `accounting` diposisikan sebagai product line pricing yang memakai modul existing, bukan katalog modul baru.

Bundle inti:
- `sales`
- `payments`
- `finance`
- `reports`
- `products`
- `contacts`

Growth/Scale menambahkan:
- `purchases`
- `inventory`

Add-on:
- `point-of-sale`
- `discounts`

## Reporting
- `reports`: dashboard and module-level reports for sales, payments, inventory, purchases, finance, POS, and products.

## Communication
- `conversations`: shared inbox domain, message ingestion contracts, activity log, and conversation UI.
- `live_chat`: embeddable website live chat widget that routes visitor chat into `conversations`.
- `whatsapp_api`: WhatsApp Cloud/API-oriented messaging, instances, templates, flows, blast, webhook ingestion. Requires `conversations`.
- `whatsapp_web`: WhatsApp Web bridge with QR auth, chat sync, and Node bridge runtime. Requires `conversations`.
- `social_media`: social account integrations and webhook-driven inbox flow. Tenant accounts are connected via platform-owned Meta OAuth rather than per-tenant raw token entry. Requires `conversations`.
- `email_marketing`: campaigns, recipients, attachment templates, unsubscribe flow. Requires `contacts`.
- `email_inbox`: mailbox account, inbound sync, outbound send, folder/message storage, and operational email workspace.

## Automation
- `chatbot`: bot accounts, playground, knowledge base, conversation mirroring, and automation modes (`rule_only`, `ai_assisted`, `ai_first`). AI usage is tracked separately so plan-based AI Credits can be reused by future automation flows. Requires `conversations`.

## Support
- `crm`: pipeline lead, owner assignment, follow-up, dan kanban/list view berbasis `contacts`. Requires `contacts`.
- `contacts`: contact directory and merge/import flows.
- `task_management`: internal memo, task, subtask, and task template management.
- `shortlink`: short URL management, multi-code support, click tracking, redirect endpoint.
- `sample_data`: sample/demo data entry points for local testing and demos.

## Working rules
- Read `module.json` before integrating or refactoring a module.
- Use the status sections in this file when deciding whether work belongs in an existing module, needs a clearer boundary, or should become a new module.
- Declare dependencies in `requires`; do not rely on hidden coupling.
- Keep each module's SVG icon with the module itself and reference it from `module.json`.
- Keep provider logic, routes, migrations, views, and assets inside the owning module.
- If a module integrates with another module, keep the integration adapter in the dependent module.
