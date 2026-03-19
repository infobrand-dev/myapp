# AGENTS.md - Working Guide for MyApp

## What the app is
- Laravel app with Breeze (Blade), Vite, Tabler UI, Spatie Permission, MySQL.
- Core features: Dashboard, Profile (edit + password + avatar), Users & Roles CRUD.
- Optional/modules for sale: see `MODULES.md` (Internal Memo includes Task Templates; Shortlink; WhatsAppBro).

## Core vs module boundaries
- Core files must stay clean and contain no module-specific logic, routes, views, assets, migrations, service wiring, or hardcoded references to any optional module.
- The base app must remain usable without optional modules installed or enabled. If a feature is optional, its implementation must live entirely inside its own module folder.
- Every module must provide a `module.json` manifest as the single source of truth for module metadata such as slug, name, provider, version, dependency requirements, navigation items.
- When reading or integrating a module, check `module.json` first to understand what the module exposes, what it requires, and how it should be registered.
- Every module must be standalone by default. A module may depend on another module only when that dependency is explicitly required by the feature design.
- Any cross-module dependency must be declared explicitly in `module.json` via `requires`; do not rely on hidden runtime coupling or silent fallbacks as a substitute for declaring dependencies.
- If one module needs another module, the dependent module must still load or inject its own scripts, assets, bootstrapping, and integration code from its own folder instead of placing that code in core files.
- Do not move module behavior into core just to make integration easier. Prefer module service providers, module routes, module views, module assets, and module-local initialization.
- When adding new work, treat `app`, `resources`, `routes`, `database`, and other core paths as framework/app-shell territory only unless the change is truly part of the non-optional core product.

## Module metadata roadmap
- `module.json` should gradually support richer metadata beyond technical registration, especially `category`, `industry`, and `module_type`.
- `category` is intended for functional grouping and filtering in the Modules page, for example: `commerce`, `communication`, `reporting`, `automation`, `support`.
- `industry` is intended for business-domain relevance, for example: `general`, `retail`, `restaurant`, `pharmacy`, `clinic`, `workshop`.
- `module_type` is intended to describe the architectural role of the module, for example: `core`, `industry-extension`, `support`, `integration`.
- Categories must remain metadata only. Do not put runtime logic, dependency rules, service wiring, or authorization rules into a category layer.
- Industry tags must remain metadata only. Do not use industry tags as a substitute for declaring real module dependencies in `requires`.
- Long-term module strategy for reusable groups should follow these buckets.
- `commerce`: reusable business modules such as `products`, `sales`, `payments`, `inventory`, `purchases`, `finance`, `point-of-sale`.
- `reporting`: modules such as `reports`.
- `communication`: modules such as `whatsapp_api`, `whatsapp_bro`, `social_media`, `email_marketing`, `conversations`.
- `automation`: modules such as `chatbot`.
- `support`: modules such as `contacts`, `task_management`, `sample_data`, `shortlink`.
- When creating future industry-specific features such as restaurant or pharmacy, prefer new standalone modules that extend the reusable core instead of forcing industry logic into core modules.
- When adding new modules, keep the source of truth for metadata in `module.json` and update the Modules management UI so filtering can use `category` now and `industry` later.

## How to run (generic)
- Backend deps: `composer install` (PHP >= 8.2).
- Frontend deps: `npm install`.
- Build assets: `npm run production` (or `npm run development` / `npm run watch` for local dev).
- DB setup: configure `.env`, then `php artisan migrate --seed`.
- Caches: `php artisan config:clear && php artisan route:clear && php artisan view:clear`.
- WhatsApp bridge: run `node app/Modules/WhatsAppBro/node/server.js` (Socket.IO, default port 3020).

## Realtime / broadcasting
- Realtime features use the Laravel `pusher` broadcast driver and Pusher-compatible clients, but the intended server is self-hosted `soketi` (free), not the paid Pusher SaaS.
- `pusher/pusher-php-server` on the backend and `pusher-js` / `laravel-echo` on the frontend are used as protocol-compatible libraries so Laravel can talk to a Pusher-compatible websocket server.
- Prefer `soketi` for local/dev/self-hosted realtime. Do not assume the app should use the hosted Pusher service unless explicitly requested.
- Default local websocket env pattern: `BROADCAST_DRIVER=pusher`, `MIX_PUSHER_HOST`, `MIX_PUSHER_PORT`, and `MIX_PUSHER_SCHEME` should point to the local/self-hosted websocket server.
- If realtime is optional for a module, keep its websocket wiring inside the module and avoid leaking channel-specific logic into core.

## Auth & roles
- Seeded roles: `Super-admin`, `Admin`; seeded super admin user `superadmin@myapp.test` / `password123!`.
- Spatie middleware aliases: `role`, `permission`, `role_or_permission`.
- Menu access: Users/Roles only for `Super-admin`; Internal Memo & Task Templates require auth.

## UI guidelines (Tabler)
- Sidebar items: Dashboard, Profile, Internal Memo, Task Templates, WhatsApp Bro, Users, Roles.
- Avoid centered sidebar text; give each list item its own hover/active background rather than whole sidebar color change.
- Repeatable UI parts live in `resources/views/shared` (sidebar, cards, etc.).
- Forms: task cards and company detail cards can sit side-by-side on desktop; keep clear card titles.
- Selects should show clean labels (no `id)>Name`), styled with `form-select`.

## Task Management (Internal Memo)
- One memo holds tasks; each task has subtasks, PIC (user select), due date, progress auto-calculated from subtasks; modal edit for task/subtask; `Add Task` / `Add Subtask` use JS clones; templates reuse the same structure.
- Task Templates: CRUD + index; form identical to memo task form; stored meta can be applied into memo.

## WhatsAppBro
- UI: show QR scan card initially; after authenticated, hide it and show full chat pane.
- Chat list shows unread badge with readable text; group messages display sender name; live updates via Socket.IO.
- Session key on client uses authenticated user ID.

## Profile
- Allows updating name, email, password, and avatar upload (avatar stored on users table).

## Git hygiene
- Keep out of VCS: `vendor/`, `node_modules/`, `public/build/`, `public/hot`, `.env`, `storage/*.key`, module-local node artifacts `app/Modules/*/node/node_modules` and `app/Modules/*/node/.wwebjs_auth`, IDE/editor files.
