@extends('layouts.dms')
@section('title', 'Purchase Order Register')
@section('content')
<x-ui.page-header title="Purchase Order Register" description="All purchase orders by period — view, export, and print">
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
    search-placeholder="Search PO no, supplier, warehouse initials..."
    :reset-url="route('reports.purchase-orders')"
    :show-supplier="true"
    :show-warehouse="true"
    :show-branch="true"
    :show-status="true"
    :statuses="['draft', 'submitted', 'approved', 'partial', 'received', 'cancelled']"
    :suppliers="$suppliers"
    :warehouses="$warehouses"
    :branches="$branches"
/>

<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3">
    <x-ui.stat-card label="Purchase Orders" value="{{ $summary['count'] }}" accent="indigo" />
    <x-ui.stat-card label="Grand Total" value="₹{{ number_format($summary['total'], 2) }}" accent="violet" />
    <x-ui.stat-card label="Open" value="{{ $summary['open'] }}" accent="amber" />
</div>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">PO No</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Supplier</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Warehouse</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Status</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Total</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Expected</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($orders as $o)
                    <tr data-search="{{ trim(($o->po_no ?? '').' '.($o->supplier?->name ?? '').' '.($o->warehouse?->name ?? '').' '.($o->status ?? '')) }}">
                        <td class="px-3 py-2">{{ optional($o->po_date)->format('d M Y') }}</td>
                        <td class="px-3 py-2">
                            <a href="{{ route('purchasing.orders.show', $o) }}" class="text-indigo-600 hover:underline font-medium">{{ $o->po_no }}</a>
                        </td>
                        <td class="px-3 py-2">{{ $o->supplier?->name }}</td>
                        <td class="px-3 py-2">{{ $o->warehouse?->name }}</td>
                        <td class="px-3 py-2"><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs">{{ $o->status }}</span></td>
                        <td class="px-3 py-2 text-right">₹{{ number_format((float) $o->grand_total, 2) }}</td>
                        <td class="px-3 py-2">{{ optional($o->expected_date)->format('d M Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-8 text-center text-slate-500">No purchase orders in this period.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
