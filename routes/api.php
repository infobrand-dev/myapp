<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\GlobalSearchController;
use App\Http\Controllers\Api\PlatformHealthController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Broadcast auth for Echo (private channels)
Route::middleware('auth:sanctum')->post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
});

Route::prefix('v1')
    ->middleware(['auth:sanctum', 'throttle:tenant-api'])
    ->group(function () {
        Route::get('/platform/health', PlatformHealthController::class)->name('api.v1.platform.health');
        Route::get('/platform/search', GlobalSearchController::class)->name('api.v1.platform.search');
    });
