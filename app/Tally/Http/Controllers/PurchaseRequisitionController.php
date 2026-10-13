<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\PurchaseRequisition;
use Tally\Purchasing\PurchaseRequisitionService;

class PurchaseRequisitionController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Purchase requisitions', 'message' => 'Select a company first.']);
        }

        return view('tally::purchase-requisitions.index', [
            'company' => $company,
            'requisitions' => PurchaseRequisition::query()
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('requisition_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::purchase-requisitions.form', [
            'company' => $company,
            'requisition' => new PurchaseRequisition([
                'requisition_date' => now()->toDateString(),
                'status' => 'draft',
                'priority' => 'normal',
            ]),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PurchaseRequisitionService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'requisition_date' => ['required', 'date'],
            'priority' => ['nullable', Rule::in(['low', 'normal', 'high', 'urgent'])],
            'status' => ['nullable', Rule::in(['draft', 'submitted', 'approved', 'rejected', 'cancelled'])],
            'department_id' => ['nullable', 'integer'],
            'narration' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.quantity' => ['required'],
            'lines.*.unit' => ['nullable', 'string', 'max:40'],
            'lines.*.required_by' => ['nullable', 'date'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ]);

        $requisition = $service->save($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.purchase-requisitions.show', $requisition))
            ->with('status', 'Purchase requisition saved.');
    }

    public function show(WorkspaceContext $context, PurchaseRequisition $purchaseRequisition): View
    {
        abort_unless($context->company() && $purchaseRequisition->company_id === $context->company()->id, 404);
        $purchaseRequisition->load('lines');

        return view('tally::purchase-requisitions.show', [
            'company' => $context->company(),
            'requisition' => $purchaseRequisition,
        ]);
    }

    public function convertToRfq(
        Request $request,
        WorkspaceContext $context,
        PurchaseRequisition $purchaseRequisition,
        PurchaseRequisitionService $service,
    ): RedirectResponse {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year && $purchaseRequisition->company_id === $company->id, 404);

        $rfq = $service->convertToRfq($company, $branch, $year, $request->user(), $purchaseRequisition);

        return redirect()->to(tally_route('books.tally.rfqs.show', $rfq))
            ->with('status', 'RFQ created from purchase requisition.');
    }
}
