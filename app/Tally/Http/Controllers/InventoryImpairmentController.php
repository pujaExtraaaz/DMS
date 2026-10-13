<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Assets\InventoryImpairmentService;
use Tally\Context\WorkspaceContext;
use Tally\Models\InventoryImpairment;
use Tally\Models\Product;

class InventoryImpairmentController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Inventory impairment',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::inventory-impairments.index', [
            'company' => $company,
            'products' => Product::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->limit(200)->get(),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
            'entries' => InventoryImpairment::query()
                ->with(['product', 'voucher'])
                ->where('company_id', $company->id)
                ->latest('entry_date')
                ->paginate(25),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, InventoryImpairmentService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'entry_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'product_id' => ['nullable', 'integer'],
            'expense_ledger_id' => ['required', 'integer'],
            'provision_ledger_id' => ['required', 'integer'],
            'reference' => ['nullable', 'string', 'max:80'],
            'narration' => ['nullable', 'string', 'max:500'],
        ]);

        $entry = $service->post($company, $branch, $year, $request->user(), $data);

        return back()->with('status', 'Impairment posted'.($entry->voucher ? ' as '.$entry->voucher->voucher_number : '').'.');
    }
}
