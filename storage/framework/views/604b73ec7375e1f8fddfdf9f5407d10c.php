<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('reports.view', 'reports.manage')): ?>
    <?php
        $sNum = $num();
        $sectionActive = $is('reports.*', 'od.report*');
    ?>
    <div
        data-sidebar-section="reports"
        class="sidebar-section-item transition-all rounded-xl"
        @dragover="onDragOver($event)"
        @dragleave="onDragLeave($event)"
        @drop="onDrop($event)"
        x-data="{ open: <?php echo e($sectionActive ? 'true' : 'false'); ?> }"
    >
        
        <div x-show="!$root.sidebarCollapsed" class="flex items-center gap-1">
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
            <button
                type="button"
                class="<?php echo e($sectionBtn); ?> flex-1 group"
                @click="if (!customizing) open = !open"
            >
                <span class="flex items-center gap-2.5 min-w-0">
                    <svg class="h-4 w-4 shrink-0 text-slate-400 group-hover:text-slate-200 transition" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                    </svg>
                    <span class="truncate">Reports</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="<?php echo e(route('reports.sales')); ?>"
                title="Reports"
                aria-label="Reports"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 <?php echo e($sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'); ?>"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
                </svg>
            </a>
        </div>

        
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
            <a href="<?php echo e(route('reports.sales')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.sales'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Sales Register</span></a>
            <a href="<?php echo e(route('reports.stock')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.stock'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Movement</span></a>
            <a href="<?php echo e(route('reports.stock-ledger')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.stock-ledger'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Stock Ledger</span></a>
            <a href="<?php echo e(route('reports.party-statement')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.party-statement'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Party Ledger</span></a>
            <a href="<?php echo e(route('reports.purchase-register')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.purchase-register'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Purchase Register</span></a>
            <a href="<?php echo e(route('reports.outstanding')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.outstanding'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Outstanding Statement</span></a>
            <a href="<?php echo e(route('reports.margin')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.margin'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Margin Analysis</span></a>
            <a href="<?php echo e(route('reports.day-book')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.day-book'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Day Book</span></a>
            <a href="<?php echo e(route('reports.profit-loss')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.profit-loss'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Profit &amp; Loss</span></a>
            <a href="<?php echo e(route('reports.balance-sheet')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.balance-sheet'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Balance Sheet</span></a>
            <a href="<?php echo e(route('reports.trial-balance')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.trial-balance'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Trial Balance</span></a>
            <a href="<?php echo e(route('reports.od')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['reports.od', 'od.report*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>OD Report</span></a>
            <a href="<?php echo e(route('organization.sister-concerns.reports')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.sister-concerns.reports*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Sister Concern Reports</span></a>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/partials/sidebar-sections/reports.blade.php ENDPATH**/ ?>