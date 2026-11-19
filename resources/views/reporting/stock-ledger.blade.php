@extends('layouts.dms')
@section('title', 'Stock Ledger')
@section('content')
<x-ui.page-header title="Stock Ledger" description="Stock movements with running balance">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">🖨 Print</button>
    </x-slot>
</x-ui.page-header>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search product, type, warehouse, notes initials..."
    :reset-url="route('reports.stock-ledger')"
    submit-label="Filter"
    :show-product="true"
    :show-warehouse="true"
    :show-brand="true"
    :show-category="true"
    :products="$products"
    :warehouses="$warehouses"
    :brands="$brands"
    :categories="$categories"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
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
                    <tr class="hover:bg-slate-50" data-search="{{ trim(($m->product?->name ?? '').' '.($m->type ?? '').' '.($m->warehouse?->name ?? '').' '.($m->notes ?? '')) }}">
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
                        <td colspan="6" class="px-3 py-8 text-center text-slate-500" data-report-empty>
                            <x-ui.empty-state title="No movements found" description="Try adjusting your search or date filters." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $movements->links() }}</div>
</x-ui.card>
@endsection
