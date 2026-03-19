# ARCHITECTURE.md - App-Specific Notes

## Product snapshot
- Laravel 11 app with Breeze (Blade), Tabler UI, Laravel Mix, Spatie Permission, and MySQL/MariaDB.
- The app is installer-first. Fresh setup is expected to go through `/install`.
- Core app responsibilities are the shell and shared platform pieces such as installer, authentication, dashboard, profile, users, roles, module registry, shared layout, and cross-cutting infrastructure.
- Business features live under `app/Modules/*` and are discovered from each module's `module.json`.

## Current architecture
- Core routes live in `routes/web.php` and `routes/auth.php`.
- Shared module loading is handled by `App\Support\ModuleManager`.
- Module state is persisted in the `modules` table and controlled from the Modules page.
- Sidebar navigation is partly core and partly generated from active module manifests.

## Boundaries
- Keep root framework paths focused on app-shell concerns unless the feature is part of the non-optional base product.
- Do not place optional business logic, routes, views, migrations, assets, or provider wiring in core files.
- Each module should stay self-contained under its own folder, including routes, migrations, views, services, actions, and bootstrapping.
- `module.json` is the source of truth for module metadata such as slug, name, provider, version, description, category, `requires`, and navigation items.

## Module registry rules
- Install and activation are separate states.
- Install may run module migrations and role seeding.
- Activation must respect declared dependencies.
- Deactivation must refuse when active dependents still require the module.
- Category is metadata for grouping and filtering in the Modules UI; it is not a runtime policy layer.

## Installer and auth
- Treat the web installer as the default bootstrap path for a fresh deployment.
- The first Super-admin account is created from the installer form, not from a permanent default password.
- `superadmin@myapp.test` is only a form placeholder in the installer UI, not a guaranteed seeded account.
- Installation state is determined by a valid `APP_KEY`, installer lock file, `APP_INSTALLED`, and backward-compatible core table checks.

## Roles and permissions
- Core permissions include `users.*`, `roles.*`, and `modules.*`.
- Core seeded roles are `Super-admin` and `Admin`.
- Role seeding also pulls default permission maps from some modules via their service providers.

## Frontend
- Stack: Blade + Tabler + Laravel Mix.
- Shared reusable UI belongs in `resources/views/shared`.
- Sidebar styling should keep text left-aligned and use per-item hover/active states.
- Asset commands come from `package.json`, including `npm run dev`, `npm run watch`, and `npm run production`.

## Realtime and integrations
- Realtime uses Laravel's `pusher` broadcast driver with Pusher-compatible clients.
- Preferred local/self-hosted websocket server is `soketi`.
- Queue-heavy or integration-heavy features should be verified against actual env/config before changes are made.
- There is backward-compatibility code for older WhatsApp naming; prefer current naming in new work.

## Sample and demo data
- Sample seeders exist in several modules.
- Acting user resolution now prefers a `Super-admin` account and then falls back to the first available user.
- Do not treat placeholder emails as business rules.

## Related docs
- `README.md`: setup, install, and runtime commands
- `MODULES.md`: module catalog and high-level module notes
