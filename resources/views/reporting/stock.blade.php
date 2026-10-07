@extends('layouts.dms')
@section('title', 'Stock Report')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Stock Report" description="Current stock quantities with brand / category / warehouse filters.">
        <x-slot name="actions">
            <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
            <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search product, SKU or serial..."
            :reset-url="route('reports.stock')"
        >
            <x-slot name="filters">
                <select
                    name="warehouse_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Warehouse: All</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(request('warehouse_id') == $w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>

                <select
                    name="brand_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Brand: All</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>

                <select
                    name="category_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Category: All</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(request('category_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>

                <select
                    name="product_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Product: All</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>

                <label class="flex items-center gap-1.5 text-xs text-slate-700">
                    <input
                        type="checkbox"
                        name="low_only"
                        value="1"
                        @checked(request('low_only'))
                        data-dynamic-filter
                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                    >
                    Low stock only
                </label>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="product" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Product</x-ui.sortable-th>
                        <x-ui.sortable-th column="brand" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Brand</x-ui.sortable-th>
                        <x-ui.sortable-th column="warehouse" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Warehouse</x-ui.sortable-th>
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">UOM</th>
                        <x-ui.sortable-th column="quantity" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'" align="right">Qty</x-ui.sortable-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stockLevels as $level)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $level->product->name }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $level->product?->brand?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $level->warehouse?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-slate-500">{{ $level->uom->code }}</td>
                            <td class="px-3 py-2 text-right font-medium {{ (float)$level->quantity < 10 ? 'text-amber-600 font-semibold' : 'text-slate-900' }}">{{ $level->quantity }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No stock records found" description="Try adjusting your search or filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
