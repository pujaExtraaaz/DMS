@extends('layouts.dms')
@section('title', 'Stock Report')
@section('content')
<x-ui.page-header title="Stock Report" description="Current stock quantities with brand / category / warehouse filters.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3 items-end">

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                Search
            </label>
            <input
                type="search"
                name="search"
                value="{{ request('search') }}"
                placeholder="Product, SKU or Serial No."
                class="w-full rounded-lg border-gray-300 text-sm"
            >
        </div>

        <x-ui.select name="warehouse_id" label="Warehouse" placeholder="All">
            <option value=""></option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>
                    {{ $w->name }}
                </option>
            @endforeach
        </x-ui.select>

        <x-ui.select name="brand_id" label="Brand" placeholder="All">
            <option value=""></option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>
                    {{ $b->name }}
                </option>
            @endforeach
        </x-ui.select>

        <x-ui.select name="category_id" label="Category" placeholder="All">
            <option value=""></option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>
                    {{ $c->name }}
                </option>
            @endforeach
        </x-ui.select>

        <x-ui.select name="product_id" label="Item" placeholder="All">
            <option value=""></option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>
                    {{ $p->name }}
                </option>
            @endforeach
        </x-ui.select>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                Sort By
            </label>
            <select
                name="sort"
                class="w-full rounded-lg border-gray-300 text-sm"
            >
                <option value="product" @selected(request('sort', 'product') === 'product')>
                    Product
                </option>
                <option value="serial_no" @selected(request('sort') === 'serial_no')>
                    Serial No.
                </option>
                <option value="quantity" @selected(request('sort') === 'quantity')>
                    Quantity
                </option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-sm font-medium text-slate-700">
                Order
            </label>
            <select
                name="direction"
                class="w-full rounded-lg border-gray-300 text-sm"
            >
                <option value="asc" @selected(request('direction', 'asc') === 'asc')>
                    ASC
                </option>
                <option value="desc" @selected(request('direction') === 'desc')>
                    DESC
                </option>
            </select>
        </div>

        <label class="flex items-center gap-2 text-sm">
            <input
                type="checkbox"
                name="low_only"
                value="1"
                @checked(request('low_only'))
                class="rounded border-gray-300"
            >
            Low stock only
        </label>

        <x-ui.button type="submit" variant="secondary">
            Filter
        </x-ui.button>
    </form>

    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Brand</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Warehouse</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
            </tr>
        </x-slot>
        @foreach($stockLevels as $level)
            <tr>
                <td class="px-6 py-4 text-sm">{{ $level->product->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product?->brand?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->warehouse?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->uom->code }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ $level->quantity }}</td>
            </tr>
        @endforeach
    </x-ui.table>
</x-ui.card>
@endsection
