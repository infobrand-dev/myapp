<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class EnsureInstalled
{
    public function handle(Request $request, Closure $next)
    {
        $installed = $this->isInstalled();

        if (!$installed && !$request->is('install') && !$request->is('install/*')) {
            return redirect()->route('install.index');
        }

        if ($installed && ($request->is('install') || $request->is('install/*'))) {
            return redirect('/dashboard');
        }

        return $next($request);
    }

    private function isInstalled(): bool
    {
        if (file_exists(storage_path('app/installed.lock'))) {
            return true;
        }

        $envValue = (string) env('APP_INSTALLED', 'false');
        if (in_array(strtolower($envValue), ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        // Backward compatibility for existing deployments before installer lock existed.
        // If app key exists and core tables are already present, treat as installed.
        $appKey = trim((string) config('app.key', ''));
        if ($appKey === '') {
            return false;
        }

        try {
            return Schema::hasTable('migrations') && Schema::hasTable('users');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
