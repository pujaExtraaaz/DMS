<?php $__env->startSection('title', 'Products'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $currentListingUrl = request()->getRequestUri();
?>
<div id="listing-container" data-dynamic-container x-data="productIndex()">
    <?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Products']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Products']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if (isset($component)) { $__componentOriginal35b050e20f206e9347afbbec3f9521d8 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal35b050e20f206e9347afbbec3f9521d8 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.export-menu','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.export-menu'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal35b050e20f206e9347afbbec3f9521d8)): ?>
<?php $attributes = $__attributesOriginal35b050e20f206e9347afbbec3f9521d8; ?>
<?php unset($__attributesOriginal35b050e20f206e9347afbbec3f9521d8); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal35b050e20f206e9347afbbec3f9521d8)): ?>
<?php $component = $__componentOriginal35b050e20f206e9347afbbec3f9521d8; ?>
<?php unset($__componentOriginal35b050e20f206e9347afbbec3f9521d8); ?>
<?php endif; ?>
            <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'primary','href' => route('masters.products.create', ['return_url' => $currentListingUrl]),'dataPreserveState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.create', ['return_url' => $currentListingUrl])),'data-preserve-state' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
+ Add Product <?php echo $__env->renderComponent(); ?>
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

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($bannerProduct) && $bannerProduct): ?>
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="h-9 w-9 shrink-0 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-emerald-900">Recently Created Product:</span>
                        <span class="text-xs font-semibold text-emerald-800"><?php echo e($bannerProduct->name); ?></span>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($bannerProduct->sku): ?>
                            <span class="font-mono text-[11px] text-emerald-700 bg-emerald-100/60 px-1.5 py-0.5 rounded">Part: <?php echo e($bannerProduct->sku); ?></span>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        Product was created successfully. Your previously selected filters and page are preserved below.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','size' => 'sm','href' => route('masters.products.show', ['product' => $bannerProduct, 'return_url' => $currentListingUrl]),'dataPreserveState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','size' => 'sm','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.show', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])),'data-preserve-state' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','size' => 'sm','href' => route('masters.products.edit', ['product' => $bannerProduct, 'return_url' => $currentListingUrl]),'dataPreserveState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','size' => 'sm','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.edit', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])),'data-preserve-state' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Edit <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
            </div>
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

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(($serialMatches ?? collect())->isNotEmpty()): ?>
            <div class="mb-6 space-y-4">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $serialMatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $serial): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                        <p class="mb-3 text-sm font-semibold text-indigo-900">Serial <?php echo e($serial->serial_number); ?></p>
                        <?php echo $__env->make('inventory.serials.partials.warranty-result', ['serial' => $serial], array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if (isset($component)) { $__componentOriginala060c6a0206375167a5f0e5fbcb7b575 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala060c6a0206375167a5f0e5fbcb7b575 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.table-toolbar','data' => ['search' => $search ?? request('search'),'searchPlaceholder' => 'Search Name, Part Number, Serial No, Model...','resetUrl' => route('masters.products.index')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.table-toolbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['search' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($search ?? request('search')),'search-placeholder' => 'Search Name, Part Number, Serial No, Model...','reset-url' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.index'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

             <?php $__env->slot('filters', null, []); ?> 
                <div
                    class="flex flex-wrap items-center gap-2"
                    data-hierarchical-filter-group
                    data-all-categories='<?php echo json_encode($allCategoriesData ?? [], 15, 512) ?>'
                    data-all-subcategories='<?php echo json_encode($allSubCategoriesData ?? [], 15, 512) ?>'
                >
                    <select
                        name="brand_id"
                        id="filter-brand-id"
                        data-dynamic-filter
                        aria-label="Filter by Brand"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Brand: All</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $brands ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($b->id); ?>" <?php if((string) ($brandId ?? request('brand_id')) === (string) $b->id): echo 'selected'; endif; ?>><?php echo e($b->name); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>

                    <select
                        name="category_id"
                        id="filter-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Category"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Category: All</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $categories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $cat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($cat->id); ?>" <?php if((string) ($categoryId ?? request('category_id')) === (string) $cat->id): echo 'selected'; endif; ?>><?php echo e($cat->name); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>

                    <select
                        name="sub_category_id"
                        id="filter-sub-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Subcategory"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Subcategory: All</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $subCategories ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $sc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($sc->id); ?>" <?php if((string) ($subCategoryId ?? request('sub_category_id', request('subcategory_id'))) === (string) $sc->id): echo 'selected'; endif; ?>><?php echo e($sc->name); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>

                    <select
                        name="product_type"
                        data-dynamic-filter
                        aria-label="Filter by Product Type"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Product Type: All</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $productTypes ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $pt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <option value="<?php echo e($pt->name); ?>" <?php if(request('product_type') == $pt->name || request('product_type') == $pt->id): echo 'selected'; endif; ?>><?php echo e($pt->name); ?></option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </select>

                    <select
                        name="status"
                        data-dynamic-filter
                        aria-label="Filter by Visibility / Status"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="" <?php if(request('status') === '' || request('status') === 'all_visible'): echo 'selected'; endif; ?>>All Visible Products</option>
                        <option value="active" <?php if(request('status') === 'active'): echo 'selected'; endif; ?>>Active</option>
                        <option value="inactive" <?php if(request('status') === 'inactive'): echo 'selected'; endif; ?>>Inactive</option>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canViewHidden ?? false): ?>
                            <option value="hidden" <?php if(request('status') === 'hidden'): echo 'selected'; endif; ?>>Hidden</option>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </select>
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
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <?php if (isset($component)) { $__componentOriginal5967529496f56086d13f1257a544c7e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5967529496f56086d13f1257a544c7e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'serial_no','currentSort' => $sort ?? request('sort', 'name'),'currentDirection' => $direction ?? request('direction', 'asc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'serial_no','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'name')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'asc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Serial No.
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'name','currentSort' => $sort ?? request('sort', 'name'),'currentDirection' => $direction ?? request('direction', 'asc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'name','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'name')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'asc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Name
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'sku','currentSort' => $sort ?? request('sort', 'name'),'currentDirection' => $direction ?? request('direction', 'asc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'sku','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'name')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'asc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Part Number
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'model','currentSort' => $sort ?? request('sort', 'name'),'currentDirection' => $direction ?? request('direction', 'asc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'model','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'name')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'asc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Models
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.sortable-th','data' => ['column' => 'product_type','currentSort' => $sort ?? request('sort', 'name'),'currentDirection' => $direction ?? request('direction', 'asc')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.sortable-th'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['column' => 'product_type','current-sort' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($sort ?? request('sort', 'name')),'current-direction' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($direction ?? request('direction', 'asc'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            Product Type
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
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Unit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="Physical stock available across all warehouses">Available Stock</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="In-transit stock incoming from suppliers">In-Transit Stock</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="Total expected stock (Available + In-Transit)">Total Expected Stock</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold tracking-wider text-slate-500" title="Next expected delivery date from suppliers">Next Expected Delivery</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Tax</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Warranty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $isNew = (string) $item->id === (string) ($newProductId ?? request('new_product_id'));
                            $isUpdated = (string) $item->id === (string) ($updatedProductId ?? request('updated_product_id'));
                        ?>
                        <tr class="<?php echo e($isNew ? 'bg-emerald-50/70 ring-1 ring-emerald-300' : ($isUpdated ? 'bg-indigo-50/70 ring-1 ring-indigo-300' : 'hover:bg-slate-50/75')); ?> transition-colors"
                            <?php if($isNew || $isUpdated): ?> x-init="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); })" <?php endif; ?>>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo e($item->serial_no ?: '—'); ?></td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="<?php echo e(route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl])); ?>" data-preserve-state class="hover:text-indigo-700 hover:underline"><?php echo e($item->name); ?></a>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isNew): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">New</span>
                                    <?php elseif($isUpdated): ?>
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800">Updated</span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->is_hidden): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-900 border border-amber-300 shadow-xs" title="Hidden Product">
                                            <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                            Hidden Product
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $item->is_active): ?>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300 shadow-xs" title="Inactive Product">
                                            Inactive Product
                                        </span>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->brand || $item->category || $item->subCategory): ?>
                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                        <?php echo e(collect([$item->brand?->name, $item->category?->name, $item->subCategory?->name])->filter()->join(' · ')); ?>

                                    </div>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600"><?php echo e($item->sku); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($item->models ?: ($item->model ?: '—')); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($item->product_type ?: '—'); ?></td>
                            <td class="px-4 py-3 text-slate-600"><?php echo e($item->baseUom?->code ?? '—'); ?></td>
                            <?php
                                $stock = $stockSummary[$item->id] ?? [
                                    'physical_stock' => 0.0,
                                    'in_transit_stock' => 0.0,
                                    'total_expected_stock' => 0.0,
                                    'next_expected_delivery_date' => null,
                                    'next_expected_delivery_date_formatted' => '—',
                                    'is_delivery_overdue' => false,
                                    'warehouses' => [],
                                    'invoices' => [],
                                ];
                                $pPhys = (float) $stock['physical_stock'];
                                $pTransit = (float) $stock['in_transit_stock'];
                                $pTotal = (float) $stock['total_expected_stock'];
                                $pNextDate = $stock['next_expected_delivery_date_formatted'] ?? '—';
                                $pOverdue = $stock['is_delivery_overdue'] ?? false;
                            ?>
                            <td class="px-3 py-3 text-right font-mono font-medium text-slate-800">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pPhys > 0): ?>
                                    <span class="text-emerald-700 font-bold"><?php echo e(number_format($pPhys, 2)); ?></span>
                                <?php else: ?>
                                    <span class="text-slate-400">0.00</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pTransit > 0): ?>
                                    <button type="button"
                                            @click="openStockDetailModal(<?php echo e($item->id); ?>, '<?php echo e(addslashes($item->name)); ?>', '<?php echo e(addslashes($item->sku)); ?>', <?php echo \Illuminate\Support\Js::from($stock)->toHtml() ?>, 'invoices')"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100 hover:border-indigo-300 transition text-xs"
                                            title="Click to view incoming purchase deliveries">
                                        <span><?php echo e(number_format($pTransit, 2)); ?></span>
                                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                <?php else: ?>
                                    <span class="text-slate-300">0.00</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-slate-900">
                                <?php echo e(number_format($pTotal, 2)); ?>

                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pTransit > 0 && $pNextDate !== '—'): ?>
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium <?php echo e($pOverdue ? 'text-rose-600 font-semibold' : 'text-slate-700'); ?>">
                                            <?php echo e($pNextDate); ?>

                                        </span>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($pOverdue): ?>
                                            <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 uppercase">Overdue</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </div>
                                <?php elseif($pTransit > 0): ?>
                                    <span class="text-slate-400 italic text-[11px]">Date not set</span>
                                <?php else: ?>
                                    <span class="text-slate-300">—</span>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 font-medium"><?php echo e($item->tax_rate); ?>%</td>
                            <td class="px-4 py-3 text-slate-700"><?php echo e($item->warranty ?: 'No warranty'); ?></td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="openStockDetailModal(<?php echo e($item->id); ?>, '<?php echo e(addslashes($item->name)); ?>', '<?php echo e(addslashes($item->sku)); ?>', <?php echo \Illuminate\Support\Js::from($stock)->toHtml() ?>, 'warehouses')"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-2xs px-2 py-1 text-xs"
                                            title="View Warehouse-wise Stock & Incoming Deliveries"
                                            aria-label="View Stock Details">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span>Stock</span>
                                    </button>
                                    <?php if (isset($component)) { $__componentOriginala8bb031a483a05f647cb99ed3a469847 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala8bb031a483a05f647cb99ed3a469847 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','size' => 'sm','href' => route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl]),'title' => 'View Product','ariaLabel' => 'View Product','dataPreserveState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','size' => 'sm','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl])),'title' => 'View Product','aria-label' => 'View Product','data-preserve-state' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
View <?php echo $__env->renderComponent(); ?>
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
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.button','data' => ['variant' => 'secondary','size' => 'sm','href' => route('masters.products.edit', ['product' => $item, 'return_url' => $currentListingUrl]),'title' => 'Edit Product','ariaLabel' => 'Edit Product','dataPreserveState' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'secondary','size' => 'sm','href' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(route('masters.products.edit', ['product' => $item, 'return_url' => $currentListingUrl])),'title' => 'Edit Product','aria-label' => 'Edit Product','data-preserve-state' => true]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
Edit <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $attributes = $__attributesOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__attributesOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala8bb031a483a05f647cb99ed3a469847)): ?>
<?php $component = $__componentOriginala8bb031a483a05f647cb99ed3a469847; ?>
<?php unset($__componentOriginala8bb031a483a05f647cb99ed3a469847); ?>
<?php endif; ?>

                                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($item->is_hidden): ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canUnhideProduct ?? false): ?>
                                            <button type="button"
                                                    @click="promptUnhide('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->name)); ?>', '<?php echo e(addslashes($item->sku)); ?>', '<?php echo e(addslashes($item->serial_no ?: '—')); ?>')"
                                                    :disabled="isProcessing"
                                                    class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                    title="Unhide Product"
                                                    aria-label="Unhide Product"
                                                    data-unhide-product="<?php echo e($item->id); ?>">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>Unhide</span>
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php else: ?>
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canHideProduct ?? false): ?>
                                            <button type="button"
                                                    @click="promptHide('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->name)); ?>', '<?php echo e(addslashes($item->sku)); ?>', '<?php echo e(addslashes($item->serial_no ?: '—')); ?>')"
                                                    :disabled="isProcessing"
                                                    class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                    title="Hide Product"
                                                    aria-label="Hide Product"
                                                    data-hide-product="<?php echo e($item->id); ?>">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                </svg>
                                                <span>Hide</span>
                                            </button>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                                    <button type="button"
                                            @click="promptDelete('<?php echo e($item->id); ?>', '<?php echo e(addslashes($item->name)); ?>', '<?php echo e(addslashes($item->sku)); ?>', '<?php echo e(addslashes($item->serial_no ?: '—')); ?>')"
                                            :disabled="isProcessing"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                            title="Delete Product"
                                            aria-label="Delete Product"
                                            data-delete-product="<?php echo e($item->id); ?>">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        <tr>
                            <td colspan="14" class="p-0">
                                <?php if (isset($component)) { $__componentOriginal3607a477fdef7402bc742abad5df9c51 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3607a477fdef7402bc742abad5df9c51 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.empty-state','data' => ['title' => 'No matching products found','description' => 'Try clearing search or filters to see more results.','class' => 'border-0 rounded-none py-10']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'No matching products found','description' => 'Try clearing search or filters to see more results.','class' => 'border-0 rounded-none py-10']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                                     <?php $__env->slot('action', null, []); ?> 
                                        <a href="<?php echo e(route('masters.products.index')); ?>" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
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

    <!-- Hide Confirmation Modal -->
    <div x-show="showHideModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="hide-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelHide()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showHideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelHide()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showHideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="hide-modal-title">Hide Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to hide this product? It will be removed from the regular product listing and excluded from new transaction dropdowns by default. It will not be deleted, and all historical records, serials, and transactions remain intact.
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeHide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Hide Product</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Hiding...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelHide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unhide Confirmation Modal -->
    <div x-show="showUnhideModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="unhide-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelUnhide()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showUnhideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelUnhide()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showUnhideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="unhide-modal-title">Unhide Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to restore this product to the regular product listing? It will become visible in the normal product list and available in dropdowns.
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeUnhide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Unhide Product</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Restoring...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelUnhide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

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
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this product? This action cannot be undone.</p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Delete</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Details Modal (Warehouse Breakdown & In-Transit Invoices) -->
    <div x-show="showStockModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="closeStockModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showStockModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                 @click="closeStockModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showStockModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 border border-slate-100"
                 @click.stop>

                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </span>
                            <span>Product Stock & In-Transit Deliveries</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            <strong class="text-slate-800" x-text="stockModalProduct.name"></strong>
                            <span class="text-slate-400 mx-1">|</span>
                            <span class="font-mono text-slate-600">Part No: <span x-text="stockModalProduct.sku"></span></span>
                        </p>
                    </div>
                    <button type="button" @click="closeStockModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Stock Summary Metric Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4">
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                        <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Physical Available</div>
                        <div class="text-lg font-bold font-mono text-emerald-900 mt-0.5" x-text="formatQty(stockModalData.physical_stock)"></div>
                        <div class="text-[10px] text-emerald-600 mt-0.5">Ready for dispatch</div>
                    </div>
                    <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100">
                        <div class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider">In-Transit Stock</div>
                        <div class="text-lg font-bold font-mono text-indigo-900 mt-0.5" x-text="formatQty(stockModalData.in_transit_stock)"></div>
                        <div class="text-[10px] text-indigo-600 mt-0.5">Purchased, on the way</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Total Expected</div>
                        <div class="text-lg font-bold font-mono text-slate-900 mt-0.5" x-text="formatQty(stockModalData.total_expected_stock)"></div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Physical + In-Transit</div>
                    </div>
                    <div class="p-3 rounded-xl border"
                         :class="stockModalData.is_delivery_overdue ? 'bg-rose-50/70 border-rose-200' : 'bg-purple-50/70 border-purple-100'">
                        <div class="text-[11px] font-semibold uppercase tracking-wider"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-700' : 'text-purple-700'">
                            Next Expected Date
                        </div>
                        <div class="text-sm font-bold mt-1"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-900' : 'text-purple-900'"
                             x-text="stockModalData.next_expected_delivery_date_formatted || '—'"></div>
                        <div class="text-[10px] mt-0.5 font-medium"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-600 font-semibold' : 'text-purple-600'"
                             x-text="stockModalData.is_delivery_overdue ? 'Delivery Overdue' : (stockModalData.in_transit_stock > 0 ? 'Earliest delivery' : 'No shipments')">
                        </div>
                    </div>
                </div>

                <!-- Tabs: Warehouses vs Incoming Deliveries -->
                <div class="flex items-center gap-2 border-b border-slate-200 mb-3">
                    <button type="button"
                            @click="stockModalTab = 'warehouses'"
                            class="px-4 py-2 text-xs font-semibold border-b-2 transition -mb-px flex items-center gap-1.5"
                            :class="stockModalTab === 'warehouses' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Warehouse Breakdown</span>
                    </button>
                    <button type="button"
                            @click="stockModalTab = 'invoices'"
                            class="px-4 py-2 text-xs font-semibold border-b-2 transition -mb-px flex items-center gap-1.5"
                            :class="stockModalTab === 'invoices' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span>Incoming Deliveries (In-Transit)</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                              :class="(stockModalData.invoices && stockModalData.invoices.length > 0) ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'"
                              x-text="(stockModalData.invoices && stockModalData.invoices.length) || 0"></span>
                    </button>
                </div>

                <!-- Tab 1: Warehouse Breakdown -->
                <div x-show="stockModalTab === 'warehouses'">
                    <template x-if="getWarehouseList().length > 0">
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="p-2.5">Warehouse</th>
                                        <th class="p-2.5 text-right">Physical Available</th>
                                        <th class="p-2.5 text-right">In-Transit</th>
                                        <th class="p-2.5 text-right">Total Expected</th>
                                        <th class="p-2.5">Next Delivery Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="w in getWarehouseList()" :key="w.warehouse_id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="p-2.5 font-medium text-slate-800 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span x-text="w.warehouse_name"></span>
                                            </td>
                                            <td class="p-2.5 text-right font-mono font-semibold"
                                                :class="w.physical_stock > 0 ? 'text-emerald-700' : 'text-slate-400'"
                                                x-text="formatQty(w.physical_stock)"></td>
                                            <td class="p-2.5 text-right font-mono font-semibold"
                                                :class="w.in_transit_stock > 0 ? 'text-indigo-700' : 'text-slate-300'"
                                                x-text="formatQty(w.in_transit_stock)"></td>
                                            <td class="p-2.5 text-right font-mono font-bold text-slate-900"
                                                x-text="formatQty(w.total_expected_stock)"></td>
                                            <td class="p-2.5 text-slate-700" x-text="w.next_delivery_date || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="getWarehouseList().length === 0">
                        <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <p class="text-xs">No warehouse stock or pending shipments recorded for this product.</p>
                        </div>
                    </template>
                </div>

                <!-- Tab 2: Incoming In-Transit Invoices -->
                <div x-show="stockModalTab === 'invoices'">
                    <template x-if="stockModalData.invoices && stockModalData.invoices.length > 0">
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="p-2.5">Invoice #</th>
                                        <th class="p-2.5">Supplier</th>
                                        <th class="p-2.5">Warehouse</th>
                                        <th class="p-2.5 text-right">Ordered</th>
                                        <th class="p-2.5 text-right">Received</th>
                                        <th class="p-2.5 text-right">In Transit</th>
                                        <th class="p-2.5">Expected Delivery</th>
                                        <th class="p-2.5">Status</th>
                                        <th class="p-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="inv in stockModalData.invoices" :key="inv.item_id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="p-2.5 font-mono font-semibold text-slate-800" x-text="inv.invoice_no"></td>
                                            <td class="p-2.5 text-slate-700" x-text="inv.supplier_name"></td>
                                            <td class="p-2.5 text-slate-700" x-text="inv.warehouse_name"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-600" x-text="formatQty(inv.ordered_qty)"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-600" x-text="formatQty(inv.received_qty)"></td>
                                            <td class="p-2.5 text-right font-mono font-bold text-indigo-700 bg-indigo-50/40" x-text="formatQty(inv.in_transit_qty)"></td>
                                            <td class="p-2.5 whitespace-nowrap">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-medium"
                                                          :class="inv.is_overdue ? 'text-rose-600 font-semibold' : 'text-slate-800'"
                                                          x-text="inv.expected_delivery_date || 'Not specified'"></span>
                                                    <span x-show="inv.is_overdue"
                                                          class="px-1 py-0.2 text-[9px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase">Overdue</span>
                                                </div>
                                            </td>
                                            <td class="p-2.5">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                                      :class="inv.material_receipt_status === 'partially_received' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                                      x-text="inv.status_label"></span>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <a :href="`<?php echo e(url('purchases/purchase-invoices')); ?>/${inv.invoice_id}`"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                                    <span>View</span>
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!stockModalData.invoices || stockModalData.invoices.length === 0">
                        <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <p class="text-xs">No incoming in-transit purchase invoices for this product.</p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 italic">In-transit stock is tracked from pending purchase invoices and cannot be allocated for sales until received.</span>
                    <button type="button"
                            @click="closeStockModal()"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Close
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
function productIndex() {
    return {
        showHideModal: false,
        showUnhideModal: false,
        showDeleteModal: false,
        isProcessing: false,
        isDeleting: false,
        targetProductId: null,
        targetProductName: '',
        targetProductSku: '',
        targetProductSerial: '',

        // Stock Details Modal state
        showStockModal: false,
        stockModalTab: 'warehouses',
        stockModalProduct: { id: null, name: '', sku: '' },
        stockModalData: {
            physical_stock: 0,
            in_transit_stock: 0,
            total_expected_stock: 0,
            next_expected_delivery_date: null,
            next_expected_delivery_date_formatted: '—',
            is_delivery_overdue: false,
            warehouses: {},
            invoices: []
        },

        openStockDetailModal(id, name, sku, stockData, initialTab = 'warehouses') {
            this.stockModalProduct = { id, name, sku };
            this.stockModalData = stockData || {
                physical_stock: 0,
                in_transit_stock: 0,
                total_expected_stock: 0,
                next_expected_delivery_date: null,
                next_expected_delivery_date_formatted: '—',
                is_delivery_overdue: false,
                warehouses: {},
                invoices: []
            };
            this.stockModalTab = initialTab;
            this.showStockModal = true;
        },

        closeStockModal() {
            this.showStockModal = false;
        },

        getWarehouseList() {
            if (!this.stockModalData || !this.stockModalData.warehouses) return [];
            return Object.values(this.stockModalData.warehouses);
        },

        formatQty(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return '0.00';
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptHide(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showHideModal = true;
        },

        cancelHide() {
            if (this.isProcessing) return;
            this.showHideModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeHide() {
            if (!this.targetProductId || this.isProcessing) return;
            this.isProcessing = true;

            const url = `<?php echo e(url('masters/products')); ?>/${this.targetProductId}/hide`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showHideModal = false;
                    this.showNotification('success', data.message || 'Product marked as hidden.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to hide product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while hiding product.');
            } finally {
                this.isProcessing = false;
            }
        },

        promptUnhide(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showUnhideModal = true;
        },

        cancelUnhide() {
            if (this.isProcessing) return;
            this.showUnhideModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeUnhide() {
            if (!this.targetProductId || this.isProcessing) return;
            this.isProcessing = true;

            const url = `<?php echo e(url('masters/products')); ?>/${this.targetProductId}/unhide`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showUnhideModal = false;
                    this.showNotification('success', data.message || 'Product restored to visible products.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to unhide product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while unhiding product.');
            } finally {
                this.isProcessing = false;
            }
        },

        promptDelete(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isProcessing || this.isDeleting) return;
            this.showDeleteModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeDelete() {
            if (!this.targetProductId || this.isProcessing || this.isDeleting) return;
            this.isProcessing = true;
            this.isDeleting = true;

            const url = `<?php echo e(url('masters/products')); ?>/${this.targetProductId}`;
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
                    this.showNotification('success', data.message || 'Product deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting product.');
            } finally {
                this.isProcessing = false;
                this.isDeleting = false;
            }
        },

        async refreshList() {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) {
                    window.location.reload();
                    return;
                }
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#listing-container');
                const currentContainer = document.querySelector('#listing-container');

                if (newContainer && currentContainer) {
                    const currentTableWrapper = currentContainer.querySelector('.overflow-x-auto');
                    const newTableWrapper = newContainer.querySelector('.overflow-x-auto');
                    if (currentTableWrapper && newTableWrapper) {
                        currentTableWrapper.innerHTML = newTableWrapper.innerHTML;
                    }

                    const currentPagination = currentContainer.querySelector('.mt-4');
                    const newPagination = newContainer.querySelector('.mt-4');
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    } else if (!currentPagination && newPagination) {
                        currentTableWrapper.insertAdjacentElement('afterend', newPagination);
                    } else if (currentPagination && !newPagination) {
                        currentPagination.remove();
                    }
                } else {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Failed to refresh table dynamic container:', e);
                window.location.reload();
            }
        },

        showNotification(type, message) {
            this.notification.type = type;
            this.notification.message = message;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
            }, 4500);
        }
    };
}

(function() {
    if (window.__hierarchicalFiltersBound) return;
    window.__hierarchicalFiltersBound = true;

    // Listen on capture phase so invalid dependent dropdown selections
    // are reset BEFORE dynamic-tables.js builds the URL and triggers fetchAndSwap.
    document.addEventListener('change', function(e) {
        const filter = e.target;
        if (!filter || !filter.matches || !filter.matches('[data-dynamic-filter]')) return;

        const group = filter.closest('[data-hierarchical-filter-group]');
        if (!group) return;

        const brandSelect = group.querySelector('select[name="brand_id"]');
        const categorySelect = group.querySelector('select[name="category_id"]');
        const subCategorySelect = group.querySelector('select[name="sub_category_id"]');

        if (!brandSelect || !categorySelect || !subCategorySelect) return;

        let allCats = [];
        let allSubCats = [];
        try {
            allCats = JSON.parse(group.getAttribute('data-all-categories') || '[]');
            allSubCats = JSON.parse(group.getAttribute('data-all-subcategories') || '[]');
        } catch (_) {}

        if (filter === brandSelect) {
            const newBrandId = brandSelect.value;
            const currentCatId = categorySelect.value;

            if (currentCatId && newBrandId) {
                // Check if current category is tied to another brand
                const currentCat = allCats.find(c => String(c.id) === String(currentCatId));
                if (currentCat && currentCat.brand_id && String(currentCat.brand_id) !== String(newBrandId)) {
                    categorySelect.value = '';
                    subCategorySelect.value = '';
                }
            }
        } else if (filter === categorySelect) {
            const newCatId = categorySelect.value;
            const currentSubId = subCategorySelect.value;

            if (currentSubId && newCatId) {
                // Check if current subcategory belongs to another category
                const currentSub = allSubCats.find(sc => String(sc.id) === String(currentSubId));
                if (currentSub && currentSub.category_id && String(currentSub.category_id) !== String(newCatId)) {
                    subCategorySelect.value = '';
                }
            }
        }
    }, true);

    // Keep return_url on all state-preserving actions updated with live query string
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-preserve-state]');
        if (!link || !link.href) return;
        try {
            const u = new URL(link.href, window.location.origin);
            const livePathAndQuery = window.location.pathname + window.location.search;
            u.searchParams.set('return_url', livePathAndQuery);
            link.href = u.toString();
        } catch (_) {}
    }, true);
})();
</script>
<?php $__env->stopPush(); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/u766425373/domains/extraaaz.com/public_html/dms_uat/resources/views/masters/products/index.blade.php ENDPATH**/ ?>