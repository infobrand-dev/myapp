<?php

namespace App\Support\Commerce;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AffiliateAttribution
{
    private const SESSION_PREFIX = 'tenant_affiliate_referral_code';

    public function store(Request $request, string $code, ?int $cookieDays = null): void
    {
        $normalized = $this->normalizeCode($code);
        if ($normalized === null) {
            return;
        }

        $request->session()->put($this->sessionKey($request), $normalized);
        Cookie::queue(
            $this->cookieName($request),
            $normalized,
            max(1, $cookieDays ?? (int) config('services.tenant_affiliate.cookie_days', 30)) * 24 * 60
        );
    }

    public function capture(Request $request, string $code, ?int $cookieDays = null): void
    {
        $this->store($request, $code, $cookieDays);
    }

    public function currentCode(?Request $request = null): ?string
    {
        $request ??= request();
        if (!$request instanceof Request) {
            return null;
        }

        $session = $this->normalizeCode($request->session()->get($this->sessionKey($request), ''));
        if ($session !== null) {
            return $session;
        }

        return $this->normalizeCode($request->cookie($this->cookieName($request), ''));
    }

    public function clear(?Request $request = null): void
    {
        $request ??= request();
        if (!$request instanceof Request) {
            return;
        }

        $request->session()->forget($this->sessionKey($request));
        Cookie::queue(Cookie::forget($this->cookieName($request)));
    }

    private function sessionKey(Request $request): string
    {
        return self::SESSION_PREFIX . '_' . $this->tenantScope($request);
    }

    private function cookieName(Request $request): string
    {
        return 'tenant_affiliate_referral_code_' . $this->tenantScope($request);
    }

    private function tenantScope(Request $request): string
    {
        $tenantId = (int) ($request->attributes->get('tenant_id') ?? 0);

        return $tenantId > 0 ? (string) $tenantId : 'guest';
    }

    private function normalizeCode(mixed $code): ?string
    {
        $normalized = trim((string) $code);

        return $normalized === '' ? null : mb_strtoupper($normalized);
    }
}
