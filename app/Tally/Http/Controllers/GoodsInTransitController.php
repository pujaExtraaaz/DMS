<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Inventory\GoodsInTransitService;
use Tally\Models\GoodsInTransit;
use Tally\Models\Product;

class GoodsInTransitController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Goods in transit',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::goods-in-transit.index', [
            'company' => $company,
            'rows' => GoodsInTransit::query()
                ->with(['product', 'fromGodown', 'toGodown'])
                ->where('company_id', $company->id)
                ->latest('dispatch_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();
        abort_unless($company, 404);

        return view('tally::goods-in-transit.form', [
            'company' => $company,
            'record' => new GoodsInTransit([
                'dispatch_date' => now()->toDateString(),
                'status' => 'in_transit',
            ]),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
            'products' => Product::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, GoodsInTransitService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $record = $service->save($company, $this->validated($request, $company->id));

        return redirect()->to(tally_route('books.tally.goods-in-transit.show', $record))
            ->with('status', 'Goods in transit saved.');
    }

    public function show(WorkspaceContext $context, GoodsInTransit $goodsInTransit): View
    {
        abort_unless($context->company() && $goodsInTransit->company_id === $context->company()->id, 404);
        $goodsInTransit->load(['product', 'fromGodown', 'toGodown']);

        return view('tally::goods-in-transit.show', [
            'company' => $context->company(),
            'record' => $goodsInTransit,
        ]);
    }

    public function receive(Request $request, WorkspaceContext $context, GoodsInTransit $goodsInTransit, GoodsInTransitService $service): RedirectResponse
    {
        abort_unless($context->company() && $goodsInTransit->company_id === $context->company()->id, 404);

        $data = $request->validate([
            'received_date' => ['nullable', 'date'],
        ]);

        $service->receive($goodsInTransit, $data['received_date'] ?? null);

        return back()->with('status', 'Marked as received.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, int $companyId): array
    {
        return $request->validate([
            'product_id' => ['required', 'integer', Rule::exists('acct_products', 'id')->where('company_id', $companyId)],
            'from_godown_id' => ['nullable', 'integer', Rule::exists('acct_godowns', 'id')->where('company_id', $companyId)],
            'to_godown_id' => ['nullable', 'integer', Rule::exists('acct_godowns', 'id')->where('company_id', $companyId)],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'dispatch_date' => ['required', 'date'],
            'expected_receipt_date' => ['nullable', 'date', 'after_or_equal:dispatch_date'],
            'reference' => ['nullable', 'string', 'max:80'],
            'is_consignment' => ['nullable', 'boolean'],
        ]);
    }
}
