<?php

namespace App\Http\Middleware;

use App\Support\TenantPlanManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless(
            app(TenantPlanManager::class)->hasFeature($feature),
            403,
            'Fitur ini tidak tersedia di plan tenant saat ini.'
        );

        return $next($request);
    }
}
