# SAAS_TENANCY.md - SaaS Tenancy Target

## Product model
- The platform has a main public website that handles landing page, registration, plan selection, billing, and other SaaS-facing flows.
- A successful registration from that public website creates one `tenant` for the registrant.
- The current web app in this repository is the tenant application that will be accessed by clients who have already registered.

## Tenant lifecycle
- One SaaS customer registration creates one tenant.
- The first owner/admin user for that customer belongs to the created tenant.
- Tenant `id = 1` remains reserved for platform/bootstrap-safe behavior and internal control-plane needs.
- Tenant resolution should prefer session-based context for the web application, with safe fallback to the authenticated user's `tenant_id` and then tenant `id = 1`.

## App separation
- Public SaaS website concerns:
  - landing page
  - registration
  - plan catalog
  - billing and subscription lifecycle
  - tenant creation and initial owner provisioning
- Tenant web app concerns:
  - all business modules already present in this application
  - day-to-day operational usage by registered client users
  - feature access controlled by subscription plan

## Plan enforcement
- Access inside the tenant web app must be limited by the subscribed plan.
- Plan enforcement should happen at feature/module capability level, not only at menu visibility level.
- Module activation, quotas, and premium features should be designed so they can be gated by tenant subscription state.
- A hidden menu item is not enough; controller, policy, job, API, and webhook paths should also respect plan limits.
- Plan limits should support hard quotas such as `max_companies`, `max_users`, `max_products`, `max_contacts`, and similar resource caps.
- Feature toggles should support boolean capabilities such as `multi_company`, `email_marketing`, `whatsapp_api`, and advanced reporting.

## Multi-company direction
- `tenant` represents the SaaS customer account.
- `company` represents a business entity, branch group, or legal/operational unit inside one tenant.
- Multi-company is a sub-scope under a tenant, not a replacement for tenant.
- New design should avoid mixing the meaning of tenant and company.
- `branch` is a lower operational scope under `company`, not a replacement for company.
- Restaurant outlets, store outlets, warehouse branches, and regional offices belong to the `branch` layer.

## Recommended data model direction
- `tenants`
  - SaaS account / customer
- `companies`
  - belongs to a tenant
  - used when a tenant needs more than one business entity or branch grouping
- `branches`
  - belongs to a company
  - used for outlet/location-level operations
- `users`
  - belong to a tenant
  - may later be limited to one or more companies under that tenant
- business records
  - must remain tenant-scoped
  - may later become tenant-scoped, company-scoped, and branch-scoped where operationally needed

## Authorization direction
- Users must only access records that belong to their tenant unless they are explicitly authorized for cross-tenant internal operations.
- Tenant-aware route model binding is necessary but not sufficient.
- Policies, gates, service checks, background jobs, and reporting paths must all honor tenant ownership.
- When multi-company is introduced, company access must be enforced as an additional scope under the active tenant.
- Role and permission assignment is tenant-scoped through Spatie Permission `teams` using `tenant_id`.

## Session direction
- Session is the primary tenant context for the tenant web app.
- Login should establish tenant context from the authenticated user's tenant.
- Logout and session rotation should clear tenant context.
- Login must not be ambiguous across tenants. If the same email can exist in multiple tenants, the app must already know the target tenant before authentication, for example from session bootstrap or another trusted entry context.
- APIs or machine integrations may later use token/header-based tenant resolution, but web usage should stay session-first.

## Immediate implementation priorities
1. Keep `TenantContext` as the single source of active tenant resolution.
2. Build tenant-aware login/bootstrap so session always carries the correct tenant.
3. Add tenant ownership authorization rules across app entry points.
4. Design plan capability checks for modules and premium features.
5. Introduce `companies` under tenants only after tenant authz and plan boundaries are stable.

## Current foundation in code
- `subscription_plans` stores plan definitions, feature flags, and numeric limits.
- `tenant_subscriptions` stores the active plan per tenant plus override fields for future billing integration.
- `companies` is prepared as a first-class entity under `tenant`.
- `App\Support\TenantPlanManager` is the central service for:
  - current tenant plan lookup
  - feature checks
  - quota checks
  - future route/controller middleware integration

## Related product model
- See `SAAS_PRODUCT_MODEL.md` for the full target model of:
  - tenant
  - company
  - branch
  - industry profile
  - module entitlement
  - plan quota and feature rollout
''
