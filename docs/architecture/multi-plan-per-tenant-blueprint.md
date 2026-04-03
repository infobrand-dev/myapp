# Multi-Plan Per Tenant Blueprint

## Goal
- Allow one tenant to hold multiple active subscriptions in parallel.
- Primary target shape:
  - `omnichannel`
  - `commerce`
  - `productivity`
  - future product lines without redesigning billing again
- Preserve current platform billing tables and platform-owner workflow as much as possible.

## Current constraint
- Runtime still assumes one active subscription per tenant.
- Main single-plan assumptions live in:
  - `App\Models\Tenant::activeSubscription()`
  - `App\Support\TenantPlanManager`
  - `App\Http\Controllers\PlatformOwnerController::assignPlan()`
  - `App\Http\Controllers\PlatformOwnerController::markOrderPaid()`
  - `App\Services\TenantOnboardingSalesService`
- Current behavior on plan activation:
  - expire all active subscriptions for the tenant
  - create one new active subscription
- This blocks a tenant from stacking plans by product line.

## Target model

### 1. Product-line scoped active subscriptions
- A tenant may have multiple active subscriptions.
- Only one active subscription is allowed per `product_line`.
- Example:
  - one active `omnichannel`
  - one active `commerce`
  - one active `productivity`
- This keeps plan conflict resolution simple.

### 2. Product line source of truth
- `subscription_plans.meta.product_line` remains the source of truth for the plan family.
- `tenant_subscriptions` must persist the resolved `product_line` directly as a denormalized runtime column.
- Reason:
  - cheaper filtering
  - safer historical billing
  - avoids repeated JSON extraction from plans at runtime

### 3. Runtime contract
- Features and limits are resolved from:
  - the active subscription for the relevant `product_line`
  - plus tenant-level overrides already stored on that subscription
- Cross-product features should stay explicit.
- Do not silently merge unrelated plans unless the key is declared mergeable.

## Recommended schema changes

### tenant_subscriptions
- Add `product_line` string column, indexed with tenant and status.
- Add composite index:
  - `tenant_id`
  - `product_line`
  - `status`
  - `starts_at`
- Optional later:
  - `stack_key` if product lines ever need sub-packages

### platform_plan_orders
- Add denormalized `product_line`.
- Used for:
  - safer order voiding
  - product-line-scoped activation
  - reporting

### platform_invoices
- Add denormalized `product_line`.
- Useful for billing filters and export.

## Model changes

### Tenant
Replace the mental model:
- current: `activeSubscription()`
- target:
  - `activeSubscriptions()`
  - `activeSubscriptionFor(string $productLine)`

Keep `activeSubscription()` temporarily for backward compatibility, but mark it transitional.
It should return:
- the active `omnichannel` subscription if present, or
- the latest active subscription as fallback only during migration

### TenantSubscription
Add helpers:
- `productLine(): ?string`
- `scopeForProductLine($query, string $productLine)`
- `scopeCurrentForProductLine($query, int $tenantId, string $productLine)`

## TenantPlanManager redesign

## New runtime API
- `subscriptionForFeature(string $feature, ?int $tenantId = null): ?TenantSubscription`
- `subscriptionForLimit(string $limitKey, ?int $tenantId = null): ?TenantSubscription`
- `currentSubscriptions(?int $tenantId = null): Collection`
- `currentSubscriptionFor(string $productLine, ?int $tenantId = null): ?TenantSubscription`

## Feature resolution
- Every feature must map to one owning `product_line`.
- Example:
  - omnichannel features -> `omnichannel`
  - commerce features -> `commerce`
  - productivity features -> `productivity`
- Add a central map in code, for example:
  - `App\Support\PlanProductLineMap`

## Limit resolution
- Limits should also map to an owning `product_line`.
- Do not sum limits from different product lines by default.
- Example:
  - `max_users` might stay tenant-global, but must choose one rule:
    - owner plan only
    - highest active plan wins
    - explicit add-on bucket
- Recommended default:
  - channel/module-specific limits are product-line scoped
  - tenant-global limits are explicitly flagged as `tenant_shared`

## Recommended merge policy
- `tenant_shared`:
  - `max_users`
  - `max_total_storage_bytes`
- `product_line_owned`:
  - whatsapp instances
  - social accounts
  - live chat widgets
  - chatbot accounts
  - AI credits
  - commerce/productivity future limits

## Shared limit rule
- Avoid implicit summing by default.
- For phase 1:
  - highest active entitlement wins for shared limits
- This is simpler than additive stacking and avoids abuse.

## Billing behavior changes

### assignPlan
- Current behavior expires every active subscription.
- Target behavior:
  - resolve selected plan `product_line`
  - expire only the active subscription in the same `product_line`
  - keep active subscriptions from other product lines untouched

### order payment activation
- Current `markOrderPaid()` and invoice payment activation expire every active subscription.
- Target behavior:
  - activate subscription for the order's `product_line`
  - expire only the previous active subscription in that same `product_line`

### onboarding
- Current public onboarding is omnichannel-only.
- Keep that for now.
- Future onboarding may allow choosing a product line first, but this is not required for the multi-plan refactor itself.

## Platform owner UX target

### Tenant detail page
Replace single plan block with:
- `Active Plans`
  - Omnichannel
  - Commerce
  - Productivity
- each card shows:
  - plan tier
  - status
  - starts_at / ends_at
  - provider
  - overrides
  - replace / cancel actions

### Assign plan action
- Require product line visibility in the selector.
- UX should read like:
  - `Assign Omnichannel Plan`
  - `Assign Commerce Plan`
  - `Assign Productivity Plan`

### Orders and invoices
- Show `product_line` column.
- This will make test data, void flows, and revenue audit easier to understand.

## Tenant UI target
- Tenant-facing settings should stop talking about one plan only.
- Replace with:
  - `Workspace Subscriptions`
  - grouped by product line
- For now, tenant shell may still highlight the omnichannel subscription first if that is the current active product.

## Migration strategy

### Phase 1. Foundation
- Add denormalized `product_line` columns.
- Backfill from `subscription_plans.meta.product_line`.
- Add new helper methods without removing old ones.

### Phase 2. Safe runtime bridge
- Introduce product-line-aware lookups in `TenantPlanManager`.
- Keep backward-compatible `currentSubscription()` temporarily.
- Route existing omnichannel logic through `product_line = omnichannel`.

### Phase 3. Billing activation change
- Update:
  - `assignPlan()`
  - `markOrderPaid()`
  - manual payment activation
  - Midtrans activation
- Expire subscriptions only within the same product line.

### Phase 4. UI split
- Platform tenant detail:
  - replace single plan panel with product-line panels
- Tenant settings:
  - show stacked subscriptions

### Phase 5. Cleanup
- Remove legacy assumptions that a tenant only has one active subscription.
- Reduce use of `activeSubscription()` until it becomes optional or is removed.

## Risks
- `max_users` and `total_storage` are tenant-global; careless stacking can create unclear pricing or double-counting.
- Existing analytics and dashboards may still display only one active plan.
- Existing add-ons like BYO AI currently sit on the active subscription; once there are multiple subscriptions, the add-on must remain attached only to the owning product line, likely `omnichannel`.
- Existing onboarding emails and invoices may assume one current plan label.

## Recommended decisions now
- Do not support multiple active subscriptions in the same `product_line`.
- Do not sum shared limits in phase 1.
- Treat `omnichannel` as the first migrated product line and keep public onboarding there.
- Keep add-ons attached to the owning product line subscription, not to the tenant globally.

## First implementation slice
- Add `product_line` to:
  - `tenant_subscriptions`
  - `platform_plan_orders`
  - `platform_invoices`
- Backfill values.
- Add:
  - `Tenant::activeSubscriptionFor()`
  - `Tenant::activeSubscriptions()`
  - `TenantPlanManager::currentSubscriptionFor()`
- Change billing activation to expire only within the same product line.
- Leave tenant-facing UX mostly unchanged in the first release except platform owner tenant detail.

## Not included in phase 1
- additive stacking of shared limits
- multi-product onboarding catalog
- bundled pricing packages that auto-create multiple subscriptions in one checkout
- tenant self-serve upgrade matrix across different product lines
