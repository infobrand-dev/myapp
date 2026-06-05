# Custom Domains via Cloudflare for SaaS

## Purpose
- Internal technical reference for tenant custom domain support.
- Non-public control-plane capability. Tenant only sees their own domain records and DNS instructions.

## Core model
- `tenant_domains` is the lifecycle record for a tenant hostname.
- `tenant_domain_events` stores immutable audit events.
- `cloudflare_saas_settings` stores the single owner-managed Cloudflare connection and routing defaults.

## Resolution order
1. `dash.<saas-root>` for platform owner.
2. Exact active tenant custom domain from `tenant_domains`.
3. Default tenant slug subdomain under SaaS root.
4. Apex public/onboarding routes.

## Lifecycle states
- `draft`: domain request accepted locally.
- `pending_provider`: create request sent to Cloudflare.
- `pending_dns`: Cloudflare hostname exists, tenant still needs DNS.
- `pending_ownership`: Cloudflare is checking ownership.
- `pending_ssl`: ownership is good, SSL not yet active.
- `active`: hostname and SSL both active.
- `blocked`: domain cannot proceed, usually apex without owner entitlement.
- `failed`: provider or provisioning failure.
- `removing`: delete in progress.
- `removed`: historical tombstone.

## Canonical host behavior
- If a tenant has an active canonical custom domain, it becomes the preferred workspace host.
- The legacy SaaS subdomain remains available as bootstrap/fallback.
- Authenticated users landing on the wrong tenant host are redirected through a one-time domain handoff token because cookies cannot be shared across arbitrary domains.

## Cloudflare integration
- Provider: Cloudflare for SaaS Custom Hostnames.
- Ownership verification default: DNS TXT.
- Sync model: application polling via jobs/owner actions, not webhook-driven.
- Apex domains require owner-side `apex_proxying_enabled`; otherwise the request is stored as `blocked`.

## Operational notes
- If owner API token is revoked, active domains stay untouched, but new provisioning and sync operations fail closed.
- `tenant:domains-audit` is the first owner diagnostic surface for blocked, failed, or provider-unreachable domains.
