<?php

namespace App\Http\Middleware;

use App\Support\FeatureMode;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireAdvancedMode
{
    public function handle(Request $request, Closure $next, string $mode = FeatureMode::ADVANCED, string $productLine = 'accounting'): Response
    {
        $resolved = app(FeatureMode::class)->current($request, $productLine);

        if ($mode === FeatureMode::ADVANCED && $resolved !== FeatureMode::ADVANCED) {
            abort(403, 'Fitur ini hanya tersedia pada Advanced Mode.');
        }

        return $next($request);
    }
}
