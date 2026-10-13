<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Accounting\VoucherEngine;
use Tally\Context\WorkspaceContext;
use Tally\Controls\ApprovalService;
use Tally\Models\ApprovalRequest;
use Tally\Models\Voucher;

class ApprovalController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Approvals',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::approvals.index', [
            'company' => $company,
            'requests' => ApprovalRequest::query()
                ->with(['policy', 'requester'])
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, ApprovalService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'document_type' => ['required', Rule::in(['voucher', 'payment_batch', 'purchase_order', 'expense_claim'])],
            'document_id' => ['required', 'integer'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $service->request($company, $request->user(), $data);

        return back()->with('status', 'Approval requested.');
    }

    public function show(WorkspaceContext $context, ApprovalRequest $approvalRequest): View
    {
        abort_unless($context->company() && $approvalRequest->company_id === $context->company()->id, 404);
        $approvalRequest->load(['policy', 'requester', 'decider']);

        return view('tally::approvals.show', [
            'company' => $context->company(),
            'request' => $approvalRequest,
        ]);
    }

    public function approve(Request $request, WorkspaceContext $context, ApprovalRequest $approvalRequest, ApprovalService $service, VoucherEngine $vouchers): RedirectResponse
    {
        abort_unless($context->company() && $approvalRequest->company_id === $context->company()->id, 404);
        $service->approve($approvalRequest, $request->user(), $request->input('remarks'));

        if ($approvalRequest->document_type === 'voucher') {
            $voucher = Voucher::query()
                ->where('company_id', $context->company()->id)
                ->whereKey($approvalRequest->document_id)
                ->first();

            if ($voucher) {
                $voucher->update([
                    'approval_status' => 'approved',
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ]);

                if ($voucher->isDraft()) {
                    $vouchers->post($voucher->fresh());
                }
            }
        }

        return back()->with('status', 'Approved.');
    }

    public function reject(Request $request, WorkspaceContext $context, ApprovalRequest $approvalRequest, ApprovalService $service): RedirectResponse
    {
        abort_unless($context->company() && $approvalRequest->company_id === $context->company()->id, 404);
        $service->reject($approvalRequest, $request->user(), $request->input('remarks'));

        if ($approvalRequest->document_type === 'voucher') {
            Voucher::query()
                ->where('company_id', $context->company()->id)
                ->whereKey($approvalRequest->document_id)
                ->update(['approval_status' => 'rejected']);
        }

        return back()->with('status', 'Rejected.');
    }
}
