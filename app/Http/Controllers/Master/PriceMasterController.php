<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class PriceMasterController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = PriceMaster::query()
            ->with(['customerType', 'product', 'uom'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('customer_type_id', $request->customer_type_id));

        $this->applySearch($query, $request->input('search'), [], [
            'product' => ['name', 'sku'],
            'customerType' => ['name'],
        ]);

        $allowedSorts = [
            'rate' => 'rate',
            'product' => function ($q, $dir) {
                $q->join('products', 'price_masters.product_id', '=', 'products.id')
                  ->orderBy('products.name', $dir)
                  ->select('price_masters.*');
            },
            'customer_type' => function ($q, $dir) {
                $q->join('customer_types', 'price_masters.customer_type_id', '=', 'customer_types.id')
                  ->orderBy('customer_types.name', $dir)
                  ->select('price_masters.*');
            },
            'created_at' => 'price_masters.created_at',
        ];

        $sortData = $this->applySorting($query, $request, $allowedSorts, defaultSort: 'product', defaultDirection: 'asc');

        $items = $query->paginate(15)->withQueryString();

        return view('masters.price-masters.index', [
            'items' => $items,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
            'search' => $request->string('search'),
        ]);
    }

    public function create(): View
    {
        return view('masters.price-masters.form', array_merge(['item' => new PriceMaster], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        PriceMaster::create($this->validated($request));

        return $this->flashSuccess('Price created successfully.', 'masters.price-masters.index');
    }

    public function show(PriceMaster $priceMaster): RedirectResponse
    {
        return redirect()->route('masters.price-masters.edit', $priceMaster);
    }

    public function edit(PriceMaster $priceMaster): View
    {
        return view('masters.price-masters.form', array_merge(['item' => $priceMaster], $this->formData()));
    }

    public function update(Request $request, PriceMaster $priceMaster): RedirectResponse
    {
        $priceMaster->update($this->validated($request));

        return $this->flashSuccess('Price updated successfully.', 'masters.price-masters.index');
    }

    public function destroy(Request $request, PriceMaster $priceMaster): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('masters.manage') &&
            ! $user->can('masters.edit') &&
            ! $user->can('masters.delete') &&
            ! $user->can('price-master.manage') &&
            ! $user->can('price-master.edit') &&
            ! $user->can('price-master.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete price list records.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete price list records.');
        }

        $priceMaster->loadMissing(['customerType', 'product', 'uom']);

        // Multi-tenancy check (company boundary)
        if ($user && ! $user->hasAnyRole(['super-admin', 'client-admin']) && $user->company_id && $priceMaster->product?->company_id && (int) $priceMaster->product->company_id !== (int) $user->company_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: This price record belongs to another company.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: This price record belongs to another company.');
        }

        $productName = $priceMaster->product?->name ?? 'Product';
        $customerTypeName = $priceMaster->customerType?->name ?? 'Customer Type';
        $rateFormatted = '₹' . number_format((float) $priceMaster->rate, 2);

        try {
            DB::transaction(function () use ($priceMaster) {
                // Delete only this specific PriceMaster record
                $priceMaster->delete();
            });

            $message = "Price record for {$productName} ({$customerTypeName} - {$rateFormatted}) deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'masters.price-masters.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete price record: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError('Failed to delete price record: ' . $e->getMessage(), 'masters.price-masters.index')
                ->with('error', 'Failed to delete price record: ' . $e->getMessage());
        }
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'customer_type_id' => 'required|exists:customer_types,id',
            'product_id' => 'required|exists:products,id',
            'uom_id' => 'required|exists:uoms,id',
            'rate' => 'required|numeric|min:0',
            'min_qty' => 'nullable|numeric|min:0',
        ]);
    }

    protected function formData(): array
    {
        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ];
    }
}
