<?php

namespace App\Http\Middleware;

use App\Support\BranchContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveBranchContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldBypassBranchContext($request)) {
            return $next($request);
        }

        $branchId = null;

        if ($request->hasSession()) {
            $session = $request->session();
            $hasExplicitBranchSelection = $session->has('branch_all')
                || $session->has('branch_id')
                || $session->has('branch_slug');

            if (!$hasExplicitBranchSelection) {
                $session->put('branch_all', true);
            }

            $forceAllBranches = (bool) $session->get('branch_all');
            $branchId = $forceAllBranches ? null : BranchContext::resolveIdFromRequest($request);
        } else {
            $branchId = BranchContext::resolveIdFromRequest($request);
        }

        BranchContext::setCurrentId($branchId);
        $request->attributes->set('branch_id', $branchId);

        if ($request->hasSession()) {
            $request->session()->put('branch_id', $branchId);
            $request->session()->put('branch_slug', BranchContext::currentBranch()?->slug);

            if ($branchId !== null) {
                $request->session()->forget('branch_all');
            }
        }

        try {
            return $next($request);
        } finally {
            BranchContext::forget();
        }
    }

    private function shouldBypassBranchContext(Request $request): bool
    {
        if ($request->is('install') || $request->is('install/*')) {
            return true;
        }

        if ($request->routeIs('health')
            || $request->routeIs('onboarding.create')
            || $request->routeIs('onboarding.store')
            || $request->routeIs('platform.billing.midtrans.webhook')
            || $request->routeIs('platform.invoices.public')
            || $request->routeIs('platform.invoices.public.midtrans.checkout')) {
            return true;
        }

        if (config('multitenancy.mode') !== 'saas') {
            return false;
        }

        return !$request->attributes->get('tenant_id') && !$request->user();
    }
}
