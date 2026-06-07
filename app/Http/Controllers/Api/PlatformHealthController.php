<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\Api\ApiResponseFactory;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;

class PlatformHealthController extends Controller
{
    public function __invoke(ApiResponseFactory $responses)
    {
        return $responses->success([
            'status' => 'ok',
            'tenant_id' => TenantContext::currentId(),
            'database' => DB::connection()->getName(),
            'time' => now()->toIso8601String(),
        ]);
    }
}
