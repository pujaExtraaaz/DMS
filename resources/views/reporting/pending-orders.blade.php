@extends('layouts.dms')
@section('title', 'Pending Orders')
@section('content')
<x-ui.page-header title="Pending Orders Report" description="Ordered / reserved / delivered / pending with back-order flags">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
    </x-slot>
</x-ui.page-header>
<x-ui.report-filters
    :date-from="$dateFrom ?? ''"
    :date-to="$dateTo ?? ''"
    search-placeholder="Search order no or party initials..."
    :reset-url="route('reports.pending-orders')"
    submit-label="Filter"
    :show-party="true"
    :show-salesperson="true"
    :show-branch="true"
    :show-brand="true"
    :show-category="true"
    :show-product="true"
    :show-fulfilment="true"
    :show-back-order="true"
    :customers="$customers"
    :salespeople="$salespeople"
    :branches="$branches"
    :brands="$brands"
    :categories="$categories"
    :products="$products"
/>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50"><tr>
                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Order</th>
                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Customer</th>
                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Mode</th>
                <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Due</th>
                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Ordered</th>
                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Reserved</th>
                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Delivered</th>
                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Pending</th>
                <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Back Order</th>
            </tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr data-search="{{ trim(($row['order']->order_no ?? '').' '.($row['order']->customer?->name ?? '').' '.($row['order']->fulfilment_mode ?? '')) }}">
                        <td class="px-3 py-2 font-medium"><a class="text-indigo-600" href="{{ route('orders.show', $row['order']) }}">{{ $row['order']->order_no }}</a></td>
                        <td class="px-3 py-2">{{ $row['order']->customer?->name }}</td>
                        <td class="px-3 py-2">{{ ucfirst($row['order']->fulfilment_mode ?? 'van') }}</td>
                        <td class="px-3 py-2">{{ $row['order']->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['ordered'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['reserved'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['delivered'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['pending'], 4) }}</td>
                        <td class="px-3 py-2 text-right">{{ number_format($row['back_order'], 4) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-3 py-6 text-center text-slate-500" data-report-empty>No pending orders.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
