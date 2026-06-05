<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\DomainHandoffService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantDomainHandoffController extends Controller
{
    public function __invoke(Request $request, string $token, DomainHandoffService $handoff): RedirectResponse
    {
        $payload = $handoff->consume($token);

        Auth::loginUsingId($payload['user_id'], false);
        $request->session()->regenerate();
        $request->session()->put('tenant_id', $payload['tenant_id']);
        TenantContext::setCurrentId($payload['tenant_id']);

        return redirect()->to($payload['target_path'] ?: '/dashboard');
    }
}
