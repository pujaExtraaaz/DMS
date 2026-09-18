<nav class="dms-bottom-nav lg:hidden" aria-label="Mobile primary">
    <a href="{{ route('dashboard') }}" class="dms-bottom-nav__item {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10.5L12 3l9 7.5V20a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1v-9.5z"/></svg>
        <span>Home</span>
    </a>

    <a href="{{ route('orders.index') }}" class="dms-bottom-nav__item {{ request()->routeIs('orders.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
        <span>Orders</span>
    </a>

    <a href="{{ route('inventory.stock.index') }}" class="dms-bottom-nav__item {{ request()->routeIs('inventory.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8l-9-5-9 5 9 5 9-5zM3 8v8l9 5 9-5V8"/></svg>
        <span>Stock</span>
    </a>

    <a href="{{ route('payments.index') }}" class="dms-bottom-nav__item {{ request()->routeIs('payments.*', 'cheques.*', 'outstanding.*', 'credit-notes.*') ? 'is-active' : '' }}">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 12v-2m9-4a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span>Collect</span>
    </a>

    <button type="button" class="dms-bottom-nav__item" @click="sidebarOpen = true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
        <span>Menu</span>
    </button>
</nav>
