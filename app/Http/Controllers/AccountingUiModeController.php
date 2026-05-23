<?php

namespace App\Http\Controllers;

use App\Support\AccountingUiMode;
use App\Support\FeatureMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccountingUiModeController extends Controller
{
    public function store(Request $request, AccountingUiMode $uiMode): RedirectResponse
    {
        $mode = $uiMode->set($request, (string) $request->input('mode', AccountingUiMode::STANDARD));

        return back()->with('status', $mode === FeatureMode::ADVANCED
            ? 'Mode advanced diaktifkan.'
            : 'Mode standar diaktifkan.');
    }
}
