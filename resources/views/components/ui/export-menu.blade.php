@props(['label' => 'Export', 'align' => 'left'])

<div
    class="relative inline-block text-left"
    x-data="{
        open: false,
        dropUp: false,
        alignLeft: {{ $align === 'right' ? 'false' : 'true' }},
        toggle() {
            if (!this.open) {
                this.calculatePlacement();
            }
            this.open = !this.open;
        },
        calculatePlacement() {
            this.$nextTick(() => {
                const btn = this.$refs.trigger;
                if (!btn) return;
                const rect = btn.getBoundingClientRect();

                // If space below the button is less than 160px and room above is greater, drop up
                const spaceBelow = window.innerHeight - rect.bottom;
                this.dropUp = spaceBelow < 160 && rect.top > 160;

                // Horizontal positioning:
                // Ensure dropdown (width ~180px) stays comfortably inside the visible viewport
                const menuWidth = 180;
                if (rect.left < menuWidth) {
                    // Close to left edge / left sidebar: open to the right
                    this.alignLeft = true;
                } else if ((window.innerWidth - rect.right) < menuWidth && rect.left >= menuWidth) {
                    // Close to right edge: open to the left
                    this.alignLeft = false;
                }
            });
        }
    }"
    @keydown.escape.window="open = false"
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        :aria-expanded="open.toString()"
        aria-haspopup="true"
        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-xs hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 active:bg-slate-100 transition-colors"
    >
        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
        </svg>
        <span>{{ $label }}</span>
        <svg
            class="h-3 w-3 text-slate-400 transition-transform duration-150"
            :class="open ? 'rotate-180' : ''"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            aria-hidden="true"
        >
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
        </svg>
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        @click.away="open = false"
        :class="[
            dropUp ? 'bottom-full mb-1.5' : 'top-full mt-1.5',
            alignLeft ? 'left-0 origin-top-left' : 'right-0 origin-top-right'
        ]"
        class="absolute z-50 w-44 rounded-xl bg-white p-1.5 shadow-xl border border-slate-200/90 ring-1 ring-slate-900/5 focus:outline-none"
        role="menu"
    >
        <a
            href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"
            @click="open = false"
            class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 hover:text-emerald-700 hover:bg-emerald-50/70 rounded-lg transition-colors group"
            role="menuitem"
            data-export-format="xlsx"
        >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-emerald-100/70 text-emerald-600 group-hover:bg-emerald-200/70 transition-colors">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
            </span>
            <div class="flex flex-col min-w-0">
                <span class="truncate">Excel <span class="text-[11px] font-normal text-slate-400 group-hover:text-emerald-600">(.xlsx)</span></span>
            </div>
        </a>

        <a
            href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
            @click="open = false"
            class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 hover:text-indigo-700 hover:bg-indigo-50/70 rounded-lg transition-colors group"
            role="menuitem"
            data-export-format="csv"
        >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-indigo-100/70 text-indigo-600 group-hover:bg-indigo-200/70 transition-colors">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7v10M12 7v10M16 7v10M3 7h18M3 17h18" />
                </svg>
            </span>
            <div class="flex flex-col min-w-0">
                <span class="truncate">CSV <span class="text-[11px] font-normal text-slate-400 group-hover:text-indigo-600">(.csv)</span></span>
            </div>
        </a>

        <a
            href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
            @click="open = false"
            class="flex items-center gap-2.5 px-3 py-2 text-xs font-medium text-slate-700 hover:text-rose-700 hover:bg-rose-50/70 rounded-lg transition-colors group"
            role="menuitem"
            data-export-format="pdf"
        >
            <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-md bg-rose-100/70 text-rose-600 group-hover:bg-rose-200/70 transition-colors">
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                </svg>
            </span>
            <div class="flex flex-col min-w-0">
                <span class="truncate">PDF <span class="text-[11px] font-normal text-slate-400 group-hover:text-rose-600">(.pdf)</span></span>
            </div>
        </a>
    </div>
</div>
