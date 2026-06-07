<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

class ApiResponseFactory
{
    public function success(array $data = [], array $meta = [], int $status = 200): JsonResponse
    {
        return response()->json([
            'ok' => true,
            'version' => config('platform-core.api.current_version', 'v1'),
            'data' => $data,
            'meta' => $meta,
            'error' => null,
        ], $status);
    }

    public function error(string $code, string $message, int $status = 422, array $details = []): JsonResponse
    {
        return response()->json([
            'ok' => false,
            'version' => config('platform-core.api.current_version', 'v1'),
            'data' => null,
            'meta' => [],
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
        ], $status);
    }

    public function paginationMeta(LengthAwarePaginator $paginator): array
    {
        return [
            'pagination' => [
                'type' => 'page',
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
