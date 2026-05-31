<?php

namespace App\Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\WalletPayoutRequest;
use App\Modules\Wallet\Services\TenantWalletService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PlatformWalletPayoutController extends Controller
{
    public function __construct(
        private readonly TenantWalletService $wallets,
    ) {
    }

    public function index(): View
    {
        return view('wallet::platform-payouts', [
            'requests' => WalletPayoutRequest::query()->with('account')->latest('id')->get(),
        ]);
    }

    public function approve(WalletPayoutRequest $payoutRequest): RedirectResponse
    {
        $this->wallets->approve($payoutRequest, auth()->id());

        return back()->with('status', 'Payout request di-approve.');
    }

    public function markPaid(WalletPayoutRequest $payoutRequest): RedirectResponse
    {
        $this->wallets->markPaid($payoutRequest, auth()->id());

        return back()->with('status', 'Payout request ditandai paid.');
    }

    public function reject(WalletPayoutRequest $payoutRequest): RedirectResponse
    {
        $this->wallets->reject($payoutRequest, auth()->id());

        return back()->with('status', 'Payout request ditolak.');
    }
}
