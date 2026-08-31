@php
    $linkClass = function (array $patterns = [], bool $exact = false): string {
        $active = collect($patterns)->contains(fn ($pattern) => request()->routeIs($pattern));

        return $active
            ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30'
            : 'text-slate-400 hover:bg-slate-800/80 hover:text-white';
    };

    $sectionClass = 'px-3 pt-6 pb-2 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-500';
    $itemClass = 'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition-all duration-150';
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
    class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm lg:hidden"
    x-cloak
></div>

<aside
    :class="[
        sidebarCollapsed ? 'lg:w-[72px]' : 'lg:w-64',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
    ]"
    class="dms-sidebar fixed inset-y-0 left-0 z-50 flex w-64 flex-col h-screen shrink-0 border-r border-slate-800/50 transition-all duration-300 lg:relative lg:translate-x-0"
>
    {{-- Brand --}}
    <div class="flex h-16 shrink-0 items-center justify-between border-b border-slate-700/60 px-4">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-3">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-sm font-bold shadow-lg shadow-indigo-900/40">D</span>
            <span x-show="!sidebarCollapsed" x-cloak class="truncate text-sm font-semibold tracking-tight text-white">Distribution MS</span>
        </a>
        <button type="button" @click="sidebarOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden">
            <span class="sr-only">Close sidebar</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    {{-- Nav: scrolls independently inside fixed sidebar --}}
    <nav class="flex-1 overflow-y-auto overflow-x-hidden px-3 py-4 space-y-0.5 scrollbar-thin">
        <a href="{{ route('dashboard') }}" class="{{ $itemClass }} {{ $linkClass(['dashboard'], true) }}">
            <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
            <span x-show="!sidebarCollapsed" x-cloak>Dashboard</span>
        </a>

        @hasanyrole('owner|super-admin|sales-manager|warehouse')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Masters</p>
            @hasanyrole('owner|super-admin|sales-manager')
                <a href="{{ route('masters.products.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.products.*']) }}">
                    <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Products</span>
                </a>
                <a href="{{ route('masters.customers.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.customers.*']) }}">
                    <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" /></svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Customers</span>
                </a>
                <a href="{{ route('masters.price-masters.index') }}" class="{{ $itemClass }} {{ $linkClass(['masters.price-masters.*']) }}">
                    <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span x-show="!sidebarCollapsed" x-cloak>Price Master</span>
                </a>
            @endhasanyrole
        @endhasanyrole

        @hasanyrole('owner|super-admin|sales-manager|salesperson')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Orders</p>
            <a href="{{ route('orders.index') }}" class="{{ $itemClass }} {{ $linkClass(['orders.index', 'orders.show']) }}">
                <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                <span x-show="!sidebarCollapsed" x-cloak>All Orders</span>
            </a>
            <a href="{{ route('orders.create') }}" class="{{ $itemClass }} {{ $linkClass(['orders.create']) }}">
                <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                <span x-show="!sidebarCollapsed" x-cloak>Create Order</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|warehouse|sales-manager')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Inventory</p>
            <a href="{{ route('inventory.stock.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.stock.*']) }}">
                <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0l-3-3m3 3l3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                <span x-show="!sidebarCollapsed" x-cloak>Stock</span>
            </a>
            <a href="{{ route('inventory.purchases.index') }}" class="{{ $itemClass }} {{ $linkClass(['inventory.purchases.*']) }}">
                <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" /></svg>
                <span x-show="!sidebarCollapsed" x-cloak>Purchases</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|sales-manager|salesperson|finance')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Sales</p>
            <a href="{{ route('invoices.index') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.index', 'invoices.show']) }}">
                <svg class="h-5 w-5 shrink-0 opacity-70" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                <span x-show="!sidebarCollapsed" x-cloak>Invoices</span>
            </a>
            @hasanyrole('owner|super-admin|sales-manager|finance')
                <a href="{{ route('invoices.create') }}" class="{{ $itemClass }} {{ $linkClass(['invoices.create']) }}">
                    <span x-show="!sidebarCollapsed" x-cloak>Direct Billing</span>
                </a>
            @endhasanyrole
        @endhasanyrole

        @hasanyrole('owner|super-admin|finance|sales-manager')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Payments</p>
            <a href="{{ route('payments.index') }}" class="{{ $itemClass }} {{ $linkClass(['payments.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Collections</span>
            </a>
            <a href="{{ route('reconciliation.index') }}" class="{{ $itemClass }} {{ $linkClass(['reconciliation.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Reconciliation</span>
            </a>
            <a href="{{ route('outstanding.index') }}" class="{{ $itemClass }} {{ $linkClass(['outstanding.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Outstanding</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|warehouse')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Logistics</p>
            <a href="{{ route('logistics.load-sheets.index') }}" class="{{ $itemClass }} {{ $linkClass(['logistics.load-sheets.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Dispatch</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|driver|delivery-person|warehouse')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Delivery</p>
            <a href="{{ route('deliveries.index') }}" class="{{ $itemClass }} {{ $linkClass(['deliveries.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Deliveries</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|finance')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Settlement</p>
            <a href="{{ route('settlements.index') }}" class="{{ $itemClass }} {{ $linkClass(['settlements.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Cash Settlement</span>
            </a>
        @endhasanyrole

        @hasanyrole('owner|super-admin|sales-manager|finance')
            <p x-show="!sidebarCollapsed" x-cloak class="{{ $sectionClass }}">Reports</p>
            <a href="{{ route('reports.sales') }}" class="{{ $itemClass }} {{ $linkClass(['reports.*']) }}">
                <span x-show="!sidebarCollapsed" x-cloak>Analytics</span>
            </a>
        @endhasanyrole
    </nav>

    {{-- User footer pinned in sidebar --}}
    <div class="shrink-0 border-t border-slate-700/60 p-4">
        <div x-show="!sidebarCollapsed" x-cloak class="rounded-xl bg-slate-800/50 px-3 py-3 ring-1 ring-slate-700/50">
            <p class="truncate text-xs font-semibold text-white">{{ Auth::user()->name }}</p>
            <p class="truncate text-[11px] text-slate-400 capitalize">{{ str_replace('-', ' ', Auth::user()->roles->first()?->name ?? 'user') }}</p>
        </div>
    </div>
</aside>
