<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\ApprovalPolicy;

class ApprovalPolicyController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Approval policies',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::approval-policies.index', [
            'company' => $company,
            'policies' => ApprovalPolicy::query()
                ->where('company_id', $company->id)
                ->orderBy('name')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'document_type' => ['required', Rule::in(['voucher', 'payment_batch', 'purchase_order', 'expense_claim'])],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'gte:min_amount'],
            'levels' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        ApprovalPolicy::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'document_type' => $data['document_type'],
            'min_amount' => $data['min_amount'] ?? 0,
            'max_amount' => $data['max_amount'] ?? null,
            'levels' => $data['levels'] ?? 1,
            'is_active' => true,
        ]);

        return back()->with('status', 'Approval policy created.');
    }
}
