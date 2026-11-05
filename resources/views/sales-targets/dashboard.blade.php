@extends('layouts.dms')

@section('title', 'Sales Target Dashboard')

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl sm:text-2xl font-bold text-slate-900 tracking-tight">Sales Target Dashboard</h1>
                <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                    Live ERP Sync
                </span>
            </div>
            <p class="text-xs sm:text-sm text-slate-500 mt-0.5">Real-time target performance tracking calculated directly from sales invoices.</p>
        </div>

        <div class="flex items-center gap-2.5 shrink-0 flex-wrap">
            <form action="{{ route('sales-targets.recalculate') }}" method="POST" class="inline">
                @csrf
                <button type="submit"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sync Invoices
                </button>
            </form>

            <a href="{{ route('sales-targets.alerts') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                Alerts
            </a>

            <a href="{{ route('sales-targets.reports') }}"
               class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition">
                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Reports
            </a>

            <a href="{{ route('sales-targets.create') }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Assign Target
            </a>
        </div>
    </div>

    {{-- Filter Panel --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-4 shadow-xs" x-data="{ expanded: false }">
        <form method="GET" action="{{ route('sales-targets.dashboard') }}" class="space-y-3">
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                {{-- Period Type --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Period</label>
                    <select name="period_type" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Periods</option>
                        <option value="monthly" @selected(request('period_type') === 'monthly')>Monthly</option>
                        <option value="quarterly" @selected(request('period_type') === 'quarterly')>Quarterly</option>
                        <option value="half_yearly" @selected(request('period_type') === 'half_yearly')>Half-Yearly</option>
                        <option value="annual" @selected(request('period_type') === 'annual')>Annually</option>
                        <option value="custom" @selected(request('period_type') === 'custom')>Custom Range</option>
                    </select>
                </div>

                {{-- Financial Year --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Financial Year</label>
                    <select name="financial_year_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Current &amp; All FY</option>
                        @foreach($financialYears as $fy)
                            <option value="{{ $fy->id }}" @selected(request('financial_year_id') == $fy->id)>{{ $fy->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Owner Type --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Target Owner</label>
                    <select name="owner_type" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Owners</option>
                        <option value="party" @selected(request('owner_type') === 'party')>Party / Customer</option>
                        <option value="individual" @selected(request('owner_type') === 'individual')>Individual Salesman</option>
                        <option value="team" @selected(request('owner_type') === 'team')>Team</option>
                        <option value="branch" @selected(request('owner_type') === 'branch')>Branch</option>
                    </select>
                </div>

                {{-- Party / Customer --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party / Customer</label>
                    <select name="customer_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Parties</option>
                        @foreach($parties as $p)
                            <option value="{{ $p->id }}" @selected(request('customer_id') == $p->id)>
                                {{ $p->name }} {{ $p->code ? '('.$p->code.')' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Salesperson --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Salesperson</label>
                    <select name="salesperson_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Salespeople</option>
                        @foreach($salespeople as $sp)
                            <option value="{{ $sp->id }}" @selected(request('salesperson_id') == $sp->id)>{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Brand --}}
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Brand</label>
                    <select name="brand_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Brands</option>
                        @foreach($brands as $b)
                            <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Actions --}}
                <div class="flex items-end gap-2">
                    <button type="submit" class="w-full inline-flex justify-center items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-xs hover:bg-indigo-700 transition">
                        Filter
                    </button>
                    <a href="{{ route('sales-targets.dashboard') }}" class="inline-flex justify-center items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 shadow-xs hover:bg-slate-50 transition" title="Clear Filters">
                        ↺
                    </a>
                </div>
            </div>

            {{-- Collapsible Advanced Filters --}}
            <div x-show="expanded" x-cloak class="pt-3 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Company</label>
                    <select name="company_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Companies</option>
                        @foreach($companies as $comp)
                            <option value="{{ $comp->id }}" @selected(request('company_id') == $comp->id)>{{ $comp->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Team</label>
                    <select name="sales_team_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Teams</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" @selected(request('sales_team_id') == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Branch</label>
                    <select name="branch_id" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Branches</option>
                        @foreach($branches as $br)
                            <option value="{{ $br->id }}" @selected(request('branch_id') == $br->id)>{{ $br->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Target Type</label>
                    <select name="target_type" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Types</option>
                        <option value="value" @selected(request('target_type') === 'value')>Value-Wise</option>
                        <option value="product" @selected(request('target_type') === 'product')>Product-Wise</option>
                        <option value="sku" @selected(request('target_type') === 'sku')>SKU / Item-Wise</option>
                        <option value="brand" @selected(request('target_type') === 'brand')>Brand-Wise</option>
                        <option value="category" @selected(request('target_type') === 'category')>Category-Wise</option>
                        <option value="combined" @selected(request('target_type') === 'combined')>Combined Target</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Progress Status</label>
                    <select name="progress_status" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">All Progress Statuses</option>
                        <option value="Not Started" @selected(request('progress_status') === 'Not Started')>Not Started</option>
                        <option value="In Progress" @selected(request('progress_status') === 'In Progress')>In Progress</option>
                        <option value="Achieved" @selected(request('progress_status') === 'Achieved')>Achieved</option>
                        <option value="Period Expired" @selected(request('progress_status') === 'Period Expired')>Period Expired</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Admin Status</label>
                    <select name="status" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Active &amp; Completed</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="completed" @selected(request('status') === 'completed')>Completed</option>
                        <option value="pending_approval" @selected(request('status') === 'pending_approval')>Pending Approval</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end">
                <button type="button" @click="expanded = !expanded" class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 focus:outline-none">
                    <span x-show="!expanded">+ Advanced Filters (Team, Branch, Category, SKU)</span>
                    <span x-show="expanded">- Fewer Filters</span>
                </button>
            </div>
        </form>
    </div>

    {{-- KPI Cards Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Card 1: Total Target Value --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Assigned Target</span>
                <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-slate-900">₹ {{ number_format($totalAssigned, 2) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $allTargets->count() }} targets assigned</span>
                <span class="text-indigo-600 font-medium">{{ $activeCount }} active</span>
            </div>
        </div>

        {{-- Card 2: Actual Sales Achievement --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Actual Achievement</span>
                <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-emerald-700">₹ {{ number_format($actualAchieved, 2) }}</span>
                <span class="text-xs font-bold px-2 py-0.5 rounded-full {{ $achievementPercent >= 100 ? 'bg-emerald-100 text-emerald-800' : ($achievementPercent >= 75 ? 'bg-indigo-100 text-indigo-800' : 'bg-amber-100 text-amber-800') }}">
                    {{ $achievementPercent }}%
                </span>
            </div>
            {{-- Progress bar --}}
            <div class="mt-3 w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-500" style="width: {{ min(100, $achievementPercent) }}%"></div>
            </div>
        </div>

        {{-- Card 3: Total Shortfall --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Shortfall</span>
                <span class="p-2 rounded-lg bg-rose-50 text-rose-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-rose-700">₹ {{ number_format($totalShortfall, 2) }}</span>
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>{{ $achievedCount }} targets achieved</span>
                <span class="text-rose-600 font-medium">{{ $periodExpiredCount }} expired</span>
            </div>
        </div>

        {{-- Card 4: Overall Achievement Percentage --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs relative overflow-hidden">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Overall Achievement</span>
                <span class="p-2 rounded-lg bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
                </span>
            </div>
            <div class="mt-2 flex items-baseline gap-2">
                <span class="text-2xl font-bold tracking-tight text-purple-900">{{ $achievementPercent }}%</span>
                @if($totalTargetQty > 0)
                    <span class="text-xs text-slate-500 font-medium">({{ number_format($totalAchievedQty, 0) }} / {{ number_format($totalTargetQty, 0) }} Units)</span>
                @endif
            </div>
            <div class="mt-3 flex items-center justify-between text-xs text-slate-500">
                <span>MTD: ₹ {{ number_format($mtdInvoices, 0) }}</span>
                <span>YTD: ₹ {{ number_format($ytdInvoices, 0) }}</span>
            </div>
        </div>
    </div>

    {{-- Target Progress Status Summary --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
        {{-- Status 1: Not Started --}}
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Not Started']) }}"
           class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs hover:border-slate-300 transition flex items-center justify-between {{ request('progress_status') === 'Not Started' ? 'ring-2 ring-slate-400 bg-slate-50/50' : '' }}">
            <div>
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Targets Not Started</span>
                <p class="text-xl font-bold text-slate-700 mt-0.5">{{ $notStartedCount }}</p>
                <span class="text-[10px] text-slate-400">Future start date</span>
            </div>
            <span class="p-2 rounded-lg bg-slate-100 text-slate-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </a>

        {{-- Status 2: In Progress --}}
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'In Progress']) }}"
           class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs hover:border-indigo-300 transition flex items-center justify-between {{ request('progress_status') === 'In Progress' ? 'ring-2 ring-indigo-500 bg-indigo-50/30' : '' }}">
            <div>
                <span class="text-[11px] font-semibold text-indigo-600 uppercase tracking-wider">Targets In Progress</span>
                <p class="text-xl font-bold text-indigo-700 mt-0.5">{{ $inProgressCount }}</p>
                <span class="text-[10px] text-slate-400">Active tracking</span>
            </div>
            <span class="p-2 rounded-lg bg-indigo-50 text-indigo-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
            </span>
        </a>

        {{-- Status 3: Achieved --}}
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Achieved']) }}"
           class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs hover:border-emerald-300 transition flex items-center justify-between {{ request('progress_status') === 'Achieved' ? 'ring-2 ring-emerald-500 bg-emerald-50/30' : '' }}">
            <div>
                <span class="text-[11px] font-semibold text-emerald-600 uppercase tracking-wider">Targets Achieved</span>
                <p class="text-xl font-bold text-emerald-700 mt-0.5">{{ $achievedCount }}</p>
                <span class="text-[10px] text-slate-400">100%+ goal met</span>
            </div>
            <span class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
        </a>

        {{-- Status 4: Period Expired --}}
        <a href="{{ request()->fullUrlWithQuery(['progress_status' => 'Period Expired']) }}"
           class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-xs hover:border-rose-300 transition flex items-center justify-between {{ request('progress_status') === 'Period Expired' ? 'ring-2 ring-rose-500 bg-rose-50/30' : '' }}">
            <div>
                <span class="text-[11px] font-semibold text-rose-600 uppercase tracking-wider">Targets Period Expired</span>
                <p class="text-xl font-bold text-rose-700 mt-0.5">{{ $periodExpiredCount }}</p>
                <span class="text-[10px] text-slate-400">Unachieved past end date</span>
            </div>
            <span class="p-2 rounded-lg bg-rose-50 text-rose-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            </span>
        </a>
    </div>
    </div>

    {{-- Middle Section: Monthly Sales Trend & Salesman Leaderboard --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Trend Chart / Bar Display (2 cols) --}}
        <div class="lg:col-span-2 rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h2 class="text-base font-semibold text-slate-900">Monthly Invoiced Sales Trend</h2>
                    <p class="text-xs text-slate-500">Real actual invoice sales across the calendar year</p>
                </div>
                <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2 py-1 rounded">
                    FY Sales: ₹ {{ number_format($ytdInvoices, 2) }}
                </span>
            </div>

            <div class="h-64 flex items-end gap-2 pt-6 pb-2">
                @php
                    $maxAmount = max(1, collect($monthlyTrend)->max('amount'));
                @endphp
                @foreach($monthlyTrend as $point)
                    @php
                        $barHeight = round(($point['amount'] / $maxAmount) * 100);
                    @endphp
                    <div class="flex-1 flex flex-col items-center gap-1.5 group relative h-full justify-end">
                        <div class="w-full bg-indigo-100 hover:bg-indigo-600 rounded-t transition-all duration-300 relative"
                             style="height: {{ max(4, $barHeight) }}%">
                            {{-- Tooltip --}}
                            <div class="absolute bottom-full left-1/2 -translate-x-1/2 mb-1 hidden group-hover:block z-10 bg-slate-900 text-white text-[10px] font-mono py-1 px-2 rounded shadow-lg whitespace-nowrap">
                                {{ $point['month'] }}: ₹ {{ number_format($point['amount'], 0) }}
                            </div>
                        </div>
                        <span class="text-[10px] font-semibold text-slate-500 uppercase">{{ $point['month'] }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Salesman Leaderboard (1 col) --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-slate-900">Salesman Performance</h2>
                <a href="{{ route('sales-targets.salesman-performance') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    View All &rarr;
                </a>
            </div>

            <div class="flex-1 overflow-y-auto divide-y divide-slate-100 space-y-2 pr-1 max-h-72">
                @forelse($salesmanPerformance as $idx => $spData)
                    <div class="pt-2 first:pt-0 flex items-center justify-between">
                        <div class="flex items-center gap-2.5 min-w-0">
                            <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold shrink-0 {{ $idx === 0 ? 'bg-amber-100 text-amber-800 ring-1 ring-amber-400' : ($idx === 1 ? 'bg-slate-200 text-slate-700' : ($idx === 2 ? 'bg-amber-50 text-amber-700' : 'bg-slate-50 text-slate-500')) }}">
                                {{ $idx + 1 }}
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate">{{ $spData['salesperson'] }}</p>
                                <p class="text-[11px] text-slate-500">₹ {{ number_format($spData['achieved'], 0) }} / ₹ {{ number_format($spData['target'], 0) }}</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <span class="inline-flex items-center text-xs font-bold {{ $spData['percent'] >= 100 ? 'text-emerald-700' : ($spData['percent'] >= 75 ? 'text-indigo-600' : 'text-amber-600') }}">
                                {{ $spData['percent'] }}%
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-slate-400">
                        No targets assigned for individual salespeople under selected filters.
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Dedicated Party-Wise Targets Performance Section --}}
    <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-base font-bold text-slate-900 tracking-tight">Party-Wise Sales Targets</h2>
                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700 ring-1 ring-inset ring-indigo-600/20">
                        {{ $partyTargets->count() }} {{ \Illuminate\Support\Str::plural('Party', $partyTargets->count()) }} Assigned
                    </span>
                </div>
                <p class="text-xs text-slate-500 mt-0.5">Live party sales achievement tracked automatically from sales invoices and credit notes.</p>
            </div>
            <a href="{{ route('sales-targets.create', ['owner_type' => 'party']) }}"
               class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 hover:bg-indigo-100 text-indigo-700 px-3.5 py-1.5 text-xs font-semibold transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                Assign Party Target
            </a>
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-xs text-left">
                <thead class="bg-slate-50 text-slate-700 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Party Name</th>
                        <th class="px-4 py-3 text-right">Target Amount (₹)</th>
                        <th class="px-4 py-3 text-center">Start Date</th>
                        <th class="px-4 py-3 text-center">End Date</th>
                        <th class="px-4 py-3 text-right">Actual Sales (₹)</th>
                        <th class="px-4 py-3 text-right">Shortfall (₹)</th>
                        <th class="px-4 py-3 text-center">Achievement %</th>
                        <th class="px-4 py-3 text-center">Remaining Days</th>
                        <th class="px-4 py-3 text-center">Target Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($partyTargets as $pt)
                        @php
                            $pAch = $pt->achievement;
                            $targetAmt = (float) $pt->target_amount;
                            $achAmt = $pAch ? (float) $pAch->achieved_amount : 0.0;
                            $shortfall = $pAch ? (float) $pAch->pending_amount : max(0, $targetAmt - $achAmt);
                            $pct = $pAch ? (float) $pAch->achievement_percent : ($targetAmt > 0 ? round(($achAmt / $targetAmt) * 100, 1) : 0);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900">{{ $pt->customer?->name ?? $pt->owner_name }}</div>
                                <div class="text-[11px] text-slate-400">
                                    {{ $pt->customer?->code ? 'Code: ' . $pt->customer->code . ' • ' : '' }}
                                    <span class="font-medium text-slate-500">{{ $pt->name }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-slate-900">
                                ₹ {{ number_format($targetAmt, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $pt->starts_on->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-center text-slate-600">
                                {{ $pt->ends_on->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold {{ $pct >= 100 ? 'text-emerald-700' : 'text-slate-900' }}">
                                ₹ {{ number_format($achAmt, 2) }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if($pt->progress_status === 'Period Expired' && $shortfall > 0)
                                    <div class="font-bold text-rose-600">₹ {{ number_format($shortfall, 2) }}</div>
                                    <div class="text-[10px] text-rose-500 font-semibold uppercase tracking-wider">Unachieved</div>
                                @elseif($shortfall > 0)
                                    <span class="font-semibold text-rose-600">₹ {{ number_format($shortfall, 2) }}</span>
                                @else
                                    <span class="font-semibold text-emerald-600">₹ 0.00</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center w-28">
                                <span class="text-xs font-bold {{ $pct >= 100 ? 'text-emerald-700' : ($pct >= 75 ? 'text-indigo-600' : 'text-amber-600') }}">
                                    {{ $pct }}%
                                </span>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 mt-1 overflow-hidden">
                                    <div class="h-1.5 rounded-full {{ $pct >= 100 ? 'bg-emerald-500' : ($pct >= 60 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                         style="width: {{ min(100, $pct) }}%"></div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @php
                                    $remLabel = $pt->remaining_days_label;
                                @endphp
                                @if($pt->progress_status === 'Achieved')
                                    <span class="inline-flex items-center gap-1 rounded-md bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700">
                                        <svg class="w-3 h-3 text-emerald-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                        {{ $remLabel }}
                                    </span>
                                @elseif($pt->progress_status === 'Period Expired')
                                    <span class="inline-flex items-center rounded-md bg-rose-50 px-2 py-0.5 text-[11px] font-semibold text-rose-700">
                                        {{ $remLabel }}
                                    </span>
                                @elseif($pt->progress_status === 'Not Started')
                                    <span class="inline-flex items-center rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                                        {{ $remLabel }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-[11px] font-semibold text-indigo-700">
                                        {{ $remLabel }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center whitespace-nowrap">
                                @if($pt->progress_status === 'Achieved')
                                    <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Achieved</span>
                                @elseif($pt->progress_status === 'Period Expired')
                                    <span class="inline-flex items-center rounded-full bg-rose-50 px-2 py-0.5 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-600/20">Period Expired</span>
                                @elseif($pt->progress_status === 'Not Started')
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-400/20">Not Started</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-600/20">In Progress</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-1.5">
                                    <a href="{{ route('sales-targets.show', $pt) }}"
                                       class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-indigo-600 transition"
                                       title="View Details">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                    <a href="{{ route('sales-targets.edit', $pt) }}"
                                       class="p-1.5 rounded-md hover:bg-slate-100 text-slate-500 hover:text-indigo-600 transition"
                                       title="Edit Target">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10 text-center text-xs text-slate-400">
                                No party-wise sales targets assigned under the current filter selection.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Bottom Section: Brand-Wise Breakdown & Recent Alerts --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Brand Breakdown --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs">
            <h2 class="text-base font-semibold text-slate-900 mb-3">Brand-Wise Performance</h2>
            <div class="space-y-3">
                @forelse($brandPerformance as $bp)
                    <div>
                        <div class="flex items-center justify-between text-xs font-medium text-slate-700 mb-1">
                            <span>{{ $bp['brand'] }}</span>
                            <span>₹ {{ number_format($bp['achieved'], 0) }} / ₹ {{ number_format($bp['target'], 0) }} ({{ $bp['percent'] }}%)</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $bp['percent'] >= 100 ? 'bg-emerald-500' : ($bp['percent'] >= 60 ? 'bg-indigo-500' : 'bg-amber-500') }}"
                                 style="width: {{ min(100, $bp['percent']) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-400">No brand-specific targets found in current filter.</p>
                @endforelse
            </div>
        </div>

        {{-- Threshold & Underperformance Alerts --}}
        <div class="rounded-xl border border-slate-200/80 bg-white p-5 shadow-xs flex flex-col">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-semibold text-slate-900">Target Alerts &amp; Milestones</h2>
                <a href="{{ route('sales-targets.alerts') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    View All Alerts &rarr;
                </a>
            </div>

            <div class="divide-y divide-slate-100 space-y-2 overflow-y-auto max-h-64 pr-1">
                @forelse($recentAlerts as $alert)
                    <div class="pt-2 first:pt-0 flex items-start justify-between gap-3">
                        <div class="flex items-start gap-2.5">
                            <span class="p-1 rounded-md mt-0.5 shrink-0 {{ str_contains($alert->alert_type, '100') ? 'bg-emerald-100 text-emerald-700' : (str_contains($alert->alert_type, 'underperformance') ? 'bg-rose-100 text-rose-700' : 'bg-indigo-100 text-indigo-700') }}">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                            <div>
                                <p class="text-xs font-semibold text-slate-800">{{ $alert->title }}</p>
                                <p class="text-[11px] text-slate-500 leading-tight mt-0.5">{{ $alert->message }}</p>
                                <span class="text-[10px] text-slate-400 mt-1 block">{{ $alert->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        @if(!$alert->is_read)
                            <form action="{{ route('sales-targets.alerts.read', $alert) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-[10px] font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                    Mark Read
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="py-8 text-center text-xs text-slate-400">No active alerts. All targets progressing within normal variance.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

