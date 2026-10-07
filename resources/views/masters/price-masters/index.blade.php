@extends('layouts.dms')
@section('title', 'Price Master')
@section('content')
<div id="listing-container" data-dynamic-container>
    <x-ui.page-header title="Price Master">
        <x-slot name="actions">
            <x-ui.button variant="primary" :href="route('masters.price-masters.create')">+ Add Price</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search product, customer type..."
            :reset-url="route('masters.price-masters.index')"
        >
            <x-slot name="filters">
                <select
                    name="customer_type_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Customer Type: All</option>
                    @foreach($customerTypes as $ct)
                        <option value="{{ $ct->id }}" @selected(request('customer_type_id') == $ct->id)>{{ $ct->name }}</option>
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

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <x-ui.sortable-th column="customer_type" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Customer Type
                    </x-ui.sortable-th>
                    <x-ui.sortable-th column="product" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Product
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Unit</th>
                    <x-ui.sortable-th column="rate" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" align="right">
                        Rate
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Min Qty</th>
                    <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
                </tr>
            </x-slot>
            @forelse($items as $item)
                <tr class="hover:bg-slate-50/75 transition-colors">
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $item->customerType?->name ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ $item->product?->name ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $item->uom?->code ?: '—' }}</td>
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-900">₹{{ number_format($item->rate, 2) }}</td>
                    <td class="px-6 py-4 text-sm text-right text-slate-600">{{ $item->min_qty ?? '—' }}</td>
                    <td class="px-6 py-4 text-right">
                        <x-ui.button variant="ghost" size="sm" :href="route('masters.price-masters.edit', $item)">Edit</x-ui.button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="p-0">
                        <x-ui.empty-state
                            title="No prices found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('masters.price-masters.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Reset Filters
                                </a>
                            </x-slot>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>
        <div class="mt-4">{{ $items->links() }}</div>
    </x-ui.card>
</div>
@endsection
