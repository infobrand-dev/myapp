<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SessionDiagnosticController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        if (!$this->canAccess($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Diagnostic access denied. Set APP_DIAGNOSTIC_KEY in .env and pass ?key=...',
            ], 403);
        }

        $session = $request->session();
        $session->put('diag_seen_at', now()->toDateTimeString());
        $session->put('diag_counter', (int) $session->get('diag_counter', 0) + 1);

        $cookieName = (string) config('session.cookie');
        $queryKey = (string) $request->query('key', '');

        return response()->json([
            'ok' => true,
            'app_env' => config('app.env'),
            'app_url' => config('app.url'),
            'request' => [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'is_secure' => $request->isSecure(),
                'user_agent' => (string) $request->userAgent(),
                'ip' => $request->ip(),
            ],
            'session' => [
                'driver' => config('session.driver'),
                'cookie_name' => $cookieName,
                'cookie_domain' => config('session.domain'),
                'cookie_secure' => config('session.secure'),
                'same_site' => config('session.same_site'),
                'has_session_cookie_on_request' => $request->hasCookie($cookieName),
                'session_id' => $session->getId(),
                'counter' => $session->get('diag_counter'),
            ],
            'csrf' => [
                'token' => csrf_token(),
                'post_url' => route('debug.session.post', ['key' => $queryKey]),
            ],
            'next_step' => 'POST ke csrf.post_url dengan field _token dari csrf.token. Jika sukses, CSRF/session normal.',
        ]);
    }

    public function post(Request $request): JsonResponse
    {
        if (!$this->canAccess($request)) {
            return response()->json([
                'ok' => false,
                'message' => 'Diagnostic access denied.',
            ], 403);
        }

        $session = $request->session();
        $session->put('diag_post_seen_at', now()->toDateTimeString());
        $session->put('diag_post_counter', (int) $session->get('diag_post_counter', 0) + 1);

        $cookieName = (string) config('session.cookie');

        return response()->json([
            'ok' => true,
            'message' => 'POST CSRF berhasil.',
            'session' => [
                'cookie_name' => $cookieName,
                'has_session_cookie_on_request' => $request->hasCookie($cookieName),
                'session_id' => $session->getId(),
                'diag_post_counter' => $session->get('diag_post_counter'),
            ],
        ]);
    }

    private function canAccess(Request $request): bool
    {
        if (app()->environment(['local', 'testing'])) {
            return true;
        }

        $expected = trim((string) env('APP_DIAGNOSTIC_KEY', ''));
        if ($expected === '') {
            return false;
        }

        $provided = (string) $request->query('key', $request->header('X-Diagnostic-Key', ''));
        if ($provided === '') {
            return false;
        }

        return hash_equals($expected, $provided);
    }
}

