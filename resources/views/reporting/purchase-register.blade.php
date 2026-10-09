@extends('layouts.dms')
@section('title', 'Purchase Register')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Purchase Register" />

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search purchase no, supplier, status, notes..."
            :reset-url="route('reports.purchase-register')"
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
                    name="supplier_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Supplier: All</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" @selected(request('supplier_id') == $s->id)>{{ $s->name }}</option>
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
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="purchase_no" :current-sort="$sort ?? 'purchase_date'" :current-direction="$direction ?? 'desc'">Purchase No</x-ui.sortable-th>
                        <x-ui.sortable-th column="purchase_date" :current-sort="$sort ?? 'purchase_date'" :current-direction="$direction ?? 'desc'">Date</x-ui.sortable-th>
                        <x-ui.sortable-th column="supplier" :current-sort="$sort ?? 'purchase_date'" :current-direction="$direction ?? 'desc'">Supplier</x-ui.sortable-th>
                        <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? 'purchase_date'" :current-direction="$direction ?? 'desc'" align="right">Total</x-ui.sortable-th>
                        <x-ui.sortable-th column="status" :current-sort="$sort ?? 'purchase_date'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($purchases as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2 font-medium text-slate-900">{{ $p->purchase_no }}</td>
                            <td class="px-3 py-2 text-slate-600">{{ $p->purchase_date?->format('d M Y') }}</td>
                            <td class="px-3 py-2 text-slate-700">{{ $p->supplierParty?->name ?? $p->supplier_name }}</td>
                            <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format((float)$p->grand_total, 2) }}</td>
                            <td class="px-3 py-2">
                                <x-ui.badge :variant="in_array($p->status, ['received', 'completed', 'paid']) ? 'emerald' : ($p->status === 'cancelled' ? 'rose' : 'amber')">
                                    {{ ucfirst($p->status) }}
                                </x-ui.badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-3 py-8 text-center text-slate-500">
                                <x-ui.empty-state title="No purchases found" description="Try adjusting your search or date filters." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">{{ $purchases->links() }}</div>
    </x-ui.card>
</div>
@endsection
