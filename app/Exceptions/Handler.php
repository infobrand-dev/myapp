<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * Inputs that are never flashed to the session on validation exceptions.
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
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            if (config('sentry.dsn')) {
                Integration::captureUnhandledException($e);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     * Provides user-friendly error pages and hides stack traces in production.
     */
    public function render($request, Throwable $e)
    {
        // Return JSON for API / AJAX requests
        if ($request->expectsJson()) {
            return $this->renderJsonException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Structured JSON error response — never leaks stack traces.
     */
    protected function renderJsonException(Request $request, Throwable $e): \Illuminate\Http\JsonResponse
    {
        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;

        $payload = [
            'message' => $this->isHttpException($e)
                ? $e->getMessage()
                : 'An unexpected error occurred. Please try again.',
        ];

        // Include validation errors when present
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            $payload['message'] = 'The given data was invalid.';
            $payload['errors']  = $e->errors();
            $status = 422;
        }

        // In local/testing environments, include debug info for developers
        if (config('app.debug')) {
            $payload['debug'] = [
                'exception' => get_class($e),
                'message'   => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
            ];
        }

        return response()->json($payload, $status);
    }
}
