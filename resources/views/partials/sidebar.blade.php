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

@php
    $activeCompany = \App\Domains\Organization\Models\Company::find(auth()->user()?->company_id) ?? \App\Domains\Organization\Models\Company::first();
@endphp

<aside
    :class="[
        sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
    class="dms-sidebar fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col h-[100dvh] shrink-0 border-r border-slate-800/50 transition-all duration-300 lg:static lg:translate-x-0"
>
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-700/60 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            @if($activeCompany?->logo_url)
                <img src="{{ $activeCompany->logo_url }}"
                     alt="{{ $activeCompany->name }}"
                     class="h-10 w-10 shrink-0 rounded-xl object-contain bg-white p-1 shadow-lg shadow-indigo-900/40"
                     onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='flex';">
                <span style="display: none;" class="h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold shadow-lg shadow-indigo-900/40 text-white">
                    {{ strtoupper(substr($activeCompany?->name ?? 'DMS', 0, 1)) }}
                </span>
            @else
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold shadow-lg shadow-indigo-900/40 text-white">
                    {{ strtoupper(substr($activeCompany?->name ?? 'DMS', 0, 1)) }}
                </span>
            @endif
            <span x-show="!$root.sidebarCollapsed" x-cloak class="truncate text-sm font-semibold tracking-tight text-white">{{ $activeCompany?->name ?? 'DMS' }}</span>
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
                    <a href="{{ route('organization.company-profile') }}" class="{{ $itemClass }} {{ $linkClass(['organization.company-profile*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Company Profile</span></a>
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
                        <a href="{{ route('masters.customers.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.customers.*', 'masters.parties.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Parties</span></a>
                    @endif
                    @if($can('price-master.view', 'masters.view'))
                        <a href="{{ route('masters.price-masters.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.price-masters.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Price List</span></a>
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
                    <span>3 · Purchases</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('purchasing.orders.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.orders.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Orders</span></a>
                    <a href="{{ route('purchasing.inwards.index') }}" class="{{ $itemClass }} {{ $linkClass(['purchasing.inwards.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Goods Receipts (GRN)</span></a>
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
                        <a href="{{ route('inventory.stock.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.stock.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Current Stock</span></a>
                        <a href="{{ route('inventory.transfers.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.transfers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Transfer</span></a>
                        <a href="{{ route('inventory.adjustments.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.adjustments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Adjustments</span></a>
                        <a href="{{ route('inventory.serials.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.serials.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Serial Numbers</span></a>
                        <a href="{{ route('inventory.valuation.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.valuation.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Valuation</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('orders.view', 'orders.create', 'orders.edit', 'orders.book', 'orders.manage', 'create orders', 'sales.view', 'invoices.view', 'quotations.view'))
            <div x-data="{ open: {{ $is('orders.*', 'quotations.*', 'invoices.*', 'region-policies.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>5 · Sales &amp; Orders</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('orders.view', 'orders.create', 'orders.book', 'orders.manage', 'create orders'))
                        <a href="{{ route('orders.index') }}" class="{{ $itemClass }} {{ $linkClass(['orders.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Orders</span></a>
                        <a href="{{ route('orders.create') }}" class="{{ $itemClass }} {{ $linkClass(['orders.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Create Order</span></a>
                    @endif
                    @if($can('quotations.view', 'quotations.create', 'sales.view'))
                        <a href="{{ route('quotations.index') }}" class="{{ $itemClass }} {{ $linkClass(['quotations.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Quotations</span></a>
                    @endif
                    @if($can('sales.view', 'invoices.view'))
                        <a href="{{ route('invoices.index') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.index', 'invoices.show']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Tax Invoices</span></a>
                    @endif
                    @if($can('sales.create', 'invoices.create'))
                        <a href="{{ route('invoices.create') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.create']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Create Invoice</span></a>
                    @endif
                    @if($can('sales.manage', 'quotations.manage', 'masters.manage'))
                        <a href="{{ route('region-policies.index') }}" class="{{ $itemClass }} {{ $linkClass(['region-policies.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Region Policies</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('logistics.view', 'delivery.view', 'settlement.view', 'settlement.entry', 'manage settlements'))
            <div x-data="{ open: {{ $is('logistics.*', 'deliveries.*', 'settlements.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>6 · Logistics &amp; Trips</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('logistics.view', 'logistics.create', 'logistics.manage'))
                        <a href="{{ route('logistics.load-sheets.index') }}" class="{{ $itemClass }} {{ $linkClass(['logistics.load-sheets.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Trip Sheets / Load</span></a>
                    @endif
                    @if($can('delivery.view', 'delivery.create', 'delivery.manage'))
                        <a href="{{ route('deliveries.index') }}" class="{{ $itemClass }} {{ $linkClass(['deliveries.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Delivery Runs</span></a>
                    @endif
                    @if($can('settlement.view', 'settlement.create', 'settlement.manage', 'manage settlements', 'settlement.entry'))
                        <a href="{{ route('settlements.index') }}" class="{{ $itemClass }} {{ $linkClass(['settlements.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Trip Settlements</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('payments.view', 'payments.create', 'cheques.view', 'credit-notes.view'))
            <div x-data="{ open: {{ $is('payments.*', 'cheques.*', 'credit-notes.*', 'outstanding.*', 'reconciliation.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>7 · Receivables</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('payments.index') }}" class="{{ $itemClass }} {{ $linkClass(['payments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Payment Receipts</span></a>
                    @if($can('cheques.view', 'cheques.create', 'payments.manage'))
                        <a href="{{ route('cheques.index') }}" class="{{ $itemClass }} {{ $linkClass(['cheques.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Cheque Register</span></a>
                    @endif
                    @if($can('credit-notes.view', 'credit-notes.create', 'payments.manage'))
                        <a href="{{ route('credit-notes.index') }}" class="{{ $itemClass }} {{ $linkClass(['credit-notes.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Credit Notes</span></a>
                    @endif
                    <a href="{{ route('outstanding.index') }}" class="{{ $itemClass }} {{ $linkClass(['outstanding.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Outstanding Balances</span></a>
                    <a href="{{ route('reconciliation.index') }}" class="{{ $itemClass }} {{ $linkClass(['reconciliation.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Reconciliation</span></a>
                    <a href="{{ route('reports.aging') }}" class="{{ $itemClass }} {{ $linkClass(['reports.aging']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Aging Analysis</span></a>
                </div>
            </div>
        @endif

        @if($can('deals.view', 'targets.view', 'schemes.view', 'interest.view'))
            <div x-data="{ open: {{ $is('deals.*', 'expense-types.*', 'targets.*', 'schemes.*', 'interest.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>8 · Commercials</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('deals.view', 'deals.create', 'deals.manage'))
                        <a href="{{ route('deals.index') }}" class="{{ $itemClass }} {{ $linkClass(['deals.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Special Deals</span></a>
                        <a href="{{ route('expense-types.index') }}" class="{{ $itemClass }} {{ $linkClass(['expense-types.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Expense Types</span></a>
                    @endif
                    @if($can('targets.view', 'targets.manage'))
                        <a href="{{ route('targets.index') }}" class="{{ $itemClass }} {{ $linkClass(['targets.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Targets &amp; Rebates</span></a>
                    @endif
                    @if($can('schemes.view', 'schemes.manage'))
                        <a href="{{ route('schemes.index') }}" class="{{ $itemClass }} {{ $linkClass(['schemes.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Schemes</span></a>
                    @endif
                    @if($can('interest.view', 'interest.manage'))
                        <a href="{{ route('interest.index') }}" class="{{ $itemClass }} {{ $linkClass(['interest.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Interest &amp; Overdue</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('hrms.view', 'hrms.manage', 'crm.view', 'crm.manage', 'tally.view', 'tally.manage'))
            <div x-data="{ open: {{ $is('hrms.*') || $is('crm.*') || $is('tally.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>9 · HR · CRM</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    @if($can('hrms.view', 'hrms.manage'))
                        <a href="{{ route('hrms.employees.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.employees.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Employees</span></a>
                        <a href="{{ route('hrms.departments.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.departments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Departments</span></a>
                        <a href="{{ route('hrms.designations.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.designations.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Designations</span></a>
                        <a href="{{ route('hrms.managers.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.managers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Managers</span></a>
                        <a href="{{ route('hrms.attendances.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.attendances.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Attendance</span></a>
                        <a href="{{ route('hrms.leave-requests.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.leave-requests.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leave Requests</span></a>
                        <a href="{{ route('hrms.leave-balances.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.leave-balances.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leave Balances</span></a>
                        <a href="{{ route('hrms.leave-types.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.leave-types.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leave Types</span></a>
                        <a href="{{ route('hrms.expense-claims.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.expense-claims.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Claims</span></a>
                    @endif
                    @if($can('crm.view', 'crm.manage'))
                        <a href="{{ route('crm.leads.index') }}" class="{{ $itemClass }} {{ $linkClass(['crm.leads.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leads (CRM)</span></a>
                    @endif
                </div>
            </div>
        @endif

        @if($can('tally.view', 'tally.manage', 'accounting.view'))
            <div x-data="{ open: {{ $is('books.tally*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>10 · Books</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('books.tally.dashboard') }}" class="{{ $itemClass }} {{ $linkClass(['books.tally.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Tally</span></a>
                </div>
            </div>
        @endif

        @if($can('reports.view', 'reports.manage'))
            <div x-data="{ open: {{ $is('reports.*') ? 'true' : 'false' }} }">
                <button type="button" class="{{ $sectionBtn }}" x-show="!$root.sidebarCollapsed" x-cloak @click="open = !open">
                    <span>11 · REPORTS</span>
                    <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open || $root.sidebarCollapsed" x-cloak class="space-y-0.5">
                    <a href="{{ route('reports.sales') }}" class="{{ $itemClass }} {{ $linkClass(['reports.sales']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Sales Register</span></a>
                    <a href="{{ route('reports.stock') }}" class="{{ $itemClass }} {{ $linkClass(['reports.stock']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Movement</span></a>
                    <a href="{{ route('reports.stock-ledger') }}" class="{{ $itemClass }} {{ $linkClass(['reports.stock-ledger']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Ledger</span></a>
                    <a href="{{ route('reports.party-statement') }}" class="{{ $itemClass }} {{ $linkClass(['reports.party-statement']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Party Ledger</span></a>
                    <a href="{{ route('reports.purchase-register') }}" class="{{ $itemClass }} {{ $linkClass(['reports.purchase-register']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Register</span></a>
                    <a href="{{ route('reports.outstanding') }}" class="{{ $itemClass }} {{ $linkClass(['reports.outstanding']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Outstanding Statement</span></a>
                    <a href="{{ route('reports.margin') }}" class="{{ $itemClass }} {{ $linkClass(['reports.margin']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Margin Analysis</span></a>
                    <a href="{{ route('reports.day-book') }}" class="{{ $itemClass }} {{ $linkClass(['reports.day-book']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Day Book</span></a>
                    <a href="{{ route('reports.profit-loss') }}" class="{{ $itemClass }} {{ $linkClass(['reports.profit-loss']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Profit &amp; Loss</span></a>
                    <a href="{{ route('reports.balance-sheet') }}" class="{{ $itemClass }} {{ $linkClass(['reports.balance-sheet']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Balance Sheet</span></a>
                    <a href="{{ route('reports.trial-balance') }}" class="{{ $itemClass }} {{ $linkClass(['reports.trial-balance']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Trial Balance</span></a>
                    <a href="{{ route('reports.od') }}" class="{{ $itemClass }} {{ $linkClass(['reports.od', 'od.report*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>OD Report</span></a>
                </div>
            </div>
        @endif

        {{-- User Section: Profile, Users & Roles, Log Out --}}
        <div class="border-t border-slate-700/60 pt-3 mt-3 space-y-2">
            <a href="{{ route('profile.edit') }}" class="{{ $itemClass }} text-slate-300 hover:bg-slate-800/80 hover:text-white">
                <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                <div x-show="!$root.sidebarCollapsed" x-cloak class="min-w-0 flex-1 text-left">
                    <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[10px] text-slate-400">{{ auth()->user()->email }}</p>
                </div>
            </a>

            @if(auth()->user()->hasAnyRole(['super-admin', 'client-admin']))
                <a href="{{ route('users.index') }}" class="{{ $itemClass }} {{ $linkClass(['users.*']) }}">
                    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <div x-show="!$root.sidebarCollapsed" x-cloak class="min-w-0 flex-1 text-left">
                        <p class="truncate text-xs font-semibold text-slate-200">Users &amp; Roles</p>
                    </div>
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center gap-3 rounded-xl px-3 py-2 text-xs font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition">
                    <svg class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                    <span x-show="!$root.sidebarCollapsed" x-cloak>Log Out</span>
                </button>
            </form>
        </div>
    </nav>
</aside>