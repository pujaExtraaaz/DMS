@extends('layouts.dms')
@section('title', 'Group Dashboard · Sister Concern Consolidated Performance')

@section('content')
<div class="max-w-[1700px] mx-auto pb-16 space-y-6" x-data="groupDashboardApp()">

    {{-- HEADER & BREADCRUMB --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-400 font-semibold mb-1">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition">Home</a>
                <span>/</span>
                <span class="text-slate-600">Reports</span>
                <span>/</span>
                <span class="text-indigo-600 font-bold">Group Dashboard</span>
            </div>
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-xl bg-gradient-to-tr from-indigo-600 to-purple-600 flex items-center justify-center text-white shadow-md shadow-indigo-500/20">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black text-slate-900 tracking-tight">Sister Concern – Group Dashboard</h1>
                    <p class="text-xs text-slate-500">Centralized consolidated financial and business monitoring without merging accounting ledgers</p>
                </div>
            </div>
        </div>

        {{-- STATUS BADGES & EXPORT MENU --}}
        <div class="flex flex-wrap items-center gap-2.5">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold {{ $isCombinedView ? 'bg-purple-50 text-purple-700 border border-purple-200' : 'bg-indigo-50 text-indigo-700 border border-indigo-200' }}">
                <span class="h-2 w-2 rounded-full {{ $isCombinedView ? 'bg-purple-600 animate-pulse' : 'bg-indigo-600' }}"></span>
                <span>{{ $activeReportingStatus }}</span>
            </span>

            {{-- Export Dropdown --}}
            <div class="relative" x-data="{ exportOpen: false }">
                <button type="button" @click="exportOpen = !exportOpen" @click.outside="exportOpen = false"
                        class="inline-flex items-center gap-2 px-3.5 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-bold transition shadow-xs">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export Reports</span>
                    <svg class="w-3 h-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7 7"/></svg>
                </button>

                <div x-show="exportOpen" x-cloak
                     class="absolute right-0 mt-2 w-64 rounded-2xl bg-white p-2 shadow-xl border border-slate-200 z-50 text-xs divide-y divide-slate-100">
                    <div class="py-1">
                        <span class="block px-3 py-1 font-bold text-slate-400 uppercase text-[10px]">Consolidated Performance</span>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'summary', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Group Summary</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'profit-loss', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Consolidated P&amp;L</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'profit-loss', 'format' => 'pdf'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Consolidated P&amp;L</span>
                            <span class="text-[10px] text-purple-600 font-mono">PDF</span>
                        </a>
                    </div>
                    <div class="py-1">
                        <span class="block px-3 py-1 font-bold text-slate-400 uppercase text-[10px]">Registers</span>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'sales', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Sales Register</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'purchases', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Purchase Register</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'inter-company', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Inter-Company Txns</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'outstanding', 'format' => 'excel'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-slate-50 text-slate-700 font-semibold">
                            <span>Outstanding Statement</span>
                            <span class="text-[10px] text-emerald-600 font-mono">XLSX</span>
                        </a>
                    </div>
                    <div class="pt-1">
                        <a href="{{ route('group-dashboard.export', array_merge(request()->query(), ['report_type' => 'summary', 'format' => 'pdf'])) }}" class="flex items-center justify-between px-3 py-1.5 rounded-lg hover:bg-purple-50 text-purple-700 font-bold">
                            <span>Full PDF Report Pack</span>
                            <span class="text-[10px] text-purple-700 font-mono">PDF</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 1: GLOBAL DASHBOARD FILTERS --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <form method="GET" action="{{ route('group-dashboard') }}" id="groupDashboardFilterForm">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3 items-end">
                {{-- 1. Company Selection Dropdown --}}
                <div class="lg:col-span-2">
                    <label for="company_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Company Selection</label>
                    <select name="company_id" id="company_id" onchange="document.getElementById('groupDashboardFilterForm').submit()"
                            class="w-full text-xs font-bold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200 bg-slate-50/50">
                        <option value="all" {{ empty($selectedCompanyId) ? 'selected' : '' }}>
                            🏢 Both Companies (Combined / Consolidated)
                        </option>
                        @foreach($allCompanies as $co)
                            <option value="{{ $co->id }}" {{ $selectedCompanyId === $co->id ? 'selected' : '' }}>
                                📌 {{ $co->name }} ({{ $co->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- 2. Periodicity / Period Presets --}}
                <div>
                    <label for="period_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Period View</label>
                    <select name="period_type" id="period_type" @change="onPeriodChange($event)"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                        <option value="current_fy" {{ $periodType === 'current_fy' ? 'selected' : '' }}>Current FY ({{ $selectedFy }})</option>
                        <option value="prev_fy" {{ $periodType === 'prev_fy' ? 'selected' : '' }}>Previous FY</option>
                        <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ $periodType === 'quarterly' ? 'selected' : '' }}>Quarterly (Q1-Q4)</option>
                        <option value="yearly" {{ $periodType === 'yearly' ? 'selected' : '' }}>Calendar Year</option>
                        <option value="custom" {{ $periodType === 'custom' ? 'selected' : '' }}>Custom Dates</option>
                    </select>
                </div>

                {{-- 3. From Date --}}
                <div>
                    <label for="from_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                </div>

                {{-- 4. To Date --}}
                <div>
                    <label for="to_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-indigo-500 focus:ring focus:ring-indigo-200">
                </div>

                {{-- 5. Filter & Reset Controls --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        Apply Filter
                    </button>
                    <a href="{{ route('group-dashboard') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition" title="Reset Filters">
                        Reset
                    </a>
                </div>
            </div>

            {{-- Consolidation / Elimination Toggle Bar --}}
            <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-xs">
                <div class="flex items-center gap-2">
                    <span class="font-bold text-slate-700">Consolidation Mode:</span>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer">
                        <input type="radio" name="elimination_mode" value="after" {{ $eliminationMode === 'after' ? 'checked' : '' }}
                               onchange="document.getElementById('groupDashboardFilterForm').submit()"
                               class="text-indigo-600 focus:ring-indigo-500">
                        <span class="font-semibold text-slate-800">Consolidated After Elimination (Net External)</span>
                    </label>
                    <label class="inline-flex items-center gap-1.5 cursor-pointer ml-3">
                        <input type="radio" name="elimination_mode" value="before" {{ $eliminationMode === 'before' ? 'checked' : '' }}
                               onchange="document.getElementById('groupDashboardFilterForm').submit()"
                               class="text-indigo-600 focus:ring-indigo-500">
                        <span class="font-semibold text-slate-800">Combined Before Elimination (Gross Aggregation)</span>
                    </label>
                </div>

                @if(($interCompany['volume'] ?? 0) > 0)
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 font-medium">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Inter-Company Eliminated: <strong>₹{{ number_format($interCompany['volume'], 2) }}</strong> ({{ $interCompany['count'] }} matched transactions)</span>
                    </div>
                @endif
            </div>
        </form>
    </div>

    {{-- SECTION 2: DASHBOARD OVERVIEW – KPI CARDS --}}
    <div class="space-y-4">
        {{-- Group 1: Sales & Purchases --}}
        <div>
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500">Sales &amp; Purchases ({{ $periodLabel }})</h2>
                <span class="text-[11px] text-slate-400">Click any card to drill down into registers</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-3.5">
                {{-- KPI 1: Total Sales --}}
                <div @click="openDrilldown('sales', 'Sales Invoices')"
                     class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition cursor-pointer flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Total Sales</span>
                        <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-slate-900 font-mono">₹{{ number_format($combined['sales'], 2) }}</div>
                        @if($isCombinedView && count($comparisonCompanies) > 1)
                            <div class="mt-1 pt-1 border-t border-slate-100 text-[10px] text-slate-500 truncate space-y-0.5">
                                @foreach($comparisonCompanies as $cc)
                                    <div class="flex justify-between"><span>{{ $cc['name'] }}:</span> <strong class="font-mono">₹{{ number_format($cc['sales'], 2) }}</strong></div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- KPI 2: Total Purchases --}}
                <div @click="openDrilldown('purchases', 'Purchase Invoices')"
                     class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition cursor-pointer flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Total Purchases</span>
                        <span class="p-1.5 rounded-lg bg-blue-50 text-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-slate-900 font-mono">₹{{ number_format($combined['purchases'], 2) }}</div>
                        @if($isCombinedView && count($comparisonCompanies) > 1)
                            <div class="mt-1 pt-1 border-t border-slate-100 text-[10px] text-slate-500 truncate space-y-0.5">
                                @foreach($comparisonCompanies as $cc)
                                    <div class="flex justify-between"><span>{{ $cc['name'] }}:</span> <strong class="font-mono">₹{{ number_format($cc['purchases'], 2) }}</strong></div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                {{-- KPI 3: Sales Returns --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Sales Returns</span>
                        <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-amber-700 font-mono">₹{{ number_format($combined['credit_notes'], 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Credit Notes (Returns)</div>
                    </div>
                </div>

                {{-- KPI 4: Purchase Returns --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Purchase Returns</span>
                        <span class="p-1.5 rounded-lg bg-orange-50 text-orange-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-orange-700 font-mono">₹{{ number_format($combined['debit_notes'] ?? 0, 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Debit Notes / Adjustments</div>
                    </div>
                </div>

                {{-- KPI 5: Net Sales --}}
                <div class="bg-white p-4 rounded-2xl border border-purple-200 shadow-xs bg-purple-50/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-purple-900">Net Sales</span>
                        <span class="p-1.5 rounded-lg bg-purple-100 text-purple-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-purple-950 font-mono">₹{{ number_format($combined['net_sales'], 2) }}</div>
                        <div class="text-[11px] text-purple-700 font-semibold mt-1">Sales − Returns</div>
                    </div>
                </div>

                {{-- KPI 6: Net Purchases --}}
                <div class="bg-white p-4 rounded-2xl border border-indigo-200 shadow-xs bg-indigo-50/20 flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-indigo-900">Net Purchases</span>
                        <span class="p-1.5 rounded-lg bg-indigo-100 text-indigo-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-indigo-950 font-mono">₹{{ number_format($combined['net_purchases'] ?? $combined['purchases'], 2) }}</div>
                        <div class="text-[11px] text-indigo-700 font-semibold mt-1">Purchases − Returns</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Group 2: Profitability & Margins --}}
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Profitability &amp; Margins</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
                {{-- Gross Profit --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Gross Profit (GP)</span>
                        <span class="p-1.5 rounded-lg {{ $combined['gross_profit'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl font-black font-mono {{ $combined['gross_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $combined['gross_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['gross_profit'], 2) }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">Net Sales − COGS</div>
                    </div>
                </div>

                {{-- Gross Margin % --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Gross Profit Margin</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">%</span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl font-black font-mono {{ $combined['gross_margin'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $combined['gross_margin'] }}%
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">GP / Net Sales</div>
                    </div>
                </div>

                {{-- Net Profit --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Net Profit (NP)</span>
                        <span class="p-1.5 rounded-lg {{ $combined['net_profit'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl font-black font-mono {{ $combined['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $combined['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['net_profit'], 2) }}
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">GP − Operating Expenses</div>
                    </div>
                </div>

                {{-- Net Margin % --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Net Profit Margin</span>
                        <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md">%</span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-xl font-black font-mono {{ $combined['net_margin'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                            {{ $combined['net_margin'] }}%
                        </div>
                        <div class="text-[11px] text-slate-500 mt-1">NP / Net Sales</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Group 3: Expenses & Outstanding Balances --}}
        <div>
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Expenses &amp; Outstanding Balances</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5">
                {{-- Total Expenses --}}
                <div @click="openDrilldown('expenses', 'Operating Expenses Breakdown')"
                     class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md hover:border-indigo-300 transition cursor-pointer flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Total Expenses</span>
                        <span class="p-1.5 rounded-lg bg-amber-50 text-amber-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-slate-900 font-mono">₹{{ number_format($combined['expenses'], 2) }}</div>
                        <div class="text-[11px] text-slate-500 mt-1">Freight, Claims, Operations</div>
                    </div>
                </div>

                {{-- Receivables Outstanding --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Receivables Outstanding</span>
                        <span class="p-1.5 rounded-lg bg-rose-50 text-rose-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-rose-600 font-mono">₹{{ number_format($combined['receivables'], 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Debtors (Pending Invoices)</div>
                    </div>
                </div>

                {{-- Payables Outstanding --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Payables Outstanding</span>
                        <span class="p-1.5 rounded-lg bg-orange-50 text-orange-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-orange-700 font-mono">₹{{ number_format($combined['payables'], 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Creditors (Pending Payables)</div>
                    </div>
                </div>

                {{-- Inter-Company Receivables --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Inter-Co Receivables</span>
                        <span class="p-1.5 rounded-lg bg-purple-50 text-purple-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-purple-700 font-mono">₹{{ number_format($interCompany['receivables'] ?? 0, 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Due from Sister Entity</div>
                    </div>
                </div>

                {{-- Inter-Company Payables --}}
                <div class="bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs flex flex-col justify-between">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-slate-600">Inter-Co Payables</span>
                        <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </span>
                    </div>
                    <div class="mt-2.5">
                        <div class="text-lg font-black text-indigo-700 font-mono">₹{{ number_format($interCompany['payables'] ?? 0, 2) }}</div>
                        <div class="text-[11px] text-slate-400 mt-1">Due to Sister Entity</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 3: VISUAL CHARTS & COMPARISONS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        {{-- Chart 1: Monthly Sales Comparison (Company A vs Company B) --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Monthly Sales Comparison</h3>
                    <p class="text-xs text-slate-500">Company A vs Company B sales across the period</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 font-mono">INR (₹)</span>
            </div>
            <div class="h-64 relative">
                <canvas id="monthlySalesChart"></canvas>
            </div>
        </div>

        {{-- Chart 2: Monthly Purchases Comparison --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Monthly Purchase Comparison</h3>
                    <p class="text-xs text-slate-500">Company-wise procurement patterns across the period</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 font-mono">INR (₹)</span>
            </div>
            <div class="h-64 relative">
                <canvas id="monthlyPurchasesChart"></canvas>
            </div>
        </div>

        {{-- Chart 3: Sales vs Purchases (Group Level) --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Group Sales vs Purchases</h3>
                    <p class="text-xs text-slate-500">Revenue generation compared to procurement volume</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-purple-50 text-purple-700 font-mono">Group Trend</span>
            </div>
            <div class="h-64 relative">
                <canvas id="salesVsPurchasesChart"></canvas>
            </div>
        </div>

        {{-- Chart 4: Profitability Comparison & Expense Breakdown --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs space-y-3">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900">Profitability &amp; Expense Breakdown</h3>
                    <p class="text-xs text-slate-500">Gross vs Net profit and operating expense categories</p>
                </div>
                <span class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 font-mono">Margins</span>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 h-64 items-center">
                <div class="h-56 relative">
                    <canvas id="profitComparisonChart"></canvas>
                </div>
                <div class="h-56 relative">
                    <canvas id="expenseDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 4: SALES & PURCHASES IN-DEPTH ANALYSIS --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden" x-data="{ activeAnalysisTab: 'sales' }">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Sales &amp; Purchase Analysis</h3>
                <p class="text-xs text-slate-500">Comparative breakdowns by entity, returns, monthly, quarterly and financial year</p>
            </div>
            <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-xl text-xs font-semibold">
                <button type="button" @click="activeAnalysisTab = 'sales'"
                        :class="activeAnalysisTab === 'sales' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                    Sales Analysis
                </button>
                <button type="button" @click="activeAnalysisTab = 'purchases'"
                        :class="activeAnalysisTab === 'purchases' ? 'bg-white text-indigo-700 shadow-xs' : 'text-slate-600 hover:text-slate-900'"
                        class="px-3 py-1.5 rounded-lg transition cursor-pointer">
                    Purchase Analysis
                </button>
            </div>
        </div>

        {{-- TAB A: SALES ANALYSIS --}}
        <div x-show="activeAnalysisTab === 'sales'" class="p-5 space-y-6">
            {{-- Summary Breakdown Table --}}
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <th class="py-3 px-4">Entity / Sister Concern</th>
                            <th class="py-3 px-3 text-right">Invoices</th>
                            <th class="py-3 px-3 text-right">Gross Sales (₹)</th>
                            <th class="py-3 px-3 text-right">Sales Returns (₹)</th>
                            <th class="py-3 px-3 text-right">Net Sales (₹)</th>
                            <th class="py-3 px-3 text-right">Collected (₹)</th>
                            <th class="py-3 px-3 text-right">Outstanding (₹)</th>
                            <th class="py-3 px-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($comparisonCompanies as $cc)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $cc['name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $cc['code'] }} • GSTIN: {{ $cc['gstin'] ?: '—' }}</div>
                                </td>
                                <td class="py-3 px-3 text-right font-mono">{{ $cc['invoices_count'] }}</td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">₹{{ number_format($cc['sales'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-amber-700">₹{{ number_format($cc['credit_notes'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-purple-900">₹{{ number_format($cc['net_sales'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-emerald-700">₹{{ number_format($cc['sales'] - $cc['receivables'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-rose-600 font-bold">₹{{ number_format($cc['receivables'], 2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" @click="openDrilldown('sales', 'Sales: ' + '{{ $cc['name'] }}', '{{ $cc['company_id'] }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-[11px] font-bold transition">
                                        <span>View Register</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-purple-50/50 font-black text-purple-950 border-t-2 border-purple-200">
                            <td class="py-3 px-4">COMBINED SALES TOTAL</td>
                            <td class="py-3 px-3 text-right font-mono">{{ array_sum(array_column($comparisonCompanies, 'invoices_count')) }}</td>
                            <td class="py-3 px-3 text-right font-mono">₹{{ number_format($combined['sales'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono">₹{{ number_format($combined['credit_notes'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-purple-900">₹{{ number_format($combined['net_sales'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-800">₹{{ number_format($combined['sales'] - $combined['receivables'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-rose-700">₹{{ number_format($combined['receivables'], 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" @click="openDrilldown('sales', 'Combined Sales Register')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-purple-700 hover:bg-purple-800 text-white text-[11px] font-bold transition">
                                    <span>All Sales</span>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Quarterly & FY Comparisons for Sales --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Quarterly Sales Trend (Q1-Q4)</h4>
                    <div class="space-y-2 text-xs">
                        @foreach($quarterlyTrends as $qKey => $q)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-100 font-mono">
                                <span class="font-bold text-slate-700">{{ $q['label'] }}</span>
                                <span class="text-indigo-700 font-bold">₹{{ number_format($q['combined']['sales'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Financial Year Trend Comparison</h4>
                    <div class="space-y-2 text-xs">
                        @forelse($yearlyTrends as $fyName => $y)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-100 font-mono">
                                <span class="font-bold text-slate-700">FY {{ $fyName }}</span>
                                <div class="text-right">
                                    <span class="text-purple-700 font-bold block">₹{{ number_format($y['combined']['sales'], 2) }}</span>
                                    <span class="text-[10px] text-slate-400">Net: ₹{{ number_format($y['combined']['net_sales'], 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs">Single FY configured in database.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- TAB B: PURCHASE ANALYSIS --}}
        <div x-show="activeAnalysisTab === 'purchases'" class="p-5 space-y-6" x-cloak>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead>
                        <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                            <th class="py-3 px-4">Entity / Sister Concern</th>
                            <th class="py-3 px-3 text-right">Invoices</th>
                            <th class="py-3 px-3 text-right">Gross Purchases (₹)</th>
                            <th class="py-3 px-3 text-right">Purchase Returns (₹)</th>
                            <th class="py-3 px-3 text-right">Net Purchases (₹)</th>
                            <th class="py-3 px-3 text-right">Paid (₹)</th>
                            <th class="py-3 px-3 text-right">Outstanding (₹)</th>
                            <th class="py-3 px-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach($comparisonCompanies as $cc)
                            <tr class="hover:bg-slate-50/70 transition">
                                <td class="py-3 px-4">
                                    <div class="font-bold text-slate-900">{{ $cc['name'] }}</div>
                                    <div class="text-[10px] text-slate-400 font-mono">{{ $cc['code'] }} • GSTIN: {{ $cc['gstin'] ?: '—' }}</div>
                                </td>
                                <td class="py-3 px-3 text-right font-mono">{{ $cc['purchases_count'] }}</td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">₹{{ number_format($cc['purchases'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-orange-700">₹{{ number_format($cc['debit_notes'] ?? 0, 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono font-bold text-indigo-900">₹{{ number_format($cc['net_purchases'] ?? $cc['purchases'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-emerald-700">₹{{ number_format($cc['purchases'] - $cc['payables'], 2) }}</td>
                                <td class="py-3 px-3 text-right font-mono text-orange-600 font-bold">₹{{ number_format($cc['payables'], 2) }}</td>
                                <td class="py-3 px-4 text-center">
                                    <button type="button" @click="openDrilldown('purchases', 'Purchases: ' + '{{ $cc['name'] }}', '{{ $cc['company_id'] }}')"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-700 text-[11px] font-bold transition">
                                        <span>View Register</span>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-indigo-50/50 font-black text-indigo-950 border-t-2 border-indigo-200">
                            <td class="py-3 px-4">COMBINED PURCHASE TOTAL</td>
                            <td class="py-3 px-3 text-right font-mono">{{ array_sum(array_column($comparisonCompanies, 'purchases_count')) }}</td>
                            <td class="py-3 px-3 text-right font-mono">₹{{ number_format($combined['purchases'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono">₹{{ number_format($combined['debit_notes'] ?? 0, 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-indigo-900">₹{{ number_format($combined['net_purchases'] ?? $combined['purchases'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-emerald-800">₹{{ number_format($combined['purchases'] - $combined['payables'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-orange-700">₹{{ number_format($combined['payables'], 2) }}</td>
                            <td class="py-3 px-4 text-center">
                                <button type="button" @click="openDrilldown('purchases', 'Combined Purchase Register')"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-indigo-700 hover:bg-indigo-800 text-white text-[11px] font-bold transition">
                                    <span>All Purchases</span>
                                </button>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {{-- Quarterly & FY Comparisons for Purchases --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 pt-3 border-t border-slate-100">
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Quarterly Purchase Trend (Q1-Q4)</h4>
                    <div class="space-y-2 text-xs">
                        @foreach($quarterlyTrends as $qKey => $q)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-100 font-mono">
                                <span class="font-bold text-slate-700">{{ $q['label'] }}</span>
                                <span class="text-blue-700 font-bold">₹{{ number_format($q['combined']['purchases'], 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                    <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-2">Financial Year Trend Comparison</h4>
                    <div class="space-y-2 text-xs">
                        @forelse($yearlyTrends as $fyName => $y)
                            <div class="flex items-center justify-between p-2 rounded-lg bg-white border border-slate-100 font-mono">
                                <span class="font-bold text-slate-700">FY {{ $fyName }}</span>
                                <div class="text-right">
                                    <span class="text-indigo-700 font-bold block">₹{{ number_format($y['combined']['purchases'], 2) }}</span>
                                    <span class="text-[10px] text-slate-400">Net: ₹{{ number_format($y['combined']['net_purchases'] ?? $y['combined']['purchases'], 2) }}</span>
                                </div>
                            </div>
                        @empty
                            <p class="text-slate-400 text-xs">Single FY configured in database.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 5: PROFIT & LOSS CONSOLIDATION MATRIX --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Profit &amp; Loss Consolidation Statement</h3>
                <p class="text-xs text-slate-500">Gross profit, operating expenses, net profit and margin breakdown across entities</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200">
                    Combined Net Profit: ₹{{ number_format($combined['net_profit'], 2) }} ({{ $combined['net_margin'] }}%)
                </span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <th class="py-3 px-4">Line Item / Particulars</th>
                        @foreach($comparisonCompanies as $cc)
                            <th class="py-3 px-3 text-right">
                                <div class="font-bold text-slate-900">{{ $cc['name'] }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $cc['code'] }}</div>
                            </th>
                        @endforeach
                        <th class="py-3 px-4 text-right bg-purple-50/70 font-black text-purple-950">Combined ({{ ucfirst($eliminationMode) }} Elimination)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {{-- Row 1: Gross Sales --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">1. Gross Revenue / Sales</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">₹{{ number_format($cc['sales'], 2) }}</td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-bold text-purple-950 bg-purple-50/30">₹{{ number_format($combined['sales'], 2) }}</td>
                    </tr>

                    {{-- Row 2: Sales Returns --}}
                    <tr class="hover:bg-slate-50/60 transition text-amber-700">
                        <td class="py-2.5 px-4 font-medium pl-6">(-) Sales Returns &amp; Credit Notes</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono">{{ $cc['credit_notes'] > 0 ? '−₹' . number_format($cc['credit_notes'], 2) : '₹0.00' }}</td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono bg-purple-50/30">{{ $combined['credit_notes'] > 0 ? '−₹' . number_format($combined['credit_notes'], 2) : '₹0.00' }}</td>
                    </tr>

                    {{-- Row 3: Net Revenue --}}
                    <tr class="hover:bg-slate-50/60 transition bg-slate-50/40">
                        <td class="py-3 px-4 font-black text-slate-900">Net Sales Revenue</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-bold text-purple-900">₹{{ number_format($cc['net_sales'], 2) }}</td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-black text-purple-950 bg-purple-50/60">₹{{ number_format($combined['net_sales'], 2) }}</td>
                    </tr>

                    {{-- Row 4: Cost of Goods Sold (Purchases - Returns) --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">2. Cost of Goods Sold (Net Purchases)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-800">₹{{ number_format($cc['net_purchases'] ?? $cc['purchases'], 2) }}</td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-semibold text-purple-900 bg-purple-50/30">₹{{ number_format($combined['net_purchases'] ?? $combined['purchases'], 2) }}</td>
                    </tr>

                    {{-- Row 5: Gross Profit --}}
                    <tr class="hover:bg-slate-50/60 transition bg-emerald-50/30">
                        <td class="py-3 px-4 font-black text-emerald-950">GROSS PROFIT (Revenue − COGS)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-black {{ $cc['gross_profit'] >= 0 ? 'text-emerald-800' : 'text-rose-600' }}">
                                {{ $cc['gross_profit'] >= 0 ? '+' : '' }}₹{{ number_format($cc['gross_profit'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-black {{ $combined['gross_profit'] >= 0 ? 'text-emerald-900' : 'text-rose-700' }} bg-purple-50/60">
                            {{ $combined['gross_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['gross_profit'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 6: Gross Profit Margin % --}}
                    <tr class="hover:bg-slate-50/60 transition text-slate-600">
                        <td class="py-2.5 px-4 font-semibold pl-6">Gross Profit Margin %</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono font-bold {{ $cc['gross_margin'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $cc['gross_margin'] }}%</td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono font-bold bg-purple-50/30 {{ $combined['gross_margin'] >= 0 ? 'text-emerald-800' : 'text-rose-700' }}">{{ $combined['gross_margin'] }}%</td>
                    </tr>

                    {{-- Row 7: Operating Expenses --}}
                    <tr class="hover:bg-slate-50/60 transition text-amber-900">
                        <td class="py-3 px-4 font-bold">3. Total Operating &amp; Indirect Expenses</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-semibold">−₹{{ number_format($cc['expenses'], 2) }}</td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-bold bg-purple-50/30">−₹{{ number_format($combined['expenses'], 2) }}</td>
                    </tr>

                    {{-- Row 8: Net Profit --}}
                    <tr class="hover:bg-slate-50/60 transition bg-purple-100/40 border-t-2 border-purple-200">
                        <td class="py-3.5 px-4 font-black text-purple-950 text-sm">NET PROFIT (Gross Profit − Expenses)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3.5 px-3 text-right font-mono font-black text-sm {{ $cc['net_profit'] >= 0 ? 'text-emerald-800' : 'text-rose-600' }}">
                                {{ $cc['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($cc['net_profit'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-3.5 px-4 text-right font-mono font-black text-sm bg-purple-100 text-purple-950">
                            {{ $combined['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['net_profit'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 9: Net Margin % --}}
                    <tr class="hover:bg-slate-50/60 transition bg-purple-50/20">
                        <td class="py-2.5 px-4 font-bold text-slate-700 pl-6">Net Profit Margin %</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono font-black {{ $cc['net_margin'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $cc['net_margin'] }}%</td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono font-black bg-purple-100/60 {{ $combined['net_margin'] >= 0 ? 'text-emerald-800' : 'text-rose-700' }}">{{ $combined['net_margin'] }}%</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 6: INTER-COMPANY TRANSACTION MANAGEMENT & CONSOLIDATION ELIMINATION --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Inter-Company Transactions &amp; Elimination Register</h3>
                <p class="text-xs text-slate-500">Internal trade between sister entities with automated reconciliation and discrepancy matching</p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200">
                    {{ $interCompany['matched_count'] ?? 0 }} Matched Verified
                </span>
                @if(($interCompany['unmatched_count'] ?? 0) > 0)
                    <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                        {{ $interCompany['unmatched_count'] }} Unmatched Discrepancy
                    </span>
                @endif
            </div>
        </div>

        {{-- Inter-Company KPI Pills --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 p-5 border-b border-slate-100 bg-slate-50/40">
            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-500 uppercase block">Internal Sales Eliminated</span>
                <span class="text-base font-black text-indigo-700 font-mono mt-1 block">₹{{ number_format($interCompany['sales_volume'] ?? 0, 2) }}</span>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-500 uppercase block">Internal Purchases Eliminated</span>
                <span class="text-base font-black text-blue-700 font-mono mt-1 block">₹{{ number_format($interCompany['purchases_volume'] ?? 0, 2) }}</span>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-500 uppercase block">Inter-Company Receivables Offset</span>
                <span class="text-base font-black text-purple-700 font-mono mt-1 block">₹{{ number_format($interCompany['receivables'] ?? 0, 2) }}</span>
            </div>
            <div class="p-3 bg-white rounded-xl border border-slate-200/80">
                <span class="text-[11px] font-bold text-slate-500 uppercase block">Inter-Company Payables Offset</span>
                <span class="text-base font-black text-orange-700 font-mono mt-1 block">₹{{ number_format($interCompany['payables'] ?? 0, 2) }}</span>
            </div>
        </div>

        {{-- Inter-Company Register Table --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                        <th class="py-3 px-4">Date</th>
                        <th class="py-3 px-3">Transaction / Inv No</th>
                        <th class="py-3 px-3">Selling Entity</th>
                        <th class="py-3 px-3">Purchasing Entity</th>
                        <th class="py-3 px-3">Type</th>
                        <th class="py-3 px-3 text-right">Amount (₹)</th>
                        <th class="py-3 px-3 text-right">Outstanding (₹)</th>
                        <th class="py-3 px-3 text-center">Status</th>
                        <th class="py-3 px-4 text-center">Matching Audit</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse(($interCompany['all_transactions'] ?? []) as $tx)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3 px-4 font-mono">{{ $tx['transaction_date'] }}</td>
                            <td class="py-3 px-3 font-mono font-bold text-slate-900">{{ $tx['transaction_no'] }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $tx['selling_company_name'] }}</td>
                            <td class="py-3 px-3 font-semibold text-slate-800">{{ $tx['purchasing_company_name'] }}</td>
                            <td class="py-3 px-3 text-slate-500">{{ $tx['transaction_type'] }}</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-900">₹{{ number_format($tx['net_amount'], 2) }}</td>
                            <td class="py-3 px-3 text-right font-mono text-rose-600">₹{{ number_format($tx['outstanding_amount'], 2) }}</td>
                            <td class="py-3 px-3 text-center">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ strtolower($tx['status']) === 'approved' || strtolower($tx['status']) === 'settled' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $tx['status'] }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-center">
                                @if($tx['match_status'] === 'Matched')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>Matched</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200" title="Flagged: Entry missing or mismatched in counterparty ledger">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                        <span>{{ $tx['match_status'] }}</span>
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 text-xs">
                                No inter-company transactions recorded between the selected sister entities in this reporting period.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 7: DRILL-DOWN MODAL --}}
    <div x-show="drilldownOpen" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl max-w-5xl w-full max-h-[85vh] shadow-2xl border border-slate-200 flex flex-col overflow-hidden"
             @click.outside="drilldownOpen = false">
            {{-- Modal Header --}}
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                <div>
                    <h3 class="text-base font-bold text-slate-900" x-text="drilldownTitle"></h3>
                    <p class="text-xs text-slate-500">Showing detailed transactions (<span x-text="drilldownRows.length"></span> records)</p>
                </div>
                <button type="button" @click="drilldownOpen = false" class="p-1.5 rounded-xl hover:bg-slate-200 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Modal Content --}}
            <div class="p-6 overflow-y-auto flex-1">
                <div x-show="drilldownLoading" class="py-12 text-center text-slate-400">
                    <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600 mb-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                    </svg>
                    <span>Loading transactions...</span>
                </div>

                <div x-show="!drilldownLoading && drilldownRows.length === 0" class="py-12 text-center text-slate-400 text-xs">
                    No transaction records found matching the selected criteria.
                </div>

                {{-- Sales & Purchases Drilldown Table --}}
                <div x-show="!drilldownLoading && drilldownRows.length > 0 && (drilldownType === 'sales' || drilldownType === 'purchases')">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <th class="py-2.5 px-3">Date</th>
                                <th class="py-2.5 px-3">Invoice No</th>
                                <th class="py-2.5 px-3">Entity</th>
                                <th class="py-2.5 px-3">Party Name</th>
                                <th class="py-2.5 px-3">GSTIN</th>
                                <th class="py-2.5 px-3 text-right">Taxable</th>
                                <th class="py-2.5 px-3 text-right">Tax</th>
                                <th class="py-2.5 px-3 text-right">Total (₹)</th>
                                <th class="py-2.5 px-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <template x-for="r in drilldownRows" :key="r.number">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-2.5 px-3 font-mono" x-text="r.date"></td>
                                    <td class="py-2.5 px-3 font-mono font-bold text-slate-900" x-text="r.number"></td>
                                    <td class="py-2.5 px-3 font-semibold text-slate-800" x-text="r.company"></td>
                                    <td class="py-2.5 px-3" x-text="r.party"></td>
                                    <td class="py-2.5 px-3 font-mono text-[11px]" x-text="r.gstin"></td>
                                    <td class="py-2.5 px-3 text-right font-mono" x-text="'₹' + Number(r.taxable).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                    <td class="py-2.5 px-3 text-right font-mono" x-text="'₹' + Number(r.tax).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900" x-text="'₹' + Number(r.total).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                    <td class="py-2.5 px-3 text-center">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-700" x-text="r.status"></span>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>

                {{-- Expenses Drilldown Table --}}
                <div x-show="!drilldownLoading && drilldownRows.length > 0 && drilldownType === 'expenses'">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 text-slate-600 font-bold border-b border-slate-200">
                                <th class="py-3 px-4">Expense Category</th>
                                <th class="py-3 px-4 text-center">Transaction Count</th>
                                <th class="py-3 px-4 text-right">Total Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            <template x-for="r in drilldownRows" :key="r.category">
                                <tr class="hover:bg-slate-50">
                                    <td class="py-3 px-4 font-bold text-slate-900" x-text="r.category"></td>
                                    <td class="py-3 px-4 text-center font-mono" x-text="r.count"></td>
                                    <td class="py-3 px-4 text-right font-mono font-bold text-slate-900" x-text="'₹' + Number(r.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})"></td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3 border-t border-slate-100 bg-slate-50/50 flex justify-end">
                <button type="button" @click="drilldownOpen = false" class="px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-700 rounded-xl text-xs font-bold transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

{{-- CHART.JS INTEGRATION SCRIPT --}}
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function groupDashboardApp() {
    return {
        drilldownOpen: false,
        drilldownLoading: false,
        drilldownTitle: '',
        drilldownType: '',
        drilldownRows: [],

        onPeriodChange(event) {
            const val = event.target.value;
            const form = document.getElementById('groupDashboardFilterForm');
            if (val !== 'custom') {
                form.submit();
            }
        },

        async openDrilldown(type, title, companyId = '') {
            this.drilldownType = type;
            this.drilldownTitle = title;
            this.drilldownLoading = true;
            this.drilldownRows = [];
            this.drilldownOpen = true;

            try {
                const params = new URLSearchParams({
                    type: type,
                    company_id: companyId || '{{ $selectedCompanyId ?: "all" }}',
                    from_date: '{{ $fromDate }}',
                    to_date: '{{ $toDate }}'
                });
                const res = await fetch(`{{ route('group-dashboard.drilldown') }}?${params.toString()}`);
                const data = await res.json();
                if (data.success) {
                    this.drilldownRows = data.rows || [];
                }
            } catch (err) {
                console.error('Failed to load drilldown:', err);
            } finally {
                this.drilldownLoading = false;
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const chartPayload = @json($chartData);
    const months = chartPayload.months || [];

    // 1. Monthly Sales Chart
    const ctxSales = document.getElementById('monthlySalesChart');
    if (ctxSales && typeof Chart !== 'undefined' && months.length > 0) {
        new Chart(ctxSales, {
            type: 'bar',
            data: {
                labels: months,
                datasets: chartPayload.sales_datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.parsed.y).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function (val) {
                                return '₹' + (val >= 100000 ? (val / 100000).toFixed(1) + 'L' : val.toLocaleString('en-IN'));
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Monthly Purchases Chart
    const ctxPurchases = document.getElementById('monthlyPurchasesChart');
    if (ctxPurchases && typeof Chart !== 'undefined' && months.length > 0) {
        new Chart(ctxPurchases, {
            type: 'bar',
            data: {
                labels: months,
                datasets: chartPayload.purchases_datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.parsed.y).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function (val) {
                                return '₹' + (val >= 100000 ? (val / 100000).toFixed(1) + 'L' : val.toLocaleString('en-IN'));
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Group Sales vs Purchases Chart
    const ctxVs = document.getElementById('salesVsPurchasesChart');
    if (ctxVs && typeof Chart !== 'undefined' && months.length > 0) {
        new Chart(ctxVs, {
            type: 'line',
            data: {
                labels: months,
                datasets: [
                    {
                        label: 'Group Sales',
                        data: chartPayload.group_sales,
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.1)',
                        fill: true,
                        tension: 0.3
                    },
                    {
                        label: 'Group Purchases',
                        data: chartPayload.group_purchases,
                        borderColor: '#f59e0b',
                        backgroundColor: 'rgba(245, 158, 11, 0.1)',
                        fill: true,
                        tension: 0.3
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.parsed.y).toLocaleString('en-IN');
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        ticks: {
                            callback: function (val) {
                                return '₹' + (val >= 100000 ? (val / 100000).toFixed(1) + 'L' : val.toLocaleString('en-IN'));
                            }
                        }
                    }
                }
            }
        });
    }

    // 4. Profitability Bar Chart
    const ctxProfit = document.getElementById('profitComparisonChart');
    if (ctxProfit && typeof Chart !== 'undefined' && chartPayload.profit_labels.length > 0) {
        new Chart(ctxProfit, {
            type: 'bar',
            data: {
                labels: chartPayload.profit_labels,
                datasets: [
                    {
                        label: 'Gross Profit',
                        data: chartPayload.gross_profits,
                        backgroundColor: '#10b981'
                    },
                    {
                        label: 'Net Profit',
                        data: chartPayload.net_profits,
                        backgroundColor: '#8b5cf6'
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 10, font: { size: 10 } } }
                }
            }
        });
    }

    // 5. Expense Donut Chart
    const ctxExpense = document.getElementById('expenseDonutChart');
    if (ctxExpense && typeof Chart !== 'undefined' && chartPayload.expense_labels.length > 0) {
        new Chart(ctxExpense, {
            type: 'doughnut',
            data: {
                labels: chartPayload.expense_labels,
                datasets: [{
                    data: chartPayload.expense_values,
                    backgroundColor: ['#6366f1', '#ec4899', '#f59e0b', '#3b82f6', '#14b8a6']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 10, font: { size: 9 } } }
                }
            }
        });
    }
});
</script>
@endsection
