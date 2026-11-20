<?php $__env->startSection('title', 'Cheque Book Management'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{
    showAddModal: false,
    showEditModal: false,
    showCancelLeafModal: false,
    editingBook: null,
    cancellingBook: null,
    cancelChequeNo: '',
    cancelReason: '',

    // Add Form state
    fromNumber: '000001',
    toNumber: '000025',
    get totalAddCheques() {
        const from = parseInt(this.fromNumber, 10);
        const to = parseInt(this.toNumber, 10);
        return (!isNaN(from) && !isNaN(to) && to >= from) ? (to - from + 1) : 0;
    },

    openCancelLeaf(book) {
        this.cancellingBook = book;
        this.cancelChequeNo = book.next_available || '';
        this.cancelReason = '';
        this.showCancelLeafModal = true;
    },

    openEdit(book) {
        this.editingBook = book;
        this.showEditModal = true;
    }
}">
    
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Cheque Book Management','description' => 'Maintain cheque books, configure number ranges, and track leaf utilization for each company bank account.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Cheque Book Management','description' => 'Maintain cheque books, configure number ranges, and track leaf utilization for each company bank account.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('cheques.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('cheques.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                Cheque Register
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','@click' => 'showAddModal = true']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','@click' => 'showAddModal = true']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Add Cheque Book
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

    
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 sm:gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Books</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-slate-900"><?php echo e(number_format($stats['total_books'])); ?></span>
                <span class="text-xs font-medium text-slate-400">Books</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-slate-500">Total Leaves</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-indigo-700"><?php echo e(number_format($stats['total_leaves'])); ?></span>
                <span class="text-xs font-medium text-indigo-400">Leaves</span>
            </div>
        </div>

        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Used Leaves</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-blue-900"><?php echo e(number_format($stats['total_used'])); ?></span>
                <span class="text-xs font-medium text-blue-600">Issued</span>
            </div>
        </div>

        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Cancelled / Void</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-rose-900"><?php echo e(number_format($stats['total_cancelled'])); ?></span>
                <span class="text-xs font-medium text-rose-600">Spoiled</span>
            </div>
        </div>

        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Remaining</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-emerald-900"><?php echo e(number_format($stats['total_remaining'])); ?></span>
                <span class="text-xs font-medium text-emerald-600">Available</span>
            </div>
        </div>

        <div class="rounded-xl border border-purple-200 bg-purple-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-purple-700">Utilization</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-purple-900"><?php echo e($stats['utilization_percent']); ?>%</span>
                <span class="text-xs font-medium text-purple-600">Used</span>
            </div>
        </div>
    </div>

    
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

        <form method="GET" action="<?php echo e(route('cheque-books.index')); ?>" class="flex flex-col sm:flex-row flex-wrap items-center gap-3">
            <div class="w-full sm:w-64">
                <select name="bank_account_no" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Company Bank Accounts</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php $no = $acc['bank_account_no'] ?? ''; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($no)): ?>
                            <option value="<?php echo e($no); ?>" <?php if($selectedAccount === $no): echo 'selected'; endif; ?>>
                                <?php echo e($acc['bank_name'] ?: 'Bank'); ?> — <?php echo e($no); ?>

                            </option>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            <div class="w-full sm:w-40">
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="active" <?php if($statusFilter === 'active'): echo 'selected'; endif; ?>>Active Only</option>
                    <option value="inactive" <?php if($statusFilter === 'inactive'): echo 'selected'; endif; ?>>Inactive Only</option>
                </select>
            </div>

            <div class="flex-1 min-w-[200px] w-full">
                <input
                    type="search"
                    name="search"
                    value="<?php echo e($search); ?>"
                    placeholder="Search by book name, cheque range, bank, remarks..."
                    class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                >
            </div>

            <div class="flex items-center gap-2">
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'secondary','size' => 'sm']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'secondary','size' => 'sm']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Filter <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($selectedAccount || $statusFilter || $search): ?>
                    <a href="<?php echo e(route('cheque-books.index')); ?>" class="text-xs text-slate-500 hover:text-slate-800 underline">Reset</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </form>

        
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80 text-slate-700 font-semibold">
                    <tr>
                        <th class="px-3.5 py-2.5 text-left">Bank Account</th>
                        <th class="px-3.5 py-2.5 text-left">Cheque Book Name</th>
                        <th class="px-3.5 py-2.5 text-left">Number Range</th>
                        <th class="px-3.5 py-2.5 text-center">Leaves</th>
                        <th class="px-3.5 py-2.5 text-left">Utilization</th>
                        <th class="px-3.5 py-2.5 text-center">Next Available</th>
                        <th class="px-3.5 py-2.5 text-center">Issue Date</th>
                        <th class="px-3.5 py-2.5 text-center">Status</th>
                        <th class="px-3.5 py-2.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $book): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $used = $book->used_count;
                            $cancelled = $book->cancelled_count;
                            $remaining = $book->remaining_count;
                            $utilization = $book->utilization_percent;
                            $nextNo = $book->next_available_cheque_number;
                        ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-3.5 py-2.5">
                                <div class="font-medium text-slate-900"><?php echo e($book->bank_name ?: 'Bank'); ?></div>
                                <div class="font-mono text-[11px] text-slate-500"><?php echo e($book->bank_account_no); ?></div>
                            </td>
                            <td class="px-3.5 py-2.5">
                                <div class="font-semibold text-slate-800"><?php echo e($book->cheque_book_name ?: 'Cheque Book'); ?></div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($book->remarks): ?>
                                    <div class="text-[11px] text-slate-400 truncate max-w-xs" title="<?php echo e($book->remarks); ?>"><?php echo e($book->remarks); ?></div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3.5 py-2.5">
                                <span class="font-mono font-bold text-slate-800"><?php echo e($book->from_number); ?></span>
                                <span class="text-slate-400 mx-1">→</span>
                                <span class="font-mono font-bold text-slate-800"><?php echo e($book->to_number); ?></span>
                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <div class="flex items-center justify-center gap-1 font-mono text-[11px]">
                                    <span class="font-bold text-indigo-700 bg-indigo-50 px-1.5 py-0.5 rounded" title="Total Leaves"><?php echo e($book->total_cheques); ?></span>
                                    <span class="text-slate-300">/</span>
                                    <span class="font-semibold text-blue-700 bg-blue-50 px-1 py-0.5 rounded" title="Used Leaves"><?php echo e($used); ?>U</span>
                                    <span class="font-semibold text-rose-700 bg-rose-50 px-1 py-0.5 rounded" title="Cancelled Leaves"><?php echo e($cancelled); ?>C</span>
                                    <span class="font-semibold text-emerald-700 bg-emerald-50 px-1 py-0.5 rounded" title="Remaining Leaves"><?php echo e($remaining); ?>R</span>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 min-w-[130px]">
                                <div class="flex items-center justify-between text-[11px] mb-1">
                                    <span class="font-medium text-slate-600"><?php echo e($remaining); ?> left</span>
                                    <span class="font-bold <?php echo e($utilization >= 90 ? 'text-rose-600' : 'text-slate-700'); ?>"><?php echo e($utilization); ?>%</span>
                                </div>
                                <div class="w-full bg-slate-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full <?php echo e($utilization >= 90 ? 'bg-rose-500' : ($utilization >= 60 ? 'bg-amber-500' : 'bg-indigo-600')); ?>" style="width: <?php echo e($utilization); ?>%"></div>
                                </div>
                            </td>
                            <td class="px-3.5 py-2.5 text-center font-mono font-bold">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nextNo): ?>
                                    <span class="text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200 text-xs">
                                        <?php echo e($nextNo); ?>

                                    </span>
                                <?php else: ?>
                                    <span class="text-slate-400 italic text-[11px]">Exhausted</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3.5 py-2.5 text-center text-slate-600">
                                <?php echo e($book->issue_date ? $book->issue_date->format('d M Y') : '—'); ?>

                            </td>
                            <td class="px-3.5 py-2.5 text-center">
                                <form method="POST" action="<?php echo e(route('organization.cheque-books.toggle-status', $book)); ?>" class="inline">
                                    <?php echo csrf_field(); ?>
                                    <button type="submit" title="Click to toggle status" class="inline-flex items-center">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($book->status === 'active'): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 border border-emerald-200 hover:bg-emerald-100 transition">
                                                Active
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold text-slate-600 border border-slate-200 hover:bg-slate-200 transition">
                                                Inactive
                                            </span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-3.5 py-2.5 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1.5">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($remaining > 0): ?>
                                        <button
                                            type="button"
                                            @click="openCancelLeaf({
                                                id: <?php echo e($book->id); ?>,
                                                name: '<?php echo e(addslashes($book->cheque_book_name ?: 'Book')); ?>',
                                                range: '<?php echo e($book->from_number); ?> - <?php echo e($book->to_number); ?>',
                                                next_available: '<?php echo e($nextNo); ?>',
                                                available: <?php echo e(Illuminate\Support\Js::from($book->availableChequeNumbers())); ?>

                                            })"
                                            class="inline-flex items-center rounded bg-rose-50 border border-rose-200 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 transition"
                                            title="Void / Cancel an unused leaf"
                                        >
                                            Void Leaf
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <button
                                        type="button"
                                        @click="openEdit({
                                            id: <?php echo e($book->id); ?>,
                                            cheque_book_name: '<?php echo e(addslashes($book->cheque_book_name ?? '')); ?>',
                                            bank_account_no: '<?php echo e($book->bank_account_no); ?>',
                                            bank_name: '<?php echo e(addslashes($book->bank_name ?? '')); ?>',
                                            from_number: '<?php echo e($book->from_number); ?>',
                                            to_number: '<?php echo e($book->to_number); ?>',
                                            issue_date: '<?php echo e($book->issue_date ? $book->issue_date->format('Y-m-d') : ''); ?>',
                                            status: '<?php echo e($book->status); ?>',
                                            remarks: '<?php echo e(addslashes($book->remarks ?? '')); ?>'
                                        })"
                                        class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200 transition"
                                    >
                                        Edit
                                    </button>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($used === 0): ?>
                                        <form method="POST" action="<?php echo e(route('organization.cheque-books.destroy', $book)); ?>" class="inline" onsubmit="return confirm('Delete cheque book range <?php echo e($book->from_number); ?> - <?php echo e($book->to_number); ?>?')">
                                            <?php echo csrf_field(); ?>
                                            <?php echo method_field('DELETE'); ?>
                                            <button type="submit" class="inline-flex items-center rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-rose-600 hover:bg-rose-100 transition" title="Delete Cheque Book">
                                                Delete
                                            </button>
                                        </form>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <p class="font-medium text-slate-700">No cheque books configured yet.</p>
                                    <p class="text-[11px] text-slate-500">Add a cheque book for your company bank accounts to track number ranges and leaf utilization.</p>
                                    <button type="button" @click="showAddModal = true" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        + Add First Cheque Book
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($items->hasPages()): ?>
            <div class="mt-4"><?php echo e($items->links()); ?></div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    
    <div
        x-show="showAddModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showAddModal = false"
            class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Add New Cheque Book</h3>
                    <p class="text-xs text-slate-500">Configure cheque leaf number range for a bank account.</p>
                </div>
                <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form method="POST" action="<?php echo e(route('organization.cheque-books.store')); ?>" class="space-y-4">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="company_id" value="<?php echo e($company->id); ?>">

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Company Bank Account <span class="text-rose-500">*</span></label>
                    <select name="bank_account_no" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                        <option value="">-- Select Bank Account --</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <?php $no = $acc['bank_account_no'] ?? ''; ?>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($no)): ?>
                                <option value="<?php echo e($no); ?>">
                                    <?php echo e($acc['bank_name'] ?: 'Bank'); ?> — <?php echo e($no); ?>

                                </option>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Book Name / Ref</label>
                        <input type="text" name="cheque_book_name" placeholder="e.g. HDFC Main A/C" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" placeholder="e.g. HDFC Bank" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Starting Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="from_number" x-model="fromNumber" required placeholder="000001" class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ending Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="to_number" x-model="toNumber" required placeholder="000025" class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Total Leaves</label>
                        <div class="rounded-lg border border-slate-200 bg-slate-50 py-2 px-3 text-xs font-bold font-mono text-indigo-700" x-text="totalAddCheques + ' leaves'">
                            25 leaves
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Issue Date</label>
                        <input type="date" name="issue_date" value="<?php echo e(now()->toDateString()); ?>" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="active">Active (Available for issuance)</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" placeholder="Optional reference notes..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-2xs">Save Cheque Book</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showEditModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showEditModal = false"
            class="relative w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-slate-900">Edit Cheque Book</h3>
                    <p class="text-xs text-slate-500">Update cheque book details and status.</p>
                </div>
                <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form
                method="POST"
                :action="`<?php echo e(url('organization/cheque-books')); ?>/${editingBook?.id}`"
                class="space-y-4"
            >
                <?php echo csrf_field(); ?>
                <?php echo method_field('PUT'); ?>
                <input type="hidden" name="company_id" value="<?php echo e($company->id); ?>">
                <input type="hidden" name="bank_account_no" :value="editingBook?.bank_account_no">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Book Name</label>
                        <input type="text" name="cheque_book_name" x-model="editingBook.cheque_book_name" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Bank Name</label>
                        <input type="text" name="bank_name" x-model="editingBook.bank_name" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Starting Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="from_number" x-model="editingBook.from_number" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ending Number <span class="text-rose-500">*</span></label>
                        <input type="text" name="to_number" x-model="editingBook.to_number" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Issue Date</label>
                        <input type="date" name="issue_date" x-model="editingBook.issue_date" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Status</label>
                        <select name="status" x-model="editingBook.status" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="remarks" rows="2" x-model="editingBook.remarks" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showEditModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 shadow-2xs">Update Cheque Book</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showCancelLeafModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div
            @click.outside="showCancelLeafModal = false"
            class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h3 class="text-base font-bold text-rose-900">Void / Cancel Cheque Leaf</h3>
                    <p class="text-xs text-slate-500" x-text="`Book: ${cancellingBook?.name} (${cancellingBook?.range})`"></p>
                </div>
                <button type="button" @click="showCancelLeafModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>

            <form
                method="POST"
                :action="`<?php echo e(url('organization/cheque-books')); ?>/${cancellingBook?.id}/cancel-leaf`"
                class="space-y-4"
            >
                <?php echo csrf_field(); ?>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Cheque Number to Void <span class="text-rose-500">*</span></label>
                    <template x-if="cancellingBook?.available?.length > 0">
                        <select name="cheque_number" x-model="cancelChequeNo" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500 bg-white">
                            <template x-for="num in cancellingBook.available" :key="num">
                                <option :value="num" x-text="num"></option>
                            </template>
                        </select>
                    </template>
                    <template x-if="!cancellingBook?.available?.length">
                        <input type="text" name="cheque_number" x-model="cancelChequeNo" required class="w-full font-mono rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                    </template>
                    <p class="text-[11px] text-slate-500 mt-1">This leaf will be marked as spoiled/cancelled and cannot be issued.</p>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Reason for Cancellation</label>
                    <textarea name="reason" rows="2" x-model="cancelReason" placeholder="e.g. Leaf torn, printing error, spoiled..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                    <button type="button" @click="showCancelLeafModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-4 py-1.5 text-xs font-semibold text-white hover:bg-rose-700 shadow-2xs">Confirm Leaf Cancellation</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/banking/cheque-books/index.blade.php ENDPATH**/ ?>