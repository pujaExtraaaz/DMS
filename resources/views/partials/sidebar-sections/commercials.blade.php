@if($can('deals.view', 'targets.view', 'schemes.view', 'interest.view'))
    @php
        $sNum = $num();
        $sectionActive = $is('deals.*', 'expense-types.*', 'targets.*', 'schemes.*', 'interest.*');
        $primaryRoute = $can('deals.view', 'deals.create', 'deals.manage')
            ? route('deals.index')
            : ($can('targets.view', 'targets.manage') ? route('targets.index') : ($can('schemes.view', 'schemes.manage') ? route('schemes.index') : route('interest.index')));
    @endphp
    <div
        data-sidebar-section="commercials"
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
                @dragstart="onDragStart($event, 'commercials')"
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
                <button type="button" @click="moveSection('commercials', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('commercials', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                    </svg>
                    <span class="truncate">Commercials</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        {{-- Collapsed Icon Link --}}
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="{{ $primaryRoute }}"
                title="Commercials"
                aria-label="Commercials"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 {{ $sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z" />
                </svg>
            </a>
        </div>

        {{-- Submenu (Expanded Only) --}}
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
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
