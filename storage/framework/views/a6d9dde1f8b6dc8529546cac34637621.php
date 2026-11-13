<?php $__env->startSection('title', 'Purchase Invoices'); ?>
<?php $__env->startSection('content'); ?>
<div id="listing-container" data-dynamic-container x-data="purchaseInvoiceIndex()">
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Purchase Invoices']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Purchase Invoices']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','href' => route('purchasing.invoices.create')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.invoices.create'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
+ New PI <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
         <?php $__env->endSlot(); ?>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $attributes = $__attributesOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__attributesOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91a231a9270579fa1ae9246bd51fb785)): ?>
<?php $component = $__componentOriginal91a231a9270579fa1ae9246bd51fb785; ?>
<?php unset($__componentOriginal91a231a9270579fa1ae9246bd51fb785); ?>
<?php endif; ?>

    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if (isset($component)) { $__componentOriginala060c6a0206375167a5f0e5fbcb7b575 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala060c6a0206375167a5f0e5fbcb7b575 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.table-toolbar','data' => ['search' => $search ?? request('search'),'searchPlaceholder' => 'Search Invoice No, Supplier...','resetUrl' => route('purchasing.invoices.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.table-toolbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['search' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($search ?? request('search')),'search-placeholder' => 'Search Invoice No, Supplier...','reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.invoices.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php $__env->slot('filters', null, []); ?> 
                <select
                    name="status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Invoice Status: All</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['draft', 'posted', 'paid', 'cancelled']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $st): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($st); ?>" <?php if(request('status') === $st): echo 'selected'; endif; ?>><?php echo e(ucfirst(str_replace('_', ' ', $st))); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <select
                    name="material_receipt_status"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Material: All</option>
                    <option value="in_transit" <?php if(request('material_receipt_status') === 'in_transit'): echo 'selected'; endif; ?>>In Transit</option>
                    <option value="partially_received" <?php if(request('material_receipt_status') === 'partially_received'): echo 'selected'; endif; ?>>Partially Received</option>
                    <option value="received" <?php if(request('material_receipt_status') === 'received'): echo 'selected'; endif; ?>>Received</option>
                </select>

                <select
                    name="supplier_id"
                    data-dynamic-filter
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Supplier: All</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $suppliers ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $s): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($s->id); ?>" <?php if(request('supplier_id') == $s->id): echo 'selected'; endif; ?>><?php echo e($s->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>

                <div class="flex items-center gap-1">
                    <input
                        type="date"
                        name="date_from"
                        value="<?php echo e(request('date_from')); ?>"
                        data-dynamic-filter
                        placeholder="From"
                        title="From Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                    <span class="text-xs text-slate-400">to</span>
                    <input
                        type="date"
                        name="date_to"
                        value="<?php echo e(request('date_to')); ?>"
                        data-dynamic-filter
                        placeholder="To"
                        title="To Date"
                        class="rounded-lg border-slate-300 py-1 px-2 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    />
                </div>
             <?php $__env->endSlot(); ?>
             <?php $__env->slot('actions', null, []); ?> 
                <div class="relative inline-block text-left" x-data="{ open: false }">
                    <button
                        type="button"
                        @click="open = !open"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none"
                    >
                        <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        <span>Export</span>
                        <svg class="h-3 w-3 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div
                        x-show="open"
                        x-cloak
                        @click.away="open = false"
                        class="absolute right-0 z-20 mt-1 w-36 origin-top-right rounded-md bg-white py-1 shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
                    >
                        <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'xlsx'])); ?>" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            Excel (.xlsx)
                        </a>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'csv'])); ?>" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            CSV
                        </a>
                        <a href="<?php echo e(request()->fullUrlWithQuery(['export' => 'pdf'])); ?>" class="flex items-center px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                            PDF
                        </a>
                    </div>
                </div>
             <?php $__env->endSlot(); ?>
         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala060c6a0206375167a5f0e5fbcb7b575)): ?>
<?php $attributes = $__attributesOriginala060c6a0206375167a5f0e5fbcb7b575; ?>
<?php unset($__attributesOriginala060c6a0206375167a5f0e5fbcb7b575); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala060c6a0206375167a5f0e5fbcb7b575)): ?>
<?php $component = $__componentOriginala060c6a0206375167a5f0e5fbcb7b575; ?>
<?php unset($__componentOriginala060c6a0206375167a5f0e5fbcb7b575); ?>
<?php endif; ?>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'invoice_number','currentSort' => $sort ?? request('sort', 'invoice_date'),'currentDirection' => $direction ?? request('direction', 'desc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'invoice_number','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'invoice_date')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'desc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Invoice No
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $attributes = $__attributesOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__attributesOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $component = $__componentOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__componentOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'supplier_invoice_number','currentSort' => $sort ?? request('sort', 'invoice_date'),'currentDirection' => $direction ?? request('direction', 'desc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'supplier_invoice_number','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'invoice_date')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'desc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Supplier Inv
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $attributes = $__attributesOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__attributesOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $component = $__componentOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__componentOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'supplier','currentSort' => $sort ?? request('sort', 'invoice_date'),'currentDirection' => $direction ?? request('direction', 'desc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'supplier','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'invoice_date')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'desc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Supplier
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $attributes = $__attributesOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__attributesOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $component = $__componentOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__componentOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'invoice_date','currentSort' => $sort ?? request('sort', 'invoice_date'),'currentDirection' => $direction ?? request('direction', 'desc'),'defaultDirection' => 'desc']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'invoice_date','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'invoice_date')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'desc')),'default-direction' => 'desc']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Invoice Date
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $attributes = $__attributesOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__attributesOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $component = $__componentOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__componentOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500">Material Status</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500">Expected / Receipt</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-slate-500">Transit Days</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold text-slate-500">Payment Due</th>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'total_amount','currentSort' => $sort ?? request('sort', 'invoice_date'),'currentDirection' => $direction ?? request('direction', 'desc'),'defaultDirection' => 'desc','align' => 'right']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'total_amount','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'invoice_date')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'desc')),'default-direction' => 'desc','align' => 'right']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Total
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $attributes = $__attributesOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__attributesOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5967529496f56086d13f1257a544c7e9)): ?>
<?php $component = $__componentOriginal5967529496f56086d13f1257a544c7e9; ?>
<?php unset($__componentOriginal5967529496f56086d13f1257a544c7e9); ?>
<?php endif; ?>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $invoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $invoice): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $matClass = match ($invoice->material_receipt_status) {
                                'received' => 'bg-emerald-50 text-emerald-700',
                                'partially_received' => 'bg-amber-50 text-amber-800',
                                default => 'bg-sky-50 text-sky-800',
                            };
                        ?>
                        <tr class="hover:bg-slate-50/75 transition-colors">
                            <td class="px-6 py-4 text-sm font-medium font-mono text-xs text-slate-900">
                                <?php echo e($invoice->invoice_number ?? $invoice->invoice_no); ?>

                            </td>
                            <td class="px-6 py-4 text-sm font-mono text-xs text-slate-600">
                                <?php echo e($invoice->supplier_invoice_number ?? $invoice->supplier_invoice_no ?? '—'); ?>

                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">
                                <?php echo e($invoice->supplier?->name ?? '—'); ?>

                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?php echo e($invoice->invoice_date?->format('d M Y')); ?>

                            </td>
                            <td class="px-3 py-4">
                                <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-semibold <?php echo e($matClass); ?>">
                                    <?php echo e($invoice->materialReceiptLabel()); ?>

                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->isDeliveryOverdue()): ?>
                                    <span class="ml-1 text-[10px] font-bold text-rose-600">OVERDUE</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3 py-4 text-xs text-slate-600">
                                <div>Exp: <?php echo e($invoice->expected_delivery_date?->format('d M Y') ?? '—'); ?></div>
                                <div>Act: <?php echo e($invoice->actual_material_receipt_date?->format('d M Y') ?? '—'); ?></div>
                            </td>
                            <td class="px-3 py-4 text-sm text-right font-mono text-slate-700">
                                <?php echo e($invoice->daysInTransit() ?? '—'); ?>

                            </td>
                            <td class="px-3 py-4 text-xs text-slate-600">
                                <div><?php echo e($invoice->due_date?->format('d M Y') ?? '—'); ?></div>
                                <div class="font-medium"><?php echo e($invoice->paymentStatusLabel()); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-medium text-slate-900">
                                ₹<?php echo e(number_format($invoice->total_amount ?? $invoice->grand_total, 2)); ?>

                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap space-x-1">
                                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['size' => 'sm','variant' => 'secondary','href' => route('purchasing.invoices.preview', $invoice),'title' => 'Preview Purchase Invoice','ariaLabel' => 'Preview Purchase Invoice']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.invoices.preview', $invoice)),'title' => 'Preview Purchase Invoice','aria-label' => 'Preview Purchase Invoice']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    Preview
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['size' => 'sm','variant' => 'secondary','href' => route('purchasing.invoices.show', $invoice),'title' => 'View Purchase Invoice','ariaLabel' => 'View Purchase Invoice']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.invoices.show', $invoice)),'title' => 'View Purchase Invoice','aria-label' => 'View Purchase Invoice']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                    View
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->isEditable()): ?>
                                    <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['size' => 'sm','variant' => 'secondary','href' => route('purchasing.invoices.edit', $invoice),'class' => '!px-2.5 !py-1.5 inline-flex items-center gap-1','title' => 'Edit Purchase Invoice','ariaLabel' => 'Edit Purchase Invoice']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['size' => 'sm','variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('purchasing.invoices.edit', $invoice)),'class' => '!px-2.5 !py-1.5 inline-flex items-center gap-1','title' => 'Edit Purchase Invoice','aria-label' => 'Edit Purchase Invoice']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                        <svg class="w-3.5 h-3.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                                <?php else: ?>
                                    <button type="button" disabled class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs" title="Cannot edit: invoice is <?php echo e(str_replace('_', ' ', $invoice->status)); ?>" aria-label="Cannot edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($invoice->canBeDeleted()): ?>
                                    <form method="POST" action="<?php echo e(route('purchasing.invoices.destroy', $invoice)); ?>" class="inline delete-pi-form">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                        <button type="button"
                                                @click="promptDelete({
                                                    id: '<?php echo e($invoice->id); ?>',
                                                    invoiceNumber: '<?php echo e($invoice->invoice_number ?? $invoice->invoice_no); ?>',
                                                    supplierName: '<?php echo e(addslashes($invoice->supplier?->name ?? '—')); ?>',
                                                    invoiceDate: '<?php echo e($invoice->invoice_date?->format('d M Y') ?? '—'); ?>',
                                                    grandTotal: '₹<?php echo e(number_format($invoice->total_amount ?? $invoice->grand_total, 2)); ?>'
                                                })"
                                                class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-sm px-2.5 py-1.5 text-xs"
                                                title="Delete Purchase Invoice"
                                                aria-label="Delete Purchase Invoice"
                                                data-delete-pi="<?php echo e($invoice->id); ?>"
                                                data-pi-no="<?php echo e($invoice->invoice_number ?? $invoice->invoice_no); ?>">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button type="button"
                                            disabled
                                            class="inline-flex items-center gap-1 font-medium rounded-lg opacity-40 cursor-not-allowed bg-slate-100 text-slate-400 border border-slate-200 px-2.5 py-1.5 text-xs"
                                            title="<?php echo e($invoice->getDeleteBlockReason() ?? 'Cannot delete purchase invoice'); ?>"
                                            aria-label="Cannot delete">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="10" class="p-0">
                                <?php if (isset($component)) { $__componentOriginal3607a477fdef7402bc742abad5df9c51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3607a477fdef7402bc742abad5df9c51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.empty-state','data' => ['title' => 'No purchase invoices found','description' => 'Try clearing search or filters to see more results.','class' => 'border-0 rounded-none py-10']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'No purchase invoices found','description' => 'Try clearing search or filters to see more results.','class' => 'border-0 rounded-none py-10']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                     <?php $__env->slot('action', null, []); ?> 
                                        <a href="<?php echo e(route('purchasing.invoices.index')); ?>" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                     <?php $__env->endSlot(); ?>
                                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3607a477fdef7402bc742abad5df9c51)): ?>
<?php $attributes = $__attributesOriginal3607a477fdef7402bc742abad5df9c51; ?>
<?php unset($__attributesOriginal3607a477fdef7402bc742abad5df9c51); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3607a477fdef7402bc742abad5df9c51)): ?>
<?php $component = $__componentOriginal3607a477fdef7402bc742abad5df9c51; ?>
<?php unset($__componentOriginal3607a477fdef7402bc742abad5df9c51); ?>
<?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="mt-4"><?php echo e($invoices->links()); ?></div>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $attributes = $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>
<?php if (isset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93)): ?>
<?php $component = $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93; ?>
<?php unset($__componentOriginaldae4cd48acb67888a4631e1ba48f2f93); ?>
<?php endif; ?>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelDelete()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs transition-opacity"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Dialog panel -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200">
                <div class="bg-white p-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 text-red-600">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Purchase Invoice?</h3>
                            
                            <div class="mt-3 bg-slate-50 rounded-lg p-3 border border-slate-100 text-xs space-y-1.5">
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Invoice Number:</span>
                                    <span class="font-bold font-mono text-slate-800" x-text="deletingInvoiceNo"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Supplier:</span>
                                    <span class="font-semibold text-slate-800" x-text="deletingSupplierName"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Invoice Date:</span>
                                    <span class="text-slate-700" x-text="deletingInvoiceDate"></span>
                                </div>
                                <div class="flex justify-between border-t border-slate-200 pt-1 font-semibold">
                                    <span class="text-slate-600">Grand Total:</span>
                                    <span class="text-indigo-600 font-mono" x-text="deletingGrandTotal"></span>
                                </div>
                            </div>

                            <div class="mt-3">
                                <p class="text-xs text-amber-700 bg-amber-50 p-2.5 rounded border border-amber-200 leading-relaxed">
                                    <strong>Warning:</strong> Deleting this purchase invoice may affect linked accounting and inventory records.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isDeleting">Delete Invoice</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

<?php $__env->startPush('scripts'); ?>
<script>
function purchaseInvoiceIndex() {
    return {
        showDeleteModal: false,
        deletingInvoiceId: null,
        deletingInvoiceNo: '',
        deletingSupplierName: '',
        deletingInvoiceDate: '',
        deletingGrandTotal: '',
        isDeleting: false,
        notification: { show: false, type: 'success', message: '' },

        promptDelete(data) {
            this.deletingInvoiceId = data.id;
            this.deletingInvoiceNo = data.invoiceNumber || 'this purchase invoice';
            this.deletingSupplierName = data.supplierName || '—';
            this.deletingInvoiceDate = data.invoiceDate || '—';
            this.deletingGrandTotal = data.grandTotal || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingInvoiceId = null;
            this.deletingInvoiceNo = '';
            this.deletingSupplierName = '';
            this.deletingInvoiceDate = '';
            this.deletingGrandTotal = '';
        },

        async executeDelete() {
            if (!this.deletingInvoiceId || this.isDeleting) return;
            this.isDeleting = true;

            const url = `<?php echo e(url('purchasing/invoices')); ?>/${this.deletingInvoiceId}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Purchase invoice deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete purchase invoice.');
                    this.showDeleteModal = false;
                }
            } catch (err) {
                console.error('Delete error:', err);
                this.showNotification('error', 'A network error occurred while deleting the purchase invoice.');
                this.showDeleteModal = false;
            } finally {
                this.isDeleting = false;
                this.deletingInvoiceId = null;
            }
        },

        async refreshList() {
            const container = document.querySelector('#listing-container');
            if (!container) return;
            const currentUrl = new URL(window.location.href);

            try {
                const refreshRes = await fetch(currentUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-DMS-Dynamic': '1',
                    }
                });

                if (refreshRes.ok) {
                    const html = await refreshRes.text();
                    const doc = new DOMParser().parseFromString(html, 'text/html');
                    const newContainer = doc.querySelector('#listing-container');
                    if (newContainer) {
                        container.innerHTML = newContainer.innerHTML;
                        window.history.replaceState(null, '', currentUrl.toString());
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(container);
                        }
                    } else {
                        window.location.href = currentUrl.toString();
                    }
                } else {
                    window.location.href = currentUrl.toString();
                }
            } catch (e) {
                console.error('Refresh list error:', e);
                window.location.href = currentUrl.toString();
            }
        },

        showNotification(type, message) {
            this.notification = { show: true, type, message };
            setTimeout(() => {
                this.notification.show = false;
            }, 6000);
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/purchasing/invoices/index.blade.php ENDPATH**/ ?>