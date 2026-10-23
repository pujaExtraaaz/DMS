@php $sNum = $num(); @endphp
<div
    data-sidebar-section="guide"
    class="sidebar-section-item transition-all rounded-xl"
    @dragover="onDragOver($event)"
    @dragleave="onDragLeave($event)"
    @drop="onDrop($event)"
>
    <div class="flex items-center gap-1">
        <span
            x-show="customizing"
            x-cloak
            draggable="true"
            @dragstart="onDragStart($event, 'guide')"
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
            <button type="button" @click="moveSection('guide', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
            </button>
            <button type="button" @click="moveSection('guide', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>
        <a
            href="{{ route('guide') }}"
            class="{{ $sectionBtn }} flex-1 {{ request()->routeIs('guide') ? 'bg-slate-800/60 text-slate-300' : '' }}"
            x-show="!$root.sidebarCollapsed"
            x-cloak
        >
            <span><span class="section-num">{{ $sNum }}</span> · Guide</span>
        </a>
    </div>
    <a
        href="{{ route('guide') }}"
        class="flex items-center justify-center rounded-xl px-2 py-2 text-[10px] font-bold uppercase tracking-[0.12em] {{ request()->routeIs('guide') ? 'bg-slate-800/60 text-slate-300' : 'text-slate-500 hover:bg-slate-800/60 hover:text-slate-300' }} transition"
        x-show="$root.sidebarCollapsed"
        x-cloak
        title="Guide"
    ><span class="section-num">{{ $sNum }}</span></a>
</div>

