<?php

namespace App\Http\Controllers;

use App\Services\PlatformAffiliateService;
use Illuminate\View\View;

class AffiliateProgramController extends Controller
{
    public function __invoke(PlatformAffiliateService $affiliateService): View
    {
        return view('affiliate-program', [
            'policy' => $affiliateService->publicPolicy(),
        ]);
    }
}
