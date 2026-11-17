@extends('layouts.dms')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard-analytics"
     x-data="dashboardAnalytics({
        stats: @js($stats),
        cardLabels: @js($cardLabels),
        dateRange: @js($dateRange),
        from: @js($from),
        to: @js($to),
        showingText: @js($showingText),
        salesGrowthLabel: @js($salesGrowthLabel),
        salesGrowthType: @js($salesGrowthType),
        topCustomers: @js($formattedTopCustomers),
        recentOrders: @js($formattedRecentOrders),
        recentInvoices: @js($formattedRecentInvoices),
        salesTrend: @js($salesTrend),
        orderStatus: @js($orderStatusBreakdown),
        paymentMethods: @js($paymentMethods)
     })"
     data-sales-trend='@json($salesTrend)'
     data-order-status='@json($orderStatusBreakdown)'
     data-payment-methods='@json($paymentMethods)'>

    {{-- Hero header --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-indigo-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="absolute inset-0 bg-[url('data:image/svg+xml,%3Csvg width=\"60\" height=\"60\" viewBox=\"0 0 60 60\" xmlns=\"http://www.w3.org/2000/svg\"%3E%3Cg fill=\"none\" fill-rule=\"evenodd\"%3E%3Cg fill=\"%23ffffff\" fill-opacity=\"0.03\"%3E%3Cpath d=\"M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z\"/%3E%3C/g%3E%3C/g%3E%3C/svg%3E')] opacity-50"></div>
        <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
            <div>
                <p class="text-indigo-300 text-sm font-medium">{{ now()->format('l, F j, Y') }}</p>
                <h2 class="mt-1 text-2xl sm:text-3xl font-bold tracking-tight">Good {{ now()->hour < 12 ? 'morning' : (now()->hour < 17 ? 'afternoon' : 'evening') }}, {{ Auth::user()->name }}</h2>
                <p class="mt-2 text-slate-300 text-sm max-w-xl">Here's what's happening across your distribution network today.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                @can('orders.book')
                    <x-ui.button variant="primary" href="{{ route('orders.create') }}" class="!bg-white !text-indigo-700 hover:!bg-indigo-50 shadow-lg">+ New Order</x-ui.button>
                @endcan
                @hasanyrole('super-admin|client-admin|finance|sales-manager')
                    <x-ui.button variant="secondary" href="{{ route('invoices.create') }}" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">Direct Billing</x-ui.button>
                @endhasanyrole
                @hasanyrole('owner|super-admin|client-admin|sales-manager|finance')
                    <x-ui.button variant="ghost" href="{{ route('reports.sales') }}" class="!text-white hover:!bg-white/10">View Reports</x-ui.button>
                @endhasanyrole
            </div>
        </div>
    </div>

    {{-- Date range filter toolbar --}}
    <x-ui.card class="my-4">
        <form method="GET" action="{{ route('dashboard') }}" @submit.prevent="applyCustom()" class="flex flex-wrap gap-3 items-end">
            <div>
                <label for="dashboard-date-range-select" class="block text-xs font-medium text-slate-500 mb-1">Date range</label>
                <select id="dashboard-date-range-select"
                        name="date_range"
                        x-model="dateRange"
                        @change="onDateRangeChange($event.target.value)"
                        class="rounded-lg border-gray-300 text-sm font-medium text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px]">
                    @foreach($dateRangeOptions as $val => $label)
                        <option value="{{ $val }}" @selected(($dateRange ?? 'this_month') === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <template x-if="dateRange === 'custom'">
                <div class="flex flex-wrap items-end gap-3">
                    <div>
                        <label for="dashboard-date-from" class="block text-xs font-medium text-slate-500 mb-1">From Date</label>
                        <input id="dashboard-date-from"
                               type="date"
                               name="date_from"
                               x-model="dateFrom"
                               required
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px]">
                    </div>
                    <div>
                        <label for="dashboard-date-to" class="block text-xs font-medium text-slate-500 mb-1">To Date</label>
                        <input id="dashboard-date-to"
                               type="date"
                               name="date_to"
                               x-model="dateTo"
                               required
                               class="rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px]">
                    </div>
                    <button type="submit"
                            :disabled="isLoading"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 shadow-sm transition disabled:opacity-50 min-h-[38px]">
                        <span x-show="!isLoading">Apply</span>
                        <span x-show="isLoading" class="inline-flex items-center gap-1">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                            Applying...
                        </span>
                    </button>
                </div>
            </template>

            <div class="text-xs font-medium text-slate-600 pb-2.5 flex items-center gap-2">
                <span class="inline-block h-2 w-2 rounded-full bg-indigo-500"></span>
                <span id="dashboard-showing-text" x-text="showingText">{{ $showingText }}</span>
            </div>

            <div x-show="isLoading" x-cloak class="pb-2.5 text-xs text-indigo-600 font-medium animate-pulse flex items-center gap-1">
                <svg class="animate-spin h-3.5 w-3.5" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                Refreshing data...
            </div>
        </form>

        <div x-show="validationError" x-cloak class="mt-2 text-xs text-rose-600 font-semibold flex items-center gap-1">
            <svg class="h-4 w-4 shrink-0 text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
            <span x-text="validationError"></span>
        </div>

        {{-- Auxiliary badges --}}
        <div class="mt-3 flex flex-wrap gap-2.5 text-sm">
            <span class="rounded-full bg-rose-50 text-rose-700 px-3 py-1 text-xs font-medium border border-rose-200/60">Frozen parties: <strong x-text="stats.frozen_parties">{{ $stats['frozen_parties'] ?? 0 }}</strong></span>
            <span class="rounded-full bg-amber-50 text-amber-700 px-3 py-1 text-xs font-medium border border-amber-200/60">Bounce risk: <strong x-text="stats.bounce_risk">{{ $stats['bounce_risk'] ?? 0 }}</strong></span>
            <span class="rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-medium border border-slate-200/60">Dead stock: <strong x-text="stats.dead_stock">{{ $stats['dead_stock'] ?? 0 }}</strong></span>
            <span class="rounded-full bg-emerald-50 text-emerald-700 px-3 py-1 text-xs font-medium border border-emerald-200/60">Margin stub: <strong x-text="formatCurrency(stats.margin_stub)">₹{{ number_format($stats['margin_stub'] ?? 0, 0) }}</strong></span>
            <span class="rounded-full bg-indigo-50 text-indigo-700 px-3 py-1 text-xs font-medium border border-indigo-200/60">Target achievement: <strong x-text="(stats.targets_achievement || 0) + '%'">{{ $stats['targets_achievement'] ?? 0 }}%</strong></span>
        </div>
    </x-ui.card>

    {{-- Main KPI Cards Grid --}}
    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4 items-stretch">

        {{-- 1. Sales Amount / Total Sales --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500" x-text="cardLabels.sales">
                    {{ $cardLabels['sales'] ?? "Sales Amount" }}
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-600 text-white shadow-lg shadow-indigo-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15M9 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm6 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.range_sales)">
                    ₹{{ number_format($stats['range_sales'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200/60"
                      x-text="(stats.sales_invoice_count || 0) + ' sales invoices'">
                    {{ $stats['sales_invoice_count'] ?? 0 }} sales invoices
                </span>
            </div>
        </div>

        {{-- 2. Purchase Amount --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500" x-text="cardLabels.purchases">
                    {{ $cardLabels['purchases'] ?? "Purchase Amount" }}
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg shadow-amber-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.25V3.75A1.125 1.125 0 0013.125 2.625h-9a1.125 1.125 0 00-1.125 1.125v10.5M3.75 6.75h10.5" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.purchase_amount)">
                    ₹{{ number_format($stats['purchase_amount'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200/60"
                      x-text="(stats.purchase_invoice_count || 0) + ' purchase invoices'">
                    {{ $stats['purchase_invoice_count'] ?? 0 }} purchase invoices
                </span>
            </div>
        </div>

        {{-- 3. Month / Period Revenue --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500" x-text="cardLabels.revenue">
                    {{ $cardLabels['revenue'] ?? "Period Revenue" }}
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-violet-500 to-violet-600 text-white shadow-lg shadow-violet-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.month_sales)">
                    ₹{{ number_format($stats['month_sales'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium"
                      :class="salesGrowthType === 'positive' ? 'text-emerald-700 bg-emerald-50 border border-emerald-200/60' : 'text-red-700 bg-red-50 border border-red-200/60'"
                      x-text="salesGrowthLabel">
                    {{ $salesGrowthLabel }}
                </span>
            </div>
        </div>

        {{-- 4. Pending Orders --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500">
                    Pending Orders
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-amber-500 to-amber-600 text-white shadow-lg shadow-amber-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="stats.pending_orders">
                    {{ $stats['pending_orders'] ?? 0 }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200/60"
                      x-text="(stats.approved_orders || 0) + ' awaiting conversion'">
                    {{ ($stats['approved_orders'] ?? 0).' awaiting conversion' }}
                </span>
            </div>
        </div>

        {{-- 5. Collections --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500" x-text="cardLabels.collections">
                    {{ $cardLabels['collections'] ?? "Collections" }}
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-emerald-500 to-emerald-600 text-white shadow-lg shadow-emerald-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.today_collections)">
                    ₹{{ number_format($stats['today_collections'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-emerald-700 bg-emerald-50 border border-emerald-200/60"
                      x-text="(stats.collections_count || 0) + ' payments received'">
                    {{ $stats['collections_count'] ?? 0 }} payments received
                </span>
            </div>
        </div>

        {{-- 6. Receivables --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500">
                    Receivables
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-rose-500 to-rose-600 text-white shadow-lg shadow-rose-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.outstanding)">
                    ₹{{ number_format($stats['outstanding'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200/60">
                    Across customer accounts
                </span>
            </div>
        </div>

        {{-- 7. Payables --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500">
                    Payables
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-sky-500 to-sky-600 text-white shadow-lg shadow-sky-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.625-1.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0m7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0M3 15.75A2.25 2.25 0 005.25 18h13.5A2.25 2.25 0 0021 15.75v-1.5H3v1.5z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="formatCurrency(stats.payables)">
                    ₹{{ number_format($stats['payables'] ?? 0, 0) }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-slate-600 bg-slate-50 border border-slate-200/60">
                    Across supplier accounts
                </span>
            </div>
        </div>

        {{-- 8. Low Stock Part Numbers --}}
        <div class="relative flex w-full min-w-0 min-h-[150px] flex-col rounded-2xl bg-white border border-slate-200/80 p-5 shadow-sm hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between gap-4">
                <p class="min-w-0 pr-2 text-sm font-semibold tracking-wider text-slate-500">
                    Low Stock Part Numbers
                </p>
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-600 to-slate-700 text-white shadow-lg shadow-slate-500/25">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                </div>
            </div>
            <div class="mt-3 min-w-0 flex-1">
                <p class="min-w-0 break-words text-xl font-bold leading-snug tracking-tight text-slate-900 tabular-nums sm:text-2xl"
                   x-text="stats.low_stock">
                    {{ $stats['low_stock'] ?? 0 }}
                </p>
            </div>
            <div class="mt-3 min-h-[24px]">
                <span class="inline-flex max-w-full items-center rounded-full px-3 py-1 text-xs font-medium text-amber-700 bg-amber-50 border border-amber-200/60"
                      x-text="(stats.pending_deliveries || 0) + ' pending deliveries'">
                    {{ ($stats['pending_deliveries'] ?? 0).' pending deliveries' }}
                </span>
            </div>
        </div>

    </div>

    {{-- Charts row --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="dashboard-panel xl:col-span-2 rounded-[24px] p-6 bg-white border border-slate-200/80 shadow-sm">
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Sales, Purchases & Orders Trend</h3>
                    <p class="mt-1 text-sm text-slate-500">Performance across selected period</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-medium">
                    <span class="inline-flex items-center gap-1.5 text-indigo-700"><span class="h-2 w-2 rounded-full bg-indigo-600"></span> Sales</span>
                    <span class="inline-flex items-center gap-1.5 text-amber-700"><span class="h-2 w-2 rounded-full bg-amber-500"></span> Purchases</span>
                    <span class="inline-flex items-center gap-1.5 text-teal-700"><span class="h-2 w-2 rounded-full bg-teal-500"></span> Orders</span>
                </div>
            </div>
            <div class="h-72">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <div class="dashboard-panel rounded-[24px] p-6 bg-white border border-slate-200/80 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Order Pipeline</h3>
                <p class="mt-1 text-sm text-slate-500">Status breakdown for period</p>
            </div>
            <div class="h-64 flex items-center justify-center">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Second analytics row --}}
    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="dashboard-panel rounded-[24px] p-6 bg-white border border-slate-200/80 shadow-sm">
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-slate-900">Payment Methods</h3>
                <p class="mt-1 text-sm text-slate-500">Collections by method for period</p>
            </div>
            <div class="h-56">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

        <div class="dashboard-panel rounded-[24px] p-6 bg-white border border-slate-200/80 shadow-sm">
            <div class="mb-4">
                <h3 class="text-lg font-semibold text-slate-900">Top Customers</h3>
                <p class="mt-1 text-sm text-slate-500">By revenue in selected period</p>
            </div>
            <div class="space-y-4">
                <template x-for="row in topCustomers" :key="row.customer_id">
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700 truncate pr-4">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-500 mr-2" x-text="row.index"></span>
                                <span x-text="row.name"></span>
                            </span>
                            <span class="font-semibold text-slate-900 shrink-0" x-text="row.total_formatted"></span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-all duration-500" :style="`width: ${row.percentage}%`"></div>
                        </div>
                    </div>
                </template>
                <div x-show="topCustomers.length === 0">
                    <x-ui.empty-state title="No sales data yet" description="Invoice customers to see rankings here." />
                </div>
            </div>
        </div>
    </div>

    {{-- Activity tables --}}
    <div class="mt-6 grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="dashboard-panel overflow-hidden rounded-[24px] bg-white border border-slate-200/80 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Recent Orders</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Order activity within period</p>
                </div>
                <a href="{{ route('orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold tracking-wider text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="order in recentOrders" :key="order.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3.5 text-sm">
                                    <a :href="order.url" class="font-medium text-indigo-600 hover:text-indigo-700" x-text="order.order_no"></a>
                                    <p class="text-xs text-slate-400" x-text="order.date_formatted"></p>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-slate-700" x-text="order.customer_name"></td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="{
                                              'bg-amber-50 text-amber-700 border border-amber-200': order.status_color === 'warning',
                                              'bg-indigo-50 text-indigo-700 border border-indigo-200': order.status_color === 'primary',
                                              'bg-emerald-50 text-emerald-700 border border-emerald-200': order.status_color === 'success',
                                              'bg-rose-50 text-rose-700 border border-rose-200': order.status_color === 'danger',
                                              'bg-slate-100 text-slate-700 border border-slate-200': order.status_color === 'neutral'
                                          }"
                                          x-text="order.status.charAt(0).toUpperCase() + order.status.slice(1)">
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-right font-semibold text-slate-900" x-text="order.amount_formatted"></td>
                            </tr>
                        </template>
                        <tr x-show="recentOrders.length === 0">
                            <td colspan="4" class="px-6 py-10">
                                <x-ui.empty-state title="No orders in this period" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dashboard-panel overflow-hidden rounded-[24px] bg-white border border-slate-200/80 shadow-sm">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold text-slate-900">Recent Invoices</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Billing activity within period</p>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold tracking-wider text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <template x-for="invoice in recentInvoices" :key="invoice.id">
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3.5 text-sm">
                                    <a :href="invoice.url" class="font-medium text-indigo-600 hover:text-indigo-700" x-text="invoice.invoice_no"></a>
                                    <p class="text-xs text-slate-400" x-text="invoice.date_formatted"></p>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-slate-700" x-text="invoice.customer_name"></td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium"
                                          :class="{
                                              'bg-emerald-50 text-emerald-700 border border-emerald-200': invoice.status_color === 'success',
                                              'bg-amber-50 text-amber-700 border border-amber-200': invoice.status_color === 'warning',
                                              'bg-indigo-50 text-indigo-700 border border-indigo-200': invoice.status_color === 'primary'
                                          }"
                                          x-text="invoice.status.charAt(0).toUpperCase() + invoice.status.slice(1)">
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-right font-semibold text-slate-900" x-text="invoice.amount_formatted"></td>
                            </tr>
                        </template>
                        <tr x-show="recentInvoices.length === 0">
                            <td colspan="4" class="px-6 py-10">
                                <x-ui.empty-state title="No invoices in this period" />
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
function dashboardAnalytics(initial) {
    return {
        stats: initial.stats || {},
        cardLabels: initial.cardLabels || {},
        dateRange: initial.dateRange || 'this_month',
        dateFrom: initial.from || '',
        dateTo: initial.to || '',
        showingText: initial.showingText || '',
        salesGrowthLabel: initial.salesGrowthLabel || '',
        salesGrowthType: initial.salesGrowthType || 'neutral',
        topCustomers: initial.topCustomers || [],
        recentOrders: initial.recentOrders || [],
        recentInvoices: initial.recentInvoices || [],
        salesTrend: initial.salesTrend || [],
        orderStatus: initial.orderStatus || {},
        paymentMethods: initial.paymentMethods || {},
        isLoading: false,
        validationError: '',

        formatCurrency(val) {
            const num = Number(val) || 0;
            return '₹' + num.toLocaleString('en-IN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
        },

        onDateRangeChange(newVal) {
            this.validationError = '';
            this.dateRange = newVal;
            if (newVal !== 'custom') {
                this.fetchData();
            }
        },

        applyCustom() {
            this.validationError = '';
            if (!this.dateFrom || !this.dateTo) {
                this.validationError = 'Please select both From and To dates.';
                return;
            }
            if (this.dateFrom > this.dateTo) {
                this.validationError = 'From Date cannot be later than To Date.';
                return;
            }
            this.fetchData();
        },

        async fetchData() {
            this.isLoading = true;
            this.validationError = '';

            let url = new URL('{{ route('dashboard') }}', window.location.origin);
            url.searchParams.set('date_range', this.dateRange);
            if (this.dateRange === 'custom') {
                if (this.dateFrom) url.searchParams.set('date_from', this.dateFrom);
                if (this.dateTo) url.searchParams.set('date_to', this.dateTo);
            }

            try {
                const response = await fetch(url.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                if (!response.ok) {
                    throw new Error('Failed to load dashboard data');
                }

                const data = await response.json();
                this.stats = data.stats || {};
                this.cardLabels = data.cardLabels || {};
                this.salesGrowthLabel = data.salesGrowthLabel || '';
                this.salesGrowthType = data.salesGrowthType || 'neutral';
                this.showingText = data.showingText || '';
                this.topCustomers = data.topCustomers || [];
                this.recentOrders = data.recentOrders || [];
                this.recentInvoices = data.recentInvoices || [];
                this.dateRange = data.dateRange || this.dateRange;
                this.dateFrom = data.from || this.dateFrom;
                this.dateTo = data.to || this.dateTo;
                this.salesTrend = data.salesTrend || [];
                this.orderStatus = data.orderStatusBreakdown || {};
                this.paymentMethods = data.paymentMethods || {};

                if (typeof window.updateDashboardCharts === 'function') {
                    window.updateDashboardCharts(this.salesTrend, this.orderStatus, this.paymentMethods);
                }

                window.history.pushState({}, '', url.toString());
            } catch (err) {
                console.error(err);
                this.validationError = 'Error updating dashboard. Please try again.';
            } finally {
                this.isLoading = false;
            }
        }
    };
}
</script>
@endsection

@push('scripts')
    @if (dms_vite_ready(['resources/js/dashboard.js']))
        @vite('resources/js/dashboard.js')
    @else
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.5.1/dist/chart.umd.min.js"></script>
        <script src="{{ asset('js/dashboard-fallback.js') }}?v=20261116"></script>
    @endif
@endpush
