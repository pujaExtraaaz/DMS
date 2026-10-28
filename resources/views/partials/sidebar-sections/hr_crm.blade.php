@if($can('hrms.view', 'hrms.manage', 'crm.view', 'crm.manage', 'tally.view', 'tally.manage'))
    @php
        $sNum = $num();
        $sectionActive = $is('hrms.*') || $is('crm.*') || $is('tally.*');
        $primaryRoute = $can('hrms.view', 'hrms.manage')
            ? route('hrms.employees.index')
            : route('crm.leads.index');
    @endphp
    <div
        data-sidebar-section="hr_crm"
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
                @dragstart="onDragStart($event, 'hr_crm')"
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
                <button type="button" @click="moveSection('hr_crm', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('hr_crm', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="truncate">HR · CRM</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        {{-- Collapsed Icon Link --}}
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="{{ $primaryRoute }}"
                title="HR · CRM"
                aria-label="HR · CRM"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 {{ $sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white' }}"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </a>
        </div>

        {{-- Submenu (Expanded Only) --}}
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
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
                <a href="{{ route('crm.leads.index') }}" class="{{ $itemClass }} {{ $linkClass(['crm.leads.index', 'crm.leads.show', 'crm.leads.edit', 'crm.leads.create', 'crm.leads.bulk-upload*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leads (CRM)</span></a>
                <a href="{{ route('crm.leads.segments.index') }}" class="{{ $itemClass }} {{ $linkClass(['crm.leads.segments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Lead Segments</span></a>
            @endif
        </div>
    </div>
@endif
