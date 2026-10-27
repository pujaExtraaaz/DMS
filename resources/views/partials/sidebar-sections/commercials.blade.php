@if($can('deals.view', 'targets.view', 'schemes.view', 'interest.view'))
    @php $sNum = $num(); @endphp
    <div
        data-sidebar-section="commercials"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{ open: {{ $is('deals.*', 'expense-types.*', 'targets.*', 'schemes.*', 'interest.*') ? 'true' : 'false' }} }"
    >
        <div class="flex items-center gap-1">
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
            <button type="button" class="{{ $sectionBtn }} flex-1" x-show="!$root.sidebarCollapsed" x-cloak @click="if (!customizing) open = !open">
                <span><span class="section-num">{{ $sNum }}</span> · Commercials</span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>
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

