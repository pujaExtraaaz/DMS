<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $items = Product::query()
            ->with('baseUom')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('sku', 'like', '%'.$request->search.'%');
            }))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('masters.products.index', compact('items'));
    }

    public function create(): View
    {
        return view('masters.products.form', [
            'item' => new Product,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Product::create($data);

        return $this->flashSuccess('Product created successfully.', 'masters.products.index');
    }

    public function show(Product $product): RedirectResponse
    {
        return redirect()->route('masters.products.edit', $product);
    }

    public function edit(Product $product): View
    {
        return view('masters.products.form', [
            'item' => $product,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Product $product): RedirectResponse
    {
        $product->update($this->validated($request, $product));

        return $this->flashSuccess('Product updated successfully.', 'masters.products.index');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return $this->flashSuccess('Product deleted successfully.', 'masters.products.index');
    }

    protected function validated(Request $request, ?Product $product = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'sku' => 'required|string|max:50|unique:products,sku'.($product ? ','.$product->id : ''),
            'description' => 'nullable|string',
            'base_uom_id' => 'required|exists:uoms,id',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'boolean',
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['tax_rate'] = $data['tax_rate'] ?? 0;

        return $data;
    }
}
