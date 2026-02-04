# AGENTS.md — Working Guide for MyApp

## What the app is
- Laravel app with Breeze (Blade), Vite, Tabler UI, Spatie Permission, MySQL.
- Core features: Dashboard, Profile (edit + password + avatar), Users & Roles CRUD.
- Optional/modules for sale: see `MODULES.md` (Internal Memo includes Task Templates; WhatsAppBro).

## How to run (generic)
- Backend deps: `composer install` (PHP ≥ 8.2).
- Frontend deps: `npm install`.
- Build assets: `npm run build` (or `npm run dev -- --host` for local dev).
- DB setup: configure `.env`, then `php artisan migrate --seed`.
- Caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear`.
- WhatsApp bridge: run `node app/Modules/WhatsAppBro/node/server.js` (Socket.IO, default port 3020).

## Auth & roles
- Seeded roles: `Super-admin`, `Admin`; seeded super admin user `superadmin@myapp.test` / `password123!`.
- Spatie middleware aliases: `role`, `permission`, `role_or_permission`.
- Menu access: Users/Roles only for `Super-admin`; Internal Memo & Task Templates require auth.

## UI guidelines (Tabler)
- Sidebar items: Dashboard, Profile, Internal Memo, Task Templates, WhatsApp Bro, Users, Roles.
- Avoid centered sidebar text; give each list item its own hover/active background rather than whole sidebar color change.
- Repeatable UI parts live in `resources/views/shared` (sidebar, cards, etc.).
- Forms: task cards and company detail cards can sit side‑by‑side on desktop; keep clear card titles.
- Selects should show clean labels (no “id)>Name”), styled with `form-select`.

## Task Management (Internal Memo)
- One memo holds tasks; each task has subtasks, PIC (user select), due date, progress auto‑calculated from subtasks; modal edit for task/subtask; “Add Task” / “Add Subtask” use JS clones; templates reuse the same structure.
- Task Templates: CRUD + index; form identical to memo task form; stored meta can be applied into memo.

## WhatsAppBro
- UI: show QR scan card initially; after authenticated, hide it and show full chat pane.
- Chat list shows unread badge with readable text; group messages display sender name; live updates via Socket.IO.
- Session key on client uses authenticated user ID.

## Profile
- Allows updating name, email, password, and avatar upload (avatar stored on users table).

## Git hygiene
- Keep out of VCS: `vendor/`, `node_modules/`, `public/build/`, `public/hot`, `.env`, `storage/*.key`, module-local node artifacts `app/Modules/*/node/node_modules` and `app/Modules/*/node/.wwebjs_auth`, IDE/editor files.
