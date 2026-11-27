@if($can('hrms.view', 'hrms.manage', 'crm.view', 'crm.manage', 'service-support.view', 'service-support.manage', 'tally.view', 'tally.manage'))
    @php
        $sNum = $num();
        $sectionActive = $is('hrms.*') || $is('crm.*') || $is('service.*') || $is('tally.*') || $is('ess.*');
        $primaryRoute = $can('hrms.view', 'hrms.manage')
            ? route('hrms.dashboard')
            : ($can('service-support.view', 'service-support.manage') ? route('service.tickets.index') : route('crm.leads.index'));
    @endphp
    <div
        data-sidebar-section="hr_crm"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{
            open: {{ $sectionActive ? 'true' : 'false' }} || (sessionStorage.getItem('dmsSidebarHrCrmOpen') === '1'),
            serviceOpen: {{ $is('service.*') ? 'true' : 'false' }} || (sessionStorage.getItem('dmsSidebarServiceOpen') === '1'),
            init() {
                this.$watch('open', v => sessionStorage.setItem('dmsSidebarHrCrmOpen', v ? '1' : '0'));
                this.$watch('serviceOpen', v => sessionStorage.setItem('dmsSidebarServiceOpen', v ? '1' : '0'));
            }
        }"
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
                    <svg class="sidebar-section-icon shrink-0 text-slate-400 group-hover:text-slate-200 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                    </svg>
                    <span class="truncate">HR · CRM</span>
                </span>
                <svg class="sidebar-chevron shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
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
                <svg class="sidebar-item-icon shrink-0" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                </svg>
            </a>
        </div>

        {{-- Submenu (Expanded Only) --}}
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
            @if($can('hrms.view', 'hrms.manage'))
                <a href="{{ route('hrms.dashboard') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.dashboard', 'hrms.reports.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>HR Dashboard</span></a>
                <a href="{{ route('hrms.employees.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.employees.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Employees</span></a>
                <a href="{{ route('hrms.payroll.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.payroll.*', 'hrms.payroll-runs.*', 'hrms.salary-structures.*', 'hrms.pay-heads.*', 'hrms.comp-off.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Payroll</span></a>
                <a href="{{ route('hrms.departments.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.departments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Departments</span></a>
                <a href="{{ route('hrms.designations.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.designations.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Designations</span></a>
                <a href="{{ route('hrms.managers.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.managers.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Managers</span></a>
                <a href="{{ route('hrms.attendances.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.attendances.*', 'hrms.shifts.*', 'hrms.regularisations.*', 'hrms.essl.*', 'hrms.punch-conflicts.*', 'hrms.reports.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Attendance</span></a>
                <a href="{{ route('hrms.regularisations.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.regularisations.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Regularisations</span></a>
                <a href="{{ route('hrms.punch-conflicts.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.punch-conflicts.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Punch Conflicts</span></a>
                <a href="{{ route('hrms.shifts.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.shifts.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Shifts</span></a>
                <a href="{{ route('hrms.holidays.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.holidays.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Holidays</span></a>
                <a href="{{ route('hrms.leave-management.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.leave-management.*', 'hrms.leave-requests.*', 'hrms.leave-balances.*', 'hrms.leave-types.*', 'hrms.leave-policies.*', 'hrms.leave-encashments.*', 'hrms.comp-off.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leave Management</span></a>
                <a href="{{ route('hrms.reports.attendance-register') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.reports.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>HR Reports</span></a>
                <a href="{{ route('hrms.essl.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.essl.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>eSSL Sync</span></a>
                <a href="{{ route('hrms.expense-claims.index') }}" class="{{ $itemClass }} {{ $linkClass(['hrms.expense-claims.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Claims</span></a>
            @endif
            @if(auth()->user()?->employee)
                <a href="{{ route('ess.home') }}" class="{{ $itemClass }} {{ $linkClass(['ess.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>My ESS</span></a>
            @endif
            @if($can('crm.view', 'crm.manage'))
                <a href="{{ route('crm.leads.index') }}" class="{{ $itemClass }} {{ $linkClass(['crm.leads.index', 'crm.leads.show', 'crm.leads.edit', 'crm.leads.create', 'crm.leads.bulk-upload*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Leads (CRM)</span></a>
                <a href="{{ route('crm.leads.segments.index') }}" class="{{ $itemClass }} {{ $linkClass(['crm.leads.segments.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Lead Segments</span></a>
            @endif
            @if($can('service-support.view', 'service-support.manage', 'crm.view', 'crm.manage', 'hrms.view', 'hrms.manage'))
                <div class="pt-1">
                    <button
                        type="button"
                        class="{{ $itemClass }} w-full justify-between"
                        @click="serviceOpen = !serviceOpen"
                    >
                        <span x-show="!$root.sidebarCollapsed" x-cloak class="font-semibold tracking-wide">Service &amp; Support</span>
                        <svg class="h-3.5 w-3.5 shrink-0 text-slate-400 transition-transform" :class="serviceOpen && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                    </button>
                    <div x-show="serviceOpen" x-cloak class="mt-0.5 space-y-0.5 border-l border-slate-700/80 ml-3 pl-2">
                        <a href="{{ route('service.tickets.index') }}" class="{{ $itemClass }} {{ $linkClass(['service.tickets.index', 'service.tickets.show', 'service.tickets.edit', 'service.dashboard', 'service.escalate']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Ticket Register</span></a>
                        <a href="{{ route('service.schedule') }}" class="{{ $itemClass }} {{ $linkClass(['service.schedule']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Daily Visit Schedule</span></a>
                        <a href="{{ route('service.warranty-register') }}" class="{{ $itemClass }} {{ $linkClass(['service.warranty-register']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>OEM Warranty Register</span></a>
                        <a href="{{ route('service.quotations') }}" class="{{ $itemClass }} {{ $linkClass(['service.quotations']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Quotation &amp; Approval</span></a>
                        <a href="{{ route('service.feedback') }}" class="{{ $itemClass }} {{ $linkClass(['service.feedback']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Customer Feedback</span></a>
                        <a href="{{ route('service.serial-history') }}" class="{{ $itemClass }} {{ $linkClass(['service.serial-history']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Serial History</span></a>
                        <a href="{{ route('service.reports') }}" class="{{ $itemClass }} {{ $linkClass(['service.reports']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Reports &amp; Analytics</span></a>
                        <a href="{{ route('service.oem-centres.index') }}" class="{{ $itemClass }} {{ $linkClass(['service.oem-centres.*', 'service.sla.*']) }}"><span x-show="!$root.sidebarCollapsed" x-cloak>Service Master Settings</span></a>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endif
