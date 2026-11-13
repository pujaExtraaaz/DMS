<?php $__env->startSection('title', 'Party Details · ' . $customer->name); ?>

<?php
    $can = fn (...$perms) => auth()->user()?->hasAnyRole(['super-admin', 'client-admin']) || auth()->user()?->hasAnyPermission($perms);
?>

<?php $__env->startSection('content'); ?>
<div class="space-y-6 max-w-7xl mx-auto">
    
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => $customer->name]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($customer->name)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','href' => route('masters.customers.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.customers.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Parties
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
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('customers.edit', 'masters.edit', 'masters.manage')): ?>
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','href' => route('masters.customers.edit', $customer)]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.customers.edit', $customer))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                    </svg>
                    Edit Party
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

        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-indigo-700 text-white flex items-center justify-center font-bold text-2xl shadow-md shrink-0">
                <?php echo e(strtoupper(substr($customer->name, 0, 2))); ?>

            </div>
            <div class="space-y-1.5 flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl font-bold text-slate-900 truncate"><?php echo e($customer->name); ?></h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold
                        <?php echo e($customer->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600 border border-slate-200'); ?>">
                        <?php echo e($customer->is_active ? 'Active' : 'Inactive'); ?>

                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        <?php echo e($customer->party_type_key === 'sundry_creditors' ? 'bg-amber-100 text-amber-800' : ($customer->party_type_key === 'both' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800')); ?>">
                        <?php echo e($customer->party_type_label); ?>

                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">Code: <?php echo e($customer->code ?: '—'); ?></span>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->trade_name && $customer->trade_name !== $customer->name): ?>
                        <span>Trade Name: <strong class="text-slate-700 font-medium"><?php echo e($customer->trade_name); ?></strong></span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->businessGroup): ?>
                        <span class="inline-flex items-center gap-1 text-purple-700 font-medium">
                            <span class="font-emoji"><?php echo e($customer->businessGroup->logo_emoji ?: '🏢'); ?></span>
                            <?php echo e($customer->businessGroup->name); ?>

                        </span>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Basic Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Basic Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Name</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->name ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Code</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900"><?php echo e($customer->code ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Party Type</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->party_type_label); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Category / Customer Type</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->customerType?->name ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Business Group</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->businessGroup): ?>
                            <span class="inline-flex items-center gap-1 text-purple-700">
                                <span class="font-emoji"><?php echo e($customer->businessGroup->logo_emoji ?: '🏢'); ?></span>
                                <?php echo e($customer->businessGroup->name); ?>

                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Status</dt>
                    <dd class="mt-0.5">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo e($customer->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700'); ?>">
                            <?php echo e($customer->is_active ? 'Active' : 'Inactive'); ?>

                        </span>
                    </dd>
                </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->shipping_name): ?>
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold text-slate-500 tracking-wider">Shipping Name</dt>
                        <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->shipping_name); ?></dd>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </dl>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Contact Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Contact Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Phone / Mobile</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->phone): ?>
                            <a href="tel:<?php echo e($customer->phone); ?>" class="text-indigo-600 hover:underline"><?php echo e($customer->phone); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Email Address</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->email): ?>
                            <a href="mailto:<?php echo e($customer->email); ?>" class="text-indigo-600 hover:underline"><?php echo e($customer->email); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Date of Opening</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->opened_on?->format('d M Y') ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Reference By</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->reference_by ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Udyam Number</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->udyam_number ?: '—'); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">GEM Authorization Details</dt>
                    <dd class="mt-2 space-y-3">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $customer->gemAuthorizations; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $authorization): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                                <p class="text-sm font-semibold text-slate-900"><?php echo e($authorization->brand?->name ?: 'OEM'); ?></p>
                                <dl class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <dt class="text-slate-500">Product registered on GEM</dt>
                                        <dd class="font-medium text-slate-800"><?php echo e($authorization->gem_registered ? 'Yes' : 'No'); ?></dd>
                                    </div>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($authorization->gem_registered): ?>
                                        <div>
                                            <dt class="text-slate-500">Party authorized on GEM</dt>
                                            <dd class="font-medium text-slate-800"><?php echo e($authorization->gem_authorized ? 'Yes' : 'No'); ?></dd>
                                        </div>
                                        <div>
                                            <dt class="text-slate-500">GEM Authorization Number</dt>
                                            <dd class="font-medium text-slate-800"><?php echo e($authorization->authorization_number ?: '—'); ?></dd>
                                        </div>
                                        <div class="sm:col-span-2">
                                            <dt class="text-slate-500">GEM Star Rating Photo/Document</dt>
                                            <dd class="mt-1 space-y-1">
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_2 = true; $__currentLoopData = $authorization->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_2 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                    <div class="flex flex-wrap items-center gap-3">
                                                        <span class="font-medium text-slate-800"><?php echo e($document->original_name); ?></span>
                                                        <a href="<?php echo e(route('masters.customers.gem-documents.show', [$customer, $document])); ?>" target="_blank" rel="noopener" class="text-indigo-700 hover:underline">View</a>
                                                        <a href="<?php echo e(route('masters.customers.gem-documents.show', [$customer, $document])); ?>?download=1" class="text-indigo-700 hover:underline">Download</a>
                                                    </div>
                                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_2): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                                    <span>—</span>
                                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                            </dd>
                                        </div>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </dl>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            <span class="text-sm font-medium text-slate-900">—</span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Website</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->website): ?>
                            <a href="<?php echo e($customer->website); ?>" target="_blank" rel="noopener" class="text-indigo-600 hover:underline"><?php echo e($customer->website); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Location Link</dt>
                    <dd class="mt-0.5 font-medium text-slate-900 truncate">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->location_link): ?>
                            <a href="<?php echo e($customer->location_link); ?>" target="_blank" rel="noopener" class="text-indigo-600 hover:underline"><?php echo e($customer->location_link); ?></a>
                        <?php else: ?>
                            —
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Contact Person</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->defaultAddress()?->contact_person ?: ($customer->contacts->first()?->name ?: '—')); ?>

                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Contact Phone</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->defaultAddress()?->contact_phone ?: ($customer->contacts->first()?->phone ?: '—')); ?>

                    </dd>
                </div>
            </dl>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->contacts->isNotEmpty()): ?>
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <h4 class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Registered Contacts</h4>
                    <div class="space-y-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customer->contacts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contact): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <div class="flex items-center justify-between p-2 rounded-lg bg-slate-50 text-xs">
                                <div>
                                    <span class="font-semibold text-slate-800"><?php echo e($contact->name); ?></span>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contact->role): ?><span class="text-slate-500">(<?php echo e($contact->role); ?>)</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contact->is_primary): ?><span class="ml-1 text-[10px] bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded font-semibold">Primary</span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <div class="text-slate-600 space-x-2">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contact->phone): ?><span><?php echo e($contact->phone); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contact->email): ?><span><?php echo e($contact->email); ?></span><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                            </div>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </div>
                </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Tax &amp; Compliance Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tax &amp; Compliance Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">GSTIN</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900"><?php echo e($customer->gstin ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">PAN</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900"><?php echo e($customer->pan ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">State</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->state ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">PIN Code</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-900"><?php echo e($customer->pincode ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Area</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->area?->name ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Route</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->route?->name ?: '—'); ?></dd>
                </div>
            </dl>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Business &amp; Credit Details']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Business &amp; Credit Details']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Limit</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->credit_limit ? '₹ ' . number_format($customer->credit_limit, 2) : '—'); ?>

                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Days</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->credit_days ? $customer->credit_days . ' Days' : '—'); ?>

                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Period Basis</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->creditPeriodBasisLabel()); ?>

                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Interest Rate</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php echo e($customer->interest_rate ? $customer->interest_rate . '%' : '—'); ?>

                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Credit Status</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">
                        <?php
                            $creditTone = match ($customer->credit_status) {
                                'hold' => 'bg-amber-100 text-amber-800',
                                'blocked', 'frozen', 'restricted' => 'bg-red-100 text-red-800',
                                default => 'bg-emerald-100 text-emerald-800',
                            };
                        ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?php echo e($creditTone); ?>">
                            <?php echo e($customer->creditStatusLabel()); ?>

                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Payment Terms</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->payment_terms ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Sales Representative</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->salesperson?->name ?: '—'); ?></dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Sales Manager</dt>
                    <dd class="mt-0.5 font-medium text-slate-900"><?php echo e($customer->salesManager?->name ?: '—'); ?></dd>
                </div>
            </dl>
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

    
    <?php
        $target = $customer->activeSalesTarget() ?? $customer->latestSalesTarget();
        $targetAmount = (float) ($target?->target_amount ?? ($customer->target_amount ?? 0));
        $achievement = $target?->achievement;
        $achievedAmount = (float) ($achievement?->achieved_amount ?? 0);
        $shortfall = $targetAmount > 0 ? max(0, $targetAmount - $achievedAmount) : 0;
        $excess = $targetAmount > 0 ? max(0, $achievedAmount - $targetAmount) : 0;
        $percent = $targetAmount > 0 ? round(($achievedAmount / $targetAmount) * 100, 1) : 0;
        $status = $target ? $target->progress_status : ($targetAmount > 0 ? 'Not Started' : 'No Target');
        $remDays = $target ? $target->remaining_days_label : null;

        $contributingInvoices = collect();
        $contributingCreditNotes = collect();
        if ($target) {
            $startDate = $target->starts_on->toDateString();
            $endDate = $target->ends_on->toDateString();
            $contributingInvoices = \App\Domains\Sales\Models\Invoice::where('customer_id', $customer->id)
                ->whereBetween('invoice_date', [$startDate, $endDate])
                ->whereNotIn('status', ['cancelled', 'draft', 'void'])
                ->latest('invoice_date')
                ->get();
            $contributingCreditNotes = \App\Domains\Payment\Models\CreditNote::where('customer_id', $customer->id)
                ->whereBetween('credit_note_date', [$startDate, $endDate])
                ->where('status', 'posted')
                ->latest('credit_note_date')
                ->get();
        }
    ?>

    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Target Performance &amp; Status Tracking']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Target Performance &amp; Status Tracking']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="space-y-6">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($targetAmount > 0): ?>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Target</span>
                        <div class="mt-1 text-xl font-bold text-slate-900">₹ <?php echo e(number_format($targetAmount, 2)); ?></div>
                        <div class="text-[11px] text-slate-500 mt-0.5">
                            <?php echo e($target?->starts_on?->format('d M Y') ?? '—'); ?> to <?php echo e($target?->ends_on?->format('d M Y') ?? '—'); ?>

                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-emerald-200 bg-emerald-50/60">
                        <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Achieved Sales</span>
                        <div class="mt-1 text-xl font-bold text-emerald-800">₹ <?php echo e(number_format($achievedAmount, 2)); ?></div>
                        <div class="text-[11px] text-emerald-600 mt-0.5">
                            <?php echo e($contributingInvoices->count()); ?> invoice(s) net of credit notes
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200 <?php echo e($shortfall > 0 ? 'bg-amber-50/60 border-amber-200' : 'bg-slate-50'); ?>">
                        <span class="text-xs font-semibold <?php echo e($shortfall > 0 ? 'text-amber-800' : 'text-slate-500'); ?> uppercase tracking-wider">
                            Remaining Shortfall
                        </span>
                        <div class="mt-1 text-xl font-bold <?php echo e($shortfall > 0 ? 'text-amber-900' : 'text-emerald-700'); ?>">
                            ₹ <?php echo e(number_format($shortfall, 2)); ?>

                        </div>
                        <div class="text-[11px] <?php echo e($excess > 0 ? 'text-emerald-700 font-semibold' : 'text-slate-500'); ?> mt-0.5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($excess > 0): ?>
                                Excess: +₹ <?php echo e(number_format($excess, 2)); ?>

                            <?php else: ?>
                                Formula: Target &minus; Achieved
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="p-4 rounded-xl border border-slate-200 bg-slate-50">
                        <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Target Status</span>
                        <div class="mt-1 flex items-center gap-2">
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold
                                <?php if($status === 'Achieved'): ?> bg-emerald-100 text-emerald-800 border border-emerald-300
                                <?php elseif($status === 'In Progress'): ?> bg-blue-100 text-blue-800 border border-blue-300
                                <?php elseif($status === 'Period Expired'): ?> bg-rose-100 text-rose-800 border border-rose-300
                                <?php else: ?> bg-slate-100 text-slate-700 border border-slate-300 <?php endif; ?>">
                                <span class="w-2 h-2 rounded-full
                                    <?php if($status === 'Achieved'): ?> bg-emerald-500
                                    <?php elseif($status === 'In Progress'): ?> bg-blue-500
                                    <?php elseif($status === 'Period Expired'): ?> bg-rose-500
                                    <?php else: ?> bg-slate-400 <?php endif; ?>"></span>
                                <?php echo e($status); ?>

                            </span>
                        </div>
                        <div class="text-[11px] text-indigo-700 font-medium mt-1">
                            <?php echo e($remDays ?: 'Period Active'); ?>

                        </div>
                    </div>
                </div>

                
                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-2xs space-y-2">
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-semibold text-slate-700">Progress against assigned target</span>
                        <span class="font-bold text-slate-900"><?php echo e($percent); ?>% achieved</span>
                    </div>
                    <div class="w-full bg-slate-100 rounded-full h-3 overflow-hidden border border-slate-200">
                        <div class="h-3 rounded-full transition-all duration-500
                            <?php if($percent >= 100): ?> bg-emerald-500
                            <?php elseif($percent > 0): ?> bg-indigo-600
                            <?php else: ?> bg-slate-300 <?php endif; ?>"
                            style="width: <?php echo e(min(100, $percent)); ?>%"></div>
                    </div>
                    <div class="flex items-center justify-between text-[11px] text-slate-500 pt-0.5">
                        <span>Target Period: <strong class="text-slate-700"><?php echo e($target?->starts_on?->format('d-m-Y')); ?></strong> to <strong class="text-slate-700"><?php echo e($target?->ends_on?->format('d-m-Y')); ?></strong></span>
                        <span>₹ <?php echo e(number_format($achievedAmount, 2)); ?> of ₹ <?php echo e(number_format($targetAmount, 2)); ?></span>
                    </div>
                </div>

                
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Contributing Sales Invoices (Invoice-Wise Breakdown)</h4>
                        <span class="text-xs text-slate-500"><?php echo e($contributingInvoices->count()); ?> invoice(s)</span>
                    </div>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contributingInvoices->isNotEmpty()): ?>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 uppercase font-semibold">
                                    <tr>
                                        <th class="px-4 py-2.5">Invoice #</th>
                                        <th class="px-4 py-2.5">Invoice Date</th>
                                        <th class="px-4 py-2.5 text-right">Taxable Amount (₹)</th>
                                        <th class="px-4 py-2.5 text-right">Tax (₹)</th>
                                        <th class="px-4 py-2.5 text-right">Total Amount (₹)</th>
                                        <th class="px-4 py-2.5 text-center">Status</th>
                                        <th class="px-4 py-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $contributingInvoices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $inv): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-4 py-2 font-mono font-medium text-indigo-600">
                                                <a href="<?php echo e(route('sales.invoices.show', $inv)); ?>" class="hover:underline">
                                                    <?php echo e($inv->invoice_no); ?>

                                                </a>
                                            </td>
                                            <td class="px-4 py-2 text-slate-600"><?php echo e($inv->invoice_date?->format('d-m-Y')); ?></td>
                                            <td class="px-4 py-2 text-right text-slate-700">₹ <?php echo e(number_format($inv->subtotal, 2)); ?></td>
                                            <td class="px-4 py-2 text-right text-slate-500">₹ <?php echo e(number_format(($inv->cgst_amount ?? 0) + ($inv->sgst_amount ?? 0) + ($inv->igst_amount ?? 0), 2)); ?></td>
                                            <td class="px-4 py-2 text-right font-bold text-slate-900">₹ <?php echo e(number_format($inv->grand_total, 2)); ?></td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                    <?php echo e(ucfirst($inv->status)); ?>

                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-right">
                                                <a href="<?php echo e(route('sales.invoices.show', $inv)); ?>" class="text-indigo-600 hover:text-indigo-900 font-medium hover:underline">
                                                    View
                                                </a>
                                            </td>
                                        </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="rounded-lg border border-dashed border-slate-300 p-4 text-center text-xs text-slate-500">
                            No posted sales invoices recorded for this party within the active target period (<?php echo e($target?->starts_on?->format('d-m-Y')); ?> to <?php echo e($target?->ends_on?->format('d-m-Y')); ?>).
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($contributingCreditNotes->isNotEmpty()): ?>
                        <div class="mt-3 pt-3 border-t border-slate-100 space-y-2">
                            <h5 class="text-xs font-semibold text-rose-700 flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                Deducted Credit Notes &amp; Sales Returns
                            </h5>
                            <div class="overflow-x-auto rounded-lg border border-rose-200 bg-rose-50/30">
                                <table class="min-w-full divide-y divide-rose-100 text-left text-xs">
                                    <thead class="bg-rose-50 text-rose-800 uppercase font-semibold">
                                        <tr>
                                            <th class="px-4 py-2">Credit Note #</th>
                                            <th class="px-4 py-2">Date</th>
                                            <th class="px-4 py-2 text-right">Deducted Amount (₹)</th>
                                            <th class="px-4 py-2 text-center">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-rose-100 bg-white">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $contributingCreditNotes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cn): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                            <tr>
                                                <td class="px-4 py-2 font-mono text-rose-700"><?php echo e($cn->credit_note_no); ?></td>
                                                <td class="px-4 py-2 text-slate-600"><?php echo e($cn->credit_note_date?->format('d-m-Y')); ?></td>
                                                <td class="px-4 py-2 text-right font-bold text-rose-700">&minus; ₹ <?php echo e(number_format($cn->grand_total, 2)); ?></td>
                                                <td class="px-4 py-2 text-center text-[10px] text-slate-600"><?php echo e(ucfirst($cn->status)); ?></td>
                                            </tr>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->salesTargets->count() > 1): ?>
                    <div class="pt-4 border-t border-slate-200 space-y-2">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Historical Target Periods</h4>
                        <div class="overflow-x-auto rounded-lg border border-slate-200">
                            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                <thead class="bg-slate-50 text-slate-600 font-semibold">
                                    <tr>
                                        <th class="px-4 py-2">Period</th>
                                        <th class="px-4 py-2 text-right">Target Amount (₹)</th>
                                        <th class="px-4 py-2 text-right">Achieved (₹)</th>
                                        <th class="px-4 py-2 text-right">Shortfall (₹)</th>
                                        <th class="px-4 py-2 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customer->salesTargets; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $histTarget): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                        <tr class="hover:bg-slate-50">
                                            <td class="px-4 py-2 text-slate-700 font-medium">
                                                <?php echo e($histTarget->starts_on?->format('d-m-Y')); ?> to <?php echo e($histTarget->ends_on?->format('d-m-Y')); ?>

                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-slate-900">
                                                ₹ <?php echo e(number_format($histTarget->target_amount, 2)); ?>

                                            </td>
                                            <td class="px-4 py-2 text-right font-semibold text-emerald-700">
                                                ₹ <?php echo e(number_format($histTarget->achievement?->achieved_amount ?? 0, 2)); ?>

                                            </td>
                                            <td class="px-4 py-2 text-right text-slate-600">
                                                ₹ <?php echo e(number_format($histTarget->achievement?->pending_amount ?? 0, 2)); ?>

                                            </td>
                                            <td class="px-4 py-2 text-center">
                                                <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold
                                                    <?php if($histTarget->progress_status === 'Achieved'): ?> bg-emerald-100 text-emerald-800
                                                    <?php elseif($histTarget->progress_status === 'In Progress'): ?> bg-blue-100 text-blue-800
                                                    <?php elseif($histTarget->progress_status === 'Period Expired'): ?> bg-rose-100 text-rose-800
                                                    <?php else: ?> bg-slate-100 text-slate-700 <?php endif; ?>">
                                                    <?php echo e($histTarget->progress_status); ?>

                                                </span>
                                            </td>
                                        </tr>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php else: ?>
                <div class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-xs text-slate-500">
                    No sales target is currently assigned to this party.
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($can('customers.edit', 'masters.edit', 'masters.manage')): ?>
                        <a href="<?php echo e(route('masters.customers.edit', $customer)); ?>" class="text-indigo-600 font-semibold hover:underline ml-1">
                            Assign Target in Edit Party &rarr;
                        </a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Addresses &amp; Locations']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Addresses &amp; Locations']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->addresses->isNotEmpty()): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customer->addresses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $addr): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-900"><?php echo e($addr->label ?: 'Address #' . $addr->id); ?></span>
                            <div class="flex items-center gap-1.5">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-slate-200 text-slate-700">
                                    <?php echo e(ucfirst($addr->type ?: 'both')); ?>

                                </span>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addr->is_default_billing): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800">
                                        Default Billing
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addr->is_default_delivery): ?>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                        Default Delivery
                                    </span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                        </div>

                        <div class="text-xs text-slate-600 space-y-0.5">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addr->contact_person): ?>
                                <p><strong class="text-slate-700">Contact:</strong> <?php echo e($addr->contact_person); ?> <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addr->contact_phone): ?>(<?php echo e($addr->contact_phone); ?>)<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            <p class="text-slate-800"><?php echo e($addr->full_address); ?></p>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($addr->gstin): ?>
                                <p class="font-mono text-slate-500">GSTIN: <?php echo e($addr->gstin); ?></p>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php else: ?>
            <div class="text-sm text-slate-500">
                <p><strong class="text-slate-700">Address:</strong> <?php echo e($customer->address ?: '—'); ?></p>
                <p><strong class="text-slate-700">State / PIN:</strong> <?php echo e($customer->state ?: '—'); ?> <?php echo e($customer->pincode ? '(' . $customer->pincode . ')' : ''); ?></p>
            </div>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Bank Accounts']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Bank Accounts']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->bankAccounts->isNotEmpty()): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $customer->bankAccounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <?php
                        $num = (string) $acc->account_number;
                        $masked = strlen($num) > 4
                            ? str_repeat('•', max(4, strlen($num) - 4)) . ' ' . substr($num, -4)
                            : ($num ?: '—');
                    ?>
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-semibold text-sm text-slate-900"><?php echo e($acc->bank_name ?: 'Bank Account'); ?></span>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($acc->is_primary): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-800">
                                    Primary Account
                                </span>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>

                        <dl class="grid grid-cols-2 gap-2 text-xs">
                            <div>
                                <dt class="text-slate-500">Holder Name</dt>
                                <dd class="font-medium text-slate-800"><?php echo e($acc->account_holder_name ?: '—'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Account Number</dt>
                                <dd class="font-mono font-medium text-slate-800"><?php echo e($masked); ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">IFSC Code</dt>
                                <dd class="font-mono font-medium text-slate-800"><?php echo e($acc->ifsc_code ?: '—'); ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-500">Branch</dt>
                                <dd class="font-medium text-slate-800"><?php echo e($acc->branch_name ?: '—'); ?></dd>
                            </div>
                        </dl>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php else: ?>
            <p class="text-sm text-slate-400 py-2">— No bank accounts registered for this party —</p>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => 'Additional Information']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Additional Information']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
            <div class="sm:col-span-3">
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Notes / Remarks</dt>
                <dd class="mt-0.5 font-medium text-slate-800 whitespace-pre-line"><?php echo e($customer->credit_notes ?: '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Created Date</dt>
                <dd class="mt-0.5 text-xs text-slate-700"><?php echo e($customer->created_at?->format('d M Y, h:i A') ?: '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Last Updated</dt>
                <dd class="mt-0.5 text-xs text-slate-700"><?php echo e($customer->updated_at?->format('d M Y, h:i A') ?: '—'); ?></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500 tracking-wider">Company / Branch</dt>
                <dd class="mt-0.5 text-xs text-slate-700">
                    <?php echo e($customer->company?->name ?: '—'); ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($customer->branch): ?> / <?php echo e($customer->branch->name); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </dd>
            </div>
        </dl>
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


<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/masters/customers/show.blade.php ENDPATH**/ ?>