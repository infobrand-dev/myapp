<?php

namespace App\Modules\Purchases\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Purchases\Actions\CancelDraftPurchaseAction;
use App\Modules\Purchases\Actions\CreateDraftPurchaseAction;
use App\Modules\Purchases\Actions\FinalizePurchaseAction;
use App\Modules\Purchases\Actions\ReceivePurchaseGoodsAction;
use App\Modules\Purchases\Actions\UpdateDraftPurchaseAction;
use App\Modules\Purchases\Actions\VoidPurchaseAction;
use App\Modules\Purchases\Http\Requests\CancelDraftPurchaseRequest;
use App\Modules\Purchases\Http\Requests\FinalizePurchaseRequest;
use App\Modules\Purchases\Http\Requests\ReceiveGoodsRequest;
use App\Modules\Purchases\Http\Requests\StoreDraftPurchaseRequest;
use App\Modules\Purchases\Http\Requests\UpdateDraftPurchaseRequest;
use App\Modules\Purchases\Http\Requests\VoidPurchaseRequest;
use App\Modules\Purchases\Models\Purchase;
use App\Modules\Purchases\Repositories\PurchaseRepository;
use App\Modules\Purchases\Services\PurchaseLookupService;
use App\Support\CurrencySettingsResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    private $repository;
    private $lookupService;
    private $createDraftPurchase;
    private $updateDraftPurchase;
    private $finalizePurchase;
    private $receivePurchaseGoods;
    private $cancelDraftPurchase;
    private $voidPurchase;
    private $currencySettings;

    public function __construct(
        PurchaseRepository $repository,
        PurchaseLookupService $lookupService,
        CreateDraftPurchaseAction $createDraftPurchase,
        UpdateDraftPurchaseAction $updateDraftPurchase,
        FinalizePurchaseAction $finalizePurchase,
        ReceivePurchaseGoodsAction $receivePurchaseGoods,
        CancelDraftPurchaseAction $cancelDraftPurchase,
        VoidPurchaseAction $voidPurchase,
        CurrencySettingsResolver $currencySettings
    ) {
        $this->repository = $repository;
        $this->lookupService = $lookupService;
        $this->createDraftPurchase = $createDraftPurchase;
        $this->updateDraftPurchase = $updateDraftPurchase;
        $this->finalizePurchase = $finalizePurchase;
        $this->receivePurchaseGoods = $receivePurchaseGoods;
        $this->cancelDraftPurchase = $cancelDraftPurchase;
        $this->voidPurchase = $voidPurchase;
        $this->currencySettings = $currencySettings;
    }

    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'contact_id', 'status', 'payment_status', 'date_from', 'date_to']);
        if ($request->user() && $request->user()->can('purchases.view_all')) {
            $filters['scope'] = 'all';
        } else {
            $filters['scope'] = 'own';
            $filters['user_id'] = $request->user() ? $request->user()->id : null;
        }

        return view('purchases::index', [
            'purchases' => $this->repository->paginateForIndex($filters),
            'filters' => $filters,
            'suppliers' => $this->lookupService->suppliers(),
            'statusOptions' => $this->lookupService->statusOptions(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
            'dependencies' => $this->lookupService->dependencyMap(),
        ]);
    }

    public function create(): View
    {
        return view('purchases::create', $this->formViewData(new Purchase([
            'status' => Purchase::STATUS_DRAFT,
            'payment_status' => Purchase::PAYMENT_UNPAID,
            'purchase_date' => now(),
            'currency_code' => $this->currencySettings->defaultCurrency(),
        ])));
    }

    public function store(StoreDraftPurchaseRequest $request): RedirectResponse
    {
        $purchase = $this->createDraftPurchase->execute($request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Draft pembelian dibuat.');
    }

    public function show(Purchase $purchase): View
    {
        $this->authorizeView($purchase);

        return view('purchases::show', [
            'purchase' => $this->repository->findForDetail($purchase),
            'statusOptions' => $this->lookupService->statusOptions(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
        ]);
    }

    public function edit(Purchase $purchase): View
    {
        abort_unless($purchase->isDraft(), 404);
        $this->authorizeView($purchase);

        return view('purchases::edit', $this->formViewData($this->repository->findForEdit($purchase)));
    }

    public function update(UpdateDraftPurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeView($purchase);
        $purchase = $this->updateDraftPurchase->execute($purchase, $request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Draft diperbarui.');
    }

    public function finalize(FinalizePurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeView($purchase);
        $purchase = $this->finalizePurchase->execute($purchase, $request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Pembelian difinalisasi.');
    }

    public function receive(Purchase $purchase): View
    {
        abort_unless($purchase->isConfirmedLike(), 404);
        $this->authorizeView($purchase);

        return view('purchases::receive', [
            'purchase' => $this->repository->findForDetail($purchase),
            'inventoryLocations' => $this->lookupService->inventoryLocations(),
        ]);
    }

    public function storeReceipt(ReceiveGoodsRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeView($purchase);
        $purchase = $this->receivePurchaseGoods->execute($purchase, $request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Penerimaan barang diposting.');
    }

    public function cancel(CancelDraftPurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeView($purchase);
        $purchase = $this->cancelDraftPurchase->execute($purchase, $request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Draft dibatalkan.');
    }

    public function void(VoidPurchaseRequest $request, Purchase $purchase): RedirectResponse
    {
        $this->authorizeView($purchase);
        $purchase = $this->voidPurchase->execute($purchase, $request->validated(), $request->user());

        return redirect()->route('purchases.show', $purchase)->with('status', 'Pembelian di-void.');
    }

    public function print(Purchase $purchase): View
    {
        $this->authorizeView($purchase);
        return view('purchases::print', [
            'purchase' => $this->repository->findForDetail($purchase),
        ]);
    }

    private function formViewData(Purchase $purchase): array
    {
        return [
            'purchase' => $purchase->loadMissing('items'),
            'suppliers' => $this->lookupService->suppliers(),
            'purchasables' => $this->lookupService->purchasables(),
            'paymentStatusOptions' => $this->lookupService->paymentStatusOptions(),
        ];
    }

    private function authorizeView(Purchase $purchase): void
    {
        $user = request()->user();
        if (!$user) {
            abort(403);
        }

        if ($user->can('purchases.view_all')) {
            return;
        }

        abort_unless(
            $user->can('purchases.view_own') && (int) $purchase->created_by === (int) $user->id,
            403
        );
    }
}
