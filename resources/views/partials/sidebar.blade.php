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

    $orderedSections = \App\Support\SidebarManager::getOrderedSections(auth()->user());
    $sectionCounter = 0;
    $num = function () use (&$sectionCounter) {
        return ++$sectionCounter;
    };
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
    // Strict tenant: never fall back to another company's row when the user has no company_id.
    $activeCompany = \App\Domains\Organization\Models\Company::find(auth()->user()?->company_id);
    // Accounting Books: paint already collapsed so module navigations do not re-animate the rail.
    $booksWorkspaceAside = books_show_aside();
@endphp

<aside
    :data-collapsed="sidebarCollapsed ? 'true' : 'false'"
    :class="[
        sidebarCollapsed ? 'lg:w-20 books-rail-collapsed' : 'lg:w-64 books-rail-open',
        sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0',
        sidebarAnimate ? 'transition-all duration-300' : ''
    ]"
    class="dms-sidebar fixed inset-y-0 left-0 z-50 flex -translate-x-full flex-col h-[100dvh] shrink-0 border-r border-slate-800/50 lg:static lg:translate-x-0 {{ $booksWorkspaceAside ? 'lg:w-20 books-rail-collapsed' : 'w-64' }}"
>
    <div
        :class="$root.sidebarCollapsed ? 'justify-center px-2' : 'justify-between px-4'"
        class="flex h-16 shrink-0 items-center border-b border-slate-700/60 transition-all overflow-hidden"
    >
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center" :class="$root.sidebarCollapsed ? 'justify-center w-full' : 'gap-3'" title="{{ $activeCompany?->name ?? 'DMS' }}">
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
            <span x-show="!$root.sidebarCollapsed" x-cloak class="truncate text-sm font-semibold tracking-tight text-white sidebar-expanded-text">{{ $activeCompany?->name ?? 'DMS' }}</span>
        </a>
        <button type="button" @click="sidebarOpen = false" x-show="!$root.sidebarCollapsed" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-800 hover:text-white lg:hidden">
            <span class="sr-only">Close sidebar</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
    </div>

    <nav
        x-data="{
            customizing: false,
            saving: false,
            draggedKey: null,
            statusMsg: '',
            statusType: 'success',
            tooltipText: '',
            tooltipTop: 0,
            tooltipLeft: 0,
            tooltipVisible: false,
            showTooltip(el, text) {
                if (!this.$root.sidebarCollapsed || !text) return;
                const rect = el.getBoundingClientRect();
                this.tooltipText = text;
                this.tooltipTop = rect.top + (rect.height / 2);
                this.tooltipLeft = rect.right + 12;
                this.tooltipVisible = true;
            },
            hideTooltip() {
                this.tooltipVisible = false;
            },
            startCustomizing() {
                this.customizing = true;
                this.statusMsg = '';
            },
            cancelCustomizing() {
                this.customizing = false;
                window.location.reload();
            },
            getCurrentOrder() {
                const container = this.$refs.sectionsContainer;
                if (!container) return [];
                return Array.from(container.children)
                    .map(el => el.getAttribute('data-sidebar-section'))
                    .filter(Boolean);
            },
            updateNumbers() {
                // Section numeric prefixes have been removed from the UI
            },
            moveSection(key, direction) {
                const container = this.$refs.sectionsContainer;
                const currentItem = container.querySelector(`[data-sidebar-section='${key}']`);
                if (!currentItem) return;

                if (direction === 'up') {
                    const prev = currentItem.previousElementSibling;
                    if (prev && prev.hasAttribute('data-sidebar-section')) {
                        container.insertBefore(currentItem, prev);
                        this.updateNumbers();
                    }
                } else if (direction === 'down') {
                    const next = currentItem.nextElementSibling;
                    if (next && next.hasAttribute('data-sidebar-section')) {
                        container.insertBefore(next, currentItem);
                        this.updateNumbers();
                    }
                }
            },
            onDragStart(event, key) {
                this.draggedKey = key;
                if (event.dataTransfer) {
                    event.dataTransfer.effectAllowed = 'move';
                    event.dataTransfer.setData('text/plain', key);
                }
                const el = this.$refs.sectionsContainer.querySelector(`[data-sidebar-section='${key}']`);
                if (el) el.classList.add('opacity-40', 'scale-[0.98]');
            },
            onDragOver(event) {
                event.preventDefault();
                if (event.dataTransfer) {
                    event.dataTransfer.dropEffect = 'move';
                }
                const target = event.target.closest('[data-sidebar-section]');
                if (target && this.draggedKey && target.getAttribute('data-sidebar-section') !== this.draggedKey) {
                    target.classList.add('ring-2', 'ring-indigo-500/80', 'bg-slate-800/80');
                }
            },
            onDragLeave(event) {
                const target = event.target.closest('[data-sidebar-section]');
                if (target) {
                    target.classList.remove('ring-2', 'ring-indigo-500/80', 'bg-slate-800/80');
                }
            },
            onDrop(event) {
                event.preventDefault();
                const target = event.target.closest('[data-sidebar-section]');
                if (target) {
                    target.classList.remove('ring-2', 'ring-indigo-500/80', 'bg-slate-800/80');
                }
                if (!this.draggedKey || !target) return;
                const draggedKey = this.draggedKey;
                const targetKey = target.getAttribute('data-sidebar-section');
                if (draggedKey === targetKey) return;

                const container = this.$refs.sectionsContainer;
                const draggedEl = container.querySelector(`[data-sidebar-section='${draggedKey}']`);
                if (!draggedEl) return;

                const targetRect = target.getBoundingClientRect();
                const isAfter = event.clientY > (targetRect.top + targetRect.height / 2);
                if (isAfter) {
                    target.after(draggedEl);
                } else {
                    container.insertBefore(draggedEl, target);
                }
                this.updateNumbers();
            },
            onDragEnd() {
                if (this.draggedKey) {
                    const el = this.$refs.sectionsContainer.querySelector(`[data-sidebar-section='${this.draggedKey}']`);
                    if (el) el.classList.remove('opacity-40', 'scale-[0.98]');
                }
                this.draggedKey = null;
                this.$refs.sectionsContainer.querySelectorAll('[data-sidebar-section]').forEach(el => {
                    el.classList.remove('ring-2', 'ring-indigo-500/80', 'bg-slate-800/80');
                });
            },
            async saveOrder() {
                this.saving = true;
                this.statusMsg = '';
                const order = this.getCurrentOrder();
                try {
                    const res = await fetch('{{ route('sidebar.preferences.update') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ order: order })
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        this.statusMsg = 'Order saved!';
                        this.statusType = 'success';
                        setTimeout(() => {
                            this.customizing = false;
                            this.statusMsg = '';
                        }, 800);
                    } else {
                        this.statusMsg = data.message || 'Failed to save order';
                        this.statusType = 'error';
                    }
                } catch (err) {
                    this.statusMsg = 'Error saving order';
                    this.statusType = 'error';
                } finally {
                    this.saving = false;
                }
            },
            async resetOrder() {
                if (!confirm('Reset sidebar menu sections to default order?')) return;
                this.saving = true;
                this.statusMsg = '';
                try {
                    const res = await fetch('{{ route('sidebar.preferences.reset') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json();
                    if (res.ok && data.success) {
                        window.location.reload();
                    } else {
                        this.statusMsg = data.message || 'Failed to reset';
                        this.statusType = 'error';
                    }
                } catch (err) {
                    this.statusMsg = 'Error resetting order';
                    this.statusType = 'error';
                } finally {
                    this.saving = false;
                }
            }
        }"
        :class="$root.sidebarCollapsed ? 'px-2 py-3 space-y-2' : 'px-3 py-3 space-y-1'"
        class="flex-1 overflow-y-auto overflow-x-hidden scrollbar-thin transition-all"
        @mouseover="
            const target = $event.target.closest('[title]');
            if (target && $root.sidebarCollapsed && $el.contains(target)) {
                showTooltip(target, target.getAttribute('title'));
            }
        "
        @mouseout="
            if ($event.target.closest('[title]')) {
                hideTooltip();
            }
        "
        @click.capture="if (!customizing && $event.target.closest('a')) sidebarOpen = false"
    >
        <div :class="$root.sidebarCollapsed ? 'flex justify-center py-0.5' : ''">
            <a
                href="{{ route('dashboard') }}"
                title="Dashboard"
                aria-label="Dashboard"
                :class="$root.sidebarCollapsed ? 'h-10 w-10 justify-center p-0 mx-auto' : 'w-full px-3 py-2 gap-3'"
                class="group flex items-center rounded-xl text-sm font-medium transition-all duration-150 {{ $linkClass(['dashboard'], true) }}"
            >
                <svg class="h-5 w-5 shrink-0 opacity-90" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" /></svg>
                <span x-show="!$root.sidebarCollapsed" x-cloak class="sidebar-expanded-text">Dashboard</span>
            </a>
        </div>

        {{-- Customize Mode Controls --}}
        <div x-show="!$root.sidebarCollapsed" class="py-1">
            <div x-show="!customizing" class="flex items-center justify-between px-1">
                <button
                    type="button"
                    @click="startCustomizing()"
                    class="group inline-flex items-center gap-1.5 rounded-lg px-2 py-1 text-[11px] font-medium text-slate-400 hover:bg-slate-800/80 hover:text-indigo-300 transition"
                    title="Rearrange sidebar sections"
                >
                    <svg class="h-3.5 w-3.5 text-slate-500 group-hover:text-indigo-400 transition" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                    <span>Customize Order</span>
                </button>
            </div>

            {{-- Active Customize Toolbar --}}
            <div x-show="customizing" x-cloak class="rounded-xl border border-indigo-500/30 bg-indigo-950/40 p-2 text-xs space-y-2 mb-2">
                <div class="flex items-center justify-between text-[11px] font-semibold text-indigo-200">
                    <span>Reorder Sections</span>
                    <span x-show="statusMsg" :class="statusType === 'error' ? 'text-rose-400' : 'text-emerald-400'" class="text-[10px]" x-text="statusMsg"></span>
                </div>
                <p class="text-[10px] text-slate-400 leading-tight">Drag handles or use ▲/▼ to arrange sections.</p>
                <div class="flex items-center gap-1.5 pt-1">
                    <button
                        type="button"
                        @click="saveOrder()"
                        :disabled="saving"
                        class="flex-1 inline-flex justify-center items-center rounded-lg bg-indigo-600 px-2 py-1 text-[11px] font-semibold text-white shadow hover:bg-indigo-500 disabled:opacity-50 transition"
                    >
                        <span x-show="!saving">Save</span>
                        <span x-show="saving">Saving...</span>
                    </button>
                    <button
                        type="button"
                        @click="cancelCustomizing()"
                        :disabled="saving"
                        class="rounded-lg border border-slate-700 bg-slate-800/80 px-2 py-1 text-[11px] font-medium text-slate-300 hover:bg-slate-700 disabled:opacity-50 transition"
                    >
                        Cancel
                    </button>
                    <button
                        type="button"
                        @click="resetOrder()"
                        :disabled="saving"
                        class="rounded-lg border border-rose-900/40 bg-rose-950/30 px-2 py-1 text-[11px] font-medium text-rose-300 hover:bg-rose-900/50 disabled:opacity-50 transition"
                        title="Reset to default order"
                    >
                        Reset
                    </button>
                </div>
            </div>
        </div>

        {{-- Dynamic Sections Container --}}
        <div x-ref="sectionsContainer" id="sidebar-sections-container" :class="$root.sidebarCollapsed ? 'space-y-2' : 'space-y-1'">
            @foreach($orderedSections as $sectionKey)
                @includeIf('partials.sidebar-sections.' . $sectionKey)
            @endforeach
        </div>

        {{-- User Section: Profile, Users & Roles, Log Out --}}
        <div class="border-t border-slate-700/60 pt-3 mt-3 space-y-1.5">
            <div :class="$root.sidebarCollapsed ? 'flex justify-center' : ''">
                <a
                    href="{{ route('profile.edit') }}"
                    title="Profile ({{ auth()->user()->name }})"
                    aria-label="Profile"
                    :class="$root.sidebarCollapsed ? 'h-10 w-10 justify-center p-0 mx-auto' : 'w-full px-3 py-2 gap-3'"
                    class="group flex items-center rounded-xl text-sm font-medium transition-all duration-150 text-slate-300 hover:bg-slate-800/80 hover:text-white"
                >
                    <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z" /></svg>
                    <div x-show="!$root.sidebarCollapsed" x-cloak class="min-w-0 flex-1 text-left sidebar-expanded-text">
                        <p class="truncate text-xs font-semibold text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-[10px] text-slate-400">{{ auth()->user()->email }}</p>
                    </div>
                </a>
            </div>

            @if(auth()->user()->hasAnyRole(['super-admin', 'client-admin']))
                <div :class="$root.sidebarCollapsed ? 'flex justify-center' : ''">
                    <a
                        href="{{ route('users.index') }}"
                        title="Users & Roles"
                        aria-label="Users & Roles"
                        :class="$root.sidebarCollapsed ? 'h-10 w-10 justify-center p-0 mx-auto' : 'w-full px-3 py-2 gap-3'"
                        class="group flex items-center rounded-xl text-sm font-medium transition-all duration-150 {{ $linkClass(['users.*']) }}"
                    >
                        <svg class="h-5 w-5 shrink-0 opacity-80" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />
                        </svg>
                        <div x-show="!$root.sidebarCollapsed" x-cloak class="min-w-0 flex-1 text-left sidebar-expanded-text">
                            <p class="truncate text-xs font-semibold text-slate-200">Users &amp; Roles</p>
                        </div>
                    </a>
                </div>
            @endif

            <form method="POST" action="{{ route('logout') }}" class="w-full">
                @csrf
                <div :class="$root.sidebarCollapsed ? 'flex justify-center' : ''">
                    <button
                        type="submit"
                        title="Log Out"
                        aria-label="Log Out"
                        :class="$root.sidebarCollapsed ? 'h-10 w-10 justify-center p-0 mx-auto' : 'w-full px-3 py-2 gap-3'"
                        class="flex items-center rounded-xl text-xs font-medium text-rose-400 hover:bg-rose-950/40 hover:text-rose-300 transition"
                    >
                        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" /></svg>
                        <span x-show="!$root.sidebarCollapsed" x-cloak class="sidebar-expanded-text">Log Out</span>
                    </button>
                </div>
            </form>
        </div>
    </nav>

    {{-- Collapsed Sidebar Tooltip (floating viewport element, outside nav overflow clipping) --}}
    <div
        x-show="tooltipVisible && $root.sidebarCollapsed"
        x-cloak
        :style="`top: ${tooltipTop}px; left: ${tooltipLeft}px;`"
        class="fixed z-[100] -translate-y-1/2 pointer-events-none rounded-lg bg-slate-900/95 px-3 py-1.5 text-xs font-semibold text-white shadow-xl shadow-black/50 border border-slate-700/80 whitespace-nowrap transition-opacity duration-150"
        x-text="tooltipText"
    ></div>
</aside>
