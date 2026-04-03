<?php

namespace App\Http\Middleware;

use App\Support\TenantPlanManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantFeature
{
    public function handle(Request $request, Closure $next, string ...$features): Response
    {
        $features = array_values(array_filter(array_map('trim', $features), fn ($feature) => $feature !== ''));

        abort_unless(
            !empty($features) && collect($features)->contains(
                fn (string $feature) => app(TenantPlanManager::class)->hasFeature($feature)
            ),
            403,
            'Fitur ini tidak tersedia di plan tenant saat ini.'
        );

        return $next($request);
    }
}
