@if($can('inventory.view', 'stock.view', 'purchases.view'))
    @php
        $sNum = $num();
        $sectionActive = $is('inventory.*');
    @endphp
    <div
        data-sidebar-section="inventory"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{ open: {{ $sectionActive ? 'true' : 'false' }} }"
    >
        {{-- Expanded Header --}}
        <div x-show="!$root.sidebarCollapsed" class="flex items-center gap-1">
            <span
                x-show="customizing"
                x-cloak
                draggable="true"
                @dragstart="onDragStart($event, 'inventory')"
                @dragend="onDragEnd()"
                @click.stop
                @mousedown.stop
                class="cursor-grab active:cursor-grabbing p-1 rounded hover:bg-slate-700/80 text-indigo-400 hover:text-indigo-300 transition"
                title="Drag to reorder section"
            >
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                </svg>
            </span>
            <div x-show="customizing" x-cloak class="flex items-center gap-0.5" @click.stop @mousedown.stop>
                <button type="button" @click="moveSection('inventory', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('inventory', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
            <button
                type="button"
                class="{{ $sectionBtn }} flex-1 group"
                @click="if (!customizing) open = !open"
            >
                <span class="flex items-center gap-2.5 min-w-0">
                    <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-slate-200 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                    </svg>
                    <span class="truncate">Inventory</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        {{-- Collapsed Icon Link --}}
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="{{ route('inventory.stock.index') }}"
                title="Inventory"
                aria-label="Inventory"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 {{ $sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />
                </svg>
            </a>
        </div>

        {{-- Submenu (Expanded Only) --}}
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
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
