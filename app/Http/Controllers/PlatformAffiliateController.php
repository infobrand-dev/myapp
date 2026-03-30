<?php

namespace App\Http\Controllers;

use App\Models\PlatformAffiliate;
use App\Models\PlatformAffiliateReferral;
use App\Services\PlatformAffiliateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformAffiliateController extends Controller
{
    public function payouts(PlatformAffiliateService $affiliateService, Request $request): View
    {
        $ready = $affiliateService->tablesReady();
        $status = (string) $request->query('status', 'pending');

        $payouts = $ready
            ? PlatformAffiliateReferral::query()
                ->with(['affiliate:id,name,email,slug', 'tenant:id,name,slug', 'order.plan:id,name,code'])
                ->when($status !== 'all', fn ($query) => $query->where('payout_status', $status))
                ->orderByRaw("CASE payout_status WHEN 'pending' THEN 0 WHEN 'approved' THEN 1 WHEN 'paid' THEN 2 WHEN 'rejected' THEN 3 ELSE 4 END")
                ->latest('converted_at')
                ->latest('id')
                ->paginate(50)
                ->withQueryString()
            : collect();

        $summary = $ready ? [
            'pending_count' => PlatformAffiliateReferral::query()->where('payout_status', 'pending')->count(),
            'approved_count' => PlatformAffiliateReferral::query()->where('payout_status', 'approved')->count(),
            'paid_count' => PlatformAffiliateReferral::query()->where('payout_status', 'paid')->count(),
            'pending_amount' => (float) PlatformAffiliateReferral::query()->where('payout_status', 'pending')->sum('commission_amount'),
            'approved_amount' => (float) PlatformAffiliateReferral::query()->where('payout_status', 'approved')->sum('commission_amount'),
            'paid_amount' => (float) PlatformAffiliateReferral::query()->where('payout_status', 'paid')->sum('commission_amount'),
        ] : [];

        return view('platform.affiliates.payouts', [
            'ready' => $ready,
            'payouts' => $payouts,
            'status' => $status,
            'summary' => $summary,
        ]);
    }

    public function index(PlatformAffiliateService $affiliateService): View
    {
        $ready = $affiliateService->tablesReady();

        $affiliates = $ready
            ? PlatformAffiliate::query()
                ->withCount([
                    'referrals',
                    'referrals as converted_referrals_count' => fn ($query) => $query->where('status', 'converted'),
                    'referrals as pending_payouts_count' => fn ($query) => $query->where('payout_status', 'pending'),
                ])
                ->withSum('referrals as converted_sales_amount', 'order_amount')
                ->withSum('referrals as converted_commission_amount', 'commission_amount')
                ->orderByDesc('id')
                ->get()
            : collect();

        $stats = [
            'total_affiliates' => $affiliates->count(),
            'active_affiliates' => $affiliates->where('status', 'active')->count(),
            'total_clicks' => (int) $affiliates->sum('click_count'),
            'total_converted' => (int) $affiliates->sum('converted_referrals_count'),
            'total_pending_payouts' => (int) $affiliates->sum('pending_payouts_count'),
        ];

        return view('platform.affiliates.index', [
            'ready' => $ready,
            'affiliates' => $affiliates,
            'stats' => $stats,
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
            'stats' => [
                'clicks' => (int) $affiliate->click_count,
                'registered' => (int) $affiliate->referrals->count(),
                'converted' => (int) $affiliate->referrals->where('status', 'converted')->count(),
                'pending_payouts' => (int) $affiliate->referrals->where('payout_status', 'pending')->count(),
            ],
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

    public function updatePayoutStatus(Request $request, PlatformAffiliate $affiliate, PlatformAffiliateReferral $referral): RedirectResponse
    {
        abort_unless($referral->platform_affiliate_id === $affiliate->id, 404);

        $data = $request->validate([
            'payout_status' => ['required', 'string', 'in:approved,paid,rejected,pending'],
            'payout_reference' => ['nullable', 'string', 'max:100'],
            'payout_notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $attributes = [
            'payout_status' => $data['payout_status'],
            'payout_reference' => $data['payout_reference'] ?: null,
            'payout_notes' => $data['payout_notes'] ?: null,
        ];

        if ($data['payout_status'] === 'approved') {
            $attributes['approved_at'] = $referral->approved_at ?: now();
        }

        if ($data['payout_status'] === 'paid') {
            $attributes['approved_at'] = $referral->approved_at ?: now();
            $attributes['paid_at'] = now();
        }

        if (in_array($data['payout_status'], ['pending', 'rejected'], true)) {
            $attributes['paid_at'] = null;
        }

        if ($data['payout_status'] === 'pending') {
            $attributes['approved_at'] = null;
        }

        if ($data['payout_status'] === 'rejected') {
            $attributes['approved_at'] = null;
            $attributes['paid_at'] = null;
        }

        $referral->forceFill($attributes)->save();

        return back()->with('status', 'Status payout affiliate berhasil diperbarui.');
    }
}
