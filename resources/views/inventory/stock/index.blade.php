@extends('layouts.dms')
@section('title', 'Stock Levels')
@section('content')
<x-ui.page-header title="Stock Levels" description="Current inventory and recent movements." />

<x-ui.card>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end mb-6">
        <x-ui.select name="product_id" label="Product" placeholder="All">
            <option value=""></option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="brand_id" label="Brand" placeholder="All">
            <option value=""></option>
            @foreach($brands as $b)
                <option value="{{ $b->id }}" @selected(request('brand_id')==$b->id)>{{ $b->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="category_id" label="Category" placeholder="All">
            <option value=""></option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}" @selected(request('category_id')==$c->id)>{{ $c->name }}</option>
            @endforeach
        </x-ui.select>
        <x-ui.select name="warehouse_id" label="Warehouse" placeholder="All">
            <option value=""></option>
            @foreach($warehouses as $w)
                <option value="{{ $w->id }}" @selected(request('warehouse_id')==$w->id)>{{ $w->name }}</option>
            @endforeach
        </x-ui.select>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color / Variant</label>
            <input type="text" name="color_variant" value="{{ request('color_variant') }}" list="stock-colors" class="block w-full rounded-lg border-gray-300 text-sm">
            <datalist id="stock-colors">
                @foreach($colors as $c)<option value="{{ $c }}"></option>@endforeach
            </datalist>
        </div>
        <div class="flex items-center gap-2">
            <label class="flex items-center gap-2 text-sm">
                <input type="checkbox" name="low_stock" value="1" @checked(request()->boolean('low_stock')) class="rounded border-gray-300 text-indigo-600">
                Low stock only
            </label>
            <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Filter</x-ui.button>
            @if(request()->hasAny(['product_id','brand_id','category_id','warehouse_id','color_variant','low_stock']))
                <a href="{{ route('inventory.stock.index') }}" class="text-xs text-slate-500 hover:text-slate-700">Reset</a>
            @endif
        </div>
    </form>

    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Serial No.</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Brand</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Variant</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Warehouse</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Unit</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Quantity</th>
            </tr>
        </x-slot>
        @forelse($stockLevels as $level)
            <tr>
                <td class="px-6 py-4 text-sm font-mono text-xs">{{ $level->product->serial_no }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->brand?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->product->color_variant ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->warehouse?->name ?? '—' }}</td>
                <td class="px-6 py-4 text-sm">{{ $level->uom->code }}</td>
                <td class="px-6 py-4 text-sm text-right">
                    <x-ui.badge :variant="$level->quantity < 10 ? 'danger' : 'success'">{{ $level->quantity }}</x-ui.badge>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="px-6 py-8"><x-ui.empty-state title="No stock records" /></td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $stockLevels->links() }}</div>
</x-ui.card>

<x-ui.card class="mt-6" title="Recent Movements">
    <x-ui.table>
        <x-slot name="head">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th>
                <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th>
                <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th>
            </tr>
        </x-slot>
        @foreach($movements as $m)
            <tr>
                <td class="px-6 py-4 text-sm">{{ $m->product->name }}</td>
                <td class="px-6 py-4 text-sm">{{ $m->type }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ $m->quantity }}</td>
                <td class="px-6 py-4 text-sm text-right">{{ $m->balance_after }}</td>
            </tr>
        @endforeach
    </x-ui.table>
</x-ui.card>
@endsection
