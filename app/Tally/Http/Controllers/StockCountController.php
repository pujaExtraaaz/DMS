<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Inventory\StockCountService;
use Tally\Models\Product;
use Tally\Models\StockCount;

class StockCountController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Stock counts',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::stock-counts.index', [
            'company' => $company,
            'counts' => StockCount::query()
                ->with('godown')
                ->where('company_id', $company->id)
                ->latest('count_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::stock-counts.form', [
            'company' => $company,
            'count' => new StockCount(['count_date' => now()->toDateString(), 'status' => 'draft']),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, StockCountService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $count = $service->save($company, $context->branch(), $this->validated($request, $company->id));

        return redirect()->to(tally_route('books.tally.stock-counts.show', $count))
            ->with('status', 'Stock count saved.');
    }

    public function show(WorkspaceContext $context, StockCount $stockCount): View
    {
        abort_unless($context->company() && $stockCount->company_id === $context->company()->id, 404);
        $stockCount->load(['lines.product', 'godown']);

        return view('tally::stock-counts.show', [
            'company' => $context->company(),
            'count' => $stockCount,
        ]);
    }

    public function edit(WorkspaceContext $context, StockCount $stockCount): View
    {
        abort_unless($context->company() && $stockCount->company_id === $context->company()->id, 404);
        abort_unless($stockCount->isDraft(), 404);
        $company = $context->company();
        $stockCount->load('lines');

        return view('tally::stock-counts.form', [
            'company' => $company,
            'count' => $stockCount,
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, StockCount $stockCount, StockCountService $service): RedirectResponse
    {
        abort_unless($context->company() && $stockCount->company_id === $context->company()->id, 404);

        $count = $service->save(
            $context->company(),
            $context->branch() ?? $stockCount->branch,
            $this->validated($request, $context->company()->id),
            $stockCount
        );

        return redirect()->to(tally_route('books.tally.stock-counts.show', $count))
            ->with('status', 'Stock count updated.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        return $request->validate([
            'count_date' => ['required', 'date'],
            'godown_id' => ['nullable', 'integer', Rule::exists('acct_godowns', 'id')->where('company_id', $companyId)],
            'narration' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'cancelled'])],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.product_id' => ['required', 'integer', Rule::exists('acct_products', 'id')->where('company_id', $companyId)],
            'lines.*.counted_qty' => ['required', 'numeric'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
        ]);
    }
}
