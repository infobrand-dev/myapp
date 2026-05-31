<?php

namespace App\Modules\Wallet\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Wallet\Models\WalletPayoutRequest;
use App\Modules\Wallet\Services\TenantWalletService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function __construct(
        private readonly TenantWalletService $wallets,
    ) {
    }

    public function index(): View
    {
        $account = $this->wallets->account();

        return view('wallet::index', [
            'account' => $account,
            'balances' => $this->wallets->balances($account),
            'entries' => $account->entries()->latest('id')->take(25)->get(),
            'payoutRequests' => WalletPayoutRequest::query()
                ->where('tenant_id', TenantContext::currentId())
                ->latest('id')
                ->get(),
        ]);
    }

    public function storePayoutRequest(): RedirectResponse
    {
        $this->wallets->requestPayout(request()->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'bank_name' => ['required', 'string', 'max:255'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_number' => ['required', 'string', 'max:80'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]), auth()->id());

        return redirect()->route('wallet.index')->with('status', 'Payout request berhasil dibuat.');
    }
}
