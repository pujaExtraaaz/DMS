@extends('layouts.dms')
@section('title', 'Trial Balance')
@section('content')
<x-ui.page-header title="Trial Balance" description="Ledger-style summary derived from transactions in the selected period.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
        <x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
        <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
    </form>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Ledger</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Debit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Credit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Net</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $r)
                    <tr>
                        <td class="px-3 py-2">{{ $r['ledger'] }}</td>
                        <td class="px-3 py-2 text-right">{{ $r['debit'] ? '₹ '.number_format((float) $r['debit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right">{{ $r['credit'] ? '₹ '.number_format((float) $r['credit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right font-medium">₹ {{ number_format((float) $r['balance'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr>
                    <td class="px-3 py-2 text-right">Totals</td>
                    <td class="px-3 py-2 text-right">₹ {{ number_format((float) $totals['debit'], 2) }}</td>
                    <td class="px-3 py-2 text-right">₹ {{ number_format((float) $totals['credit'], 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-ui.card>
@endsection
