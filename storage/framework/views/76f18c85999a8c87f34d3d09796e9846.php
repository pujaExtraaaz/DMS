<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('masters.view', 'products.view', 'customers.view', 'price-master.view')): ?>
    <?php
        $sNum = $num();
        $sectionActive = $is('masters.*');
        $primaryRoute = $can('customers.view', 'masters.view')
            ? route('masters.customers.index')
            : ($can('products.view', 'masters.view') ? route('masters.products.index') : route('masters.brands.index'));
    ?>
    <div
        data-sidebar-section="masters"
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
                @dragstart="onDragStart($event, 'masters')"
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
                <button type="button" @click="moveSection('masters', 'up')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Up">
                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" /></svg>
                </button>
                <button type="button" @click="moveSection('masters', 'down')" class="p-1 rounded hover:bg-slate-700/80 text-slate-400 hover:text-white transition" title="Move Down">
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
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    <span class="truncate">Masters</span>
                </span>
                <svg class="h-3.5 w-3.5 shrink-0 transition-transform text-slate-400" :class="open && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
            </button>
        </div>

        
        <div x-show="$root.sidebarCollapsed" x-cloak class="flex justify-center py-0.5">
            <a
                href="<?php echo e($primaryRoute); ?>"
                title="Masters"
                aria-label="Masters"
                class="flex h-10 w-10 items-center justify-center rounded-xl transition-all duration-150 <?php echo e($sectionActive ? 'bg-indigo-600/90 text-white shadow-lg shadow-indigo-900/30' : 'text-slate-400 hover:bg-slate-800/80 hover:text-white'); ?>"
            >
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 5.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                </svg>
            </a>
        </div>

        
        <div x-show="open && !$root.sidebarCollapsed" x-cloak class="space-y-0.5">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('products.view', 'masters.view')): ?>
                <a href="<?php echo e(route('masters.brands.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.brands.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Brands</span></a>
                <a href="<?php echo e(route('masters.categories.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.categories.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Categories</span></a>
                <a href="<?php echo e(route('masters.sub-categories.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.sub-categories.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Sub Categories</span></a>
                <a href="<?php echo e(route('masters.products.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.products.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Products</span></a>
                <a href="<?php echo e(route('masters.product-types.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.product-types.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Product Types</span></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('customers.view', 'masters.view')): ?>
                <a href="<?php echo e(route('masters.customers.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.customers.*', 'masters.parties.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Parties</span></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('price-master.view', 'masters.view')): ?>
                <a href="<?php echo e(route('masters.price-masters.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.price-masters.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Price List</span></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('masters.view', 'products.view', 'customers.view')): ?>
                <a href="<?php echo e(route('masters.bulk-import.index')); ?>" class="<?php echo e($itemClass); ?> <?php echo e($linkClass(['masters.bulk-import.*'])); ?>"><span x-show="!$root.sidebarCollapsed" x-cloak>Bulk Import</span></a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/partials/sidebar-sections/masters.blade.php ENDPATH**/ ?>