<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

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
        $validated = $request->validate([
            'customer_type_id' => 'required|exists:customer_types,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'nullable|exists:products,id',
            'lines.*.uom_id' => 'nullable|exists:uoms,id',
            'lines.*.rate' => 'nullable|numeric|min:0',
            'lines.*.rate_includes_gst' => 'nullable|boolean',
            'lines.*.min_qty' => 'nullable|numeric|min:0',
        ]);

        $lines = collect($validated['lines'] ?? [])
            ->filter(fn ($row) => filled($row['product_id'] ?? null)
                || filled($row['uom_id'] ?? null)
                || filled($row['rate'] ?? null)
                || filled($row['min_qty'] ?? null))
            ->values();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'lines' => 'Add at least one product with UOM and rate.',
            ]);
        }

        $errors = [];
        foreach ($lines as $index => $row) {
            if (! filled($row['product_id'] ?? null)) {
                $errors["lines.{$index}.product_id"] = 'Product is required.';
            }
            if (! filled($row['uom_id'] ?? null)) {
                $errors["lines.{$index}.uom_id"] = 'UOM is required.';
            }
            $rate = $row['rate'] ?? null;
            if ($rate === null || $rate === '') {
                $errors["lines.{$index}.rate"] = 'Rate is required.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        // Detect duplicates within the submitted form
        $seen = [];
        foreach ($lines as $index => $row) {
            $minQty = $row['min_qty'] ?? null;
            $key = implode('|', [
                (string) $validated['customer_type_id'],
                (string) $row['product_id'],
                (string) $row['uom_id'],
                $minQty === null || $minQty === '' ? '' : (string) $minQty,
            ]);
            if (isset($seen[$key])) {
                throw ValidationException::withMessages([
                    "lines.{$index}.product_id" => 'Duplicate product / UOM / min qty combination in this form.',
                ]);
            }
            $seen[$key] = true;
        }

        try {
            $products = Product::query()
                ->whereIn('id', $lines->pluck('product_id')->filter()->unique()->all())
                ->get()
                ->keyBy('id');

            DB::transaction(function () use ($validated, $lines, $products) {
                foreach ($lines as $row) {
                    $product = $products->get($row['product_id']);
                    PriceMaster::create(array_merge(
                        $this->pricedAttributes($row, (float) ($product->tax_rate ?? 0)),
                        [
                            'customer_type_id' => $validated['customer_type_id'],
                            'product_id' => $row['product_id'],
                            'uom_id' => $row['uom_id'],
                            'min_qty' => filled($row['min_qty'] ?? null) ? $row['min_qty'] : null,
                        ]
                    ));
                }
            });
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'lines' => 'One or more prices already exist for this customer type, product, UOM, and min qty.',
                ]);
            }
            throw $e;
        }

        $count = $lines->count();

        return $this->flashSuccess(
            $count === 1 ? 'Price created successfully.' : "{$count} prices created successfully.",
            'masters.price-masters.index'
        );
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
        $validated = $this->validated($request);
        $product = Product::query()->find($validated['product_id']);

        try {
            $priceMaster->update(array_merge(
                $validated,
                $this->pricedAttributes($validated, (float) ($product->tax_rate ?? 0))
            ));
        } catch (QueryException $e) {
            if ($this->isUniqueConstraintViolation($e)) {
                throw ValidationException::withMessages([
                    'product_id' => 'A price already exists for this customer type, product, UOM, and min qty.',
                ]);
            }
            throw $e;
        }

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
            'rate_includes_gst' => 'nullable|boolean',
            'min_qty' => 'nullable|numeric|min:0',
        ]);
    }

    /**
     * Keep the billed rate exclusive of GST. The entered amount is stored beside it.
     *
     * @param  array<string, mixed>  $input
     * @return array{rate: float, entered_rate: float, rate_includes_gst: bool}
     */
    protected function pricedAttributes(array $input, float $taxPercent): array
    {
        $includesGst = filter_var($input['rate_includes_gst'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $split = PriceMaster::splitRate((float) ($input['rate'] ?? 0), $includesGst, $taxPercent);

        return [
            'rate' => $split['exclusive'],
            'entered_rate' => $split['entered'],
            'rate_includes_gst' => $includesGst,
        ];
    }

    protected function formData(): array
    {
        return [
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(['id', 'name', 'sku', 'base_uom_id', 'tax_rate']),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ];
    }

    protected function isUniqueConstraintViolation(QueryException $e): bool
    {
        $message = $e->getMessage();

        return str_contains($message, 'price_master_unique')
            || str_contains($message, 'Duplicate entry')
            || str_contains($message, 'UNIQUE constraint failed')
            || (string) $e->getCode() === '23000';
    }
}
