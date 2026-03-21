<?php

namespace App\Modules\PointOfSale\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\PointOfSale\Http\Requests\StoreReceiptReprintRequest;
use App\Modules\PointOfSale\Models\PosReceiptReprintLog;
use App\Modules\PointOfSale\Services\PosCashSessionService;
use App\Modules\Sales\Models\Sale;
use App\Support\CompanyContext;
use App\Support\DocumentSettingsResolver;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReceiptController extends Controller
{

    private $cashSessionService;
    private $documentSettings;

    public function __construct(PosCashSessionService $cashSessionService, DocumentSettingsResolver $documentSettings)
    {
        $this->cashSessionService = $cashSessionService;
        $this->documentSettings = $documentSettings;
    }

    public function show(Request $request, Sale $sale): View
    {
        $this->authorizeReceiptAccess($request->user(), $sale, false);

        return $this->renderReceipt($sale, false, false);
    }

    public function print(Request $request, Sale $sale): View
    {
        $this->authorizeReceiptAccess($request->user(), $sale, false);

        return $this->renderReceipt($sale, true, false);
    }

    public function reprint(StoreReceiptReprintRequest $request, Sale $sale): View
    {
        $user = $request->user();
        $this->authorizeReceiptAccess($user, $sale, true);

        $reprintLog = DB::transaction(function () use ($request, $sale, $user) {
            $sale = Sale::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->with('cashSession')
                ->lockForUpdate()
                ->findOrFail($sale->id);

            $this->assertSaleCanRender($sale);

            $reprintSequence = (int) PosReceiptReprintLog::query()
                ->where('sale_id', $sale->id)
                ->lockForUpdate()
                ->count() + 1;

            $log = PosReceiptReprintLog::query()->create([
                'tenant_id' => TenantContext::currentId(),
                'company_id' => CompanyContext::currentId(),
                'branch_id' => $sale->branch_id,
                'sale_id' => $sale->id,
                'pos_cash_session_id' => $sale->pos_cash_session_id,
                'reprint_sequence' => $reprintSequence,
                'reason' => $request->validated('reason'),
                'requested_by' => $user ? $user->id : null,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'meta' => [
                    'sale_number' => $sale->sale_number,
                    'source' => $sale->source,
                ],
            ]);

            $sale->statusHistories()->create([
                'tenant_id' => TenantContext::currentId(),
                'from_status' => $sale->status,
                'to_status' => $sale->status,
                'event' => 'receipt_reprinted',
                'reason' => $request->validated('reason'),
                'actor_id' => $user ? $user->id : null,
                'meta' => [
                    'reprint_log_id' => $log->id,
                    'reprint_sequence' => $reprintSequence,
                    'branch_id' => $sale->branch_id,
                    'pos_cash_session_id' => $sale->pos_cash_session_id,
                ],
            ]);

            return $log;
        });

        return $this->renderReceipt($sale->fresh(), true, true, $reprintLog);
    }

    private function renderReceipt(
        Sale $sale,
        bool $printMode,
        bool $isReprint,
        mixed $reprintLog = null
    ): View {
        $sale->load([
            'items',
            'contact',
            'creator',
            'finalizer',
            'paymentAllocations.payment.method',
        ]);

        if ($reprintLog instanceof PosReceiptReprintLog) {
            $reprintLog->loadMissing('requester');
        }

        $cashPaid = (float) $sale->paymentAllocations
            ->filter(function ($allocation) {
                return $allocation->payment
                    && $allocation->payment->method
                    && $allocation->payment->method->code === 'cash';
            })
            ->sum('amount');

        $changeAmount = round(max(0, $cashPaid - (float) $sale->grand_total), 2);

        return view('pos::receipt', [
            'sale' => $sale,
            'printMode' => $printMode,
            'isReprint' => $isReprint,
            'reprintLog' => $reprintLog,
            'changeAmount' => $changeAmount,
            'documentSettings' => $this->documentSettings->forScope($sale->tenant_id, $sale->company_id, $sale->branch_id),
        ]);
    }

    private function authorizeReceiptAccess(?User $user, Sale $sale, bool $isReprint): void
    {
        if (!$user) {
            throw new HttpException(403, 'Unauthenticated.');
        }

        $sale->loadMissing('cashSession');
        $this->assertSaleCanRender($sale);

        if ($user->can('pos.manage-all-shifts')) {
            return;
        }

        $ownsSale = (int) ($sale->finalized_by ?? 0) === (int) $user->id
            || (int) ($sale->created_by ?? 0) === (int) $user->id
            || (int) optional($sale->cashSession)->cashier_user_id === (int) $user->id;

        if (!$ownsSale) {
            throw new HttpException(403, 'Anda tidak memiliki akses ke receipt transaksi ini.');
        }

        $activeSession = $this->cashSessionService->activeSessionFor($user);
        if (!$activeSession || (int) ($sale->branch_id ?? 0) !== (int) ($activeSession->branch_id ?? 0)) {
            throw new HttpException(403, 'Receipt hanya dapat diakses dari shift aktif branch yang sama.');
        }

        if ($isReprint && !$user->can('pos.reprint-receipt')) {
            throw new HttpException(403, 'Anda tidak memiliki akses reprint receipt.');
        }
    }

    private function assertSaleCanRender(Sale $sale): void
    {
        if ($sale->source !== Sale::SOURCE_POS || !$sale->isFinalized()) {
            throw new HttpException(404, 'Receipt POS tidak tersedia untuk transaksi ini.');
        }
    }
}
