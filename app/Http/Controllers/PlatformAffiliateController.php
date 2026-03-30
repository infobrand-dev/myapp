<?php

namespace App\Http\Controllers;

use App\Models\PlatformAffiliate;
use App\Services\PlatformAffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAffiliateController extends Controller
{
    public function index(PlatformAffiliateService $affiliateService): View
    {
        $ready = $affiliateService->tablesReady();

        $affiliates = $ready
            ? PlatformAffiliate::query()
                ->withCount([
                    'referrals',
                    'referrals as converted_referrals_count' => fn ($query) => $query->where('status', 'converted'),
                ])
                ->withSum('referrals as converted_sales_amount', 'order_amount')
                ->withSum('referrals as converted_commission_amount', 'commission_amount')
                ->orderByDesc('id')
                ->get()
            : collect();

        return view('platform.affiliates.index', [
            'ready' => $ready,
            'affiliates' => $affiliates,
        ]);
    }

    public function show(PlatformAffiliate $affiliate, PlatformAffiliateService $affiliateService): View
    {
        abort_unless($affiliateService->tablesReady(), 404);

        $affiliate->load([
            'referrals' => fn ($query) => $query
                ->with(['tenant:id,name,slug', 'order.plan:id,name,code'])
                ->latest()
                ->limit(50),
        ]);

        return view('platform.affiliates.show', [
            'affiliate' => $affiliate,
            'referralLink' => $affiliateService->referralLink($affiliate),
        ]);
    }

    public function store(Request $request, PlatformAffiliateService $affiliateService): RedirectResponse
    {
        if (!$affiliateService->tablesReady()) {
            return back()->with('error', 'Table affiliate platform belum tersedia. Jalankan migration terlebih dahulu.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:platform_affiliates,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'commission_type' => ['required', 'string', 'in:percentage,flat'],
            'commission_rate' => ['required', 'numeric', 'min:0'],
            'status' => ['required', 'string', 'in:active,inactive'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $affiliateService->createAffiliate($data);

        return redirect()
            ->route('platform.affiliates.index')
            ->with('status', 'Affiliate berhasil dibuat. Email referral telah diantrikan.');
    }
}
