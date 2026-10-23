<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Http\Requests\ProductRequest;
use Tally\Models\Company;
use Tally\Models\Product;
use Tally\Models\ProductGroup;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request, WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Products',
                'message' => 'Select or create a company before managing products.',
            ]);
        }

        $products = $this->filtered($request, $company)
            ->with(['productGroup', 'primaryUnit'])
            ->paginate(25)
            ->withQueryString();

        return view('tally::products.index', [
            'company' => $company,
            'products' => $products,
            'groups' => ProductGroup::flatten($company->productGroups()->orderBy('name')->get()),
            'filters' => $request->only(['q', 'product_group_id', 'status']),
        ]);
    }

    public function create(WorkspaceContext $context): View|RedirectResponse
    {
        $company = $this->company($context);

        if (! $company instanceof Company) {
            return $company;
        }

        return view('tally::products.create', [
            'company' => $company,
            'product' => new Product([
                'purchase_rate' => '0.00',
                'sales_rate' => '0.00',
                'opening_quantity' => '0',
                'opening_rate' => '0.00',
                'opening_value' => '0.00',
                'minimum_stock' => '0',
                'reorder_level' => '0',
                'is_active' => true,
            ]),
            'groups' => ProductGroup::flatten($company->productGroups()->where('is_active', true)->get()),
            'units' => $company->units()->where('is_active', true)->orderBy('name')->get(),
            'taxRates' => $company->taxRates()->where('is_active', true)->orderBy('name')->get(),
            'hsnSacs' => $company->hsnSacs()->where('is_active', true)->orderBy('code')->get(),
            'godowns' => $company->godowns()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(ProductRequest $request, WorkspaceContext $context): RedirectResponse
    {
        $data = $request->validated();
        $extras = $request->input('extra_barcodes', '');
        $openings = $data['openings'] ?? [];
        unset($data['extra_barcodes'], $data['openings']);
        $product = $context->company()->products()->create($data);
        $this->syncGodownOpenings($product, $openings);
        app(\Tally\Inventory\BarcodeDirectory::class)->sync($product, preg_split('/\r\n|\r|\n/', (string) $extras) ?: []);
        $this->syncOpeningStock($context, $request->user());

        return redirect()
            ->route('books.tally.products.show', $product)
            ->with('status', 'Product created.');
    }

    public function show(WorkspaceContext $context, Product $product): View
    {
        $product->load(['productGroup', 'primaryUnit', 'alternateUnit', 'hsnSac', 'taxRate', 'barcodes', 'godownOpenings.godown']);

        return view('tally::products.show', [
            'company' => $context->company(),
            'product' => $product,
        ]);
    }

    public function edit(WorkspaceContext $context, Product $product): View
    {
        $company = $context->company();

        $product->load('godownOpenings');

        return view('tally::products.edit', [
            'company' => $company,
            'product' => $product,
            'godowns' => $company->godowns()->orderBy('name')->get(),
            'groups' => ProductGroup::flatten(
                $company->productGroups()
                    ->where(function ($query) use ($product) {
                        $query->where('is_active', true)->orWhere('id', $product->product_group_id);
                    })
                    ->get()
            ),
            'units' => $company->units()
                ->where(function ($query) use ($product) {
                    $query->where('is_active', true)->orWhere('id', $product->primary_unit_id);

                    if ($product->alternate_unit_id) {
                        $query->orWhere('id', $product->alternate_unit_id);
                    }
                })
                ->orderBy('name')
                ->get(),
            'taxRates' => $company->taxRates()
                ->where(function ($query) use ($product) {
                    $query->where('is_active', true);

                    if ($product->tax_rate_id) {
                        $query->orWhere('id', $product->tax_rate_id);
                    }
                })
                ->orderBy('name')
                ->get(),
            'hsnSacs' => $company->hsnSacs()
                ->where(function ($query) use ($product) {
                    $query->where('is_active', true);

                    if ($product->hsn_sac_id) {
                        $query->orWhere('id', $product->hsn_sac_id);
                    }
                })
                ->orderBy('code')
                ->get(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();
        $openings = $data['openings'] ?? [];
        unset($data['extra_barcodes'], $data['openings']);
        $product->update($data);
        $this->syncGodownOpenings($product, $openings);
        app(\Tally\Inventory\BarcodeDirectory::class)->sync($product, preg_split('/\r\n|\r|\n/', (string) $request->input('extra_barcodes', '')) ?: []);
        $this->syncOpeningStock(app(\Tally\Context\WorkspaceContext::class), $request->user());

        return redirect()
            ->route('books.tally.products.show', $product)
            ->with('status', 'Product updated.');
    }

    private function syncOpeningStock(\Tally\Context\WorkspaceContext $context, ?\App\Models\User $user): void
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();

        if (! $company || ! $branch || ! $year || ! $user) {
            return;
        }

        app(\Tally\Inventory\InventoryPosting::class)->syncOpening($company, $branch, $year, $user);
    }

    public function updateActivation(Request $request, Product $product): RedirectResponse
    {
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $product->update([
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('status', $product->is_active ? 'Product activated.' : 'Product deactivated.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        if (! $product->canBeDeleted()) {
            return app(\Tally\Audit\AuditLogger::class)->deny($product, 'This product is used by stock, invoices, or manufacturing and cannot be deleted.');
        }

        $product->delete();

        return redirect()
            ->route('books.tally.products.index')
            ->with('status', 'Product deleted.');
    }

    private function filtered(Request $request, Company $company)
    {
        $query = $company->products();
        $search = trim($request->string('q')->toString());

        if ($search !== '') {
            $like = '%'.addcslashes($search, '%_\\').'%';
            $query->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('code', 'like', $like)
                    ->orWhere('barcode', 'like', $like)
                    ->orWhereHas('barcodes', fn ($query) => $query->where('barcode', 'like', $like));
            });
        }

        if ($request->filled('product_group_id')) {
            $query->where('product_group_id', $request->integer('product_group_id'));
        }

        if ($request->input('status') === 'active') {
            $query->where('is_active', true);
        } elseif ($request->input('status') === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('name');
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function syncGodownOpenings(Product $product, array $rows): void
    {
        $placing = false;

        foreach ($rows as $row) {
            if (is_array($row) && ($row['godown_id'] ?? '') !== '' && (float) ($row['quantity'] ?? 0) > 0) {
                $placing = true;
            }
        }

        $context = app(\Tally\Context\WorkspaceContext::class);
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();

        if ($placing && (! $company || ! $branch || ! $year)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'openings' => 'Select a branch and financial year before placing opening stock in a godown.',
            ]);
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($product, $rows, $placing, $company, $branch, $year): void {
            $existing = $product->godownOpenings()->get();

            if ($existing->isNotEmpty()) {
                \Tally\Models\StockMovement::query()
                    ->where('reference_type', (new \Tally\Models\StockGodownOpening)->getMorphClass())
                    ->whereIn('reference_id', $existing->modelKeys())
                    ->delete();
            }

            $product->godownOpenings()->delete();

            foreach ($rows as $row) {
                if (! is_array($row) || ($row['godown_id'] ?? '') === '' || $row['godown_id'] === null) {
                    continue;
                }

                $quantity = (string) ($row['quantity'] ?? '0');

                if ((float) $quantity <= 0) {
                    continue;
                }

                $rate = (string) ($row['rate'] ?? '0');
                $product->godownOpenings()->create([
                    'company_id' => $product->company_id,
                    'godown_id' => (int) $row['godown_id'],
                    'batch_name' => trim((string) ($row['batch_name'] ?? '')),
                    'quantity' => $quantity,
                    'rate' => $rate === '' ? '0' : $rate,
                    'value' => number_format(((float) $quantity) * (float) ($rate === '' ? 0 : $rate), 2, '.', ''),
                ]);
            }

            if (! $placing || ! $company || ! $branch || ! $year) {
                return;
            }

            $movements = app(\Tally\Inventory\StockMovementService::class);
            $date = $year->start_date->toDateString();

            foreach ($product->godownOpenings()->with('godown')->get() as $opening) {
                if (! $opening->godown) {
                    continue;
                }

                $scaled = \Tally\Inventory\Quantity::scale((string) $opening->quantity, 4);

                if ($scaled <= 0) {
                    continue;
                }

                $movements->move(
                    $company,
                    $branch,
                    $year,
                    $product,
                    $opening->godown,
                    $date,
                    $scaled,
                    (string) $opening->rate,
                    \Tally\Inventory\StockMovementType::Adjustment,
                    $opening,
                );
            }
        });
    }

    private function company(WorkspaceContext $context): Company|RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return redirect()->route('books.tally.products.index');
        }

        return $company;
    }
}
