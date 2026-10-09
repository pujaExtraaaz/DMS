@extends('layouts.dms')
@section('title', 'Day Book')
@section('content')
<x-ui.page-header title="Day Book" description="Chronological voucher listing (Sales / Receipt / Credit Note / Purchase).">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<div id="listing-container" data-dynamic-container>
    <x-ui.card>
        <x-ui.table-toolbar
            placeholder="Search day book by voucher, party, narration..."
            :searchValue="request('search')"
            :resetUrl="route('reports.day-book')"
        >
            <x-slot name="filters">
                <input type="date" name="date_from" value="{{ $dateFrom }}" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter />
                <input type="date" name="date_to" value="{{ $dateTo }}" class="rounded-lg border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" data-dynamic-filter />
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 sticky top-0">
                    <tr>
                        <x-ui.sortable-th column="date" label="Date" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="type" label="Type" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="voucher" label="Voucher No" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="party" label="Party" :currentSort="$sort" :currentDirection="$direction" />
                        <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Narration</th>
                        <x-ui.sortable-th column="debit" label="Debit" align="right" :currentSort="$sort" :currentDirection="$direction" />
                        <x-ui.sortable-th column="credit" label="Credit" align="right" :currentSort="$sort" :currentDirection="$direction" />
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($rows as $row)
                        <tr class="hover:bg-slate-50">
                            <td class="px-3 py-2">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                            <td class="px-3 py-2"><x-ui.badge>{{ $row['type'] }}</x-ui.badge></td>
                            <td class="px-3 py-2 font-mono font-medium">{{ $row['voucher'] }}</td>
                            <td class="px-3 py-2">{{ $row['party'] }}</td>
                            <td class="px-3 py-2 text-xs text-slate-500">{{ $row['narration'] }}</td>
                            <td class="px-3 py-2 text-right text-rose-600">{{ $row['debit'] ? '₹ '.number_format($row['debit'], 2) : '' }}</td>
                            <td class="px-3 py-2 text-right text-emerald-600">{{ $row['credit'] ? '₹ '.number_format($row['credit'], 2) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No vouchers found in this period.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-50 font-semibold">
                    <tr>
                        <td colspan="5" class="px-3 py-2 text-right">Totals</td>
                        <td class="px-3 py-2 text-right text-rose-600">₹ {{ number_format((float) $totals['debit'], 2) }}</td>
                        <td class="px-3 py-2 text-right text-emerald-600">₹ {{ number_format((float) $totals['credit'], 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
