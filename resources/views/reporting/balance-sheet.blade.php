@extends('layouts.dms')
@section('title', 'Balance Sheet')
@section('content')
<x-ui.page-header title="Balance Sheet" description="Assets, Liabilities, and Equity as of the selected date. Derived from outstanding ledger, FIFO stock cost, and supplier payables.">
    <x-slot name="actions">
        <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}" class="rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50">⬇ CSV</a>
        <a href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}" class="rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">⬇ PDF</a>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <x-ui.input name="as_of" type="date" label="As of" :value="$asOf" />
        <x-ui.button type="submit" variant="secondary">Refresh</x-ui.button>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
        <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-3"><span class="text-xs text-slate-500">Total Assets</span><div class="text-xl font-semibold text-emerald-700">₹ {{ number_format($summary['assetsTotal'], 2) }}</div></div>
        <div class="rounded-lg bg-red-50 border border-red-100 px-3 py-3"><span class="text-xs text-slate-500">Total Liabilities</span><div class="text-xl font-semibold text-red-700">₹ {{ number_format($summary['liabilitiesTotal'], 2) }}</div></div>
        <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-3"><span class="text-xs text-slate-500">Equity (proxy)</span><div class="text-xl font-semibold text-indigo-700">₹ {{ number_format($summary['equity'], 2) }}</div></div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Side</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Line Item</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($rows as $r)
                    <tr @class(['bg-slate-50 font-semibold' => ($r['_total'] ?? false)])>
                        <td class="px-3 py-2">{{ $r['Side'] }}</td>
                        <td class="px-3 py-2">{{ $r['Line Item'] }}</td>
                        <td class="px-3 py-2 text-right">₹ {{ $r['Amount'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
