<?php

namespace App\Support\Commerce;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

class AffiliateAttribution
{
    private const SESSION_KEY = 'tenant_affiliate_referral_code';

    public function capture(Request $request, string $code): void
    {
        $normalized = trim(mb_strtolower($code));
        if ($normalized === '') {
            return;
        }

        $request->session()->put(self::SESSION_KEY, $normalized);
        Cookie::queue(
            $this->cookieName($request),
            $normalized,
            max(1, (int) config('services.tenant_affiliate.cookie_days', 30)) * 24 * 60
        );
    }

    public function currentCode(Request $request): ?string
    {
        $session = trim((string) $request->session()->get(self::SESSION_KEY, ''));
        if ($session !== '') {
            return mb_strtolower($session);
        }

        $cookie = trim((string) $request->cookie($this->cookieName($request), ''));

        return $cookie !== '' ? mb_strtolower($cookie) : null;
    }

    public function clear(Request $request): void
    {
        $request->session()->forget(self::SESSION_KEY);
        Cookie::queue(Cookie::forget($this->cookieName($request)));
    }

    private function cookieName(Request $request): string
    {
        $tenantId = (int) ($request->attributes->get('tenant_id') ?? 0);

        return 'tenant_affiliate_referral_code_' . ($tenantId > 0 ? $tenantId : 'guest');
    }
}
