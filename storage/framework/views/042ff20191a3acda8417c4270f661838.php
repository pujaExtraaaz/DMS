<?php $__env->startSection('title', 'Cheque Register'); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6" x-data="{
    // Modal states
    showDepositModal: false,
    showClearModal: false,
    showBounceModal: false,
    showCancelModal: false,
    showStaleModal: false,
    selectedCheque: null,

    // Form fields
    depositDate: '<?php echo e(now()->toDateString()); ?>',
    clearanceDate: '<?php echo e(now()->toDateString()); ?>',
    bounceReason: '',
    bounceCharges: '0.00',
    cancelReason: '',
    staleReason: 'Cheque expired validity period (> 90 days).',

    openDeposit(cheque) {
        this.selectedCheque = cheque;
        this.depositDate = '<?php echo e(now()->toDateString()); ?>';
        this.showDepositModal = true;
    },
    openClear(cheque) {
        this.selectedCheque = cheque;
        this.clearanceDate = '<?php echo e(now()->toDateString()); ?>';
        this.showClearModal = true;
    },
    openBounce(cheque) {
        this.selectedCheque = cheque;
        this.bounceReason = '';
        this.bounceCharges = '0.00';
        this.showBounceModal = true;
    },
    openCancel(cheque) {
        this.selectedCheque = cheque;
        this.cancelReason = '';
        this.showCancelModal = true;
    },
    openStale(cheque) {
        this.selectedCheque = cheque;
        this.staleReason = 'Cheque expired validity period (> 90 days).';
        this.showStaleModal = true;
    }
}">
    
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Cheque Register','description' => 'Comprehensive register for incoming and outgoing cheques, clearance tracking, deposit dates, and bounce management.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Cheque Register','description' => 'Comprehensive register for incoming and outgoing cheques, clearance tracking, deposit dates, and bounce management.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('cheque-books.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('cheque-books.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path></svg>
                Manage Cheque Books
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('cheques.export', request()->all())]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('cheques.export', request()->all()))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                Export CSV
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','href' => route('cheques.create')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('cheques.create'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Record / Issue Cheque
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
        
        <div class="rounded-xl border border-blue-200 bg-blue-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-blue-700">Cheques Issued</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-blue-900"><?php echo e(number_format($stats['total_issued_count'])); ?></span>
                <span class="text-xs font-semibold text-blue-700">₹<?php echo e(number_format($stats['total_issued_amount'], 2)); ?></span>
            </div>
        </div>

        
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-700">Cheques Received</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-indigo-900"><?php echo e(number_format($stats['total_received_count'])); ?></span>
                <span class="text-xs font-semibold text-indigo-700">₹<?php echo e(number_format($stats['total_received_amount'], 2)); ?></span>
            </div>
        </div>

        
        <div class="rounded-xl border border-amber-200 bg-amber-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-amber-700">Pending</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-amber-900"><?php echo e(number_format($stats['pending_count'])); ?></span>
                <span class="text-xs font-medium text-amber-600">Awaiting Deposit</span>
            </div>
        </div>

        
        <div class="rounded-xl border border-cyan-200 bg-cyan-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-cyan-700">Deposited</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-cyan-900"><?php echo e(number_format($stats['deposited_count'])); ?></span>
                <span class="text-xs font-medium text-cyan-600">In Clearing</span>
            </div>
        </div>

        
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-emerald-700">Cleared</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-emerald-900"><?php echo e(number_format($stats['cleared_count'])); ?></span>
                <span class="text-xs font-medium text-emerald-600">Realized</span>
            </div>
        </div>

        
        <div class="rounded-xl border border-rose-200 bg-rose-50/40 p-3.5 shadow-2xs">
            <span class="text-[11px] font-semibold uppercase tracking-wider text-rose-700">Bounced</span>
            <div class="mt-1 flex items-baseline justify-between">
                <span class="text-xl sm:text-2xl font-bold text-rose-900"><?php echo e(number_format($stats['bounced_count'])); ?></span>
                <span class="text-xs font-medium text-rose-600">Dishonored</span>
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

        <form method="GET" action="<?php echo e(route('cheques.index')); ?>" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-3">
            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Company Bank A/C</label>
                <select name="bank_account_no" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Bank Accounts</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php $no = $acc['bank_account_no'] ?? ''; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($no)): ?>
                            <option value="<?php echo e($no); ?>" <?php if(($filters['bank_account_no'] ?? '') === $no): echo 'selected'; endif; ?>>
                                <?php echo e($acc['bank_name'] ?: 'Bank'); ?> (<?php echo e(substr($no, -4)); ?>)
                            </option>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Cheque Type</label>
                <select name="cheque_type" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Types</option>
                    <option value="issued" <?php if(($filters['cheque_type'] ?? '') === 'issued'): echo 'selected'; endif; ?>>Issued (Outgoing)</option>
                    <option value="received" <?php if(($filters['cheque_type'] ?? '') === 'received'): echo 'selected'; endif; ?>>Received (Incoming)</option>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Status</label>
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Statuses</option>
                    <option value="pending" <?php if(($filters['status'] ?? '') === 'pending'): echo 'selected'; endif; ?>>Pending (Issued/Received)</option>
                    <option value="deposited" <?php if(($filters['status'] ?? '') === 'deposited'): echo 'selected'; endif; ?>>Deposited</option>
                    <option value="cleared" <?php if(($filters['status'] ?? '') === 'cleared'): echo 'selected'; endif; ?>>Cleared</option>
                    <option value="bounced" <?php if(($filters['status'] ?? '') === 'bounced'): echo 'selected'; endif; ?>>Bounced</option>
                    <option value="cancelled" <?php if(($filters['status'] ?? '') === 'cancelled'): echo 'selected'; endif; ?>>Cancelled</option>
                    <option value="stale" <?php if(($filters['status'] ?? '') === 'stale'): echo 'selected'; endif; ?>>Stale / Expired</option>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party Name</label>
                <select name="customer_id" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
                    <option value="">All Parties</option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <option value="<?php echo e($c->id); ?>" <?php if(($filters['customer_id'] ?? '') == $c->id): echo 'selected'; endif; ?>><?php echo e($c->name); ?></option>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </select>
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">From Date</label>
                <input type="date" name="date_from" value="<?php echo e($filters['date_from'] ?? ''); ?>" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
            </div>

            
            <div>
                <label class="block text-[11px] font-semibold text-slate-600 mb-1">To Date</label>
                <input type="date" name="date_to" value="<?php echo e($filters['date_to'] ?? ''); ?>" onchange="this.form.submit()" class="w-full rounded-lg border-gray-300 text-xs py-2 px-2.5 focus:ring-indigo-500 focus:border-indigo-500 bg-white">
            </div>

            
            <div class="sm:col-span-2 md:col-span-3 lg:col-span-6 flex items-center gap-2 pt-1 border-t border-slate-100">
                <div class="flex-1">
                    <input
                        type="search"
                        name="search"
                        value="<?php echo e($filters['search'] ?? ''); ?>"
                        placeholder="Search by cheque number, party name, voucher, bank, remarks..."
                        class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500 bg-white"
                    >
                </div>
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
Search <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(array_filter($filters ?? [])): ?>
                    <a href="<?php echo e(route('cheques.index')); ?>" class="text-xs text-slate-500 hover:text-slate-800 underline ml-2">Clear Filters</a>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>
        </form>

        
        <div class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-white">
            <table class="min-w-full divide-y divide-slate-200 text-xs">
                <thead class="bg-slate-50/80 text-slate-700 font-semibold">
                    <tr>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'cheque_no','label' => 'Cheque Number','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'cheque_no','label' => 'Cheque Number','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'bank_account_no','label' => 'Bank Account','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'bank_account_no','label' => 'Bank Account','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'cheque_date','label' => 'Cheque Date','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'cheque_date','label' => 'Cheque Date','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'customer','label' => 'Party Name','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'customer','label' => 'Party Name','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
                        <th class="px-3 py-2 text-center">Cheque Type</th>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'amount','label' => 'Amount','align' => 'right','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'amount','label' => 'Amount','align' => 'right','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
                        <th class="px-3 py-2 text-left">Purpose</th>
                        <th class="px-3 py-2 text-left">Reference Voucher</th>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'deposit_date','label' => 'Deposit Date','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'deposit_date','label' => 'Deposit Date','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'status','label' => 'Status','currentSort' => $sort,'currentDirection' => $direction]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'status','label' => 'Status','currentSort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort),'currentDirection' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
                        <th class="px-3 py-2 text-left">Remarks</th>
                        <th class="px-3 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isIssued = $item->isIssued();
                            $badge = $item->statusBadgeClass();
                        ?>
                        <tr class="hover:bg-slate-50/70 transition">
                            
                            <td class="px-3 py-2 font-mono font-bold text-slate-900 whitespace-nowrap">
                                <a href="<?php echo e(route('cheques.show', $item)); ?>" class="text-indigo-600 hover:text-indigo-900 hover:underline">
                                    <?php echo e($item->cheque_no); ?>

                                </a>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->chequeBook): ?>
                                    <span class="text-[10px] text-slate-400 block font-sans font-normal" title="<?php echo e($item->chequeBook->cheque_book_name); ?>">
                                        <?php echo e($item->chequeBook->cheque_book_name ?: 'Book'); ?>

                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="font-medium text-slate-800"><?php echo e($item->bank_name ?: 'Bank'); ?></span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->bank_account_no): ?>
                                    <span class="font-mono text-[11px] text-slate-500 block"><?php echo e($item->bank_account_no); ?></span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-3 py-2 whitespace-nowrap font-mono text-slate-700">
                                <?php echo e($item->cheque_date ? $item->cheque_date->format('d M Y') : '—'); ?>

                            </td>

                            
                            <td class="px-3 py-2 font-medium text-slate-900">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->customer): ?>
                                    <a href="<?php echo e(route('masters.customers.show', $item->customer)); ?>" class="hover:underline">
                                        <?php echo e($item->customer->name); ?>

                                    </a>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-3 py-2 text-center whitespace-nowrap">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isIssued): ?>
                                    <span class="inline-flex items-center rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-semibold text-blue-700 border border-blue-200">
                                        Issued
                                    </span>
                                <?php else: ?>
                                    <span class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-[10px] font-semibold text-indigo-700 border border-indigo-200">
                                        Received
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                                ₹<?php echo e(number_format($item->amount, 2)); ?>

                            </td>

                            
                            <td class="px-3 py-2 text-slate-700 uppercase font-mono text-[11px] whitespace-nowrap">
                                <?php echo e($item->purpose ?: '—'); ?>

                            </td>

                            
                            <td class="px-3 py-2 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->payment): ?>
                                    <a href="<?php echo e(route('payments.show', $item->payment)); ?>" class="text-indigo-600 hover:underline">
                                        <?php echo e($item->payment->payment_no); ?>

                                    </a>
                                <?php elseif($item->reference_voucher): ?>
                                    <span><?php echo e($item->reference_voucher); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">—</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>

                            
                            <td class="px-3 py-2 font-mono text-[11px] text-slate-700 whitespace-nowrap">
                                <?php echo e($item->deposit_date ? $item->deposit_date->format('d M Y') : '—'); ?>

                            </td>

                            
                            <td class="px-3 py-2 whitespace-nowrap">
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border <?php echo e($badge); ?>">
                                    <?php echo e($item->status_label); ?>

                                </span>
                            </td>

                            
                            <td class="px-3 py-2 text-slate-500 max-w-xs truncate" title="<?php echo e($item->notes ?: ($item->cancelled_reason ?: '')); ?>">
                                <?php echo e($item->notes ?: ($item->cancelled_reason ?: '—')); ?>

                            </td>

                            
                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-1">
                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED])): ?>
                                        <button
                                            type="button"
                                            @click="openDeposit({
                                                id: <?php echo e($item->id); ?>,
                                                cheque_no: '<?php echo e($item->cheque_no); ?>',
                                                amount: '<?php echo e(number_format($item->amount, 2)); ?>'
                                            })"
                                            class="rounded bg-cyan-50 border border-cyan-200 px-2 py-0.5 text-[11px] font-semibold text-cyan-700 hover:bg-cyan-100 transition"
                                            title="Mark as deposited in bank"
                                        >
                                            Deposit
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_DEPOSITED, \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED])): ?>
                                        <button
                                            type="button"
                                            @click="openClear({
                                                id: <?php echo e($item->id); ?>,
                                                cheque_no: '<?php echo e($item->cheque_no); ?>',
                                                amount: '<?php echo e(number_format($item->amount, 2)); ?>'
                                            })"
                                            class="rounded bg-emerald-50 border border-emerald-200 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 hover:bg-emerald-100 transition"
                                            title="Mark as cleared"
                                        >
                                            Clear
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(in_array($item->status, ['pending', \App\Domains\Payment\Models\Cheque::STATUS_DEPOSITED, \App\Domains\Payment\Models\Cheque::STATUS_ISSUED, \App\Domains\Payment\Models\Cheque::STATUS_RECEIVED])): ?>
                                        <button
                                            type="button"
                                            @click="openBounce({
                                                id: <?php echo e($item->id); ?>,
                                                cheque_no: '<?php echo e($item->cheque_no); ?>',
                                                party: '<?php echo e(addslashes($item->customer?->name ?: 'Party')); ?>',
                                                amount: '<?php echo e(number_format($item->amount, 2)); ?>'
                                            })"
                                            class="rounded bg-rose-50 border border-rose-200 px-2 py-0.5 text-[11px] font-semibold text-rose-700 hover:bg-rose-100 transition"
                                            title="Record cheque bounce"
                                        >
                                            Bounce
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED])): ?>
                                        <button
                                            type="button"
                                            @click="openCancel({
                                                id: <?php echo e($item->id); ?>,
                                                cheque_no: '<?php echo e($item->cheque_no); ?>'
                                            })"
                                            class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 hover:bg-slate-200 transition"
                                            title="Cancel cheque"
                                        >
                                            Cancel
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(!in_array($item->status, [\App\Domains\Payment\Models\Cheque::STATUS_CLEARED, \App\Domains\Payment\Models\Cheque::STATUS_BOUNCED, \App\Domains\Payment\Models\Cheque::STATUS_CANCELLED, \App\Domains\Payment\Models\Cheque::STATUS_STALE])): ?>
                                        <button
                                            type="button"
                                            @click="openStale({
                                                id: <?php echo e($item->id); ?>,
                                                cheque_no: '<?php echo e($item->cheque_no); ?>'
                                            })"
                                            class="rounded bg-amber-50 border border-amber-200 px-2 py-0.5 text-[11px] font-semibold text-amber-700 hover:bg-amber-100 transition"
                                            title="Mark as stale / expired"
                                        >
                                            Stale
                                        </button>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <a
                                        href="<?php echo e(route('cheques.show', $item)); ?>"
                                        class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-700 hover:bg-slate-200 transition"
                                    >
                                        View
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="12" class="px-4 py-8 text-center text-slate-500">
                                <div class="max-w-sm mx-auto space-y-2">
                                    <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                    <p class="font-medium text-slate-700">No cheques found in register.</p>
                                    <p class="text-[11px] text-slate-500">Record incoming client cheques or issue cheques against your configured cheque books.</p>
                                    <a href="<?php echo e(route('cheques.create')); ?>" class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700 transition">
                                        + Record / Issue Cheque
                                    </a>
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
        x-show="showDepositModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showDepositModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-slate-900" x-text="`Deposit Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showDepositModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`<?php echo e(url('cheques')); ?>/${selectedCheque?.id}/deposit`" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Deposit Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="deposit_date" x-model="depositDate" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-cyan-500 focus:border-cyan-500">
                </div>
                <p class="text-[11px] text-slate-500">Cheque status will transition to <strong>Deposited</strong> and track clearing date.</p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showDepositModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-cyan-600 px-3 py-1.5 font-semibold text-white hover:bg-cyan-700 shadow-2xs">Confirm Deposit</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showClearModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showClearModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-emerald-900" x-text="`Clear Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showClearModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`<?php echo e(url('cheques')); ?>/${selectedCheque?.id}/clear`" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Clearance Date <span class="text-rose-500">*</span></label>
                    <input type="date" name="clearance_date" x-model="clearanceDate" required class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-emerald-500 focus:border-emerald-500">
                </div>
                <p class="text-[11px] text-slate-500">The cheque amount (₹<span x-text="selectedCheque?.amount"></span>) will be recognized as cleared in your bank accounts.</p>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showClearModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-1.5 font-semibold text-white hover:bg-emerald-700 shadow-2xs">Confirm Clearance</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showBounceModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showBounceModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <div>
                    <h3 class="text-sm font-bold text-rose-900" x-text="`Cheque Bounce #${selectedCheque?.cheque_no}`"></h3>
                    <p class="text-[11px] text-slate-500" x-text="`Party: ${selectedCheque?.party}`"></p>
                </div>
                <button type="button" @click="showBounceModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`<?php echo e(url('cheques')); ?>/${selectedCheque?.id}/bounce`" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Bounce Reason</label>
                    <input type="text" name="reason" x-model="bounceReason" placeholder="e.g. Insufficient funds, signature mismatch" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Bank Bounce Charges (₹)</label>
                    <input type="number" step="0.01" name="charges" x-model="bounceCharges" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-rose-500 focus:border-rose-500">
                </div>
                <div class="rounded-lg bg-rose-50 p-2.5 text-[11px] text-rose-800 space-y-1">
                    <p class="font-semibold">⚠️ Party Risk & Freeze Logic:</p>
                    <p>Increments the party's cheque bounce counter. If the party exceeds 3 bounces, their credit status will be automatically frozen.</p>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showBounceModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-rose-600 px-3 py-1.5 font-semibold text-white hover:bg-rose-700 shadow-2xs">Confirm Bounce</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showCancelModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showCancelModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-slate-900" x-text="`Cancel Cheque #${selectedCheque?.cheque_no}`"></h3>
                <button type="button" @click="showCancelModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`<?php echo e(url('cheques')); ?>/${selectedCheque?.id}/cancel`" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Cancellation Reason</label>
                    <textarea name="reason" x-model="cancelReason" rows="2" placeholder="e.g. Stopped payment, replacement cheque issued..." class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showCancelModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-slate-800 px-3 py-1.5 font-semibold text-white hover:bg-slate-900 shadow-2xs">Confirm Cancel</button>
                </div>
            </form>
        </div>
    </div>

    
    <div
        x-show="showStaleModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4"
    >
        <div @click.outside="showStaleModal = false" class="relative w-full max-w-sm rounded-2xl bg-white p-5 shadow-2xl space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                <h3 class="text-sm font-bold text-amber-900" x-text="`Mark Cheque #${selectedCheque?.cheque_no} as Stale`"></h3>
                <button type="button" @click="showStaleModal = false" class="text-slate-400 hover:text-slate-600 text-lg font-bold">&times;</button>
            </div>
            <form method="POST" :action="`<?php echo e(url('cheques')); ?>/${selectedCheque?.id}/mark-stale`" class="space-y-3 text-xs">
                <?php echo csrf_field(); ?>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Remarks</label>
                    <textarea name="reason" x-model="staleReason" rows="2" class="w-full rounded-lg border-gray-300 text-xs py-2 px-3 focus:ring-amber-500 focus:border-amber-500"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showStaleModal = false" class="rounded-lg border border-slate-300 px-3 py-1.5 font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="rounded-lg bg-amber-600 px-3 py-1.5 font-semibold text-white hover:bg-amber-700 shadow-2xs">Mark as Stale</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/payments/cheques/index.blade.php ENDPATH**/ ?>