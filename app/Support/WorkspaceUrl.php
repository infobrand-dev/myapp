<?php

namespace App\Support;

use App\Services\TenantHostResolver;
use Illuminate\Http\Request;

class WorkspaceUrl
{
    public function forCurrentUser(Request $request, bool $appendDashboard = true): string
    {
        $user = $request->user();
        $path = $appendDashboard ? '/dashboard' : '/login';
        $scheme = $this->scheme($request);

        if ($user && (int) $user->tenant_id === 1 && $user->hasRole('Super-admin')) {
            return $scheme . '://' . SaasHost::platformHost($request) . $path;
        }

        if ($user && $user->tenant) {
            $host = app(TenantHostResolver::class)->canonicalHostForTenant($request, $user->tenant);

            return $scheme . '://' . $host . $path;
        }

        return route('workspace.finder');
    }

    public function loginForTenant(Request $request, string $slug): string
    {
        return $this->scheme($request) . '://' . SaasHost::tenantHost($request, $slug) . '/login';
    }

    private function scheme(Request $request): string
    {
        $appUrl = (string) config('app.url');

        return parse_url($appUrl, PHP_URL_SCHEME) ?: ($request->isSecure() ? 'https' : 'http');
    }
}
