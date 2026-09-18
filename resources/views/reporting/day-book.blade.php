@extends('layouts.dms')
@section('title', 'Day Book')
@section('content')
<x-ui.page-header title="Day Book" description="Chronological voucher listing (Sales / Receipt / Credit Note / Purchase).">
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
            <thead class="bg-slate-50 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Type</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Voucher No</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Narration</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Debit</th>
                    <th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Credit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr>
                        <td class="px-3 py-2">{{ \Carbon\Carbon::parse($row['date'])->format('d M Y') }}</td>
                        <td class="px-3 py-2">{{ $row['type'] }}</td>
                        <td class="px-3 py-2 font-mono">{{ $row['voucher'] }}</td>
                        <td class="px-3 py-2">{{ $row['party'] }}</td>
                        <td class="px-3 py-2 text-xs text-slate-500">{{ $row['narration'] }}</td>
                        <td class="px-3 py-2 text-right">{{ $row['debit'] ? '₹ '.number_format($row['debit'], 2) : '' }}</td>
                        <td class="px-3 py-2 text-right">{{ $row['credit'] ? '₹ '.number_format($row['credit'], 2) : '' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-3 py-6 text-center text-slate-500">No vouchers in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 font-semibold">
                <tr>
                    <td colspan="5" class="px-3 py-2 text-right">Totals</td>
                    <td class="px-3 py-2 text-right">₹ {{ number_format((float) $totals['debit'], 2) }}</td>
                    <td class="px-3 py-2 text-right">₹ {{ number_format((float) $totals['credit'], 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-ui.card>
@endsection
