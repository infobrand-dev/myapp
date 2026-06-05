# Runbook: Cloudflare Custom Domains

## Owner bootstrap
1. Add SaaS zone to Cloudflare.
2. Enable Cloudflare for SaaS.
3. Create and proxy fallback origin.
4. Create a friendly CNAME target, for example `customers.example.com`.
5. Fill `Platform > Domains`:
   - Account ID
   - Zone ID
   - API token
   - Fallback origin hostname
   - CNAME target
   - Apex Proxying capability and assigned A/AAAA targets if available

## Tenant flow
1. Tenant admin opens `Settings > Custom Domains`.
2. Tenant submits a hostname.
3. System creates Cloudflare custom hostname unless the request is blocked by policy.
4. Tenant copies DNS instructions:
   - TXT for ownership
   - CNAME for subdomain routing
   - A/AAAA for apex only when owner entitlement exists
5. Tenant or owner runs sync until status becomes `active`.
6. Active domain can be promoted to canonical.

## Common failures
- `apex_proxying_required`
  - Cause: tenant asked for apex but owner account does not support Apex Proxying.
  - Action: keep blocked or ask tenant to use a subdomain.
- `provider_unreachable`
  - Cause: invalid/revoked API token or Cloudflare API issue.
  - Action: fix owner credentials, then sync again.
- `failed`
  - Cause: provider-side create/delete error.
  - Action: inspect `last_error_message`, `tenant_domain_events`, and retry provisioning if needed.

## Audit trail
- Check `tenant_domain_events` for requested, provider_created, ownership_verified, ssl_active, canonical_promoted, activation_failed, and removed.
- Run `php artisan tenant:domains-audit` for summary and problematic domains.
