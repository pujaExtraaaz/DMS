@extends('layouts.dms')
@section('title', 'Stock Report')
@section('content')
<x-ui.page-header title="Stock Report" description="Current stock quantities with brand / category / warehouse filters.">
    <x-slot name="actions">
        @if(\Illuminate\Support\Facades\Route::has('inventory.stock-register.index'))
            <a href="{{ route('inventory.stock-register.index') }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">Inventory Stock</a>
        @endif
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

@include('reporting.partials.integrity-hints')

<x-ui.report-filters
    :show-dates="false"
    search-placeholder="Search product, brand, warehouse, SKU initials..."
    :reset-url="route('reports.stock')"
    submit-label="Filter"
    :show-warehouse="true"
    :show-brand="true"
    :show-category="true"
    :show-product="true"
    :show-low-stock="true"
    :warehouses="$warehouses"
    :brands="$brands"
    :categories="$categories"
    :products="$products"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <x-ui.sortable-th column="product" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Product</x-ui.sortable-th>
                    <x-ui.sortable-th column="brand" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Brand</x-ui.sortable-th>
                    <x-ui.sortable-th column="warehouse" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'">Warehouse</x-ui.sortable-th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">UOM</th>
                    <x-ui.sortable-th column="quantity" :current-sort="$sort ?? 'product'" :current-direction="$direction ?? 'asc'" align="right">Qty</x-ui.sortable-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($stockLevels as $level)
                    <tr class="hover:bg-slate-50" data-search="{{ trim(($level->product?->name ?? '').' '.($level->product?->sku ?? '').' '.($level->product?->brand?->name ?? '').' '.($level->warehouse?->name ?? '')) }}">
                        <td class="px-3 py-2 font-medium text-slate-900">
                            @if($level->product && \Illuminate\Support\Facades\Route::has('masters.products.show'))
                                <a href="{{ route('masters.products.show', $level->product) }}" class="text-indigo-600 hover:underline">{{ $level->product->name }}</a>
                            @else
                                {{ $level->product?->name ?? '—' }}
                            @endif
                        </td>
                        <td class="px-3 py-2 text-slate-600">{{ $level->product?->brand?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $level->warehouse?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-slate-500">{{ $level->uom?->code ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium {{ (float)$level->quantity < 10 ? 'text-amber-600 font-semibold' : 'text-slate-900' }}">{{ $level->quantity }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-slate-500" data-report-empty>
                            <x-ui.empty-state title="No stock records found" description="Import Books openings or post stock in Inventory, then refresh this report." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
