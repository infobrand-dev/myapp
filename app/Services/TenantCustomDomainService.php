<?php

namespace App\Services;

use App\Models\CloudflareSaasSetting;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantDomainEvent;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class TenantCustomDomainService
{
    public function __construct(
        private readonly CloudflareSaasClient $cloudflare,
        private readonly TenantDomainDnsInstructionService $dnsInstructions,
        private readonly TenantHostResolver $hostResolver,
    ) {
    }

    public function requestDomain(Tenant $tenant, string $hostname, ?int $actorUserId = null): TenantDomain
    {
        $normalized = $this->normalizeHostname($hostname);
        $this->validateRequestedHostname($tenant, $normalized);

        return DB::connection('central')->transaction(function () use ($tenant, $normalized, $actorUserId): TenantDomain {
            $domain = TenantDomain::query()->create([
                'tenant_id' => $tenant->id,
                'hostname' => $normalized,
                'hostname_type' => $this->inferHostnameType($normalized),
                'provider' => TenantDomain::PROVIDER_CLOUDFLARE_SAAS,
                'is_primary' => false,
                'is_canonical' => false,
                'status' => TenantDomain::STATUS_DRAFT,
                'verification_method' => 'txt',
            ]);

            $this->recordEvent($domain, 'requested', $actorUserId, 'Tenant meminta custom domain.', [
                'hostname' => $domain->normalizedHostname(),
            ]);

            $settings = CloudflareSaasSetting::current();

            if ($domain->hostname_type === 'apex' && !$settings->apex_proxying_enabled) {
                $domain->forceFill([
                    'status' => TenantDomain::STATUS_BLOCKED,
                    'last_error_code' => 'apex_proxying_required',
                    'last_error_message' => 'Apex Proxying Cloudflare belum aktif pada akun owner.',
                ])->save();

                $this->recordEvent($domain, 'activation_failed', $actorUserId, 'Domain apex diblok karena Apex Proxying belum aktif.', [
                    'reason' => 'apex_proxying_required',
                ]);

                return $domain->fresh();
            }

            $domain->update(['status' => TenantDomain::STATUS_PENDING_PROVIDER]);

            try {
                $provider = $this->cloudflare->createCustomHostname($domain->normalizedHostname(), [
                    'ssl' => [
                        'method' => 'txt',
                        'type' => 'dv',
                    ],
                ]);
            } catch (\Throwable $exception) {
                $settings->forceFill([
                    'last_health_checked_at' => now(),
                    'last_error_summary' => mb_substr($exception->getMessage(), 0, 4000),
                ])->save();

                $domain->markFailure('provider_unreachable', $exception->getMessage());
                $this->recordEvent($domain, 'activation_failed', $actorUserId, 'Provisioning ke Cloudflare gagal.', [
                    'error' => $exception->getMessage(),
                ]);

                return $domain->fresh();
            }

            $routingType = $domain->hostname_type === 'apex' ? 'A' : 'CNAME';
            $routingValue = $domain->hostname_type === 'apex'
                ? implode(', ', array_filter(array_merge((array) $settings->apex_ipv4_targets, (array) $settings->apex_ipv6_targets)))
                : (string) $settings->cname_target;

            $domain->forceFill([
                'status' => TenantDomain::STATUS_PENDING_DNS,
                'cloudflare_hostname_id' => (string) ($provider['id'] ?? ''),
                'cloudflare_ssl_status' => (string) data_get($provider, 'ssl.status'),
                'ownership_dns_name' => (string) data_get($provider, 'ownership_verification.name'),
                'ownership_dns_value' => (string) data_get($provider, 'ownership_verification.value'),
                'routing_record_type' => $routingType,
                'routing_record_name' => $domain->normalizedHostname(),
                'routing_record_value' => $routingValue,
                'last_synced_at' => now(),
                'meta' => array_filter([
                    'cloudflare_response' => Arr::only($provider, ['id', 'hostname', 'status']),
                ]),
            ])->save();

            $this->recordEvent($domain, 'provider_created', $actorUserId, 'Custom hostname berhasil dibuat di Cloudflare.', [
                'cloudflare_hostname_id' => $domain->cloudflare_hostname_id,
            ]);

            return $domain->fresh();
        });
    }

    public function syncDomain(TenantDomain $domain, ?int $actorUserId = null): TenantDomain
    {
        if (!$domain->cloudflare_hostname_id) {
            return $domain;
        }

        $settings = CloudflareSaasSetting::current();

        try {
            $provider = $this->cloudflare->getCustomHostname($domain->cloudflare_hostname_id);
            $settings->forceFill([
                'last_health_checked_at' => now(),
                'last_error_summary' => null,
            ])->save();
        } catch (\Throwable $exception) {
            $settings->forceFill([
                'last_health_checked_at' => now(),
                'last_error_summary' => mb_substr($exception->getMessage(), 0, 4000),
            ])->save();

            $domain->forceFill([
                'last_error_code' => 'provider_unreachable',
                'last_error_message' => mb_substr($exception->getMessage(), 0, 4000),
                'last_synced_at' => now(),
            ])->save();

            $this->recordEvent($domain, 'activation_failed', $actorUserId, 'Sinkronisasi Cloudflare gagal.', [
                'error' => $exception->getMessage(),
            ]);

            return $domain->fresh();
        }

        $status = (string) ($provider['status'] ?? '');
        $sslStatus = (string) data_get($provider, 'ssl.status', '');
        $ownershipName = (string) data_get($provider, 'ownership_verification.name', $domain->ownership_dns_name);
        $ownershipValue = (string) data_get($provider, 'ownership_verification.value', $domain->ownership_dns_value);

        $nextStatus = match (true) {
            $status === 'active' && $sslStatus === 'active' => TenantDomain::STATUS_ACTIVE,
            $status === 'active' => TenantDomain::STATUS_PENDING_SSL,
            $status !== '' => TenantDomain::STATUS_PENDING_OWNERSHIP,
            default => TenantDomain::STATUS_PENDING_DNS,
        };

        $wasActive = $domain->status === TenantDomain::STATUS_ACTIVE;

        $domain->forceFill([
            'status' => $nextStatus,
            'cloudflare_ssl_status' => $sslStatus ?: null,
            'ownership_dns_name' => $ownershipName ?: null,
            'ownership_dns_value' => $ownershipValue ?: null,
            'last_synced_at' => now(),
            'last_verified_at' => $nextStatus === TenantDomain::STATUS_ACTIVE ? now() : $domain->last_verified_at,
            'activation_checked_at' => now(),
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();

        if (!$wasActive && $nextStatus === TenantDomain::STATUS_ACTIVE) {
            $this->recordEvent($domain, 'ownership_verified', $actorUserId, 'Ownership domain tervalidasi.', []);
            $this->recordEvent($domain, 'ssl_active', $actorUserId, 'SSL custom domain aktif.', []);
            Cache::forget('trust-hosts:tenant-custom-domains');
            $this->hostResolver->clearTenantCache($domain->tenant_id, $domain->normalizedHostname());
        }

        return $domain->fresh();
    }

    public function promoteCanonical(TenantDomain $domain, ?int $actorUserId = null): TenantDomain
    {
        if ($domain->status !== TenantDomain::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'domain' => 'Domain harus aktif sebelum dijadikan canonical.',
            ]);
        }

        DB::connection('central')->transaction(function () use ($domain, $actorUserId): void {
            TenantDomain::query()
                ->where('tenant_id', $domain->tenant_id)
                ->update([
                    'is_primary' => false,
                    'is_canonical' => false,
                ]);

            $domain->forceFill([
                'is_primary' => true,
                'is_canonical' => true,
            ])->save();

            $this->recordEvent($domain, 'canonical_promoted', $actorUserId, 'Domain dijadikan canonical host tenant.', []);
            $this->hostResolver->clearTenantCache($domain->tenant_id, $domain->normalizedHostname());
        });

        return $domain->fresh();
    }

    public function removeDomain(TenantDomain $domain, ?int $actorUserId = null): void
    {
        if ($domain->is_primary || $domain->is_canonical) {
            throw ValidationException::withMessages([
                'domain' => 'Domain primary/canonical tidak bisa dihapus langsung.',
            ]);
        }

        $domain->update(['status' => TenantDomain::STATUS_REMOVING]);

        if ($domain->cloudflare_hostname_id) {
            try {
                $this->cloudflare->deleteCustomHostname($domain->cloudflare_hostname_id);
            } catch (\Throwable $exception) {
                $domain->markFailure('delete_failed', $exception->getMessage());
                throw $exception;
            }
        }

        $this->recordEvent($domain, 'removed', $actorUserId, 'Custom domain dihapus.', []);

        $hostname = $domain->normalizedHostname();

        $domain->forceFill([
            'status' => TenantDomain::STATUS_REMOVED,
            'last_synced_at' => now(),
        ])->save();

        Cache::forget('trust-hosts:tenant-custom-domains');
        $this->hostResolver->clearTenantCache($domain->tenant_id, $hostname);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function dnsInstructions(TenantDomain $domain): array
    {
        return $this->dnsInstructions->instructionsFor($domain);
    }

    private function validateRequestedHostname(Tenant $tenant, string $hostname): void
    {
        Validator::make([
            'hostname' => $hostname,
        ], [
            'hostname' => ['required', 'string', 'max:255'],
        ])->validate();

        if (!str_contains($hostname, '.')) {
            throw ValidationException::withMessages([
                'hostname' => 'Hostname harus berupa FQDN lengkap.',
            ]);
        }

        $reservedHosts = array_filter([
            strtolower(trim((string) config('multitenancy.saas_domain'))),
            strtolower(trim((string) parse_url((string) config('app.url'), PHP_URL_HOST))),
            strtolower(trim((string) config('multitenancy.platform_admin_subdomain', 'dash') . '.' . config('multitenancy.saas_domain'))),
        ]);

        if (in_array($hostname, $reservedHosts, true)) {
            throw ValidationException::withMessages([
                'hostname' => 'Hostname bentrok dengan host sistem.',
            ]);
        }

        $existing = TenantDomain::query()->hostname($hostname)->where('status', '!=', TenantDomain::STATUS_REMOVED)->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'hostname' => 'Hostname sudah dipakai tenant lain.',
            ]);
        }

    }

    private function normalizeHostname(string $hostname): string
    {
        return strtolower(trim($hostname));
    }

    private function inferHostnameType(string $hostname): string
    {
        return substr_count($hostname, '.') <= 1 ? 'apex' : 'subdomain';
    }

    private function recordEvent(TenantDomain $domain, string $event, ?int $actorUserId, string $message, array $payload): void
    {
        TenantDomainEvent::query()->create([
            'tenant_domain_id' => $domain->id,
            'tenant_id' => $domain->tenant_id,
            'event' => $event,
            'actor_user_id' => $actorUserId,
            'actor_scope' => $actorUserId ? 'user' : 'system',
            'message' => $message,
            'payload' => $payload,
        ]);
    }
}
