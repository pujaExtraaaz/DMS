@extends('layouts.dms')
@section('title', 'Sales Report')
@section('content')
<x-ui.page-header title="Sales Report" description="Comprehensive sales analysis and invoicing details" />

{{-- Filters --}}
<x-ui.card class="mb-6">
    <form method="GET" class="flex flex-row items-end gap-4">
        <div class="flex-1">
            <x-ui.input name="date_from" type="date" label="From" :value="$dateFrom" />
        </div>
        <div class="flex-1">
            <x-ui.input name="date_to" type="date" label="To" :value="$dateTo" />
        </div>
        <div class="flex-1">
            <x-ui.select name="customer_id" label="Customer" placeholder="All customers">
                <option value=""></option>
                @foreach($customers as $c)
                    <option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>
                @endforeach
            </x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Run Report</x-ui.button>
    </form>
</x-ui.card>

{{-- Summary KPIs --}}
<div class="grid grid-cols-1 gap-4 mb-6 md:grid-cols-3 lg:grid-cols-6">
    <x-ui.stat-card label="Total Invoices" :value="$summary['count']" accent="indigo" />
    <x-ui.stat-card label="Subtotal" :value="'₹'.number_format($summary['subtotal'], 2)" accent="blue" />
    <x-ui.stat-card label="Tax" :value="'₹'.number_format($summary['tax'], 2)" accent="purple" />
    <x-ui.stat-card label="Gross Total" :value="'₹'.number_format($summary['total'], 2)" accent="violet" />
    <x-ui.stat-card label="Collected" :value="'₹'.number_format($summary['collected'], 2)" accent="emerald" :change="$summary['percentage_collected'].'% collected'" change-type="positive" />
    <x-ui.stat-card label="Outstanding" :value="'₹'.number_format($summary['outstanding'], 2)" accent="rose" />
</div>

{{-- Status & Customer Breakdowns --}}
<div class="grid grid-cols-1 gap-6 mb-6 lg:grid-cols-2">
    {{-- By Status --}}
    <x-ui.card title="By Status">
        <div class="space-y-3">
            @forelse($byStatus as $status => $count)
                @php
                    $statusColors = ['draft' => 'gray', 'issued' => 'blue', 'partial' => 'amber', 'paid' => 'emerald', 'cancelled' => 'red'];
                    $color = $statusColors[$status] ?? 'gray';
                @endphp
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <x-ui.badge :variant="$color">{{ ucfirst($status) }}</x-ui.badge>
                        <span class="text-sm text-slate-600">{{ $count }} invoice{{ $count != 1 ? 's' : '' }}</span>
                    </div>
                    <span class="text-sm font-semibold text-slate-900">{{ round(($count / $summary['count']) * 100) }}%</span>
                </div>
            @empty
                <p class="text-sm text-slate-500">No invoices</p>
            @endforelse
        </div>
    </x-ui.card>

    {{-- Top Customers --}}
    <x-ui.card title="Top Customers">
        <div class="space-y-3">
            @forelse($byCustomer->sortByDesc('total')->take(5) as $row)
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-slate-900">{{ $row['customer'] }}</p>
                        <p class="text-xs text-slate-500">{{ $row['count'] }} invoice{{ $row['count'] != 1 ? 's' : '' }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-slate-900">₹{{ number_format($row['total'], 2) }}</p>
                        <p class="text-xs text-emerald-600">{{ round(($row['collected'] / $row['total']) * 100) }}% collected</p>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">No customers</p>
            @endforelse
        </div>
    </x-ui.card>
</div>

{{-- Detailed Invoices Table --}}
<x-ui.card title="Invoice Details">
    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse text-sm">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Invoice</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-600">Customer</th>
                    <th class="px-4 py-3 text-center font-semibold text-slate-600">Status</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Date</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Items</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Subtotal</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Tax</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Total</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Collected</th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-600">Outstanding</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @php $totalItems = 0; $totalSubtotalCol = 0; $totalTaxCol = 0; $totalGrandCol = 0; $totalPaidCol = 0; $totalOutstandingCol = 0; @endphp
                @forelse($invoices as $invoice)
                    @php
                        $outstanding = $invoice->grand_total - $invoice->paid_amount;
                        $itemCount = $invoice->items->count();
                        $totalItems += $itemCount;
                        $totalSubtotalCol += $invoice->subtotal;
                        $totalTaxCol += $invoice->tax_amount;
                        $totalGrandCol += $invoice->grand_total;
                        $totalPaidCol += $invoice->paid_amount;
                        $totalOutstandingCol += $outstanding;
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <a href="{{ route('invoices.show', $invoice) }}" class="text-indigo-600 hover:text-indigo-700 font-medium">{{ $invoice->invoice_no }}</a>
                        </td>
                        <td class="px-4 py-3 text-slate-700">{{ $invoice->customer->name }}</td>
                        <td class="px-4 py-3 text-center">
                            @php
                                $statusColors = ['draft' => 'gray', 'issued' => 'blue', 'partial' => 'amber', 'paid' => 'emerald', 'cancelled' => 'red'];
                            @endphp
                            <x-ui.badge :variant="$statusColors[$invoice->status] ?? 'gray'">{{ ucfirst($invoice->status) }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $invoice->invoice_date->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">{{ $itemCount }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($invoice->subtotal, 2) }}</td>
                        <td class="px-4 py-3 text-right text-slate-700">₹{{ number_format($invoice->tax_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right font-semibold text-slate-900">₹{{ number_format($invoice->grand_total, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $invoice->paid_amount > 0 ? 'text-emerald-600 font-semibold' : 'text-slate-700' }}">₹{{ number_format($invoice->paid_amount, 2) }}</td>
                        <td class="px-4 py-3 text-right {{ $outstanding > 0 ? 'text-rose-600 font-semibold' : 'text-emerald-600' }}">₹{{ number_format($outstanding, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-4 py-8">
                            <x-ui.empty-state title="No invoices found" description="Adjust filters and try again" />
                        </td>
                    </tr>
                @endforelse
                @if($invoices->isNotEmpty())
                    <tr class="bg-slate-50 font-semibold text-slate-900 border-t-2 border-slate-300">
                        <td colspan="4" class="px-4 py-4 text-right">TOTALS</td>
                        <td class="px-4 py-4 text-right">{{ $totalItems }}</td>
                        <td class="px-4 py-4 text-right">₹{{ number_format($totalSubtotalCol, 2) }}</td>
                        <td class="px-4 py-4 text-right">₹{{ number_format($totalTaxCol, 2) }}</td>
                        <td class="px-4 py-4 text-right">₹{{ number_format($totalGrandCol, 2) }}</td>
                        <td class="px-4 py-4 text-right text-emerald-600">₹{{ number_format($totalPaidCol, 2) }}</td>
                        <td class="px-4 py-4 text-right text-rose-600">₹{{ number_format($totalOutstandingCol, 2) }}</td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
