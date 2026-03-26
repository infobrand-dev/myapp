<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class SetLocale
{
    public function handle(Request $request, Closure $next): mixed
    {
        $supported = ['en', 'id'];

        $locale = null;

        // 1. Authenticated user preference
        if ($request->user() && in_array($request->user()->locale, $supported)) {
            $locale = $request->user()->locale;
        }

        // 2. Session fallback
        if (!$locale && in_array(session('locale'), $supported)) {
            $locale = session('locale');
        }

        // 3. App default
        if (!$locale) {
            $locale = config('app.locale', 'en');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
