@extends('layouts.dms')
@section('title', 'Sister Concern Management · Multi-Business Performance Dashboard')

@section('content')
<div class="max-w-7xl mx-auto pb-12 space-y-6" x-data="sisterConcernDashboardApp()">
    {{-- Header Banner & Navigation Tabs --}}
    @include('organization.sister-concerns.partials.header', [
        'activeTab' => 'dashboard',
        'tabCounts' => $tabCounts,
    ])

    {{-- SECTION 1: COMMON PROPRIETOR MANAGEMENT HEADER --}}
    <div class="bg-gradient-to-br from-slate-900 via-purple-950 to-indigo-950 rounded-2xl p-6 sm:p-7 text-white shadow-lg border border-purple-800/40 relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-64 h-64 bg-purple-500/10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div class="flex items-start sm:items-center gap-4">
                <div class="h-16 w-16 rounded-2xl bg-gradient-to-tr from-purple-600 to-indigo-500 flex items-center justify-center text-white font-black text-2xl shadow-md border border-white/20 shrink-0">
                    {{ substr($selectedGroup?->proprietor_title ?: 'Vivek Gupta', 0, 1) }}
                </div>
                <div>
                    <div class="inline-flex items-center gap-2 px-2.5 py-0.5 rounded-full text-xs font-bold bg-purple-500/20 text-purple-200 border border-purple-400/30 uppercase tracking-wider mb-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <span>Common Proprietor</span>
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white flex items-center gap-2">
                        <span>{{ $selectedGroup?->proprietor_title ?: 'Mr. Vivek Gupta' }}</span>
                        @if($selectedGroup?->proprietor_pan)
                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-white/10 text-purple-200 border border-white/10 font-normal">PAN: {{ $selectedGroup->proprietor_pan }}</span>
                        @endif
                    </h2>
                    <p class="mt-1 text-sm text-purple-200 flex items-center gap-2">
                        <span class="font-medium">Relationship:</span>
                        <span class="text-white font-semibold">{{ $selectedGroup?->proprietor_relation ?: 'Common ownership and business oversight' }}</span>
                        <span class="text-purple-400">•</span>
                        <span>Group: <strong>{{ $selectedGroup?->name }}</strong></span>
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                @if($businessGroups->count() > 1)
                    <form method="GET" action="{{ route('organization.sister-concerns.dashboard') }}" class="inline-flex">
                        <select name="business_group_id" onchange="this.form.submit()"
                                class="bg-white/10 text-white text-xs font-semibold rounded-xl border border-white/20 px-3 py-2 focus:ring-purple-400 focus:bg-slate-900">
                            @foreach($businessGroups as $bg)
                                <option value="{{ $bg->id }}" class="text-slate-900" {{ $selectedGroup && $selectedGroup->id === $bg->id ? 'selected' : '' }}>
                                    {{ $bg->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                @endif

                <button type="button" @click="openEditProprietorModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/20 px-3.5 py-2 text-xs font-semibold text-white transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    <span>Edit Proprietor</span>
                </button>

                <button type="button" @click="openAddBusinessModal()"
                        class="inline-flex items-center gap-2 rounded-xl bg-purple-500 hover:bg-purple-600 px-4 py-2 text-xs font-bold text-white shadow-md transition cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>+ Add Business</span>
                </button>
            </div>
        </div>
    </div>

    {{-- SECTION 2: ASSOCIATED BUSINESSES (CARDS UNDER COMMON PROPRIETOR) --}}
    <div class="space-y-3">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-bold text-slate-900">Associated Businesses & Sister Concerns</h3>
                <p class="text-xs text-slate-500">Entities operating under common ownership of {{ $selectedGroup?->proprietor_title ?: 'Mr. Vivek Gupta' }}</p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200">
                {{ $groupCompanies->count() }} Entities Associated
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
            @forelse($groupCompanies as $co)
                @php
                    $isProposed = str_contains(strtolower($co->business_type), 'proposed');
                    $badgeBg = $isProposed ? 'bg-blue-50 text-blue-700 border-blue-200' : 'bg-emerald-50 text-emerald-700 border-emerald-200';
                @endphp
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs hover:shadow-md transition flex flex-col justify-between overflow-hidden">
                    <div class="p-5 border-b border-slate-100 space-y-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <div class="h-11 w-11 rounded-xl bg-gradient-to-tr from-purple-100 to-indigo-50 text-purple-700 font-bold flex items-center justify-center text-sm border border-purple-200">
                                    {{ substr($co->name, 0, 2) }}
                                </div>
                                <div>
                                    <h4 class="text-base font-bold text-slate-900 leading-tight">{{ $co->name }}</h4>
                                    <span class="text-xs text-slate-400 font-mono">{{ $co->code }}</span>
                                </div>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-bold border {{ $badgeBg }}">
                                {{ $co->business_type ?: 'Existing Business' }}
                            </span>
                        </div>

                        {{-- Main Work / Nature of Business --}}
                        <div class="p-2.5 rounded-xl bg-slate-50 border border-slate-100 text-xs">
                            <span class="font-bold text-slate-600 block mb-0.5">Main Work / Business Activity:</span>
                            <p class="text-slate-800 font-medium leading-relaxed">
                                {{ $co->main_work ?: 'AV / IT equipment, displays, projectors and audio solutions' }}
                            </p>
                        </div>

                        {{-- Credentials Grid --}}
                        <div class="grid grid-cols-2 gap-2 text-xs pt-1">
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">GSTIN</span>
                                <span class="font-mono font-semibold text-slate-800 text-[11px] truncate block" title="{{ $co->gstin }}">
                                    {{ $co->gstin ?: 'Pending / Unregistered' }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Registration</span>
                                <span class="font-semibold text-slate-800 text-[11px] truncate block" title="{{ $co->cin ?: $co->pan }}">
                                    {{ $co->pan ? 'PAN: ' . $co->pan : ($co->cin ? 'CIN: ' . $co->cin : 'Reg: ' . $co->code) }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Location</span>
                                <span class="font-semibold text-slate-800 text-[11px] truncate block">
                                    {{ $co->city ? $co->city . ', ' . $co->state : ($co->state ?: 'India') }}
                                </span>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px] uppercase font-bold">Bank Account</span>
                                <div class="font-semibold text-slate-800 text-[11px] truncate" title="{{ $co->bank_name }}: {{ $co->bank_account_no }} (IFSC: {{ $co->bank_ifsc }})">
                                    @if($co->bank_name)
                                        <span>{{ $co->bank_name }} ({{ $co->bank_account_no }})</span>
                                        @if($co->bank_ifsc)
                                            <span class="text-[10px] text-slate-400 font-mono block">IFSC: {{ $co->bank_ifsc }}</span>
                                        @endif
                                    @else
                                        <span class="text-slate-400 font-normal">Bank Configured</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions Bar --}}
                    <div class="px-5 py-3 bg-slate-50/70 border-t border-slate-100 flex items-center justify-between">
                        <a href="{{ route('organization.companies.edit', $co) }}" class="text-xs font-bold text-purple-700 hover:text-purple-800 flex items-center gap-1">
                            <span>Manage Business</span>
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                        <a href="{{ route('invoices.create', ['company_id' => $co->id]) }}" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-white border border-slate-200 text-xs font-semibold text-slate-700 hover:bg-purple-50 hover:text-purple-700 transition">
                            <svg class="w-3 h-3 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Invoice</span>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                    <p class="text-slate-500 text-sm">No businesses currently associated with this proprietor.</p>
                    <button type="button" @click="openAddBusinessModal()" class="mt-3 inline-flex items-center gap-2 px-4 py-2 bg-purple-700 text-white rounded-xl text-xs font-bold">
                        <span>+ Add Associated Business</span>
                    </button>
                </div>
            @endforelse

            {{-- Quick Add Business Action Card --}}
            <div @click="openAddBusinessModal()" class="border-2 border-dashed border-purple-200 hover:border-purple-400 bg-purple-50/30 hover:bg-purple-50/60 rounded-2xl p-6 flex flex-col items-center justify-center text-center cursor-pointer transition min-h-[200px]">
                <div class="h-12 w-12 rounded-2xl bg-purple-100 text-purple-700 flex items-center justify-center font-bold mb-3 shadow-xs">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </div>
                <h4 class="text-sm font-bold text-slate-900">+ Associate New Business</h4>
                <p class="text-xs text-slate-500 mt-1 max-w-xs">Register a new entity or link an existing sister concern under {{ $selectedGroup?->proprietor_title ?: 'Mr. Vivek Gupta' }}</p>
            </div>
        </div>
    </div>

    {{-- SECTION 3: CONSOLIDATED PERFORMANCE DASHBOARD & FILTERS --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs space-y-4">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Consolidated Business Performance</h3>
                <p class="text-xs text-slate-500">Financial metrics calculated dynamically across selected sister concerns ({{ $periodLabel }})</p>
            </div>

            {{-- Export Actions --}}
            <div class="flex items-center gap-2">
                <span class="text-xs font-bold text-slate-400">Export:</span>
                <a href="{{ route('organization.sister-concerns.reports.export', array_merge(request()->query(), ['report_type' => 'all', 'format' => 'csv'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-xl text-xs font-semibold transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>CSV</span>
                </a>
                <a href="{{ route('organization.sister-concerns.reports.export', array_merge(request()->query(), ['report_type' => 'all', 'format' => 'excel'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-200/60 rounded-xl text-xs font-semibold transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Excel</span>
                </a>
                <a href="{{ route('organization.sister-concerns.reports.export', array_merge(request()->query(), ['report_type' => 'all', 'format' => 'pdf'])) }}"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-purple-50 hover:bg-purple-100 text-purple-800 border border-purple-200/60 rounded-xl text-xs font-semibold transition shadow-xs">
                    <svg class="w-3.5 h-3.5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    <span>PDF</span>
                </a>
            </div>
        </div>

        {{-- Filters Form --}}
        <form method="GET" action="{{ route('organization.sister-concerns.dashboard') }}" id="dashboardFilterForm">
            <input type="hidden" name="business_group_id" value="{{ $selectedGroup?->id }}">

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-3 items-end">
                {{-- Business Selector --}}
                <div>
                    <label for="company_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Business Selector</label>
                    <select name="company_id" id="company_id" onchange="document.getElementById('dashboardFilterForm').submit()"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        <option value="all" {{ empty($selectedCompanyId) ? 'selected' : '' }}>
                            🏢 All Sister Concerns (Consolidated View)
                        </option>
                        @foreach($groupCompanies as $co)
                            <option value="{{ $co->id }}" {{ $selectedCompanyId === $co->id ? 'selected' : '' }}>
                                {{ $co->name }} ({{ $co->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Period Preset --}}
                <div>
                    <label for="period_type" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Reporting Period</label>
                    <select name="period_type" id="period_type" @change="onPeriodChange($event)"
                            class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        <option value="current_fy" {{ $periodType === 'current_fy' ? 'selected' : '' }}>Current Financial Year (FY {{ $selectedFy }})</option>
                        <option value="prev_fy" {{ $periodType === 'prev_fy' ? 'selected' : '' }}>Previous Financial Year</option>
                        <option value="monthly" {{ $periodType === 'monthly' ? 'selected' : '' }}>Monthly</option>
                        <option value="quarterly" {{ $periodType === 'quarterly' ? 'selected' : '' }}>Quarterly (Q1-Q4)</option>
                        <option value="yearly" {{ $periodType === 'yearly' ? 'selected' : '' }}>Full Calendar Year</option>
                        <option value="custom" {{ $periodType === 'custom' ? 'selected' : '' }}>Custom Date Range</option>
                    </select>
                </div>

                {{-- From Date --}}
                <div>
                    <label for="from_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">From Date</label>
                    <input type="date" name="from_date" id="from_date" value="{{ $fromDate }}"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                {{-- To Date --}}
                <div>
                    <label for="to_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">To Date</label>
                    <input type="date" name="to_date" id="to_date" value="{{ $toDate }}"
                           class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                {{-- Submit & Reset --}}
                <div class="flex items-center gap-2">
                    <button type="submit" class="flex-1 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold transition shadow-xs">
                        Filter
                    </button>
                    <a href="{{ route('organization.sister-concerns.dashboard') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-semibold transition">
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>

    {{-- SECTION 4: CONSOLIDATED SUMMARY CARDS (6 DYNAMIC METRIC CARDS) --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        {{-- Card 1: Combined Sales --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Sales</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black text-slate-900 tracking-tight font-mono">₹{{ number_format($combined['sales'], 2) }}</div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Net Sales</span>
                    @if($combined['credit_notes'] > 0)
                        <span class="text-amber-600 font-semibold" title="Sales Returns">−₹{{ number_format($combined['credit_notes'], 2) }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card 2: Combined Purchases --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Purchases</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black text-slate-900 tracking-tight font-mono">₹{{ number_format($combined['purchases'], 2) }}</div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Total Procurement</span>
                </div>
            </div>
        </div>

        {{-- Card 3: Combined Expenses --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Expenses</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black text-slate-900 tracking-tight font-mono">₹{{ number_format($combined['expenses'], 2) }}</div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Direct & Operating</span>
                </div>
            </div>
        </div>

        {{-- Card 4: Combined Profit --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Combined Profit</span>
                <span class="p-2 rounded-xl {{ $combined['net_profit'] >= 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black {{ $combined['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }} tracking-tight font-mono">
                    {{ $combined['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['net_profit'], 2) }}
                </div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Net Margin</span>
                    <span class="font-bold {{ $combined['net_margin'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">{{ $combined['net_margin'] }}%</span>
                </div>
            </div>
        </div>

        {{-- Card 5: Total Receivables --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Receivables</span>
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black text-rose-600 tracking-tight font-mono">₹{{ number_format($combined['receivables'], 2) }}</div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Pending Invoices</span>
                </div>
            </div>
        </div>

        {{-- Card 6: Total Payables --}}
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-xs relative overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-500">Total Payables</span>
                <span class="p-2 rounded-xl bg-orange-50 text-orange-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <div class="text-xl font-black text-orange-700 tracking-tight font-mono">₹{{ number_format($combined['payables'], 2) }}</div>
                <div class="mt-1 flex items-center justify-between text-[11px] text-slate-500">
                    <span>Vendor Dues</span>
                </div>
            </div>
        </div>
    </div>

    {{-- SECTION 5: BUSINESS-WISE COMPARISON TABLE (DYNAMIC PERFORMANCE MATRIX) --}}
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-base font-bold text-slate-900">Business-Wise Performance Comparison</h3>
                <p class="text-xs text-slate-500">Financial breakdown comparing each sister concern against consolidated totals</p>
            </div>
            @if($interCompany['count'] > 0)
                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-purple-50 text-purple-700 border border-purple-200" title="Internal inter-concern sales eliminated from consolidated totals">
                    Inter-Company Elimination: ₹{{ number_format($interCompany['volume'], 2) }} ({{ $interCompany['count'] }} txns)
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-slate-50 text-slate-500 font-semibold border-b border-slate-200">
                        <th class="py-3 px-4">Particulars</th>
                        @foreach($comparisonCompanies as $cc)
                            <th class="py-3 px-3 text-right">
                                <div class="font-bold text-slate-900 truncate max-w-[150px] inline-block">{{ $cc['name'] }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ $cc['code'] }}</div>
                            </th>
                        @endforeach
                        <th class="py-3 px-4 text-right bg-purple-50/60 font-black text-purple-950">Combined Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    {{-- Row 1: Sales (Gross) --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">Gross Sales</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800">
                                <a href="{{ route('organization.sister-concerns.reports', ['report_type' => 'sales', 'company_id' => $cc['company_id'], 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="hover:underline hover:text-purple-700">
                                    ₹{{ number_format($cc['sales'], 2) }}
                                </a>
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-black text-purple-900 bg-purple-50/40">
                            ₹{{ number_format($combined['sales'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 2: Credit Notes / Returns --}}
                    <tr class="hover:bg-slate-50/60 transition text-amber-700">
                        <td class="py-2.5 px-4 font-medium">(-) Sales Returns & Credit Notes</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono">
                                {{ $cc['credit_notes'] > 0 ? '−₹' . number_format($cc['credit_notes'], 2) : '₹0.00' }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono font-bold bg-purple-50/40">
                            {{ $combined['credit_notes'] > 0 ? '−₹' . number_format($combined['credit_notes'], 2) : '₹0.00' }}
                        </td>
                    </tr>

                    {{-- Row 3: Net Sales --}}
                    <tr class="hover:bg-slate-50/60 transition bg-slate-50/30">
                        <td class="py-3 px-4 font-bold text-slate-900">Net Sales (Revenue)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-bold text-purple-900">
                                ₹{{ number_format($cc['net_sales'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-black text-purple-950 bg-purple-50/60">
                            ₹{{ number_format($combined['net_sales'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 4: Purchases (COGS) --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">Purchases & Procurement</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-800">
                                <a href="{{ route('organization.sister-concerns.reports', ['report_type' => 'purchases', 'company_id' => $cc['company_id'], 'from_date' => $fromDate, 'to_date' => $toDate]) }}" class="hover:underline hover:text-indigo-700">
                                    ₹{{ number_format($cc['purchases'], 2) }}
                                </a>
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-bold text-indigo-900 bg-purple-50/40">
                            ₹{{ number_format($combined['purchases'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 5: Operating Expenses --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-3 px-4 font-bold text-slate-900">Operating Expenses</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-semibold text-slate-700">
                                ₹{{ number_format($cc['expenses'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-bold text-amber-900 bg-purple-50/40">
                            ₹{{ number_format($combined['expenses'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 6: Gross Profit --}}
                    <tr class="hover:bg-slate-50/60 transition bg-slate-50/40">
                        <td class="py-3 px-4 font-extrabold text-slate-900">Gross Profit (Sales − Purchases)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3 px-3 text-right font-mono font-bold {{ $cc['gross_profit'] >= 0 ? 'text-teal-700' : 'text-rose-600' }}">
                                <div>{{ $cc['gross_profit'] >= 0 ? '+' : '' }}₹{{ number_format($cc['gross_profit'], 2) }}</div>
                                <div class="text-[10px] text-slate-400 font-sans font-normal">{{ $cc['gross_margin'] }}% margin</div>
                            </td>
                        @endforeach
                        <td class="py-3 px-4 text-right font-mono font-black {{ $combined['gross_profit'] >= 0 ? 'text-teal-800' : 'text-rose-600' }} bg-purple-50/60">
                            <div>{{ $combined['gross_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['gross_profit'], 2) }}</div>
                            <div class="text-[10px] text-purple-700 font-sans font-normal">{{ $combined['gross_margin'] }}% margin</div>
                        </td>
                    </tr>

                    {{-- Row 7: Net Profit --}}
                    <tr class="hover:bg-slate-50/60 transition bg-emerald-50/20">
                        <td class="py-3.5 px-4 font-black text-slate-900">Net Profit (Gross Profit − Expenses)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-3.5 px-3 text-right font-mono font-black {{ $cc['net_profit'] >= 0 ? 'text-emerald-700' : 'text-rose-600' }}">
                                <div class="text-sm">{{ $cc['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($cc['net_profit'], 2) }}</div>
                                <div class="text-[10px] text-slate-500 font-sans font-normal">{{ $cc['net_margin'] }}% net</div>
                            </td>
                        @endforeach
                        <td class="py-3.5 px-4 text-right font-mono font-black {{ $combined['net_profit'] >= 0 ? 'text-emerald-800' : 'text-rose-700' }} bg-purple-100/50">
                            <div class="text-sm">{{ $combined['net_profit'] >= 0 ? '+' : '' }}₹{{ number_format($combined['net_profit'], 2) }}</div>
                            <div class="text-[10px] text-purple-900 font-sans font-bold">{{ $combined['net_margin'] }}% net</div>
                        </td>
                    </tr>

                    {{-- Row 8: Receivables --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-2.5 px-4 text-slate-600 font-medium">Pending Receivables (Debtors)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono text-rose-600 font-semibold">
                                ₹{{ number_format($cc['receivables'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-rose-700 bg-purple-50/40">
                            ₹{{ number_format($combined['receivables'], 2) }}
                        </td>
                    </tr>

                    {{-- Row 9: Payables --}}
                    <tr class="hover:bg-slate-50/60 transition">
                        <td class="py-2.5 px-4 text-slate-600 font-medium">Pending Payables (Creditors)</td>
                        @foreach($comparisonCompanies as $cc)
                            <td class="py-2.5 px-3 text-right font-mono text-orange-600 font-semibold">
                                ₹{{ number_format($cc['payables'], 2) }}
                            </td>
                        @endforeach
                        <td class="py-2.5 px-4 text-right font-mono font-bold text-orange-700 bg-purple-50/40">
                            ₹{{ number_format($combined['payables'], 2) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    {{-- SECTION 6: COMPARATIVE CHARTS --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Chart 1: Business-Wise Sales, Purchases & Profit --}}
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 flex flex-col justify-between">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Sister Concerns Financial Comparison</h4>
                    <p class="text-xs text-slate-500">Sales, Purchases and Net Profit by Business</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-50 text-purple-700 border border-purple-200">
                    Comparative
                </span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="businessComparisonChart"></canvas>
            </div>
        </div>

        {{-- Chart 2: Monthly Performance Trends --}}
        <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 flex flex-col justify-between">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-slate-900">Monthly Performance Trends</h4>
                    <p class="text-xs text-slate-500">Consolidated Sales vs Purchases over time</p>
                </div>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    Trends
                </span>
            </div>
            <div class="relative h-72 w-full">
                <canvas id="monthlyTrendChart"></canvas>
            </div>
        </div>
    </div>

    {{-- SECTION 7: ENTERPRISE INFRASTRUCTURE OVERVIEW --}}
    <div class="mt-8 pt-6 border-t border-slate-200">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-base font-bold text-slate-900">Enterprise Infrastructure & Operations</h3>
                <p class="text-xs text-slate-500">Overall group footprint across business units, facilities, and staff</p>
            </div>
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700">
                Network Footprint
            </span>
        </div>

        {{-- 8 Infrastructure Summary Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {{-- Card 1: Total Business Groups --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Business Groups</span>
                    <div class="h-9 w-9 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['business_groups_count']) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700">Groups</span>
                </div>
            </div>

            {{-- Card 2: Total Sister Concerns --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Sister Concerns</span>
                    <div class="h-9 w-9 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['companies_count']) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700">Entities</span>
                </div>
            </div>

            {{-- Card 3: Total Head Offices --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Head Offices</span>
                    <div class="h-9 w-9 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['head_offices_count']) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700">HQ Sites</span>
                </div>
            </div>

            {{-- Card 4: Total Turnover --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Turnover</span>
                    <div class="h-9 w-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900 font-mono">₹{{ number_format($stats['total_turnover'], 2) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700">FY {{ $selectedFy }}</span>
                </div>
            </div>

            {{-- Card 5: Total Employees --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Employees</span>
                    <div class="h-9 w-9 rounded-xl bg-cyan-50 text-cyan-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['employees_count'] ?? $stats['total_employees'] ?? 0) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-50 text-cyan-700">Active</span>
                </div>
            </div>

            {{-- Card 6: Active Transactions --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Active Transactions</span>
                    <div class="h-9 w-9 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['active_transactions_count']) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700">Open</span>
                </div>
            </div>

            {{-- Card 7: Total Transaction Value --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Transaction Value</span>
                    <div class="h-9 w-9 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900 font-mono">₹{{ number_format($stats['total_transaction_value'], 2) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-violet-50 text-violet-700">Inter-Co</span>
                </div>
            </div>

            {{-- Card 8: Settled Transactions --}}
            <div class="bg-white rounded-2xl p-5 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="flex items-center justify-between">
                    <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Settled Transactions</span>
                    <div class="h-9 w-9 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </div>
                </div>
                <div class="mt-3 flex items-baseline justify-between">
                    <span class="text-2xl font-bold tracking-tight text-slate-900">{{ number_format($stats['settled_transactions_count']) }}</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-teal-50 text-teal-700">Settled</span>
                </div>
            </div>
        </div>

        {{-- 2 Group-Level Footprint Charts --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Group-wise Turnover (₹ Lakhs)</h4>
                        <p class="text-xs text-slate-500">Distribution across business groups</p>
                    </div>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="groupTurnoverChart"></canvas>
                </div>
            </div>

            <div class="bg-white rounded-2xl p-6 shadow-xs border border-slate-200/80 flex flex-col justify-between">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-900">Entity Type Distribution</h4>
                        <p class="text-xs text-slate-500">Breakdown of head offices, branches and warehouses</p>
                    </div>
                </div>
                <div class="relative h-64 w-full">
                    <canvas id="entityDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL 1: EDIT COMMON PROPRIETOR MODAL --}}
    <div x-show="editProprietorModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="editProprietorModal = false"
             class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-purple-100 text-purple-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Edit Common Proprietor Details</h3>
                        <p class="text-xs text-slate-500">Owner identity for {{ $selectedGroup?->name }}</p>
                    </div>
                </div>
                <button type="button" @click="editProprietorModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('organization.sister-concerns.proprietor.update') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="business_group_id" value="{{ $selectedGroup?->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Proprietor Name *</label>
                    <input type="text" name="proprietor_name" required
                           value="{{ $selectedGroup?->proprietor_name ?: ($selectedGroup?->parentCompany?->legal_name ?: 'Mr. Vivek Gupta') }}"
                           class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Relationship / Role</label>
                    <input type="text" name="proprietor_relationship"
                           value="{{ $selectedGroup?->proprietor_relationship ?: 'Common ownership and business oversight' }}"
                           class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Email Address</label>
                        <input type="email" name="proprietor_email" value="{{ $selectedGroup?->proprietor_email }}"
                               class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1">Phone Number</label>
                        <input type="text" name="proprietor_phone" value="{{ $selectedGroup?->proprietor_phone }}"
                               class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">PAN Card Number</label>
                    <input type="text" name="proprietor_pan" maxlength="10" value="{{ $selectedGroup?->proprietor_pan }}"
                           class="w-full text-xs font-mono uppercase rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" @click="editProprietorModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-xs">
                        Save Proprietor Details
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- MODAL 2: ADD / ASSOCIATE SISTER CONCERN MODAL --}}
    <div x-show="addBusinessModal" x-cloak
         class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4">
        <div @click.away="addBusinessModal = false"
             class="bg-white rounded-2xl max-w-xl w-full p-6 shadow-2xl border border-slate-200 space-y-5 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div class="flex items-center gap-2.5">
                    <div class="p-2 bg-purple-100 text-purple-700 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Associate Business with Proprietor</h3>
                        <p class="text-xs text-slate-500">Add an existing entity or configure business activity</p>
                    </div>
                </div>
                <button type="button" @click="addBusinessModal = false" class="text-slate-400 hover:text-slate-600 text-xl font-bold">✕</button>
            </div>

            <form method="POST" action="{{ route('organization.sister-concerns.associate-business') }}" class="space-y-4">
                @csrf
                <input type="hidden" name="business_group_id" value="{{ $selectedGroup?->id }}">

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Select Company / Business *</label>
                    <select name="company_id" required class="w-full text-xs font-semibold rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        @foreach(\App\Domains\Organization\Models\Company::where('is_active', true)->orderBy('name')->get() as $co)
                            <option value="{{ $co->id }}" {{ $groupCompanies->contains('id', $co->id) ? 'selected' : '' }}>
                                {{ $co->name }} ({{ $co->code }}) — {{ $co->business_type ?: 'Existing Business' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Business Type / Status</label>
                    <select name="business_type" class="w-full text-xs font-medium rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200">
                        <option value="Existing Business">Existing Business</option>
                        <option value="Proposed New Business">Proposed New Business</option>
                        <option value="Subsidiary">Subsidiary</option>
                        <option value="Associate Entity">Associate Entity</option>
                        <option value="Branch Office">Branch Office</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Main Work / Business Activity *</label>
                    <textarea name="main_work" rows="3" required placeholder="e.g. AV / IT equipment, displays, projectors and audio solutions"
                              class="w-full text-xs rounded-xl border-slate-200 focus:border-purple-500 focus:ring focus:ring-purple-200"></textarea>
                </div>

                <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                    <a href="{{ route('organization.companies.create', ['business_group_id' => $selectedGroup?->id, 'redirect_to' => 'sister-concerns']) }}"
                       class="text-xs font-bold text-purple-700 hover:text-purple-800 flex items-center gap-1">
                        <span>Or create entirely new company →</span>
                    </a>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="addBusinessModal = false" class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-xs font-semibold hover:bg-slate-200">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 bg-purple-700 hover:bg-purple-800 text-white rounded-xl text-xs font-bold shadow-xs">
                            Associate Business
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
function sisterConcernDashboardApp() {
    return {
        editProprietorModal: false,
        addBusinessModal: false,

        openEditProprietorModal() {
            this.editProprietorModal = true;
        },
        openAddBusinessModal() {
            this.addBusinessModal = true;
        },

        onPeriodChange(e) {
            const val = e.target.value;
            const form = document.getElementById('dashboardFilterForm');
            if (val !== 'custom') {
                form.submit();
            }
        }
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const comparisonLabels = @json($comparisonChart['labels']);
    const salesData = @json($comparisonChart['sales']);
    const purchasesData = @json($comparisonChart['purchases']);
    const profitsData = @json($comparisonChart['profits']);
    const expensesData = @json($comparisonChart['expenses']);

    const monthlyTrends = @json($monthlyTrends);

    // 1. Business-Wise Comparison Bar Chart
    const ctxComp = document.getElementById('businessComparisonChart');
    if (ctxComp && typeof Chart !== 'undefined') {
        new Chart(ctxComp, {
            type: 'bar',
            data: {
                labels: comparisonLabels,
                datasets: [
                    {
                        label: 'Sales (₹)',
                        data: salesData,
                        backgroundColor: 'rgba(147, 51, 234, 0.85)',
                        borderColor: '#7e22ce',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Purchases (₹)',
                        data: purchasesData,
                        backgroundColor: 'rgba(99, 102, 241, 0.85)',
                        borderColor: '#4f46e5',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Net Profit (₹)',
                        data: profitsData,
                        backgroundColor: 'rgba(16, 185, 129, 0.85)',
                        borderColor: '#059669',
                        borderWidth: 1,
                        borderRadius: 6,
                    },
                    {
                        label: 'Expenses (₹)',
                        data: expensesData,
                        backgroundColor: 'rgba(245, 158, 11, 0.85)',
                        borderColor: '#d97706',
                        borderWidth: 1,
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) {
                                return '₹' + (v >= 100000 ? (v / 100000).toFixed(1) + 'L' : v.toLocaleString('en-IN'));
                            }
                        }
                    }
                }
            }
        });
    }

    // 2. Monthly Trend Line Chart
    const ctxTrend = document.getElementById('monthlyTrendChart');
    if (ctxTrend && typeof Chart !== 'undefined' && monthlyTrends.length > 0) {
        new Chart(ctxTrend, {
            type: 'line',
            data: {
                labels: monthlyTrends.map(m => m.month_label),
                datasets: [
                    {
                        label: 'Sales (₹)',
                        data: monthlyTrends.map(m => m.sales),
                        borderColor: '#9333ea',
                        backgroundColor: 'rgba(147, 51, 234, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                    },
                    {
                        label: 'Purchases (₹)',
                        data: monthlyTrends.map(m => m.purchases),
                        borderColor: '#6366f1',
                        backgroundColor: 'rgba(99, 102, 241, 0.05)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                    tooltip: {
                        backgroundColor: '#0f172a',
                        padding: 10,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (ctx) {
                                return ctx.dataset.label + ': ₹' + Number(ctx.raw).toLocaleString('en-IN', { minimumFractionDigits: 2 });
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) {
                                return '₹' + (v >= 100000 ? (v / 100000).toFixed(1) + 'L' : v.toLocaleString('en-IN'));
                            }
                        }
                    }
                }
            }
        });
    }

    // 3. Group-wise Turnover Bar Chart
    const groupLabels = @json($groupTurnoverLabels ?? []);
    const groupValues = @json($groupTurnoverValues ?? []);
    const ctxTurnover = document.getElementById('groupTurnoverChart');
    if (ctxTurnover && typeof Chart !== 'undefined' && groupLabels.length > 0) {
        new Chart(ctxTurnover, {
            type: 'bar',
            data: {
                labels: groupLabels,
                datasets: [{
                    label: 'Turnover (₹ Lakhs)',
                    data: groupValues,
                    backgroundColor: [
                        'rgba(147, 51, 234, 0.85)',
                        'rgba(99, 102, 241, 0.85)',
                        'rgba(59, 130, 246, 0.85)',
                        'rgba(16, 185, 129, 0.85)',
                        'rgba(245, 158, 11, 0.85)'
                    ],
                    borderColor: [
                        '#7e22ce',
                        '#4f46e5',
                        '#2563eb',
                        '#059669',
                        '#d97706'
                    ],
                    borderWidth: 1.5,
                    borderRadius: 8,
                    maxBarThickness: 48,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                return ' Turnover: ₹' + context.parsed.y + ' Lakhs';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: { display: false },
                        ticks: { font: { family: 'Inter', size: 12, weight: '500' }, color: '#64748b' }
                    },
                    y: {
                        beginAtZero: true,
                        grid: { color: '#f1f5f9' },
                        ticks: {
                            font: { family: 'Inter', size: 12 },
                            color: '#64748b',
                            callback: function (val) {
                                return '₹' + val + ' L';
                            }
                        }
                    }
                }
            }
        });
    }

    // 4. Entity Type Distribution Doughnut Chart
    const entityLabels = @json($entityDistribution['labels'] ?? []);
    const entityData = @json($entityDistribution['data'] ?? []);
    const ctxEntity = document.getElementById('entityDistributionChart');
    if (ctxEntity && typeof Chart !== 'undefined' && entityLabels.length > 0) {
        new Chart(ctxEntity, {
            type: 'doughnut',
            data: {
                labels: entityLabels,
                datasets: [{
                    data: entityData,
                    backgroundColor: [
                        '#7c3aed',
                        '#4f46e5',
                        '#06b6d4'
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff',
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { family: 'Inter', size: 12, weight: '500' },
                            padding: 18,
                            color: '#334155',
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: '#1e1b4b',
                        padding: 12,
                        cornerRadius: 8,
                        callbacks: {
                            label: function (context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const val = context.parsed;
                                const pct = total > 0 ? Math.round((val / total) * 100) : 0;
                                return ' ' + context.label + ': ' + val + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
