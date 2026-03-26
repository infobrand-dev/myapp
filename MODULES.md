# MODULES.md - Module Overview

This file is a quick catalog. The authoritative metadata for each module lives in its own `app/Modules/<Module>/module.json`.

## Commerce
- `products`: product master data, variants, options, units, media, pricing structure.
- `inventory`: stock balances, movements, opening stock, adjustments, transfers, opname. Requires `products`.
- `discounts`: discount engine and voucher rules. Requires `products`.
- `sales`: sales workflow and returns. Requires `products`, `contacts`.
- `payments`: payment records, methods, allocations, void flow. Requires `sales`.
- `purchases`: draft/finalized purchase flow and goods receiving. Requires `products`, `contacts`, `inventory`, `payments`.
- `finance`: finance-related services and permissions used by the commerce stack.
- `point-of-sale`: POS cart, checkout, cash session, receipt flow. Requires `products`, `contacts`, `sales`, `payments`, `discounts`.

## Reporting
- `reports`: dashboard and module-level reports for sales, payments, inventory, purchases, finance, POS, and products.

## Communication
- `conversations`: shared inbox domain, message ingestion contracts, activity log, and conversation UI.
- `live_chat`: embeddable website live chat widget that routes visitor chat into `conversations`.
- `whatsapp_api`: WhatsApp Cloud/API-oriented messaging, instances, templates, flows, blast, webhook ingestion. Requires `conversations`.
- `whatsapp_web`: WhatsApp Web bridge with QR auth, chat sync, and Node bridge runtime. Requires `conversations`.
- `social_media`: social account integrations and webhook-driven inbox flow. Requires `conversations`.
- `email_marketing`: campaigns, recipients, attachment templates, unsubscribe flow. Requires `contacts`.
- `email_inbox`: mailbox account, inbound sync, outbound send, folder/message storage, and operational email workspace.

## Automation
- `chatbot`: bot accounts, playground, knowledge base, and conversation mirroring. Requires `conversations`.

## Support
- `contacts`: contact directory and merge/import flows.
- `task_management`: internal memo, task, subtask, and task template management.
- `shortlink`: short URL management, multi-code support, click tracking, redirect endpoint.
- `sample_data`: sample/demo data entry points for local testing and demos.

## Working rules
- Read `module.json` before integrating or refactoring a module.
- Declare dependencies in `requires`; do not rely on hidden coupling.
- Keep each module's SVG icon with the module itself and reference it from `module.json`.
- Keep provider logic, routes, migrations, views, and assets inside the owning module.
- If a module integrates with another module, keep the integration adapter in the dependent module.
