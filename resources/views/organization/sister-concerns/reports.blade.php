@extends('layouts.dms')

@section('title', 'Sister Concern Management · Consolidated Reports')

@section('content')
<div class="space-y-6" x-data="consolidatedReportsApp()">
    {{-- Header Banner & Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'reports',
        'tabCounts' => $tabCounts,
    ])

    {{-- Filter Toolbar Card --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-sm space-y-4">
        <form method="GET" action="{{ route('organization.sister-concerns.reports') }}" id="reportsFilterForm">
            <input type="hidden" name="custom_dates" id="customDatesFlag" value="{{ request('custom_dates', '0') }}">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-7 gap-3.5 items-end">
                {{-- 1. Business Group --}}
                <div>
                    <label for="business_group_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Business Group</label>
                    <select name="business_group_id" id="business_group_id"
                            onchange="document.getElementById('reportsFilterForm').submit()"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        @forelse($businessGroups as $bg)
                            <option value="{{ $bg->id }}" {{ $selectedGroup && $selectedGroup->id === $bg->id ? 'selected' : '' }}>
                                {{ $bg->name }}
                            </option>
                        @empty
                            <option value="">No Groups Available</option>
                        @endforelse
                    </select>
                </div>

                {{-- 2. Owner / Parent Company Badge --}}
                <div>
                    <span class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Owner / Parent Entity</span>
                    <div class="w-full px-3 py-2 bg-purple-50/70 border border-purple-200/60 rounded-xl text-xs font-semibold text-purple-900 flex items-center gap-1.5 truncate">
                        <svg class="w-3.5 h-3.5 text-purple-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                        <span class="truncate">{{ $selectedGroup?->parentCompany?->name ?? 'Avit Digital (Parent)' }}</span>
                    </div>
                </div>

                {{-- 3. Sister Concern / Company Filter --}}
                <div>
                    <label for="company_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Sister Concern</label>
                    <select name="company_id" id="company_id"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        <option value="all">All Sister Concerns ({{ $groupCompanies->count() }})</option>
                        @foreach($groupCompanies as $co)
                            <option value="{{ $co->id }}" {{ $selectedCompanyId === $co->id ? 'selected' : '' }}>
                                {{ $co->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 4. Report Type --}}
                <div>
                    <label for="report_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Report View</label>
                    <select name="report_type" id="report_type"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        <option value="all" {{ $reportType === 'all' ? 'selected' : '' }}>Dashboard (Overview)</option>
                        <option value="sales" {{ $reportType === 'sales' ? 'selected' : '' }}>Consolidated Sales</option>
                        <option value="purchases" {{ $reportType === 'purchases' ? 'selected' : '' }}>Consolidated Purchases</option>
                    </select>
                </div>

                {{-- 5. Financial Year --}}
                <div>
                    <label for="financial_year" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Financial Year</label>
                    <select name="financial_year" id="financial_year"
                            @change="onFyChange($event)"
                            class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy['name'] }}"
                                    data-start="{{ $fy['starts_on'] }}"
                                    data-end="{{ $fy['ends_on'] }}"
                                    {{ $selectedFy === $fy['name'] ? 'selected' : '' }}>
                                FY {{ $fy['name'] }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 6. Date Range --}}
                <div>
                    <label for="from_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}"
                           @change="onDateManualChange"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                <div>
                    <label for="to_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}"
                           @change="onDateManualChange"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>
            </div>

            {{-- Action Buttons & Exports Bar --}}
            <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold tracking-wide shadow-sm hover:shadow transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        <span>Apply Filters</span>
                    </button>
                    <a href="{{ route('organization.sister-concerns.reports') }}"
                       class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                        Reset
                    </a>
                </div>

                <div class="flex items-center gap-2">
                    <span class="text-xs font-semibold text-slate-400">Export:</span>
                    <a href="{{ route('organization.sister-concerns.reports.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition shadow-sm">
                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('organization.sister-concerns.reports.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/60 rounded-xl text-xs font-semibold transition shadow-sm">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        <span>Excel</span>
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- Sub-Navigation Tabs for Report Types --}}
    <div class="flex items-center gap-2 border-b border-slate-200/80 pb-2">
        <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->query(), ['report_type' => 'all'])) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $reportType === 'all' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
            <span>Overview & Dashboard</span>
        </a>
        <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->query(), ['report_type' => 'sales'])) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $reportType === 'sales' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
            <span>Consolidated Sales</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $reportType === 'sales' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $salesSummary['total_invoices'] }}</span>
        </a>
        <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->query(), ['report_type' => 'purchases'])) }}"
           class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 {{ $reportType === 'purchases' ? 'bg-purple-700 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' }}">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
            <span>Consolidated Purchases</span>
            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold {{ $reportType === 'purchases' ? 'bg-white/20 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $purchasesSummary['total_invoices'] }}</span>
        </a>
    </div>

    {{-- VIEW 1: CONSOLIDATED DASHBOARD & OVERVIEW --}}
    @if($reportType === 'all')
        {{-- Primary Financial Metric Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            {{-- Combined Sales --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Sales</span>
                    <span class="p-2 rounded-xl bg-purple-50 text-purple-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 tracking-tight">₹{{ number_format($salesSummary['total_sales'], 2) }}</div>
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $salesSummary['total_invoices'] }} invoices</span>
                        @if($salesSummary['credit_notes_total'] > 0)
                            <span class="text-amber-600 font-semibold" title="Net Sales after Credit Notes">Net: ₹{{ number_format($salesSummary['net_sales'], 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Combined Purchases --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Purchases</span>
                    <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-slate-900 tracking-tight">₹{{ number_format($purchasesSummary['total_purchases'], 2) }}</div>
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>{{ $purchasesSummary['total_invoices'] }} purchase orders/invoices</span>
                        <span class="text-slate-400 font-medium">Tax: ₹{{ number_format($purchasesSummary['tax_amount'], 2) }}</span>
                    </div>
                </div>
            </div>

            {{-- Collections & Outstanding Receivables --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Collections & Receivables</span>
                    <span class="p-2 rounded-xl bg-emerald-50 text-emerald-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black text-emerald-600 tracking-tight">₹{{ number_format($salesSummary['collected_amount'], 2) }}</div>
                    <div class="mt-1 flex items-center justify-between text-xs font-semibold">
                        <span class="text-rose-600">Pending: ₹{{ number_format($salesSummary['outstanding_amount'], 2) }}</span>
                        <span class="text-slate-400">{{ $salesSummary['collection_percentage'] }}% Rec.</span>
                    </div>
                </div>
            </div>

            {{-- Net Contribution & Inter-Company Elimination --}}
            <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm relative overflow-hidden">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Group Net Position</span>
                    <span class="p-2 rounded-xl {{ $netContribution >= 0 ? 'bg-teal-50 text-teal-700' : 'bg-rose-50 text-rose-700' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                    </span>
                </div>
                <div class="mt-3">
                    <div class="text-2xl font-black {{ $netContribution >= 0 ? 'text-teal-700' : 'text-rose-600' }} tracking-tight">
                        {{ $netContribution >= 0 ? '+' : '' }}₹{{ number_format($netContribution, 2) }}
                    </div>
                    <div class="mt-1 flex items-center justify-between text-xs text-slate-500">
                        <span>Sales vs Purchases</span>
                        @if($interCompany['count'] > 0)
                            <span class="text-purple-700 font-semibold" title="Inter-Company internal transfers excluded from external revenues">Inter-Co: ₹{{ number_format($interCompany['volume'], 2) }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Sister Concern Comparison Tables --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- 1. Sales by Sister Concern --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-purple-50 rounded-xl text-purple-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Sales by Sister Concern</h3>
                            <p class="text-xs text-slate-500">Distribution of sales revenue across entities</p>
                        </div>
                    </div>
                    <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->query(), ['report_type' => 'sales'])) }}"
                       class="text-xs font-bold text-purple-700 hover:text-purple-800 flex items-center gap-1">
                        <span>View Sales</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <th class="py-3 px-4">Company</th>
                                <th class="py-3 px-3 text-center">Invoices</th>
                                <th class="py-3 px-3 text-right">Taxable (₹)</th>
                                <th class="py-3 px-3 text-right">Total Sales (₹)</th>
                                <th class="py-3 px-4 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($salesByCompany as $sc)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        <div class="truncate max-w-[180px]">{{ $sc['company_name'] }}</div>
                                        @if($sc['gstin'])
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $sc['gstin'] }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center text-slate-600 font-medium">{{ $sc['invoices_count'] }}</td>
                                    <td class="py-3 px-3 text-right text-slate-700 font-mono">{{ number_format($sc['taxable_amount'], 2) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-purple-900 font-mono">₹{{ number_format($sc['total_sales'], 2) }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="font-bold text-slate-700">{{ $sc['sales_share'] }}%</span>
                                            <div class="w-12 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $sc['sales_share'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-medium">No sales recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 2. Purchases by Sister Concern --}}
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden flex flex-col">
                <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                    <div class="flex items-center gap-2.5">
                        <div class="p-2 bg-indigo-50 rounded-xl text-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-sm font-bold text-slate-900">Purchases by Sister Concern</h3>
                            <p class="text-xs text-slate-500">Procurement and inventory cost across entities</p>
                        </div>
                    </div>
                    <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->query(), ['report_type' => 'purchases'])) }}"
                       class="text-xs font-bold text-indigo-700 hover:text-indigo-800 flex items-center gap-1">
                        <span>View Purchases</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
                <div class="overflow-x-auto flex-1">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <th class="py-3 px-4">Company</th>
                                <th class="py-3 px-3 text-center">Invoices</th>
                                <th class="py-3 px-3 text-right">Taxable (₹)</th>
                                <th class="py-3 px-3 text-right">Total Purchases (₹)</th>
                                <th class="py-3 px-4 text-right">Share</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($purchasesByCompany as $pc)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-3 px-4 font-semibold text-slate-900">
                                        <div class="truncate max-w-[180px]">{{ $pc['company_name'] }}</div>
                                        @if($pc['gstin'])
                                            <div class="text-[10px] text-slate-400 font-mono">{{ $pc['gstin'] }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 text-center text-slate-600 font-medium">{{ $pc['invoices_count'] }}</td>
                                    <td class="py-3 px-3 text-right text-slate-700 font-mono">{{ number_format($pc['taxable_amount'], 2) }}</td>
                                    <td class="py-3 px-3 text-right font-bold text-indigo-900 font-mono">₹{{ number_format($pc['total_purchases'], 2) }}</td>
                                    <td class="py-3 px-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <span class="font-bold text-slate-700">{{ $pc['purchase_share'] }}%</span>
                                            <div class="w-12 bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                                <div class="bg-indigo-600 h-1.5 rounded-full" style="width: {{ $pc['purchase_share'] }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 font-medium">No purchases recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Monthly Sales & Purchase Trends --}}
        @if(!empty($monthlyTrends))
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Monthly Performance Trends</h3>
                        <p class="text-xs text-slate-500">Consolidated Sales vs Purchases month-by-month for {{ $selectedFy }}</p>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-purple-600 rounded"></span><span>Sales</span></div>
                        <div class="flex items-center gap-1.5"><span class="w-3 h-3 bg-indigo-400 rounded"></span><span>Purchases</span></div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                                <th class="py-2.5 px-3">Month</th>
                                <th class="py-2.5 px-3 text-right">Sales (₹)</th>
                                <th class="py-2.5 px-3 text-right">Purchases (₹)</th>
                                <th class="py-2.5 px-3 text-right">Net Margin (₹)</th>
                                <th class="py-2.5 px-4">Relative Comparison</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($monthlyTrends as $m)
                                @php
                                    $maxMVal = max(1.0, max(collect($monthlyTrends)->pluck('sales')->max(), collect($monthlyTrends)->pluck('purchases')->max()));
                                    $sPct = round(($m['sales'] / $maxMVal) * 100);
                                    $pPct = round(($m['purchases'] / $maxMVal) * 100);
                                @endphp
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="py-2.5 px-3 font-semibold text-slate-800">{{ $m['month_label'] }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-purple-900 font-mono">₹{{ number_format($m['sales'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold text-indigo-900 font-mono">₹{{ number_format($m['purchases'], 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-bold {{ $m['net'] >= 0 ? 'text-emerald-600' : 'text-rose-600' }} font-mono">
                                        {{ $m['net'] >= 0 ? '+' : '' }}₹{{ number_format($m['net'], 2) }}
                                    </td>
                                    <td class="py-2.5 px-4">
                                        <div class="space-y-1 max-w-xs">
                                            <div class="flex items-center gap-1.5">
                                                <div class="bg-purple-600 h-1.5 rounded-full" style="width: {{ $sPct }}%"></div>
                                            </div>
                                            <div class="flex items-center gap-1.5">
                                                <div class="bg-indigo-400 h-1.5 rounded-full" style="width: {{ $pPct }}%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- VIEW 2: CONSOLIDATED SALES REPORT --}}
    @if($reportType === 'sales')
        {{-- Sales Metrics Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Sales</span>
                <span class="block text-lg font-black text-purple-900 mt-1 font-mono">₹{{ number_format($salesSummary['total_sales'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Invoices</span>
                <span class="block text-lg font-black text-slate-900 mt-1 font-mono">{{ $salesSummary['total_invoices'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Taxable Value</span>
                <span class="block text-lg font-black text-slate-800 mt-1 font-mono">₹{{ number_format($salesSummary['taxable_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total GST</span>
                <span class="block text-lg font-black text-slate-800 mt-1 font-mono">₹{{ number_format($salesSummary['tax_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Collections</span>
                <span class="block text-lg font-black text-emerald-600 mt-1 font-mono">₹{{ number_format($salesSummary['collected_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Outstanding</span>
                <span class="block text-lg font-black text-rose-600 mt-1 font-mono">₹{{ number_format($salesSummary['outstanding_amount'], 2) }}</span>
            </div>
        </div>

        {{-- Sales Search and Status Toolbar --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('organization.sister-concerns.reports') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                <input type="hidden" name="business_group_id" value="{{ $selectedGroup?->id }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId ?? 'all' }}">
                <input type="hidden" name="report_type" value="sales">
                <input type="hidden" name="financial_year" value="{{ $selectedFy }}">
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">

                <div class="relative w-full sm:w-72">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search invoice #, party, product, company..."
                           class="w-full text-xs font-medium pl-8 pr-3 py-2 rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <select name="status" onchange="this.form.submit()" class="text-xs font-medium rounded-xl border-slate-200 py-2 focus:border-purple-500 focus:ring focus:ring-purple-200">
                    <option value="">All Statuses</option>
                    <option value="issued" {{ $selectedStatus === 'issued' ? 'selected' : '' }}>Issued</option>
                    <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="partial" {{ $selectedStatus === 'partial' ? 'selected' : '' }}>Partial</option>
                    <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <button type="submit" class="px-3.5 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold transition">Search</button>
                @if($search || $selectedStatus)
                    <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->except(['search', 'status']), ['report_type' => 'sales'])) }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Consolidated Sales Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Invoice #</th>
                            <th class="py-3 px-4">Selling Sister Concern</th>
                            <th class="py-3 px-4">Customer / Party</th>
                            <th class="py-3 px-3">Products</th>
                            <th class="py-3 px-3 text-right">Qty</th>
                            <th class="py-3 px-3 text-right">Taxable (₹)</th>
                            <th class="py-3 px-3 text-right">GST (₹)</th>
                            <th class="py-3 px-3 text-right">Invoice Total (₹)</th>
                            <th class="py-3 px-3 text-right">Paid (₹)</th>
                            <th class="py-3 px-3 text-right">Outstanding (₹)</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($salesItems as $inv)
                            @php
                                $coName = $inv->company?->name ?? $inv->selling_company?->name ?? 'Sister Concern';
                                $totalQty = $inv->items->sum('quantity');
                                $itemsSummary = $inv->items->map(fn ($it) => $it->product?->name)->filter()->unique()->join(', ');
                                $outstanding = max(0.0, (float) $inv->grand_total - (float) $inv->paid_amount);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 text-slate-600 font-mono whitespace-nowrap">{{ $inv->invoice_date?->format('d M Y') ?? '-' }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono whitespace-nowrap">{{ $inv->invoice_no }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-purple-50 text-purple-800 border border-purple-200/50">
                                        {{ $coName }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    <div class="truncate max-w-[160px]">{{ $inv->customer?->name ?? '-' }}</div>
                                    @if($inv->customer?->gstin)
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $inv->customer->gstin }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    <div class="truncate max-w-[150px]" title="{{ $itemsSummary }}">{{ $itemsSummary ?: 'Products' }}</div>
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format($totalQty, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format((float) $inv->subtotal, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format((float) $inv->tax_amount, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-purple-900 font-mono">₹{{ number_format((float) $inv->grand_total, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-600 font-mono">₹{{ number_format((float) $inv->paid_amount, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-rose-600 font-mono">₹{{ number_format($outstanding, 2) }}</td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @php
                                        $sCls = match($inv->status) {
                                            'paid' => 'bg-emerald-100 text-emerald-800',
                                            'partial' => 'bg-amber-100 text-amber-800',
                                            'issued' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-rose-100 text-rose-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $sCls }}">
                                        {{ $inv->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if(Route::has('invoices.show'))
                                        <a href="{{ route('invoices.show', $inv) }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                                            View
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="py-12 text-center text-slate-400 font-medium">No sales invoices found matching the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($salesItems && $salesItems->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $salesItems->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- VIEW 3: CONSOLIDATED PURCHASE REPORT --}}
    @if($reportType === 'purchases')
        {{-- Purchase Metrics Cards --}}
        <div class="grid grid-cols-2 lg:grid-cols-6 gap-3.5">
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Purchases</span>
                <span class="block text-lg font-black text-indigo-900 mt-1 font-mono">₹{{ number_format($purchasesSummary['total_purchases'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Purchase Invoices</span>
                <span class="block text-lg font-black text-slate-900 mt-1 font-mono">{{ $purchasesSummary['total_invoices'] }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Taxable Value</span>
                <span class="block text-lg font-black text-slate-800 mt-1 font-mono">₹{{ number_format($purchasesSummary['taxable_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total GST</span>
                <span class="block text-lg font-black text-slate-800 mt-1 font-mono">₹{{ number_format($purchasesSummary['tax_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Total Payments</span>
                <span class="block text-lg font-black text-emerald-600 mt-1 font-mono">₹{{ number_format($purchasesSummary['paid_amount'], 2) }}</span>
            </div>
            <div class="bg-white p-4 rounded-xl border border-slate-200/80 shadow-sm">
                <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400">Payables Pending</span>
                <span class="block text-lg font-black text-rose-600 mt-1 font-mono">₹{{ number_format($purchasesSummary['outstanding_amount'], 2) }}</span>
            </div>
        </div>

        {{-- Purchase Search and Status Toolbar --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 p-4 shadow-sm flex flex-wrap items-center justify-between gap-3">
            <form method="GET" action="{{ route('organization.sister-concerns.reports') }}" class="flex flex-wrap items-center gap-2.5 flex-1">
                <input type="hidden" name="business_group_id" value="{{ $selectedGroup?->id }}">
                <input type="hidden" name="company_id" value="{{ $selectedCompanyId ?? 'all' }}">
                <input type="hidden" name="report_type" value="purchases">
                <input type="hidden" name="financial_year" value="{{ $selectedFy }}">
                <input type="hidden" name="from_date" value="{{ $fromDate }}">
                <input type="hidden" name="to_date" value="{{ $toDate }}">

                <div class="relative w-full sm:w-72">
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Search purchase #, supplier, product, company..."
                           class="w-full text-xs font-medium pl-8 pr-3 py-2 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                    <svg class="w-4 h-4 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>

                <select name="status" onchange="this.form.submit()" class="text-xs font-medium rounded-xl border-slate-200 py-2 focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                    <option value="">All Statuses</option>
                    <option value="posted" {{ $selectedStatus === 'posted' ? 'selected' : '' }}>Posted</option>
                    <option value="paid" {{ $selectedStatus === 'paid' ? 'selected' : '' }}>Paid</option>
                    <option value="draft" {{ $selectedStatus === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="cancelled" {{ $selectedStatus === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>

                <button type="submit" class="px-3.5 py-2 bg-indigo-700 hover:bg-indigo-800 text-white rounded-xl text-xs font-bold transition">Search</button>
                @if($search || $selectedStatus)
                    <a href="{{ route('organization.sister-concerns.reports', array_merge(request()->except(['search', 'status']), ['report_type' => 'purchases'])) }}" class="text-xs text-slate-500 hover:text-slate-700">Clear</a>
                @endif
            </form>
        </div>

        {{-- Consolidated Purchase Table --}}
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-100">
                            <th class="py-3 px-4">Date</th>
                            <th class="py-3 px-4">Purchase #</th>
                            <th class="py-3 px-4">Purchasing Sister Concern</th>
                            <th class="py-3 px-4">Supplier</th>
                            <th class="py-3 px-3">Products</th>
                            <th class="py-3 px-3 text-right">Qty</th>
                            <th class="py-3 px-3 text-right">Taxable (₹)</th>
                            <th class="py-3 px-3 text-right">GST (₹)</th>
                            <th class="py-3 px-3 text-right">Purchase Total (₹)</th>
                            <th class="py-3 px-3 text-right">Paid (₹)</th>
                            <th class="py-3 px-3 text-right">Payable (₹)</th>
                            <th class="py-3 px-3 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($purchaseItems as $pi)
                            @php
                                $coName = $pi->company?->name ?? $pi->warehouse?->company?->name ?? 'Sister Concern';
                                $totalQty = $pi->items->sum('quantity');
                                $itemsSummary = $pi->items->map(fn ($it) => $it->product?->name)->filter()->unique()->join(', ');
                                $paid = $pi->status === 'paid' ? (float) $pi->grand_total : (float) ($pi->paid_amount ?? 0);
                                $outstanding = max(0.0, (float) $pi->grand_total - $paid);
                            @endphp
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4 text-slate-600 font-mono whitespace-nowrap">{{ $pi->invoice_date?->format('d M Y') ?? '-' }}</td>
                                <td class="py-3 px-4 font-bold text-slate-900 font-mono whitespace-nowrap">{{ $pi->invoice_no }}</td>
                                <td class="py-3 px-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-xs font-semibold bg-indigo-50 text-indigo-800 border border-indigo-200/50">
                                        {{ $coName }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 font-medium text-slate-900">
                                    <div class="truncate max-w-[160px]">{{ $pi->supplier?->name ?? '-' }}</div>
                                    @if($pi->supplier?->gstin)
                                        <div class="text-[10px] text-slate-400 font-mono">{{ $pi->supplier->gstin }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-3 text-slate-600">
                                    <div class="truncate max-w-[150px]" title="{{ $itemsSummary }}">{{ $itemsSummary ?: 'Items' }}</div>
                                </td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format($totalQty, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format((float) $pi->subtotal, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-slate-700">{{ number_format((float) $pi->tax_amount, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-indigo-900 font-mono">₹{{ number_format((float) $pi->grand_total, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-emerald-600 font-mono">₹{{ number_format($paid, 2) }}</td>
                                <td class="py-3 px-3 text-right font-bold text-rose-600 font-mono">₹{{ number_format($outstanding, 2) }}</td>
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    @php
                                        $pCls = match($pi->status) {
                                            'paid' => 'bg-emerald-100 text-emerald-800',
                                            'posted' => 'bg-blue-100 text-blue-800',
                                            'cancelled' => 'bg-rose-100 text-rose-800',
                                            default => 'bg-slate-100 text-slate-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider {{ $pCls }}">
                                        {{ $pi->status }}
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center whitespace-nowrap">
                                    @if(Route::has('purchase-invoices.show'))
                                        <a href="{{ route('purchase-invoices.show', $pi) }}" target="_blank" class="px-2.5 py-1 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg text-xs font-semibold transition">
                                            View
                                        </a>
                                    @else
                                        <span class="text-slate-400 text-xs">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="13" class="py-12 text-center text-slate-400 font-medium">No purchase records found matching the current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($purchaseItems && $purchaseItems->hasPages())
                <div class="p-4 border-t border-slate-100">
                    {{ $purchaseItems->links() }}
                </div>
            @endif
        </div>
    @endif
</div>

<script>
function consolidatedReportsApp() {
    return {
        onFyChange(event) {
            const selected = event.target.options[event.target.selectedIndex];
            const start = selected.getAttribute('data-start');
            const end = selected.getAttribute('data-end');

            if (start && end) {
                document.getElementById('from_date').value = start;
                document.getElementById('to_date').value = end;
                document.getElementById('customDatesFlag').value = '0';
            }
        },
        onDateManualChange() {
            document.getElementById('customDatesFlag').value = '1';
        }
    };
}
</script>
@endsection
