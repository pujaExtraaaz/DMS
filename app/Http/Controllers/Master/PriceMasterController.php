<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\CustomerType;
use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PriceMasterController extends Controller
{
    public function index(Request $request): View
    {
        $items = PriceMaster::query()
            ->with(['customerType', 'product', 'uom'])
            ->when($request->filled('product_id'), fn ($q) => $q->where('product_id', $request->product_id))
            ->when($request->filled('customer_type_id'), fn ($q) => $q->where('customer_type_id', $request->customer_type_id))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('masters.price-masters.index', [
            'items' => $items,
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'customerTypes' => CustomerType::where('is_active', true)->orderBy('name')->get(),
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

    public function destroy(PriceMaster $priceMaster): RedirectResponse
    {
        $priceMaster->delete();

        return $this->flashSuccess('Price deleted successfully.', 'masters.price-masters.index');
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
