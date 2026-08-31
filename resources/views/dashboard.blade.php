@extends('layouts.dms')

@section('title', 'Dashboard')

@section('breadcrumbs')
    <nav class="flex" aria-label="Breadcrumb">
        <ol class="inline-flex items-center gap-2 text-sm">
            <li class="text-gray-500">Home</li>
            <li class="text-gray-400">/</li>
            <li class="font-medium text-gray-900" aria-current="page">Dashboard</li>
        </ol>
    </nav>
@endsection

@php
    $statusVariants = [
        'draft' => 'default',
        'pending' => 'warning',
        'approved' => 'info',
        'converted' => 'success',
        'cancelled' => 'danger',
    ];
@endphp

@section('content')
    <div class="space-y-6">
        <x-ui.page-header
            title="Welcome back, {{ Auth::user()->name }}"
            description="Monitor orders, inventory, deliveries, and settlements from one place."
        >
            <x-slot name="actions">
                @can('reports.view')
                    <x-ui.button variant="secondary" href="#">Export</x-ui.button>
                @endcan
                @canany(['orders.book', 'orders.manage', 'create orders'])
                    <x-ui.button variant="primary" href="#">New Order</x-ui.button>
                @endcanany
            </x-slot>
        </x-ui.page-header>

        @if (count($kpis) > 0)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($kpis as $kpi)
                    <x-ui.stat-card
                        :label="$kpi['label']"
                        :value="$kpi['value']"
                        :change="$kpi['change']"
                        :change-type="$kpi['changeType']"
                        :icon="$kpi['icon']"
                    />
                @endforeach
            </div>
        @endif

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            @if ($showRecentOrders && $recentOrders->isNotEmpty())
                <x-ui.card class="xl:col-span-2" title="Recent Orders" description="Latest activity across your distribution network.">
                    <x-ui.table>
                        <x-slot name="head">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Order</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Customer</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wider text-gray-500">Amount</th>
                            </tr>
                        </x-slot>

                        @foreach ($recentOrders as $order)
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $order->order_no }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-600">{{ $order->customer?->name ?? '—' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm">
                                    <x-ui.badge :variant="$statusVariants[$order->status] ?? 'default'">
                                        {{ ucfirst($order->status) }}
                                    </x-ui.badge>
                                </td>
                                <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium text-gray-900">
                                    ₹{{ number_format((float) $order->grand_total, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </x-ui.table>
                </x-ui.card>
            @elseif ($showRecentOrders)
                <x-ui.card class="xl:col-span-2" title="Recent Orders" description="No orders found yet.">
                    <x-ui.empty-state
                        title="No orders yet"
                        description="Book your first order to see activity here."
                    />
                </x-ui.card>
            @else
                <x-ui.card class="xl:col-span-2" title="Overview" description="Your role-focused dashboard summary.">
                    <x-ui.alert type="info">
                        KPI cards above reflect the modules you can access. Use Reports for deeper analysis.
                    </x-ui.alert>
                </x-ui.card>
            @endif

            <x-ui.card title="Quick Actions">
                <div class="space-y-3">
                    @canany(['orders.book', 'orders.manage', 'create orders'])
                        <x-ui.button class="w-full justify-center" variant="primary">Create Order</x-ui.button>
                    @endcanany
                    @canany(['logistics.manage', 'delivery.manage'])
                        <x-ui.button class="w-full justify-center" variant="secondary">Assign Delivery</x-ui.button>
                    @endcanany
                    @can('reports.view')
                        <x-ui.button class="w-full justify-center" variant="ghost">View Reports</x-ui.button>
                    @endcan
                </div>

                <div class="mt-6 border-t border-gray-100 pt-6">
                    <x-ui.alert type="info" dismissible>
                        Demo data is loaded. Log in as different roles to see role-specific KPIs.
                    </x-ui.alert>
                </div>
            </x-ui.card>
        </div>
    </div>
@endsection
