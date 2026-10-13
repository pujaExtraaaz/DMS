<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\LandedCostVoucher;
use Tally\Purchasing\LandedCostService;

class LandedCostController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Landed costs', 'message' => 'Select a company first.']);
        }

        return view('tally::landed-costs.index', [
            'company' => $company,
            'vouchers' => LandedCostVoucher::query()
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('voucher_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::landed-costs.form', [
            'company' => $company,
            'voucher' => new LandedCostVoucher([
                'voucher_date' => now()->toDateString(),
                'status' => 'draft',
                'allocation_basis' => 'value',
            ]),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, LandedCostService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'voucher_date' => ['required', 'date'],
            'purchase_invoice_id' => ['nullable', 'integer'],
            'goods_receipt_id' => ['nullable', 'integer'],
            'freight' => ['nullable', 'numeric', 'min:0'],
            'duty' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'allocation_basis' => ['nullable', Rule::in(['value', 'qty'])],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'cancelled'])],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);

        $voucher = $service->save($company, $branch, $year, $data);

        return redirect()->to(tally_route('books.tally.landed-costs.show', $voucher))
            ->with('status', 'Landed cost voucher saved.');
    }

    public function show(WorkspaceContext $context, LandedCostVoucher $landedCost): View
    {
        abort_unless($context->company() && $landedCost->company_id === $context->company()->id, 404);
        $landedCost->load(['purchaseInvoice', 'goodsReceipt', 'allocations.product', 'voucher']);

        return view('tally::landed-costs.show', [
            'company' => $context->company(),
            'voucher' => $landedCost,
        ]);
    }

    public function post(Request $request, WorkspaceContext $context, LandedCostVoucher $landedCost, LandedCostService $service): RedirectResponse
    {
        abort_unless($context->company() && $landedCost->company_id === $context->company()->id, 404);

        $service->post($landedCost, $request->user());

        return redirect()->to(tally_route('books.tally.landed-costs.show', $landedCost))
            ->with('status', 'Landed cost posted.');
    }
}
