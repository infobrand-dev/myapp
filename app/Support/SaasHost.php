<?php

namespace App\Support;

use Illuminate\Http\Request;

class SaasHost
{
    public static function isPlatformAdminHost(Request $request): bool
    {
        if (config('multitenancy.mode') !== 'saas') {
            return false;
        }

        return self::matchesSubdomain($request->getHost(), (string) config('multitenancy.platform_admin_subdomain', 'dash'));
    }

    public static function platformHost(Request $request): string
    {
        return self::hostForSlug($request, (string) config('multitenancy.platform_admin_subdomain', 'dash'));
    }

    public static function tenantHost(Request $request, string $slug): string
    {
        return self::hostForSlug($request, $slug);
    }

    /**
     * @return array<int, string>
     */
    public static function candidateRootDomains(): array
    {
        $domains = [
            self::normalizeDomain(parse_url((string) config('app.url'), PHP_URL_HOST)),
            self::normalizeDomain(config('multitenancy.saas_domain')),
        ];

        return array_values(array_unique(array_filter($domains)));
    }

    private static function hostForSlug(Request $request, string $slug): string
    {
        return $slug . '.' . self::preferredRootDomain($request);
    }

    private static function preferredRootDomain(Request $request): string
    {
        $host = self::normalizeDomain($request->getHost());

        foreach (self::candidateRootDomains() as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return $domain;
            }
        }

        return self::candidateRootDomains()[0] ?? 'localhost';
    }

    private static function matchesSubdomain(string $host, string $slug): bool
    {
        foreach (self::candidateRootDomains() as $domain) {
            if ($host === $slug . '.' . $domain) {
                return true;
            }
        }

        return false;
    }

    private static function normalizeDomain(mixed $value): ?string
    {
        $domain = trim(strtolower((string) $value));

        return $domain !== '' ? $domain : null;
    }
}
