@extends('layouts.dms')
@section('title', 'Day Book')
@section('content')
@php
    $queryBase = array_filter([
        'date_from' => $dateFrom,
        'date_to' => $dateTo,
        'search' => request('search'),
        'sort' => $sort,
        'direction' => $direction,
    ], fn ($v) => $v !== null && $v !== '');
@endphp

<x-ui.page-header title="Day Book" description="Live listing from sales, receipts, credit notes, purchase invoices, freight bills, plus Books Journal / Contra / other accounting vouchers.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

{{-- Voucher type navbar --}}
<nav class="mb-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-label="Day book voucher types">
    <div class="flex flex-wrap gap-1 px-2 py-2 sm:px-3">
        @foreach($typeFilters as $key => $label)
            @php
                $count = (int) ($typeCounts[$key] ?? 0);
                $isActive = $activeType === $key;
                $typeQuery = array_merge($queryBase, ['type' => $key]);
            @endphp
            <a href="{{ route('reports.day-book', $typeQuery) }}"
               class="rounded-full border px-3.5 py-1.5 text-sm font-medium transition
                      {{ $isActive
                          ? 'border-indigo-600 bg-indigo-600 text-white shadow-sm'
                          : 'border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900' }}">
                {{ $label }}
                <span class="ml-1 text-[11px] {{ $isActive ? 'text-indigo-100' : 'text-slate-400' }}">({{ $count }})</span>
            </a>
        @endforeach
    </div>
</nav>

<x-ui.report-filters
    :date-from="$dateFrom"
    :date-to="$dateTo"
    search-placeholder="Search voucher, party, narration initials..."
    :search-value="request('search')"
    :reset-url="route('reports.day-book')"
    submit-label="Filter"
>
    <input type="hidden" name="type" value="{{ $activeType }}">
</x-ui.report-filters>

<x-ui.card>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm" data-report-table>
            <thead class="bg-slate-50 sticky top-0">
                <tr>
                    <x-ui.sortable-th column="date" :current-sort="$sort" :current-direction="$direction">Date</x-ui.sortable-th>
                    <x-ui.sortable-th column="type" :current-sort="$sort" :current-direction="$direction">Type</x-ui.sortable-th>
                    <x-ui.sortable-th column="voucher" :current-sort="$sort" :current-direction="$direction">Voucher No</x-ui.sortable-th>
                    <x-ui.sortable-th column="party" :current-sort="$sort" :current-direction="$direction">Party</x-ui.sortable-th>
                    <th class="px-3 py-2 text-left text-xs font-semibold text-slate-500">Narration</th>
                    <x-ui.sortable-th column="debit" align="right" :current-sort="$sort" :current-direction="$direction">Debit</x-ui.sortable-th>
                    <x-ui.sortable-th column="credit" align="right" :current-sort="$sort" :current-direction="$direction">Credit</x-ui.sortable-th>
                    <th class="px-3 py-2 text-right text-xs font-semibold text-slate-500">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-slate-50" data-search="{{ trim(($row['type'] ?? '').' '.($row['voucher'] ?? '').' '.($row['party'] ?? '').' '.($row['narration'] ?? '')) }}">
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td class="px-3 py-2"><x-ui.badge>{{ $row['type'] }}</x-ui.badge></td>
                        <td class="px-3 py-2 font-mono font-medium">{{ $row['voucher'] }}</td>
                        <td class="px-3 py-2">{{ $row['party'] }}</td>
                        <td class="px-3 py-2 text-xs text-slate-500">{{ $row['narration'] }}</td>
                        <td class="px-3 py-2 text-right text-rose-600">{{ $row['debit'] ? '₹ '.number_format($row['debit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right text-emerald-600">{{ $row['credit'] ? '₹ '.number_format($row['credit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right">
                            @if(!empty($row['view_url']))
                                <a href="{{ $row['view_url'] }}" class="inline-flex items-center rounded-lg border border-slate-200 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">View</a>
                            @else
                                <span class="text-xs text-slate-400">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-3 py-6 text-center text-slate-500" data-report-empty>No vouchers found for this period / type.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr data-report-empty>
                    <td colspan="5" class="px-3 py-2 text-right">Totals</td>
                    <td class="px-3 py-2 text-right text-rose-600">₹ {{ number_format((float) $totals['debit'], 2) }}</td>
                    <td class="px-3 py-2 text-right text-emerald-600">₹ {{ number_format((float) $totals['credit'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-ui.card>
@endsection
