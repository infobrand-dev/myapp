<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (TokenMismatchException $e, $request) {
            $context = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'session_id' => $request->session()->getId(),
                'has_session_cookie' => $request->hasCookie(config('session.cookie')),
                'session_cookie' => config('session.cookie'),
                'session_domain' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'session_same_site' => config('session.same_site'),
                'app_url' => config('app.url'),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
            ];

            Log::error('CSRF token mismatch', $context);

            try {
                $line = '[' . now()->toDateTimeString() . '] CSRF token mismatch ' . json_encode($context) . PHP_EOL;
                file_put_contents(storage_path('logs/csrf-debug.log'), $line, FILE_APPEND);
            } catch (\Throwable $writeError) {
                // Ignore fallback write errors in exception handler.
            }

            if (config('app.debug')) {
                return response()->json([
                    'error' => 'CSRF token mismatch (419)',
                    'hint' => 'Cek cookie/session domain/https',
                ], 419);
            }

            return response('Page Expired (419)', 419);
        });

        $this->renderable(function (Throwable $e, $request) {
            $status = null;
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
            }

            if ($status !== 419) {
                return null;
            }

            $context = [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'host' => $request->getHost(),
                'scheme' => $request->getScheme(),
                'session_id' => $request->hasSession() ? $request->session()->getId() : null,
                'has_session_cookie' => $request->hasCookie(config('session.cookie')),
                'session_cookie' => config('session.cookie'),
                'session_domain' => config('session.domain'),
                'session_secure' => config('session.secure'),
                'session_same_site' => config('session.same_site'),
                'app_url' => config('app.url'),
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ];

            try {
                $line = '[' . now()->toDateTimeString() . '] HTTP 419 fallback ' . json_encode($context) . PHP_EOL;
                file_put_contents(storage_path('logs/csrf-debug.log'), $line, FILE_APPEND);
            } catch (\Throwable $writeError) {
                // Ignore fallback write errors in exception handler.
            }

            return null;
        });
    }
}
