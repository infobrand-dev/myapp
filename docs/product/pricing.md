# Pricing Model

## Core model
- Base subscription is `per tenant / workspace`, not full `per user`.
- Each plan still has a hard `max_users` limit.
- Users are treated as a constrained infrastructure resource, not as an unlimited free dimension.

## Why this model
- The product value is broader than agent seats: modules, channels, automation, AI, storage, inbox operations, and quotas.
- A pure `per user` model would position the product too narrowly as a helpdesk seat tool.
- A pure `per tenant with unlimited users` model would create infrastructure risk and weak cost control.

## Commercial structure
- `Plan price`: charged per tenant / workspace.
- `Included capacity`: users, contacts, channels, chatbot accounts, AI credits, blast quota, and total storage.
- `Add-ons`: used for premium or expensive resources such as BYO AI, extra users, extra storage, or future premium channel capacity.

## User policy
- Every public plan must define `max_users`.
- `0` means the tenant cannot create users on that plan.
- Negative values are reserved for internal or special unlimited plans only.
- Over-limit policy is `block new only`: existing users remain usable, but tenant cannot add more until usage drops or the plan changes.

## Storage policy
- Storage must be presented to tenants as `total storage`, shared across modules.
- Do not expose fragmented storage limits per module in public plan copy.
- Internal observability may still track module-level storage usage for ops and cost analysis.

## Omnichannel policy
- Omnichannel plans should include the base workspace price plus hard caps for:
  - users
  - WhatsApp instances
  - social accounts
  - live chat widgets
  - chatbot accounts
  - AI credits
  - WA blast recipients monthly
  - total storage

## Recommended add-ons
- `BYO AI`
- `Extra Users`
- `Extra Storage`
- `Extra Blast Quota`

## Current implementation notes
- User limits are enforced through `App\Support\PlanLimit::USERS`.
- Total storage is enforced through `App\Support\PlanLimit::TOTAL_STORAGE_BYTES`.
- Module and communication limits are enforced through the centralized plan feature and limit layer.
- Pricing and quota communication should stay consistent with this document unless the billing model is intentionally revised.
