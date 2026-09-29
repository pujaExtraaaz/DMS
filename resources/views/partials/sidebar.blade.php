@php
    $linkClass = function (array $patterns = [], bool $exact = false): string {
        $active = collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));

        return $active
            ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30'
            : 'text-slate-400 hover:bg-slate-800/80 hover:text-white';
    };

    $sectionBtn = 'flex w-full items-center justify-between gap-2 rounded-xl px-3 py-2 text-left text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500 hover:bg-slate-800/60 hover:text-slate-300 transition';
    $itemClass = 'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition-all duration-150';
    $can = fn (...$perms) => auth()->user()->hasAnyRole(['super-admin', 'client-admin']) || auth()->user()->hasAnyPermission($perms);
    $is = fn (...$patterns) => collect($patterns)->contains(fn ($p) => request()->routeIs($p));
@endphp

{{-- Mobile backdrop --}}
<div
    x-show="sidebarOpen"
    x-transition:enter="transition-opacity ease-linear duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-linear duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="sidebarOpen = false"
    class="fixed inset-0 z-40 hidden bg-slate-900/60 backdrop-blur-sm lg:hidden"
    x-cloak
    :class="sidebarOpen ? '!block' : 'hidden'"
></div>

<aside
    :class="[
        sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
    class="dms-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col h-[100dvh] shrink-0 border-r border-slate-800/50 transition-all duration-300 lg:static lg:translate-x-0"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-700/60 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            @if(auth()->user()?->company?->logo_url)
                <img src="{{ auth()->user()->company->logo_url }}"
                     alt="{{ auth()->user()->company->name ?? 'Company Logo' }}"
                     class="h-10 w-10 shrink-0 rounded-xl object-contain bg-white/10 p-1"
                     onerror="this.style.display='none'; if(this.nextElementSibling) this.nextElementSibling.classList.remove('hidden');">
                <span class="hidden h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold shadow-lg shadow-indigo-900/40">
                    {{ substr(auth()->user()->company->name ?? 'D', 0, 1) }}
                </span>
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold shadow-lg shadow-indigo-900/40">
                    {{ substr(auth()->user()?->company?->name ?? 'D', 0, 1) }}
                </span>
            @endif
            <span x-show="!$root.sidebarCollapsed" x-cloak class="truncate text-sm font-semibold tracking-tight text-white">
                {{ auth()->user()?->company?->name ?? 'DMS' }}
            </span>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden">
            <span class="sr-only">Close sidebar</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-3 space-y-1 scrollbar-thin" @click.capture="if ($event.target.closest('a')) sidebarOpen = false">
        <a href="{{ route('dashboard') }}" class="{{ $itemClass }} {{ $linkClass(['dashboard'], true) }}">
            <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
            <span x-show="!$root.sidebarCollapsed" x-cloak>Dashboard</span>
        </a>

        @if($can('organization.view', 'organization.manage'))
            <div x-data="{ open: {{ $is('organization.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>1 · Setup</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('organization.company-profile.edit') }}" class="{{ $itemClass }} {{ $linkClass(['organization.company-profile.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Company Profile</span></a>
                    <a href="{{ route('organization.companies.index') }}" class="{{ $itemClass }} {{ $linkClass(['organization.companies.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Companies</span></a>
                    <a href="{{ route('organization.branches.index') }}" class="{{ $itemClass }} {{ $linkClass(['organization.branches.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Branches</span></a>
                    <a href="{{ route('organization.warehouses.index') }}" class="{{ $itemClass }} {{ $linkClass(['organization.warehouses.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Warehouses</span></a>
                    <a href="{{ route('organization.financial-years.index') }}" class="{{ $itemClass }} {{ $linkClass(['organization.financial-years.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Financial Years</span></a>
                    <a href="{{ route('organization.business-groups.index') }}" class="{{ $itemClass }} {{ $linkClass(['organization.business-groups.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Sister Concerns</span></a>
                </div>
            </div>
        @endif

        @if($can('masters.view', 'products.view', 'customers.view', 'price-master.view'))
            <div x-data="{ open: {{ $is('masters.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>2 · Masters</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('products.view', 'masters.view'))
                        <a href="{{ route('masters.brands.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.brands.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Brands</span></a>
                        <a href="{{ route('masters.categories.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.categories.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Categories</span></a>
                        <a href="{{ route('masters.sub-categories.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.sub-categories.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Sub Categories</span></a>
                        <a href="{{ route('masters.products.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.products.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Products</span></a>
                    @endif
                    @if($can('customers.view', 'masters.view'))
                        <a href="{{ route('masters.customers.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.customers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Parties</span></a>
                    @endif
                    @if($can('price-master.view', 'masters.view'))
                        <a href="{{ route('masters.price-masters.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.price-masters.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Price Master</span></a>
                    @endif
                    @if($can('masters.view', 'products.view', 'customers.view'))
                        <a href="{{ route('masters.bulk-import.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.bulk-import.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Bulk Import</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('purchases.view', 'purchases.create', 'purchase-orders.view', 'purchase-orders.create', 'purchase-orders.manage', 'inventory.view'))
            <div x-data="{ open: {{ $is('purchasing.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>3 · Buy / Stock-In</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('purchasing.orders.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.orders.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Orders</span></a>
                    <a href="{{ route('purchasing.inwards.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.inwards.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Inward / GRN</span></a>
                    <a href="{{ route('purchasing.invoices.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.invoices.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Invoices</span></a>
                    <a href="{{ route('purchasing.freight-bills.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.freight-bills.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Freight Bills</span></a>
                    <a href="{{ route('purchasing.landed-costs.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.landed-costs.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Landed Cost</span></a>
                </div>
            </div>
        @endif

        @if($can('inventory.view', 'stock.view', 'purchases.view'))
            <div x-data="{ open: {{ $is('inventory.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>4 · Inventory</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('stock.view', 'inventory.view'))
                        <a href="{{ route('inventory.stock.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.stock.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock</span></a>
                        <a href="{{ route('inventory.transfers.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.transfers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Transfers</span></a>
                        <a href="{{ route('inventory.adjustments.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.adjustments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Adjustments</span></a>
                        <a href="{{ route('inventory.serials.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.serials.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Serial Lifecycle</span></a>
                        <a href="{{ route('inventory.valuation.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.valuation.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>FIFO / LIFO</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('orders.view', 'orders.create', 'orders.edit', 'orders.book', 'orders.manage', 'create orders', 'sales.view', 'invoices.view', 'quotations.view'))
            <div x-data="{ open: {{ $is('orders.*', 'quotations.*', 'invoices.*', 'region-policies.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>5 · Sell</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('orders.view', 'orders.create', 'orders.book', 'orders.manage', 'create orders'))
                        <a href="{{ route('orders.create') }}" class="{{ $itemClass }} {{ $linkClass(['orders.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Create Order</span></a>
                        <a href="{{ route('orders.index') }}" class="{{ $itemClass }} {{ $linkClass(['orders.index', 'orders.show']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>All Orders</span></a>
                    @endif
                    @if($can('quotations.view', 'quotations.create', 'sales.view'))
                        <a href="{{ route('quotations.index') }}" class="{{ $itemClass }} {{ $linkClass(['quotations.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Quotations</span></a>
                    @endif
                    @if($can('sales.view', 'invoices.view'))
                        <a href="{{ route('invoices.index') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.index', 'invoices.show']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Invoices</span></a>
                    @endif
                    @if($can('sales.create', 'invoices.create'))
                        <a href="{{ route('invoices.create') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Direct Billing</span></a>
                    @endif
                    @if($can('region-policies.view', 'sales.view'))
                        <a href="{{ route('region-policies.index') }}" class="{{ $itemClass }} {{ $linkClass(['region-policies.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Region Policies</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('payments.view', 'payments.create', 'payments.collect', 'payments.reconcile', 'settlements.view', 'interest.view'))
            <div x-data="{ open: {{ $is('payments.*', 'outstanding.*', 'reconciliation.*', 'cheques.*', 'credit-notes.*', 'settlements.*', 'interest.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>6 · Collect & Settle</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('outstanding.index') }}" class="{{ $itemClass }} {{ $linkClass(['outstanding.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Outstanding</span></a>
                    <a href="{{ route('payments.create', ['type' => 'receipt']) }}" class="{{ $itemClass }} {{ $linkClass(['payments.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Receive Payment</span></a>
                    <a href="{{ route('payments.create', ['type' => 'payment']) }}" class="{{ $itemClass }} {{ $linkClass(['payments.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Make Payment</span></a>
                    <a href="{{ route('payments.index') }}" class="{{ $itemClass }} {{ $linkClass(['payments.index', 'payments.show']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Payment Book</span></a>
                    <a href="{{ route('cheques.index') }}" class="{{ $itemClass }} {{ $linkClass(['cheques.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Cheque Tracker</span></a>
                    <a href="{{ route('credit-notes.index') }}" class="{{ $itemClass }} {{ $linkClass(['credit-notes.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Credit Notes</span></a>
                    <a href="{{ route('reconciliation.index') }}" class="{{ $itemClass }} {{ $linkClass(['reconciliation.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Bank Reconciliation</span></a>
                    <a href="{{ route('settlements.index') }}" class="{{ $itemClass }} {{ $linkClass(['settlements.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Settlements</span></a>
                </div>
            </div>
        @endif

        @if($can('logistics.view', 'delivery.view', 'drivers.view', 'vehicles.view'))
            <div x-data="{ open: {{ $is('logistics.*', 'deliveries.*', 'masters.vehicles.*', 'masters.drivers.*', 'masters.delivery-persons.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>7 · Logistics</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('deliveries.index') }}" class="{{ $itemClass }} {{ $linkClass(['deliveries.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Deliveries</span></a>
                    <a href="{{ route('logistics.load-sheets.index') }}" class="{{ $itemClass }} {{ $linkClass(['logistics.load-sheets.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Load Sheets / Trips</span></a>
                    <a href="{{ route('masters.vehicles.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.vehicles.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Vehicles</span></a>
                    <a href="{{ route('masters.drivers.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.drivers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Drivers</span></a>
                    <a href="{{ route('masters.delivery-persons.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.delivery-persons.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Delivery Persons</span></a>
                </div>
            </div>
        @endif

        @if($can('reports.view', 'analytics.view'))
            <div x-data="{ open: {{ $is('reports.*', 'dashboard') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>8 · Reports</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('reports.sales') }}" class="{{ $itemClass }} {{ $linkClass(['reports.sales']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Sales Register</span></a>
                    <a href="{{ route('reports.purchase-register') }}" class="{{ $itemClass }} {{ $linkClass(['reports.purchase-register']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Register</span></a>
                    <a href="{{ route('reports.stock') }}" class="{{ $itemClass }} {{ $linkClass(['reports.stock']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Summary</span></a>
                    <a href="{{ route('reports.outstanding') }}" class="{{ $itemClass }} {{ $linkClass(['reports.outstanding']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Outstanding Statement</span></a>
                    <a href="{{ route('reports.profit-loss') }}" class="{{ $itemClass }} {{ $linkClass(['reports.profit-loss']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Profit & Loss</span></a>
                    <a href="{{ route('reports.balance-sheet') }}" class="{{ $itemClass }} {{ $linkClass(['reports.balance-sheet']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Balance Sheet</span></a>
                </div>
            </div>
        @endif
    </nav>
</aside>