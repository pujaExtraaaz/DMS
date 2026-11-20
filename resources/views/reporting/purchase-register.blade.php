@extends('layouts.dms')
@section('title', 'Purchase Register')
@section('content')
<x-ui.page-header title="Purchase Register" description="Purchase invoices and legacy purchases">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
        <button type="button" onclick="window.print()" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">🖨 Print</button>
    </x-slot>
</x-ui.page-header>
@include('reporting.partials.integrity-hints')

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search purchase no, supplier, status initials..."
    :reset-url="route('reports.purchase-register')"
    submit-label="Filter"
    :show-supplier="true"
    :show-warehouse="true"
    :show-brand="true"
    :show-category="true"
    :show-product="true"
    :suppliers="$suppliers"
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
                    <x-ui.sortable-th column="invoice_no" :current-sort="$sort ?? 'invoice_date'" :current-direction="$direction ?? 'desc'">Invoice No</x-ui.sortable-th>
                    <x-ui.sortable-th column="invoice_date" :current-sort="$sort ?? 'invoice_date'" :current-direction="$direction ?? 'desc'">Date</x-ui.sortable-th>
                    <x-ui.sortable-th column="supplier" :current-sort="$sort ?? 'invoice_date'" :current-direction="$direction ?? 'desc'">Supplier</x-ui.sortable-th>
                    <x-ui.sortable-th column="grand_total" :current-sort="$sort ?? 'invoice_date'" :current-direction="$direction ?? 'desc'" align="right">Total</x-ui.sortable-th>
                    <x-ui.sortable-th column="status" :current-sort="$sort ?? 'invoice_date'" :current-direction="$direction ?? 'desc'">Status</x-ui.sortable-th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($purchases as $p)
                    <tr class="hover:bg-slate-50" data-search="{{ trim(($p->invoice_no ?? '').' '.($p->supplier?->name ?? '').' '.($p->status ?? '')) }}">
                        <td class="px-3 py-2 font-medium text-slate-900">{{ $p->invoice_no }}</td>
                        <td class="px-3 py-2 text-slate-600">{{ $p->invoice_date?->format('d M Y') }}</td>
                        <td class="px-3 py-2 text-slate-700">{{ $p->supplier?->name ?? '—' }}</td>
                        <td class="px-3 py-2 text-right font-medium text-slate-900">₹{{ number_format((float)$p->grand_total, 2) }}</td>
                        <td class="px-3 py-2">
                            <x-ui.badge :variant="in_array($p->status, ['posted', 'paid']) ? 'emerald' : ($p->status === 'cancelled' ? 'rose' : 'amber')">
                                {{ ucfirst((string) $p->status) }}
                            </x-ui.badge>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-8 text-center text-slate-500" data-report-empty>
                            <x-ui.empty-state title="No purchases found" description="Try adjusting your search or date filters." />
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $purchases->links() }}</div>
</x-ui.card>
@endsection
