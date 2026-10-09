<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Models\PriceList;
use Tally\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PriceListController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Price lists', 'message' => 'Select a company before managing price lists.']);
        }

        return view('tally::price-lists.index', [
            'company' => $company,
            'lists' => PriceList::query()->withCount('lines')->where('company_id', $company->id)->orderBy('name')->get(),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Price list', 'message' => 'Select a company before managing price lists.']);
        }

        return view('tally::price-lists.form', [
            'company' => $company,
            'list' => new PriceList(['is_active' => true]),
            'products' => $company->products()->where('is_active', true)->orderBy('name')->get(),
            'lines' => [],
        ]);
    }

    public function store(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $data = $this->validateList($request, $company->id);
        $list = PriceList::query()->create([
            'company_id' => $company->id,
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $this->writeLines($list, $data['lines'], $company->id);

        return redirect()->route('books.tally.price-lists.index')->with('status', 'Price list saved.');
    }

    public function edit(WorkspaceContext $context, PriceList $priceList): View
    {
        $this->guard($context, $priceList);

        return view('tally::price-lists.form', [
            'company' => $context->company(),
            'list' => $priceList,
            'products' => $context->company()->products()->where('is_active', true)->orderBy('name')->get(),
            'lines' => $priceList->lines()->get(),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, PriceList $priceList): RedirectResponse
    {
        $this->guard($context, $priceList);
        $data = $this->validateList($request, $context->company()->id, $priceList->id);
        $priceList->update([
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);
        $priceList->lines()->delete();
        $this->writeLines($priceList, $data['lines'], $context->company()->id);

        return redirect()->route('books.tally.price-lists.index')->with('status', 'Price list saved.');
    }

    public function destroy(WorkspaceContext $context, PriceList $priceList): RedirectResponse
    {
        $this->guard($context, $priceList);
        $priceList->delete();

        return redirect()->route('books.tally.price-lists.index')->with('status', 'Price list deleted.');
    }

    /**
     * @return array{name: string, lines: list<array{product_id: int, rate: string}>}
     */
    private function validateList(Request $request, int $companyId, ?int $ignore = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120', Rule::unique('price_lists', 'name')->where('company_id', $companyId)->ignore($ignore)],
            'lines' => ['array'],
            'lines.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')->where('company_id', $companyId)],
            'lines.*.rate' => ['nullable', 'numeric', 'min:0'],
        ]);
        $lines = [];

        foreach ($data['lines'] ?? [] as $line) {
            if (empty($line['product_id']) || $line['rate'] === null || $line['rate'] === '') {
                continue;
            }

            $lines[(int) $line['product_id']] = [
                'product_id' => (int) $line['product_id'],
                'rate' => number_format((float) $line['rate'], 2, '.', ''),
            ];
        }

        return ['name' => $data['name'], 'lines' => array_values($lines)];
    }

    /**
     * @param  list<array{product_id: int, rate: string}>  $lines
     */
    private function writeLines(PriceList $list, array $lines, int $companyId): void
    {
        foreach ($lines as $line) {
            if (! Product::query()->where('company_id', $companyId)->whereKey($line['product_id'])->exists()) {
                continue;
            }

            $list->lines()->create($line);
        }
    }

    private function guard(WorkspaceContext $context, PriceList $priceList): void
    {
        abort_unless($context->company() && $priceList->company_id === $context->company()->id, 404);
    }
}
