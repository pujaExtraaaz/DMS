<?php $__env->startSection('title', $company->name); ?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6">
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => $company->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($company->name)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('organization.companies.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('organization.companies.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('organization.companies.edit', $company)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('organization.companies.edit', $company))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                </svg>
                Edit
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->is_default): ?>
                <button type="button" disabled class="inline-flex items-center gap-1 font-medium rounded-lg opacity-80 cursor-default bg-indigo-50 text-indigo-700 border border-indigo-200 px-3 py-2 text-xs shadow-xs" title="Currently selected default company">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    Default Company
                </button>
            <?php elseif($company->is_active): ?>
                <form method="POST" action="<?php echo e(route('organization.companies.set-default', $company)); ?>" class="inline" onsubmit="return confirm('Set <?php echo e(addslashes($company->name)); ?> as the default company?')">
                    <?php echo csrf_field(); ?>
                    <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['type' => 'submit','variant' => 'secondary']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'secondary']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                        Set as Default
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
                </form>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
        <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between">
            <span><?php echo e(session('status')); ?></span>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-700 font-bold">&times;</button>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error') || $errors->any()): ?>
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
            <div class="font-semibold"><?php echo e(session('error') ?: 'Please correct the errors below:'); ?></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                <ul class="list-disc list-inside mt-1 text-xs space-y-0.5">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <li><?php echo e($err); ?></li>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </ul>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

    
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

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-6 pb-6 border-b border-slate-100">
            <div class="w-24 h-24 rounded-2xl border border-slate-200 bg-slate-50 flex items-center justify-center p-2 shrink-0 shadow-xs overflow-hidden">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->hasLogo() && $company->logo_url): ?>
                    <img src="<?php echo e($company->logo_url); ?>" alt="<?php echo e($company->name); ?> Logo" class="max-w-full max-h-full object-contain">
                <?php else: ?>
                    <div class="w-full h-full rounded-xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-2xl tracking-wider">
                        <?php echo e(strtoupper(substr($company->name, 0, 2))); ?>

                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </div>

            <div class="space-y-2 flex-1">
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-900"><?php echo e($company->name); ?></h2>
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium <?php echo e($company->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'); ?>">
                        <?php echo e($company->is_active ? 'Active' : 'Inactive'); ?>

                    </span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($company->is_default): ?>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200 shadow-xs">
                            Default Company
                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 text-sm text-slate-600">
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Code:</span>
                        <span class="font-mono font-medium text-slate-800 ml-1"><?php echo e($company->code); ?></span>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Legal Name:</span>
                        <span class="font-medium text-slate-800 ml-1"><?php echo e($company->legal_name ?: '—'); ?></span>
                    </div>
                    <div>
                        <span class="text-xs uppercase font-semibold text-slate-400">Business Group:</span>
                        <span class="font-medium text-slate-800 ml-1"><?php echo e($company->businessGroup?->name ?: '—'); ?></span>
                    </div>
                </div>
            </div>
        </div>

        
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-6">
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Branches</span>
                    <p class="text-lg font-bold text-slate-800"><?php echo e($company->branches->count()); ?></p>
                </div>
                <div class="p-2 bg-indigo-50 text-indigo-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Warehouses</span>
                    <p class="text-lg font-bold text-slate-800"><?php echo e($company->warehouses->count()); ?></p>
                </div>
                <div class="p-2 bg-emerald-50 text-emerald-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
            </div>
            <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-100 flex items-center justify-between">
                <div>
                    <span class="text-xs font-semibold uppercase text-slate-500">Financial Years</span>
                    <p class="text-lg font-bold text-slate-800"><?php echo e($company->financialYears->count()); ?></p>
                </div>
                <div class="p-2 bg-amber-50 text-amber-600 rounded-lg">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
            </div>
        </div>
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

        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Statutory Registrations</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm">
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">GSTIN</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->gstin ?: '—'); ?></span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">PAN</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->pan ?: '—'); ?></span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">TAN</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->tan ?: '—'); ?></span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">CIN</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->cin ?: '—'); ?></span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">Udyam Registration No</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->udyam_registration_no ?: '—'); ?></span>
            </div>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">MSME Category</span>
                <span class="font-medium text-slate-800"><?php echo e(filled($company->msme_category) && $company->msme_category !== 'none' ? ucfirst($company->msme_category) : '—'); ?></span>
            </div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($company->msme_registration_no)): ?>
            <div class="p-3 rounded-lg bg-slate-50 border border-slate-100">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block">MSME Registration No</span>
                <span class="font-mono font-medium text-slate-800"><?php echo e($company->msme_registration_no); ?></span>
            </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
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

        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Contact Details</h3>
        <?php
            $contacts = $company->contacts;
            $hasAnyContact = collect($contacts)->contains(function($c) {
                return filled($c['phone'] ?? null) || filled($c['email'] ?? null) || filled($c['website'] ?? null) || filled($c['address'] ?? null) || filled($c['state'] ?? null) || filled($c['pincode'] ?? null);
            });
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAnyContact): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $contactFilled = filled($contact['phone'] ?? null) || filled($contact['email'] ?? null) || filled($contact['website'] ?? null) || filled($contact['address'] ?? null) || filled($contact['state'] ?? null) || filled($contact['pincode'] ?? null);
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contactFilled): ?>
                        <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <span class="text-xs font-semibold uppercase tracking-wider text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">Contact #<?php echo e($index + 1); ?></span>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Phone</span>
                                    <span class="font-medium text-slate-800"><?php echo e($contact['phone'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Email</span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($contact['email'] ?? null)): ?>
                                        <a href="mailto:<?php echo e($contact['email']); ?>" class="font-medium text-indigo-600 hover:underline"><?php echo e($contact['email']); ?></a>
                                    <?php else: ?>
                                        <span class="font-medium text-slate-800">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Website</span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($contact['website'] ?? null)): ?>
                                        <a href="<?php echo e(str_starts_with($contact['website'], 'http') ? $contact['website'] : 'https://' . $contact['website']); ?>" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:underline"><?php echo e($contact['website']); ?></a>
                                    <?php else: ?>
                                        <span class="font-medium text-slate-800">—</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">State &amp; Pincode</span>
                                    <span class="font-medium text-slate-800"><?php echo e(($contact['state'] ?: '') . ($contact['state'] && $contact['pincode'] ? ' — ' : '') . ($contact['pincode'] ?: '') ?: '—'); ?></span>
                                </div>
                            </div>
                            <div>
                                <span class="text-xs font-semibold text-slate-500 block">Address</span>
                                <p class="font-medium text-slate-800 whitespace-pre-line text-sm mt-0.5"><?php echo e($contact['address'] ?: '—'); ?></p>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-500 italic">No contact details provided.</p>
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

        <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Banking &amp; Payment Accounts</h3>
                <p class="text-xs text-slate-500">Manage bank accounts and their associated cheque book registers.</p>
            </div>
        </div>
        <?php
            $bankAccounts = $company->bank_accounts;
            $hasAnyBank = collect($bankAccounts)->contains(function($b) {
                return filled($b['bank_name'] ?? null)
                    || filled($b['bank_account_no'] ?? null)
                    || filled($b['bank_ifsc'] ?? null)
                    || filled($b['upi_id'] ?? null)
                    || filled($b['swift_code'] ?? null)
                    || filled($b['branch'] ?? null)
                    || filled($b['bank_branch'] ?? null)
                    || filled($b['bsr_code'] ?? null)
                    || filled($b['client_code'] ?? null);
            });
        ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($hasAnyBank): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $bank): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $bankFilled = filled($bank['bank_name'] ?? null)
                            || filled($bank['bank_account_no'] ?? null)
                            || filled($bank['bank_ifsc'] ?? null)
                            || filled($bank['upi_id'] ?? null)
                            || filled($bank['swift_code'] ?? null)
                            || filled($bank['branch'] ?? null)
                            || filled($bank['bank_branch'] ?? null)
                            || filled($bank['bsr_code'] ?? null)
                            || filled($bank['client_code'] ?? null);
                        $accNo = trim((string) ($bank['bank_account_no'] ?? ''));
                        $accountChequeBooks = filled($accNo)
                            ? $company->chequeBooks->where('bank_account_no', $accNo)->sortBy('from_numeric')
                            : collect();
                        $hasErrorsForThisAccount = $errors->any() && old('bank_account_no') === $accNo;
                    ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bankFilled): ?>
                        <div x-data="{
                            showChequeBooks: <?php echo e($hasErrorsForThisAccount ? 'true' : 'false'); ?>,
                            showAddForm: <?php echo e($hasErrorsForThisAccount && !old('_method') ? 'true' : 'false'); ?>,
                            editingBook: null,
                            fromNum: '<?php echo e(old('bank_account_no') === $accNo ? old('from_number', '000001') : '000001'); ?>',
                            toNum: '<?php echo e(old('bank_account_no') === $accNo ? old('to_number', '000025') : '000025'); ?>',
                            get totalCheques() {
                                const from = parseInt(this.fromNum, 10);
                                const to = parseInt(this.toNum, 10);
                                return (!isNaN(from) && !isNaN(to) && to >= from) ? (to - from + 1) : 0;
                            }
                        }" class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                            <div class="flex flex-wrap items-center justify-between gap-2 pb-2 border-b border-slate-200">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-emerald-700 bg-emerald-100 px-2 py-0.5 rounded">Account #<?php echo e($index + 1); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($bank['bank_name'] ?? null)): ?>
                                        <span class="text-xs font-semibold text-slate-700"><?php echo e($bank['bank_name']); ?></span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($accNo)): ?>
                                    <button type="button"
                                            @click="showChequeBooks = !showChequeBooks"
                                            class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-2xs">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                        <span>Manage Cheque Books</span>
                                        <span class="rounded-full bg-indigo-200 px-1.5 py-0.2 text-[10px] font-bold text-indigo-800">
                                            <?php echo e($accountChequeBooks->count()); ?>

                                        </span>
                                    </button>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Bank Name</span>
                                    <span class="font-medium text-slate-800"><?php echo e($bank['bank_name'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Account Number</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['bank_account_no'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">IFSC Code</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['bank_ifsc'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">UPI ID</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['upi_id'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">SWIFT Code</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['swift_code'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Branch</span>
                                    <span class="font-medium text-slate-800"><?php echo e(($bank['branch'] ?? $bank['bank_branch'] ?? null) ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">BSR Code</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['bsr_code'] ?: '—'); ?></span>
                                </div>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Client Code</span>
                                    <span class="font-mono font-medium text-slate-800"><?php echo e($bank['client_code'] ?: '—'); ?></span>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($bank['od_limit']) && (float)$bank['od_limit'] > 0): ?>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">OD Limit</span>
                                    <span class="font-medium text-slate-800">₹<?php echo e(number_format((float)$bank['od_limit'], 2)); ?></span>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($bank['interest_rate']) && (float)$bank['interest_rate'] > 0): ?>
                                <div>
                                    <span class="text-xs font-semibold text-slate-500 block">Interest Rate</span>
                                    <span class="font-medium text-slate-800"><?php echo e(number_format((float)$bank['interest_rate'], 2)); ?>%</span>
                                </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>

                            
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($accNo)): ?>
                                <div x-show="showChequeBooks" x-cloak class="mt-4 pt-4 border-t border-slate-200">
                                    <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                                        <div>
                                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Cheque Book Register</h4>
                                            <p class="text-[11px] text-slate-500">Account: <?php echo e($bank['bank_name'] ?: 'Bank'); ?> &bull; <span class="font-mono font-medium text-slate-700"><?php echo e($accNo); ?></span></p>
                                        </div>
                                        <button type="button"
                                                @click="showAddForm = !showAddForm; editingBook = null"
                                                class="inline-flex items-center gap-1 rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-indigo-700 transition shadow-2xs">
                                            <span x-text="showAddForm ? '✕ Close Form' : '+ Add Cheque Book'">+ Add Cheque Book</span>
                                        </button>
                                    </div>

                                    
                                    <div x-show="showAddForm" x-cloak class="mb-4 rounded-lg border border-indigo-200 bg-white p-3.5 shadow-2xs">
                                        <h5 class="text-xs font-bold text-indigo-900 mb-2.5">Register New Cheque Book</h5>
                                        <form method="POST" action="<?php echo e(route('organization.companies.cheque-books.store', $company)); ?>" class="space-y-3">
                                            <?php echo csrf_field(); ?>
                                            <input type="hidden" name="bank_account_no" value="<?php echo e($accNo); ?>">
                                            <input type="hidden" name="bank_name" value="<?php echo e($bank['bank_name'] ?? ''); ?>">

                                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 text-xs">
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">Cheque Book Name</label>
                                                    <input type="text" name="cheque_book_name" placeholder="e.g. AVIT DIGITAL" value="<?php echo e(old('bank_account_no') === $accNo ? old('cheque_book_name') : ''); ?>" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">From Number <span class="text-rose-500">*</span></label>
                                                    <input type="text" name="from_number" x-model="fromNum" required placeholder="000001" class="w-full font-mono rounded-md border-slate-300 text-xs py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">To Number <span class="text-rose-500">*</span></label>
                                                    <input type="text" name="to_number" x-model="toNum" required placeholder="000025" class="w-full font-mono rounded-md border-slate-300 text-xs py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">Cheques Count</label>
                                                    <div class="rounded-md border border-slate-200 bg-slate-50 py-1.5 px-2.5 text-xs font-bold font-mono text-indigo-700" x-text="totalCheques + ' cheques'">
                                                        0 cheques
                                                    </div>
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">Status</label>
                                                    <select name="status" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                                        <option value="active">Active</option>
                                                        <option value="inactive">Inactive</option>
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="block font-semibold text-slate-700 mb-1">Issue Date</label>
                                                    <input type="date" name="issue_date" value="<?php echo e(old('bank_account_no') === $accNo ? old('issue_date') : ''); ?>" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                                <div class="sm:col-span-2">
                                                    <label class="block font-semibold text-slate-700 mb-1">Remarks / Reference</label>
                                                    <input type="text" name="remarks" placeholder="Optional notes" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2.5 focus:ring-indigo-500 focus:border-indigo-500">
                                                </div>
                                            </div>

                                            <div class="flex items-center justify-end gap-2 pt-1 border-t border-slate-100">
                                                <button type="button" @click="showAddForm = false" class="rounded-md border border-slate-300 bg-white px-3 py-1 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                                                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-700 shadow-2xs">Save Cheque Book</button>
                                            </div>
                                        </form>
                                    </div>

                                    
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($accountChequeBooks->isNotEmpty()): ?>
                                        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-2xs">
                                            <table class="min-w-full text-xs">
                                                <thead class="bg-slate-100/70 border-b border-slate-200 text-slate-700">
                                                    <tr>
                                                        <th class="px-3 py-2 text-left font-semibold">From Number</th>
                                                        <th class="px-3 py-2 text-left font-semibold">To Number</th>
                                                        <th class="px-3 py-2 text-center font-semibold">Cheques</th>
                                                        <th class="px-3 py-2 text-left font-semibold">Cheque Book Name</th>
                                                        <th class="px-3 py-2 text-center font-semibold">Status</th>
                                                        <th class="px-3 py-2 text-right font-semibold">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $accountChequeBooks; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cb): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                        <tr class="hover:bg-slate-50/70">
                                                            <td class="px-3 py-2 font-mono font-bold text-slate-800"><?php echo e($cb->from_number); ?></td>
                                                            <td class="px-3 py-2 font-mono font-bold text-slate-800"><?php echo e($cb->to_number); ?></td>
                                                            <td class="px-3 py-2 text-center">
                                                                <span class="rounded bg-indigo-50 font-mono font-bold text-indigo-700 px-2 py-0.5">
                                                                    <?php echo e($cb->total_cheques); ?>

                                                                </span>
                                                            </td>
                                                            <td class="px-3 py-2 font-medium text-slate-800">
                                                                <?php echo e($cb->cheque_book_name ?: '—'); ?>

                                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cb->issue_date): ?>
                                                                    <span class="text-[10px] text-slate-400 block">Issued: <?php echo e($cb->issue_date->format('d M Y')); ?></span>
                                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                            </td>
                                                            <td class="px-3 py-2 text-center">
                                                                <form method="POST" action="<?php echo e(route('organization.companies.cheque-books.toggle-status', [$company, $cb])); ?>" class="inline">
                                                                    <?php echo csrf_field(); ?>
                                                                    <button type="submit" title="Click to toggle status" class="inline-flex items-center">
                                                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($cb->status === 'active'): ?>
                                                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-800 hover:bg-emerald-200">Active</span>
                                                                        <?php else: ?>
                                                                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700 hover:bg-slate-300">Inactive</span>
                                                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                                                    </button>
                                                                </form>
                                                            </td>
                                                            <td class="px-3 py-2 text-right space-x-1">
                                                                <button type="button"
                                                                        @click="editingBook = <?php echo e(json_encode($cb)); ?>"
                                                                        class="rounded px-2 py-0.5 text-xs font-semibold text-slate-600 hover:text-indigo-600 hover:bg-indigo-50">
                                                                    Edit
                                                                </button>
                                                                <form method="POST" action="<?php echo e(route('organization.companies.cheque-books.destroy', [$company, $cb])); ?>" class="inline" onsubmit="return confirm('Delete cheque book range <?php echo e($cb->from_number); ?> - <?php echo e($cb->to_number); ?>?')">
                                                                    <?php echo csrf_field(); ?>
                                                                    <?php echo method_field('DELETE'); ?>
                                                                    <button type="submit" class="rounded px-2 py-0.5 text-xs font-semibold text-rose-600 hover:bg-rose-50">
                                                                        Delete
                                                                    </button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php else: ?>
                                        <div class="rounded-lg border border-dashed border-slate-300 bg-white p-4 text-center">
                                            <p class="text-xs text-slate-500 mb-2">No cheque books registered for account <span class="font-mono font-semibold text-slate-700"><?php echo e($accNo); ?></span>.</p>
                                            <button type="button" @click="showAddForm = true" class="rounded bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                                + Register First Cheque Book
                                            </button>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    
                                    <div x-show="editingBook !== null" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4">
                                        <div @click.away="editingBook = null" class="w-full max-w-lg rounded-xl bg-white p-5 shadow-xl border border-slate-200">
                                            <div class="flex items-center justify-between pb-3 border-b border-slate-100 mb-4">
                                                <h5 class="text-sm font-bold text-slate-800">Edit Cheque Book</h5>
                                                <button type="button" @click="editingBook = null" class="text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                                            </div>
                                            <template x-if="editingBook">
                                                <form method="POST" :action="`<?php echo e(url('organization/companies/' . $company->id . '/cheque-books')); ?>/${editingBook.id}`" class="space-y-3">
                                                    <?php echo csrf_field(); ?>
                                                    <?php echo method_field('PUT'); ?>
                                                    <input type="hidden" name="bank_account_no" :value="editingBook.bank_account_no">
                                                    <input type="hidden" name="bank_name" :value="editingBook.bank_name">

                                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                                                        <div class="sm:col-span-2">
                                                            <label class="block font-semibold text-slate-700 mb-1">Cheque Book Name</label>
                                                            <input type="text" name="cheque_book_name" x-model="editingBook.cheque_book_name" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2.5">
                                                        </div>
                                                        <div>
                                                            <label class="block font-semibold text-slate-700 mb-1">From Cheque Number</label>
                                                            <input type="text" name="from_number" x-model="editingBook.from_number" required class="w-full font-mono rounded-md border-slate-300 text-xs py-1.5 px-2.5">
                                                        </div>
                                                        <div>
                                                            <label class="block font-semibold text-slate-700 mb-1">To Cheque Number</label>
                                                            <input type="text" name="to_number" x-model="editingBook.to_number" required class="w-full font-mono rounded-md border-slate-300 text-xs py-1.5 px-2.5">
                                                        </div>
                                                        <div>
                                                            <label class="block font-semibold text-slate-700 mb-1">Status</label>
                                                            <select name="status" x-model="editingBook.status" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2">
                                                                <option value="active">Active</option>
                                                                <option value="inactive">Inactive</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label class="block font-semibold text-slate-700 mb-1">Issue Date</label>
                                                            <input type="date" name="issue_date" x-model="editingBook.issue_date" class="w-full rounded-md border-slate-300 text-xs py-1.5 px-2">
                                                        </div>
                                                    </div>

                                                    <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                                        <button type="button" @click="editingBook = null" class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                                                        <button type="submit" class="rounded-md bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">Update Cheque Book</button>
                                                    </div>
                                                </form>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-500 italic">No bank accounts configured.</p>
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

        <h3 class="text-sm font-semibold text-slate-800 mb-4 pb-2 border-b border-slate-100">Terms &amp; Preferences</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">Purchase Terms &amp; Conditions</span>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-line leading-relaxed min-h-[5rem]">
                    <?php echo e($company->purchase_terms_and_conditions ?: '—'); ?>

                </div>
            </div>
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 block mb-1">Selling Terms &amp; Conditions</span>
                <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-line leading-relaxed min-h-[5rem]">
                    <?php echo e($company->selling_terms_and_conditions ?: '—'); ?>

                </div>
            </div>
        </div>
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
</div>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/organization/companies/show.blade.php ENDPATH**/ ?>