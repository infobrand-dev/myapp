<?php

namespace App\Http\Middleware;

use App\Support\SaasHost;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApexHost
{
    public function handle(Request $request, Closure $next): Response
    {
        if (SaasHost::isApexHost($request)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(404);
        }

        return redirect()->to($request->getSchemeAndHttpHost() . '/');
    }
}
