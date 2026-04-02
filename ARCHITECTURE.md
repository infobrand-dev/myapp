# ARCHITECTURE.md - App-Specific Notes

## Product snapshot
- Laravel 11 app with Breeze (Blade), Tabler UI, Laravel Mix, Spatie Permission, and MySQL/MariaDB.
- Core app responsibilities are the shell and shared platform pieces such as authentication, dashboard, profile, users, roles, module registry, shared layout, and cross-cutting infrastructure.
- Business features live under `app/Modules/*` and are discovered from each module's `module.json`.

## Current architecture
- Core routes live in `routes/web.php` and `routes/auth.php`.
- Shared module loading is handled by `App\Support\ModuleManager`.
- Module state is persisted in the `modules` table and controlled from the Modules page.
- Sidebar navigation is partly core and partly generated from active module manifests.
- Core tenant-wide settings UI now lives under `/settings` as a shared shell entry point. Company, branch, documents, subscription, access, and module entitlement views should extend that area instead of adding scattered top-level admin pages.

## Scale and tenancy expectations
- This app is expected to handle large and growing data volume. New work must consider query cost, indexing strategy, pagination, filtering, and background processing instead of optimizing only for a small demo dataset.
- Do not assume list pages, inboxes, reports, logs, webhook events, or synchronization tables will stay small. Prefer bounded queries, avoid loading unnecessary relations, and be careful with unindexed lookups, `count()` on hot paths, and repeated per-row queries.
- Features should be designed so they can be partitioned or scoped cleanly as data grows. When adding tables, foreign keys, unique keys, or search filters, think ahead about operational load and maintenance windows.
- Multi-tenant readiness is a standing requirement even where `tenant_id` is not fully rolled out yet. New schema, services, and module integrations should avoid assumptions that make tenant scoping difficult later.
- When practical, keep room for `tenant_id` in table design, query composition, unique constraints, cache keys, webhook/account resolution, and ownership rules. Avoid building new flows that implicitly assume a single global tenant.
- Core runtime tenant resolution now flows through `App\Support\TenantContext` and `App\Http\Middleware\ResolveTenantContext`.
- Current resolver order is: explicit request attribute/header/query, session, authenticated user `tenant_id`, then fallback to tenant `id = 1` only for standalone/bootstrap-safe flows. In SaaS mode, unresolved tenant context should fail closed instead of silently falling back to tenant `1`.
- In SaaS mode, guest auth pages must be reached from the tenant subdomain. Apex/root domain is for onboarding or workspace discovery, not shared tenant login.
- Public self-serve sales now starts from the apex onboarding flow. Buyer selects a public subscription plan, creates a tenant in `pending_payment`, receives a platform invoice, and then pays either through Midtrans checkout or manual bank transfer with a unique amount before the tenant is activated.
- Platform owner access is separated onto the reserved `dash` subdomain, which binds to tenant `id = 1` for control-plane work.
- Core runtime company resolution now flows through `App\Support\CompanyContext` and `App\Http\Middleware\ResolveCompanyContext`.
- Current company resolver order is: explicit request attribute/header/query, session, then first active company under the active tenant.
- Core runtime branch resolution now flows through `App\Support\BranchContext` and `App\Http\Middleware\ResolveBranchContext`.
- Current branch resolver order is: explicit request attribute/header/query, then session.
- Branch is optional under a company. When no active branch is selected, branch-aware runtime scope should target `branch_id IS NULL`, not silently fan out across all branches and not silently pick the first branch.
- Until tenant switching UI and tenant administration are completed, default/fallback tenant behavior must remain safe and deterministic, and tenant-aware writes must never leave `tenant_id` as `null`.
- Until company administration is completed, default/fallback company behavior must remain safe and deterministic, and company-aware writes must never leave `company_id` as `null` where company scoping is active.
- If a change is intentionally shipped before tenant scoping is completed, document the limitation clearly and keep the implementation easy to migrate to tenant-aware behavior.
- Core tenant bootstrap now uses a dedicated `tenants` table. Default installation must always have tenant `id = 1` with the name `Default tenant`.
- Spatie Permission now runs in `teams` mode with `tenant_id` as the scope key. Roles and role assignments are tenant-scoped, while permissions and module registry remain global.
- Default tenant roles should be provisioned through `App\Support\TenantRoleProvisioner`, not created ad hoc during request handling.

## Boundaries
- Keep root framework paths focused on app-shell concerns unless the feature is part of the non-optional base product.
- Do not place optional business logic, routes, views, migrations, assets, or provider wiring in core files.
- Each module should stay self-contained under its own folder, including routes, migrations, views, services, actions, and bootstrapping.
- `module.json` is the source of truth for module metadata such as slug, name, provider, version, description, category, `requires`, and navigation items.
- `module.json` is also the source of truth for each module's sidebar/icon metadata. Module SVG assets should live with the owning module, and shared UI should reuse those assets instead of redefining icons per page.

## Module registry rules
- Install and activation are separate states.
- Install may run module migrations and role seeding.
- Activation must respect declared dependencies.
- Deactivation must refuse when active dependents still require the module.
- Category is metadata for grouping and filtering in the Modules UI; it is not a runtime policy layer.

## Auth
- Super-admin and tenant users authenticate through the standard auth flow.
- Production runtime should continue without exposing a web installer path.

## Roles and permissions
- Core permissions include `users.*`, `roles.*`, and `modules.*`.
- Core seeded roles are `Super-admin`, `Admin`, `Customer Service`, `Sales`, `Cashier`, `Inventory Staff`, and `Finance Staff`. Module providers may extend these presets with module-specific permissions.
- Role seeding also pulls default permission maps from some modules via their service providers.
- Tenant subscription and plan enforcement foundation now lives in `subscription_plans`, `tenant_subscriptions`, `companies`, and `App\Support\TenantPlanManager`.
- Platform billing for SaaS plans lives in dedicated `platform_*` tables (`platform_plan_orders`, `platform_invoices`, `platform_invoice_items`, `platform_payments`) and must stay separate from tenant-facing `sales` / `payments` domain tables.
- Platform invoice payments may flow through Midtrans or manual bank transfer verification, while `platform_*` tables remain the internal source of truth for invoice, payment, and subscription activation.
- Chatbot knowledge retrieval currently uses a hardened keyword-first RAG path, but knowledge chunks are now prepared for future Supabase/Postgres `pgvector` rollout via embedding lifecycle columns on `chatbot_knowledge_chunks`. Treat vector retrieval as the next phase, not as a hidden runtime dependency for go-live.
- Platform-owned affiliate tracking for SaaS sales also lives in dedicated `platform_*` tables and attaches to platform plan orders; affiliate accounts are separate from tenant `users` and do not participate in tenant authentication.
- Successful self-serve payment must also finalize onboarding by activating the tenant and sending the post-payment welcome email; welcome mail should not be sent before payment settles.
- For go-live, platform-owner Midtrans credentials may be sourced directly from `.env` via `config/services.php` when no persisted `midtrans_settings` row exists for tenant `id = 1`.
- New quota or premium-feature work should prefer adding keys to the centralized plan feature/limit layer instead of hardcoding rules inside modules. Omnichannel module routes should enforce plan access with `plan.feature:*` middleware so the package sold to the tenant matches the modules they can use.
- Plan limits now use a conservative contract: `0` means the tenant has no capacity for that resource, while negative values such as `-1` are treated as unlimited and should be reserved for internal/system plans only.
- Expensive resources should register themselves with the centralized plan limit layer so usage, remaining quota, and risk state can be observed consistently in tenant settings and the `dash` control plane.
- Over-limit policy is `block new only`: tenants that already exceed a cap may continue to view and edit existing records, but must be blocked from creating additional resources until they upgrade or usage drops below the limit.
- Public sales plans may now move independently from legacy subscriber plans. New public revisions should be created with new plan codes, while legacy codes remain active for existing subscriptions but are removed from the public catalog instead of being silently mutated.
- Apex onboarding should resolve legacy marketing aliases such as `starter`, `growth`, and `scale` to the current public revision code when newer plan revisions replace the old catalog.
- Plan features can represent product bundles, not only single modules. Use umbrella features such as `commerce` and `project_management` when several modules must be sold together while still keeping route gating and plan editing centralized.
- Subscription plan naming should separate `product_line` from the tier label. Keep internal plan `code` globally unique, keep the editable `name` focused on the tier, and use `meta.product_line` to present clear labels such as `Omnichannel Growth` or `CRM Starter` without forcing category text into every raw plan name.
- Company-aware rollout has started with `finance` and `point-of-sale` cash session boundaries. New work in accounting, cashier shift, and related reporting should follow the active `tenant + company` scope.
- Branch-aware rollout has started with `point-of-sale`, `finance transactions`, `sales`, and `payments`, but branch must remain optional where the flow is company-level rather than outlet-level.
- Reports are being migrated to the same active `tenant + company + optional branch` runtime context. New or updated report queries must follow the active context resolver and must not reintroduce legacy `outlet_id`-style bypass filters.
- Contacts is an external/business master domain, not the internal company/branch tree. During the current rollout, contact visibility should honor `tenant + optional company + optional branch`, while external organization/employer linkage must stay separate from internal `companies` / `branches`.

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
- `docs/product/pricing.md`: pricing model, quota policy, and storage positioning
- `SAAS_TENANCY.md`: target SaaS tenancy model, tenant lifecycle, plan gating, and multi-company direction
- `SAAS_PRODUCT_MODEL.md`: target product model for tenant, company, branch, industry presets, module entitlement, and rollout order
