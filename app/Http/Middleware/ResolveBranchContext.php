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
        if ($request->is('install') || $request->is('install/*')) {
            return $next($request);
        }

        $branchId = BranchContext::resolveIdFromRequest($request);

        BranchContext::setCurrentId($branchId);
        $request->attributes->set('branch_id', $branchId);

        if ($request->hasSession()) {
            $request->session()->put('branch_id', $branchId);
            $request->session()->put('branch_slug', BranchContext::currentBranch()?->slug);
        }

        try {
            return $next($request);
        } finally {
            BranchContext::forget();
        }
    }
}
