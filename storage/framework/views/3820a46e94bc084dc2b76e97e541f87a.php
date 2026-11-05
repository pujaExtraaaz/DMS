<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('organization.view', 'organization.manage')): ?>
    <?php
        $sNum = $num();
        $sectionActive = $is('organization.*');
    ?>
    <div
        data-sidebar-section="setup"
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
                @dragstart="onDragStart($event, 'setup')"
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
                <button type="button" @click="moveSection('setup', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('setup', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                    <span class="truncate">Setup</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="<?php echo e(route('organization.company-profile')); ?>"
                title="Setup"
                aria-label="Setup"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 <?php echo e($sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'); ?>"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                </svg>
            </a>
        </div>

        
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
            <a href="<?php echo e(route('organization.company-profile')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.company-profile*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Company Profile</span></a>
            <a href="<?php echo e(route('organization.companies.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.companies.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Companies</span></a>
            <a href="<?php echo e(route('organization.branches.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.branches.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Branches</span></a>
            <a href="<?php echo e(route('organization.warehouses.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.warehouses.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Warehouses</span></a>
            <a href="<?php echo e(route('organization.financial-years.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.financial-years.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Financial Years</span></a>
            <a href="<?php echo e(route('organization.document-number-series.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.document-number-series.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Voucher Numbering</span></a>
            <a href="<?php echo e(route('organization.sister-concerns.dashboard')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['organization.sister-concerns.*', 'organization.business-groups.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Sister Concerns</span></a>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/partials/sidebar-sections/setup.blade.php ENDPATH**/ ?>