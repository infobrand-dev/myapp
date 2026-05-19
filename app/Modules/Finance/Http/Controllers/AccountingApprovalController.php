<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApprovalMatrixRule;
use App\Models\ApprovalRequest;
use App\Modules\Finance\Http\Requests\StoreApprovalMatrixRuleRequest;
use App\Support\ApprovalMatrixService;
use App\Support\BranchContext;
use App\Support\CompanyContext;
use App\Support\SensitiveActionApprovalService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingApprovalController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'module']);

        $approvals = ApprovalRequest::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->with(['requester', 'approver', 'decisions.approver'])
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['module']), fn ($query) => $query->where('module', $filters['module']))
            ->when(BranchContext::currentId(), fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $rules = ApprovalMatrixRule::query()
            ->where('tenant_id', TenantContext::currentId())
            ->where('company_id', CompanyContext::currentId())
            ->when(BranchContext::currentId(), fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
            }))
            ->latest('id')
            ->get();

        return view('finance::governance.approvals', [
            'approvals' => $approvals,
            'filters' => $filters,
            'rules' => $rules,
            'branches' => \App\Models\Branch::query()
                ->where('tenant_id', TenantContext::currentId())
                ->where('company_id', CompanyContext::currentId())
                ->active()
                ->orderBy('name')
                ->get(),
            'moduleActionOptions' => app(ApprovalMatrixService::class)->moduleActionOptions(),
        ]);
    }

    public function storeRule(StoreApprovalMatrixRuleRequest $request): RedirectResponse
    {
        ApprovalMatrixRule::query()->create([
            'tenant_id' => TenantContext::currentId(),
            'company_id' => CompanyContext::currentId(),
            'branch_id' => $request->integer('branch_id') ?: null,
            'module' => $request->input('module'),
            'action' => $request->input('action'),
            'min_amount' => round((float) $request->input('min_amount', 0), 2),
            'required_approvals' => (int) $request->input('required_approvals'),
            'maker_checker_required' => $request->boolean('maker_checker_required'),
            'max_backdate_days' => $request->filled('max_backdate_days') ? (int) $request->input('max_backdate_days') : null,
            'is_active' => $request->boolean('is_active', true),
            'notes' => $request->input('notes'),
            'created_by' => optional($request->user())->id,
            'updated_by' => optional($request->user())->id,
        ]);

        return back()->with('status', 'Approval matrix rule berhasil ditambahkan.');
    }

    public function destroyRule(ApprovalMatrixRule $approvalMatrixRule): RedirectResponse
    {
        $approvalMatrixRule->delete();

        return back()->with('status', 'Approval matrix rule berhasil dihapus.');
    }

    public function approve(ApprovalRequest $approvalRequest, Request $request, SensitiveActionApprovalService $service): RedirectResponse
    {
        $service->approve($approvalRequest, $request->user(), $request->input('decision_notes'));

        return back()->with('status', "Approval request #{$approvalRequest->id} disetujui.");
    }

    public function reject(ApprovalRequest $approvalRequest, Request $request, SensitiveActionApprovalService $service): RedirectResponse
    {
        $service->reject($approvalRequest, $request->user(), $request->input('decision_notes'));

        return back()->with('status', "Approval request #{$approvalRequest->id} ditolak.");
    }
}
