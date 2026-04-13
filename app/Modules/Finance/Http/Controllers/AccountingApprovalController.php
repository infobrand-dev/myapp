<?php

namespace App\Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
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
            ->with(['requester', 'approver'])
            ->when(!empty($filters['status']), fn ($query) => $query->where('status', $filters['status']))
            ->when(!empty($filters['module']), fn ($query) => $query->where('module', $filters['module']))
            ->when(BranchContext::currentId(), fn ($query) => $query->where(function ($inner) {
                $inner->whereNull('branch_id')->orWhere('branch_id', BranchContext::currentId());
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('finance::governance.approvals', [
            'approvals' => $approvals,
            'filters' => $filters,
        ]);
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
