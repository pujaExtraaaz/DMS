@extends('layouts.dms')

@section('title', 'Contributing Sales Invoices Drill-down')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Contributing Sales Invoices</h1>
            <p class="text-xs sm:text-sm text-slate-500">Live source invoice drill-down contributing to salesperson and party target achievements.</p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('sales-targets.salesman-performance') }}" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50 transition">
                ← Salesman Performance
            </a>
            <a href="{{ route('sales-targets.dashboard') }}" class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                Target Dashboard
            </a>
        </div>
    </div>

    {{-- Filter Toolbar --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs">
        <form method="GET" action="{{ route('sales-targets.invoices') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-7 gap-3">
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Salesperson</label>
                <select name="salesperson_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Salespeople</option>
                    @foreach($salespeople as $sp)
                        <option value="{{ $sp->id }}" @selected(request('salesperson_id') == $sp->id)>{{ $sp->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party / Customer</label>
                <select name="customer_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Parties</option>
                    @foreach($parties as $p)
                        <option value="{{ $p->id }}" @selected(request('customer_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Financial Year</label>
                <select name="financial_year_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All FY</option>
                    @foreach($financialYears as $fy)
                        <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>{{ $fy->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full rounded-lg border-gray-300 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full rounded-lg border-gray-300 text-xs">
            </div>

            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Company</label>
                <select name="company_id" class="w-full rounded-lg border-gray-300 text-xs">
                    <option value="">All Companies</option>
                    @foreach($companies as $c)
                        <option value="{{ $c->id }}" @selected(request('company_id') == $c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Filter</button>
                <a href="{{ route('sales-targets.invoices') }}" class="rounded-lg border px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">↺</a>
            </div>
        </form>
    </div>

    {{-- Summary Metric Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-xs font-medium text-slate-500">Contributing Invoices</span>
            <div class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalInvoicesCount) }}</div>
            <span class="text-[11px] text-slate-400">Total posted tax invoices</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-xs font-medium text-slate-500">Gross Invoice Amount</span>
            <div class="mt-1 text-2xl font-bold text-slate-900">₹ {{ number_format($totalInvoiceAmount, 2) }}</div>
            <span class="text-[11px] text-slate-400">Total billings before credit notes</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
            <span class="text-xs font-medium text-rose-600">Credit Notes / Sales Returns</span>
            <div class="mt-1 text-2xl font-bold text-rose-700">₹ {{ number_format($totalCreditNotesAmount, 2) }}</div>
            <span class="text-[11px] text-slate-400">Deducted return adjustments</span>
        </div>

        <div class="rounded-xl border border-slate-200 bg-emerald-50/50 p-4 shadow-xs">
            <span class="text-xs font-medium text-emerald-800">Net Eligible Sales</span>
            <div class="mt-1 text-2xl font-bold text-emerald-700">₹ {{ number_format($netEligibleAmount, 2) }}</div>
            <span class="text-[11px] text-emerald-600">Actual target achievement value</span>
        </div>
    </div>

    {{-- Invoices Table --}}
    <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-xs">
        <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
            <thead class="bg-slate-50/80 text-slate-700 font-semibold uppercase tracking-wider">
                <tr>
                    <th class="px-3.5 py-3">Company</th>
                    <th class="px-3.5 py-3">Invoice No</th>
                    <th class="px-3.5 py-3">Invoice Date</th>
                    <th class="px-3.5 py-3">Customer / Party Name</th>
                    <th class="px-3.5 py-3">Salesperson</th>
                    <th class="px-3.5 py-3 text-right">Invoice Amount (₹)</th>
                    <th class="px-3.5 py-3 text-right">Credit Note Adj. (₹)</th>
                    <th class="px-3.5 py-3 text-right">Eligible Sales (₹)</th>
                    <th class="px-3.5 py-3 text-center">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($invoices as $inv)
                    @php
                        $cnTotal = $inv->credit_notes_total;
                        $eligible = max(0, (float)$inv->grand_total - $cnTotal);
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-3.5 py-3 font-medium text-slate-700">
                            {{ $inv->company?->name ?? '—' }}
                        </td>
                        <td class="px-3.5 py-3 font-mono font-semibold text-indigo-600">
                            <a href="{{ route('invoices.show', $inv) }}" class="hover:underline">
                                {{ $inv->invoice_no }}
                            </a>
                        </td>
                        <td class="px-3.5 py-3 text-slate-600">
                            {{ $inv->invoice_date?->format('d M Y') }}
                        </td>
                        <td class="px-3.5 py-3 font-semibold text-slate-900">
                            {{ $inv->customer?->name ?? '—' }}
                            @if($inv->customer?->code)
                                <span class="text-[10px] text-slate-400 block font-normal">({{ $inv->customer->code }})</span>
                            @endif
                        </td>
                        <td class="px-3.5 py-3 text-slate-700 font-medium">
                            {{ $inv->salesperson?->name ?? 'Unassigned' }}
                        </td>
                        <td class="px-3.5 py-3 text-right font-medium text-slate-800">
                            ₹ {{ number_format($inv->grand_total, 2) }}
                        </td>
                        <td class="px-3.5 py-3 text-right font-medium {{ $cnTotal > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                            {{ $cnTotal > 0 ? '₹ ' . number_format($cnTotal, 2) : '—' }}
                        </td>
                        <td class="px-3.5 py-3 text-right font-bold text-emerald-700">
                            ₹ {{ number_format($eligible, 2) }}
                        </td>
                        <td class="px-3.5 py-3 text-center">
                            @php
                                $badgeClass = match($inv->status) {
                                    'paid' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'partial' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                    'issued' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'cancelled' => 'bg-rose-50 text-rose-700 ring-rose-600/20',
                                    default => 'bg-slate-50 text-slate-600 ring-slate-500/20',
                                };
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[11px] font-semibold ring-1 ring-inset {{ $badgeClass }}">
                                {{ ucfirst($inv->status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="py-12 text-center text-xs text-slate-400">
                            No contributing invoices found matching the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>
        {{ $invoices->links() }}
    </div>
</div>
@endsection
