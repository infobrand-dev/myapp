# MODULES.md — Optional Modules for MyApp

## Internal Memo
- Purpose: task management with subtasks, PIC (user select), due dates, progress aggregation; task templates can be applied into memos.
- Routes/UI: menu “Internal Memo”; templates share the same form builder as memo tasks; modal edit for task/subtask; progress bars at task and memo level.
- Data: memo + tasks + subtasks tables (with PIC, due_date, status/percentage).
- Access: requires authenticated users; visibility via role middleware as configured.

## WhatsAppBro
- Purpose: WhatsApp bridge with QR login, live chat via Socket.IO.
- Run bridge: `node app/Modules/WhatsAppBro/node/server.js` (default port 3020).
- Behavior: show QR card until authenticated, then full chat; unread badges readable; group messages display sender; session keyed to auth user ID.
- Node artifacts not committed: `app/Modules/WhatsAppBro/node/node_modules`, `app/Modules/WhatsAppBro/node/.wwebjs_auth`.

## Shortlink
- Purpose: generate short URLs with UTM, multiple codes, and click tracking.
- Public route: `/r/{code}` redirects to destination with UTM appended.
- Admin routes (auth + role Super-admin|Admin): CRUD at `/shortlinks`.
- Data: shortlinks, shortlink_codes, shortlink_clicks tables (migration in module).
- UI: Tabler-based index (chart, top codes/referrers, copy buttons) and form for code/UTM toggles.
