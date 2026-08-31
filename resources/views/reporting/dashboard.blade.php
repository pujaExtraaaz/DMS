@extends('layouts.dms')

@section('title', 'Dashboard')

@section('content')
<div id="dashboard-analytics"
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
                    <x-ui.button variant="primary" :href="route('orders.create')" class="!bg-white !text-indigo-700 hover:!bg-indigo-50 shadow-lg">+ New Order</x-ui.button>
                @endcan
                @hasanyrole('super-admin|finance|sales-manager')
                    <x-ui.button variant="secondary" :href="route('invoices.create')" class="!bg-white/10 !text-white !border-white/20 hover:!bg-white/20">Direct Billing</x-ui.button>
                @endhasanyrole
                @hasanyrole('owner|super-admin|sales-manager|finance')
                    <x-ui.button variant="ghost" :href="route('reports.sales')" class="!text-white hover:!bg-white/10">View Reports</x-ui.button>
                @endhasanyrole
            </div>
        </div>
    </div>

    {{-- KPI cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        <x-ui.stat-card
            label="Today's Sales"
            :value="'₹'.number_format($stats['today_sales'], 0)"
            :change="$stats['sales_growth'] >= 0 ? '+'.$stats['sales_growth'].'% vs last month' : $stats['sales_growth'].'% vs last month'"
            :change-type="$stats['sales_growth'] >= 0 ? 'positive' : 'negative'"
            accent="indigo"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15M9 10.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zm6 0a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z\" /></svg>
        
        <x-ui.stat-card
            label="Month Revenue"
            :value="'₹'.number_format($stats['month_sales'], 0)"
            :change="$stats['total_invoices'].' invoices this month'"
            change-type="neutral"
            accent="violet"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z\" /></svg>'
        
        <x-ui.stat-card
            label="Pending Orders"
            :value="$stats['pending_orders']"
            :change="$stats['approved_orders'].' awaiting conversion'"
            change-type="warning"
            accent="amber"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z\" /></svg>
        
        <x-ui.stat-card
            label="Collections Today"
            :value="'₹'.number_format($stats['today_collections'], 0)"
            change-type="positive"
            accent="emerald"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z\" /></svg>
        
        <x-ui.stat-card
            label="Outstanding"
            :value="'₹'.number_format($stats['outstanding'], 0)"
            change="Across all customers"
            change-type="neutral"
            accent="rose"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z\" /></svg>
        
        <x-ui.stat-card
            label="Low Stock SKUs"
            :value="$stats['low_stock']"
            :change="$stats['pending_deliveries'].' pending deliveries'
            change-type="warning"
            accent="sky"
            :icon="'<svg class=\"h-6 w-6\" fill=\"none\" viewBox=\"0 0 24 24\" stroke-width=\"1.5\" stroke=\"currentColor\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z\" /></svg>
        
    </div>

    {{-- Charts row --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
        <div class="xl:col-span-2 rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Sales & Orders Trend</h3>
                    <p class="text-sm text-slate-500 mt-0.5">Last 7 days performance</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700">Live</span>
            </div>
            <div class="h-72">
                <canvas id="salesTrendChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900">Order Pipeline</h3>
                <p class="text-sm text-slate-500 mt-0.5">Status breakdown</p>
            </div>
            <div class="h-64 flex items-center justify-center">
                <canvas id="orderStatusChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Second analytics row --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div class="rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm">
            <div class="mb-6">
                <h3 class="text-base font-semibold text-slate-900">Payment Methods</h3>
                <p class="text-sm text-slate-500 mt-0.5">Collections this month by method</p>
            </div>
            <div class="h-56">
                <canvas id="paymentMethodsChart"></canvas>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200/80 p-6 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-900">Top Customers</h3>
                <p class="text-sm text-slate-500 mt-0.5">By revenue this month</p>
            </div>
            <div class="space-y-4">
                @forelse($topCustomers as $index => $row)
                    @php
                        $maxTotal = $topCustomers->max('total') ?: 1;
                        $pct = min(100, ($row->total / $maxTotal) * 100);
                    @endphp
                    <div>
                        <div class="flex items-center justify-between text-sm mb-1.5">
                            <span class="font-medium text-slate-700 truncate pr-4">
                                <span class="inline-flex h-5 w-5 items-center justify-center rounded-md bg-slate-100 text-xs font-bold text-slate-500 mr-2">{{ $index + 1 }}</span>
                                {{ $row->customer?->name ?? 'Unknown' }}
                            </span>
                            <span class="font-semibold text-slate-900 shrink-0">₹{{ number_format($row->total, 0) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-indigo-500 to-indigo-400 transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @empty
                    <x-ui.empty-state title="No sales data yet" description="Invoice customers to see rankings here." />
                @endforelse
            </div>
        </div>
    </div>

    {{-- Activity tables --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Recent Orders</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Latest order activity</p>
                </div>
                <a href="{{ route('orders.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3.5 text-sm">
                                    <a href="{{ route('orders.show', $order) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $order->order_no }}</a>
                                    <p class="text-xs text-slate-400">{{ $order->order_date?->format('M d, Y') }}</p>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-slate-700">{{ $order->customer?->name }}</td>
                                <td class="px-6 py-3.5">
                                    @php
                                        $statusColors = [
                                            'pending' => 'warning',
                                            'approved' => 'primary',
                                            'converted' => 'success',
                                            'cancelled' => 'danger',
                                            'draft' => 'neutral',
                                        ];
                                    @endphp
                                    <x-ui.badge :variant="$statusColors[$order->status] ?? 'neutral'">{{ ucfirst($order->status) }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-right font-semibold text-slate-900">₹{{ number_format($order->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10"><x-ui.empty-state title="No orders yet" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl bg-white border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-900">Recent Invoices</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Latest billing activity</p>
                </div>
                <a href="{{ route('invoices.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50/80">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Invoice</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Customer</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-400">Status</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-400">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($recentInvoices as $invoice)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-3.5 text-sm">
                                    <a href="{{ route('invoices.show', $invoice) }}" class="font-medium text-indigo-600 hover:text-indigo-700">{{ $invoice->invoice_no }}</a>
                                    <p class="text-xs text-slate-400">{{ $invoice->invoice_date?->format('M d, Y') }}</p>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-slate-700">{{ $invoice->customer?->name }}</td>
                                <td class="px-6 py-3.5">
                                    <x-ui.badge variant="{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'partial' ? 'warning' : 'primary') }}">{{ ucfirst($invoice->status) }}</x-ui.badge>
                                </td>
                                <td class="px-6 py-3.5 text-sm text-right font-semibold text-slate-900">₹{{ number_format($invoice->grand_total, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-10"><x-ui.empty-state title="No invoices yet" /></td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @vite('resources/js/dashboard.js')
@endpush
