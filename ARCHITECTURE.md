# ARCHITECTURE.md - App-Specific Notes

## Product snapshot
- Laravel 11 app with Breeze (Blade), Tabler UI, Laravel Mix, Spatie Permission, and PostgreSQL as the primary runtime database.
- Core app responsibilities are the shell and shared platform pieces such as authentication, dashboard, profile, users, roles, module registry, shared layout, and cross-cutting infrastructure.
- Business features live under `app/Modules/*` and are discovered from each module's `module.json`.

## Current architecture
- Public apex/marketing routes live in `routes/public.php` under the `public-web` middleware group.
- Tenant-aware shell and authenticated app routes live in `routes/web.php`, with auth endpoints still loaded through `routes/auth.php`.
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
- SaaS subdomain resolution should honor the configured production root domain and also the host from `APP_URL` for local development, so tenant-scoped public routes such as `/shop` do not need a separate apex/public registration just to work in dev.
- `ResolveTenantContext` is intended for tenant-aware app routes, not for apex marketing pages. New public pages should be added to `routes/public.php` instead of extending middleware bypass lists.
- Current resolver order is: explicit request attribute/header/query, session, authenticated user `tenant_id`, then fallback to tenant `id = 1` only for standalone/bootstrap-safe flows. In SaaS mode, unresolved tenant context should fail closed instead of silently falling back to tenant `1`.
- In SaaS mode, guest auth pages must be reached from the tenant subdomain. Apex/root domain is for onboarding or workspace discovery, not shared tenant login.
- In SaaS mode, open self-registration for tenant subdomains is no longer allowed. New tenant users should come from owner/admin invite or controlled internal creation, not from a public `/register` screen.
- Public self-serve onboarding now starts from the apex onboarding flow with `accounting` as the active default product line. Buyer selects a public subscription plan and then either starts an `accounting` free trial directly on eligible public monthly plans or creates a tenant in `pending_payment`, receives a platform invoice, and pays either through Midtrans checkout or manual bank transfer with a unique amount before the tenant is activated.
- Pending self-serve onboarding may create a tenant before payment so billing records can stay attached to a concrete workspace, but stale unpaid workspaces must remain cleanup-friendly and slug reuse must be delayed through a temporary slug reservation.
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
- If a domain is sold as an optional module or bundle component, its business schema and implementation should stay owned by that module instead of drifting into core.
- `module.json` is the source of truth for module metadata such as slug, name, provider, version, description, category, `requires`, and navigation items.
- `module.json` is also the source of truth for each module's sidebar/icon metadata. Module SVG assets should live with the owning module, and shared UI should reuse those assets instead of redefining icons per page.
- Navigation is a UX decision, not a mandatory mirror of code ownership. Integration modules may intentionally keep `navigation: []` and be surfaced through shared settings pages instead of the main sidebar.

## Core notifications
- Notification infrastructure is a core concern, not a business module. Shared inbox UI, unread counters, delivery logs, push subscriptions, and email transport should stay in core.
- Modules should publish typed notification messages through the shared notification center instead of querying or mutating core notification tables directly.
- Core must not scan module tables to invent business alerts. The owning module decides when a notification is created, deduped, resolved, or superseded.
- Dashboard notification widgets, topbar bell state, and channel delivery policy should be driven from the shared notification subsystem so cross-module alerting stays consistent.
- Platform-owned mail such as onboarding, platform invoices, invitations, and welcome messages should continue using the global platform mailer.
- Tenant-to-customer transactional mail is a separate layer. For accounting it should support `Email Terkelola` with plan-based monthly quota and `SMTP Sendiri` for eligible plans, log each outbound attempt, and fail closed when the selected delivery mode is not entitled or not configured.

## Feature modes
- Product-line UI complexity now has a dedicated core resolver in `App\Support\FeatureMode`.
- Keep `plan feature gating` separate from `feature mode gating`:
  - plan gating decides whether a tenant may access a module or capability at all
  - feature mode decides whether the active experience is `standard` or `advanced`
- `commerce` is now a first-class product line separate from `accounting`. Shared modules such as `products`, `sales`, and `payments` may expose different route/menu entrypoints for `accounting` vs `commerce`, but the underlying domain owner stays single-source.
- `commerce` payment providers are modeled as integration modules behind a shared payment gateway abstraction. `midtrans`, `xendit`, and `tripay` keep vendor-specific API, webhook, and transaction log logic close to themselves, while tenant UX is centralized under `Settings > Payment Gateway`.
- `commerce` shipping providers follow the same pattern. `biteship` and `rajaongkir` keep vendor-specific quote logic and tenant credentials in their own modules, while provider selection is centralized under `Settings > Shipping Provider`.
- For `accounting`, `accounting_starter` should default to `standard`, while plans that expose `advanced_reports` default to `advanced`.
- Hidden advanced fields must stay safe at request/service level through conditional validation, payload sanitization, preserve-on-update behavior, and route middleware such as `mode:advanced`.
- Do not rely on Blade-only hide/show for business safety. If a flow is truly advanced, gate the route/controller too.

## Module registry rules
- Install and activation are separate states.
- Install may run module migrations and role seeding.
- Activation must respect declared dependencies.
- Deactivation must refuse when active dependents still require the module.
- Category is metadata for grouping and filtering in the Modules UI; it is not a runtime policy layer.

## Auth
- Super-admin and tenant users authenticate through the standard auth flow.
- Tenant user creation in SaaS should prefer invite-driven onboarding. If a tenant user is created before first login, they must still verify their email before dashboard access.
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
- For the current `commerce` rollout, finance controls, purchases, formal reports, and inventory governance stay under `accounting`, while storefront, shipping, fulfillment, and commerce-safe sales/payment views may be opened under `commerce`.
- `storefront` may expose guest tenant-subdomain routes such as `/shop` for public catalog and basic checkout, but those routes must stay tenant-scoped and must not reuse apex marketing routing.
- Public storefront now uses a session-scoped cart per `tenant + company` and separates `/shop/cart` from `/shop/checkout` so multi-item guest checkout can stay on the shared `sales` engine without inventing a second order store.
- Public storefront resolution must bind to an explicit tenant company through tenant meta such as `public_storefront_enabled` and `default_public_company_id`. Guest storefront reads for catalog, payment gateway, shipping provider, and public order lookup should use that bound company instead of falling back to an arbitrary active company.
- Public storefront checkout should call the shared payment gateway manager instead of talking directly to a vendor service, so switching the active tenant provider does not fork the storefront flow.
- Public storefront delivery checkout may also call the shared shipping provider manager to compute an estimated shipping rate before the order is handed off to the internal shipping queue. Keep provider-specific origin/destination requirements isolated behind the shared shipping quote service instead of branching the storefront form flow in many places.
- Company shipping origin should be maintained on the active company settings (`company.meta.shipping_origin_postal_code` / `shipping_origin_area_id`), while sellable product weight and dimensions should be maintained on `product.meta.shipping.*` so checkout quoting does not rely only on hidden fallbacks.
- Delivery checkout should fail closed when shipping prerequisites are missing. Missing origin data, missing product weight, missing destination fields, or missing active shipping provider should return visitor-safe validation instead of silently falling back to manual shipping.
- When a shipping provider returns multiple rates, public checkout should require an explicit rate selection and persist the chosen rate in `sales.meta.commerce.shipping.selected_rate`, while the shipping amount also contributes to official sale totals.
- Public storefront checkout currently reuses the shared `sales` domain as the single source of truth. Commerce lifecycle state such as `pending_payment`, `paid`, fulfillment, shipping, expiry, and customer-facing timeline events should be carried in `sales.meta.commerce` rather than introducing a second order engine.
- Multi-item storefront checkout should aggregate cart lines into one `Sale` while keeping shipping quote, selected rate, and payment lifecycle in the same `sales.meta.commerce` envelope.
- Public commerce payment lifecycle should normalize provider checkout creation, retry, paid, failed, expired, and cancelled states inside `sales.meta.commerce.payment`, while vendor-specific transaction logs remain owned by each payment module.
- Pending public commerce orders must be cleanup-friendly. Use the scheduled `commerce:expire-pending-orders` command and bounded checkout throttling/idempotency so unpaid storefront traffic does not silently accumulate draft/finalized noise.
- Shipping quote flows should call the shared shipping provider manager instead of talking directly to a vendor service, so switching the active tenant provider does not fork the shipping workspace flow.
- `shipping` and `fulfillment` tenant workspaces should behave like order queues, not like provider sandboxes or placeholder status pages. Prefer list/detail operational layouts, queue counts, and explicit handoff actions over free-form explanatory copy.
- For repetitive queue work, prefer lightweight bulk actions on the same `Sale` records instead of introducing a parallel batch-processing model. Keep bulk shipping/fulfillment actions as orchestration over the existing commerce lifecycle service.
- `commerce orders` may expose quick tabs for common operational slices such as pending payment, ready for fulfillment, delivery, pickup, and shipped, but deeper filtering should still flow through the main filter form so reporting and queue views stay aligned.

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
- Product-line-scoped multi-plan subscription foundation is now in the main runtime (`tenant_subscriptions.product_line`, `Tenant::activeSubscriptionFor()`, `TenantPlanManager::currentSubscriptionFor()`, and product-line-aware billing activation). Keep follow-up notes close to the owning billing/runtime docs instead of reviving a separate migration blueprint.
- `SAAS_TENANCY.md`: target SaaS tenancy model, tenant lifecycle, plan gating, and multi-company direction
- `SAAS_PRODUCT_MODEL.md`: target product model for tenant, company, branch, industry presets, module entitlement, and rollout order
