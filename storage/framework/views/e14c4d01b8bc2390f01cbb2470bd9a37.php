<?php $__env->startSection('title', 'Bulk Import'); ?>
<?php $__env->startSection('content'); ?>
<?php if (isset($component)) { $__componentOriginal91a231a9270579fa1ae9246bd51fb785 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91a231a9270579fa1ae9246bd51fb785 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.page-header','data' => ['title' => 'Bulk Import','description' => 'Import Setup, Catalog, Products, Parties and Price List from CSV/Excel. Download each template, fill it, then upload in the order shown — the system validates, updates by key, and provides an error report.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.page-header'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Bulk Import','description' => 'Import Setup, Catalog, Products, Parties and Price List from CSV/Excel. Download each template, fill it, then upload in the order shown — the system validates, updates by key, and provides an error report.']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

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
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm text-emerald-800"><?php echo e(session('status')); ?></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($summary): ?>
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['class' => 'mb-4','title' => 'Last import result — '.e(ucfirst(str_replace('-', ' ', $summary['type']))).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-4','title' => 'Last import result — '.e(ucfirst(str_replace('-', ' ', $summary['type']))).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-3 mb-3 text-sm">
            <div class="rounded-lg bg-emerald-50 border border-emerald-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Created</span><div class="text-lg font-semibold text-emerald-700"><?php echo e($summary['created']); ?></div></div>
            <div class="rounded-lg bg-indigo-50 border border-indigo-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Updated</span><div class="text-lg font-semibold text-indigo-700"><?php echo e($summary['updated']); ?></div></div>
            <div class="rounded-lg bg-amber-50 border border-amber-100 px-3 py-2"><span class="text-xs uppercase text-slate-500">Skipped</span><div class="text-lg font-semibold text-amber-700"><?php echo e($summary['skipped']); ?></div></div>
            <div class="rounded-lg bg-slate-50 border border-slate-200 px-3 py-2"><span class="text-xs uppercase text-slate-500">Errors</span><div class="text-lg font-semibold text-red-700"><?php echo e(count($summary['errors'])); ?></div></div>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['errors'])): ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($summary['errors_file']): ?>
                <a href="<?php echo e(route('masters.bulk-import.errors', ['filename' => basename($summary['errors_file'])])); ?>" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-red-700">
                    Download error CSV
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <div class="mt-3 overflow-x-auto max-h-60 border rounded-lg">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 sticky top-0">
                        <tr>
                            <th class="px-3 py-1.5 text-left">Row</th>
                            <th class="px-3 py-1.5 text-left">Field</th>
                            <th class="px-3 py-1.5 text-left">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $summary['errors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <tr>
                                <td class="px-3 py-1.5"><?php echo e($err['row']); ?></td>
                                <td class="px-3 py-1.5 font-mono text-slate-600"><?php echo e($err['field']); ?></td>
                                <td class="px-3 py-1.5"><?php echo e($err['message']); ?></td>
                            </tr>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </tbody>
                </table>
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
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<p class="mb-4 text-xs text-slate-500">
    Recommended order: Companies → Branches → Warehouses → Financial Years → UOMs → Brands → Categories → Sub Categories → Product Types → Products → Parties → Price List.
</p>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = [
        'companies' => ['Companies', 'Match on Code. Creates/updates company profile fields.'],
        'branches' => ['Branches', 'Match on Company Code + Branch Code. Company must exist first.'],
        'warehouses' => ['Warehouses', 'Match on Company + Warehouse Code. Branch must exist first.'],
        'financial-years' => ['Financial Years', 'Match on Company + date range. Can mark one year as current.'],
        'uoms' => ['UOMs', 'Match on Code. Required before Products / Price List imports.'],
        'brands' => ['Brands', 'Match on Company + Name. Optional company_code uses your login company when blank.'],
        'categories' => ['Categories', 'Match on Brand + Name. Import Brands first.'],
        'sub-categories' => ['Sub Categories', 'Match on Brand + Category + Name. Import Categories first.'],
        'product-types' => ['Product Types', 'Match on Company + Name/Code. Sets track_serial and description.'],
        'products' => ['Products', 'Match on Part Number; UOM codes must exist. Brand/category/sub-category/type auto-created if missing.'],
        'parties' => ['Parties (Customers &amp; Suppliers)', 'Match on Code, else GSTIN, else Name — re-import updates instead of duplicating. Classification, area and route auto-created.'],
        'price-master' => ['Price List', 'Match on Customer Type + Part Number + UOM + Min Qty. Supports rate_includes_gst.'],
        'leads' => ['CRM Leads', 'Match on Mobile or Email. Maps Contact Name, Company, Title, Mobiles, Address, etc.'],
    ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type => [$label, $note]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
        <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['title' => $label]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($label)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

            <p class="text-xs text-slate-500 mb-3"><?php echo $note; ?></p>
            <div class="flex flex-col gap-2">
                <a href="<?php echo e(route('masters.bulk-import.template', $type)); ?>"
                   class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-50">
                    Download template CSV
                </a>
                <form method="POST" enctype="multipart/form-data" action="<?php echo e(route('masters.bulk-import.upload', $type)); ?>" class="space-y-2">
                    <?php echo csrf_field(); ?>
                    <input type="file" name="file" required accept=".csv,.xlsx,.xls" class="block w-full text-xs">
                    <button type="submit" class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">
                        Upload &amp; import
                    </button>
                </form>
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
    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/masters/bulk-import/index.blade.php ENDPATH**/ ?>