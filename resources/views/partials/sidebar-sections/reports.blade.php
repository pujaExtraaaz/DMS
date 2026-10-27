@if($can('reports.view', 'reports.manage'))
    @php $sNum = $num(); @endphp
    <div
        data-sidebar-section="reports"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{ open: {{ $is('reports.*') ? 'true' : 'false' }} }"
    >
        <div class="flex items-center gap-1">
            <span
                x-show="customizing"
                x-cloak
                draggable="true"
                @dragstart="onDragStart($event, 'reports')"
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
                <button type="button" @click="moveSection('reports', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('reports', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
            </div>
            <button type="button" class="{{ $sectionBtn }} flex-1" x-show="!$root.sidebarCollapsed" x-cloak @click="if (!customizing) open = !open">
                <span><span class="section-num">{{ $sNum }}</span> · Reports</span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>
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

