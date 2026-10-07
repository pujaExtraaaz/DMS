@extends('layouts.dms')
@section('title', 'Stock Ledger')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Stock Ledger" />

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search product, type, warehouse, notes..."
            :reset-url="route('reports.stock-ledger')"
        >
            <x-slot name="filters">
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>From:</span>
                    <input
                        type="date"
                        name="date_from"
                        value="{{ $dateFrom }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
                <div class="flex items-center gap-1.5 text-xs text-slate-500">
                    <span>To:</span>
                    <input
                        type="date"
                        name="date_to"
                        value="{{ $dateTo }}"
                        data-dynamic-filter
                        class="rounded-lg border-slate-300 py-1.5 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>

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
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="created_at" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">When</x-ui.sortable-th>
                        <x-ui.sortable-th column="product" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Product</x-ui.sortable-th>
                        <x-ui.sortable-th column="type" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Type</x-ui.sortable-th>
                        <x-ui.sortable-th column="quantity" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'" align="right">Qty</x-ui.sortable-th>
                        <x-ui.sortable-th column="balance_after" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'" align="right">Balance</x-ui.sortable-th>
                        <x-ui.sortable-th column="notes" :current-sort="$sort ?? 'created_at'" :current-direction="$direction ?? 'desc'">Notes</x-ui.sortable-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 text-slate-600 whitespace-nowrap">{{ $m->created_at?->format('d M Y H:i') }}</td>
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $m->product?->name }} <span class="text-xs text-slate-400">({{ $m->uom?->code }})</span></td>
                            <td class="px-3 py-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ in_array($m->type, ['in', 'purchase', 'adjustment_in', 'transfer_in']) ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-800' }}">
                                    {{ $m->type }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-right font-medium {{ (float)$m->quantity >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">{{ number_format((float)$m->quantity, 4) }}</td>
                            <td class="px-3 py-2 text-right font-semibold text-slate-800">{{ number_format((float)$m->balance_after, 4) }}</td>
                            <td class="px-3 py-2 text-slate-600 max-w-xs truncate" title="{{ $m->notes }}">{{ $m->notes ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No movements found" description="Try adjusting your search or date filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $movements->links() }}</div>
    </x-ui.card>
</div>
@endsection
