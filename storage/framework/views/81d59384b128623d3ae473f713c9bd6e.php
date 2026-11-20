<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'dateFrom' => null,
    'dateTo' => null,
    'asOf' => null,
    'dateFromLabel' => 'From',
    'dateToLabel' => 'To',
    'showDates' => true,
    'showAsOf' => false,
    'searchPlaceholder' => 'Search',
    'searchValue' => null,
    'resetUrl' => null,
    'submitLabel' => 'Apply',
    'showParty' => false,
    'showSupplier' => false,
    'showSalesperson' => false,
    'showUnassignedSalesperson' => false,
    'showStatus' => false,
    'statuses' => [],
    'showBranch' => false,
    'showBrand' => false,
    'showCategory' => false,
    'showProduct' => false,
    'showWarehouse' => false,
    'showFulfilment' => false,
    'showBackOrder' => false,
    'showLowStock' => false,
    'partyRequired' => false,
    'filtersOpen' => null,
    'customers' => null,
    'suppliers' => null,
    'salespeople' => null,
    'branches' => null,
    'brands' => null,
    'categories' => null,
    'products' => null,
    'warehouses' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'dateFrom' => null,
    'dateTo' => null,
    'asOf' => null,
    'dateFromLabel' => 'From',
    'dateToLabel' => 'To',
    'showDates' => true,
    'showAsOf' => false,
    'searchPlaceholder' => 'Search',
    'searchValue' => null,
    'resetUrl' => null,
    'submitLabel' => 'Apply',
    'showParty' => false,
    'showSupplier' => false,
    'showSalesperson' => false,
    'showUnassignedSalesperson' => false,
    'showStatus' => false,
    'statuses' => [],
    'showBranch' => false,
    'showBrand' => false,
    'showCategory' => false,
    'showProduct' => false,
    'showWarehouse' => false,
    'showFulfilment' => false,
    'showBackOrder' => false,
    'showLowStock' => false,
    'partyRequired' => false,
    'filtersOpen' => null,
    'customers' => null,
    'suppliers' => null,
    'salespeople' => null,
    'branches' => null,
    'brands' => null,
    'categories' => null,
    'products' => null,
    'warehouses' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $searchValue = (string) ($searchValue ?? request('search', ''));
    $resetUrl = $resetUrl ?: url()->current();
    $customers = collect($customers ?? []);
    $suppliers = collect($suppliers ?? []);
    $salespeople = collect($salespeople ?? []);
    $branches = collect($branches ?? []);
    $brands = collect($brands ?? []);
    $categories = collect($categories ?? []);
    $products = collect($products ?? []);
    $warehouses = collect($warehouses ?? []);

    $toOpts = fn ($items) => $items->map(function ($item) {
        if (is_array($item)) {
            return [
                'id' => (string) ($item['id'] ?? ''),
                'name' => (string) ($item['name'] ?? ''),
                'code' => isset($item['code']) ? (string) $item['code'] : null,
            ];
        }

        return [
            'id' => (string) ($item->id ?? ''),
            'name' => (string) ($item->name ?? ''),
            'code' => isset($item->code) ? (string) $item->code : (isset($item->sku) ? (string) $item->sku : null),
        ];
    })->values()->all();

    $salespersonOptions = $toOpts($salespeople);
    if ($showUnassignedSalesperson) {
        array_unshift($salespersonOptions, ['id' => 'unassigned', 'name' => 'Unassigned', 'code' => null]);
    }

    $statusOptions = collect($statuses)->map(fn ($st) => [
        'id' => (string) $st,
        'name' => ucfirst(str_replace('_', ' ', $st)),
        'code' => null,
    ])->values()->all();

    $productOptions = $products->map(fn ($p) => [
        'id' => (string) $p->id,
        'name' => (string) $p->name,
        'code' => $p->sku ?? $p->barcode ?? null,
    ])->values()->all();

    $dateLabel = '';
    if ($showAsOf && $asOf) {
        $dateLabel = \Illuminate\Support\Carbon::parse($asOf)->format('d M Y');
    } elseif ($showDates && $dateFrom && $dateTo) {
        $dateLabel = \Illuminate\Support\Carbon::parse($dateFrom)->format('d M').' – '.\Illuminate\Support\Carbon::parse($dateTo)->format('d M Y');
    } else {
        $dateLabel = 'Date range';
    }

    $chipClass = 'inline-flex items-center gap-1.5 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 whitespace-nowrap';
?>

<div
    <?php echo e($attributes->merge(['class' => 'report-filters mb-5'])); ?>

    x-data="dmsReportFilters({ search: <?php echo \Illuminate\Support\Js::from($searchValue)->toHtml() ?> })"
>
    <form method="GET" class="flex flex-wrap items-center gap-2">
        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showAsOf): ?>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="<?php echo e($chipClass); ?>">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    <span><?php echo e($dateLabel); ?></span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-40 mt-1.5 w-56 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400">As of</label>
                    <input id="as_of" type="date" name="as_of" value="<?php echo e($asOf); ?>" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" @change="$el.form.requestSubmit()" />
                </div>
            </div>
        <?php elseif($showDates): ?>
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button type="button" @click="open = !open" class="<?php echo e($chipClass); ?>">
                    <svg class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" /></svg>
                    <span><?php echo e($dateLabel); ?></span>
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" /></svg>
                </button>
                <div x-show="open" x-cloak x-transition class="absolute left-0 top-full z-40 mt-1.5 w-72 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400"><?php echo e($dateFromLabel); ?></label>
                            <input type="date" name="date_from" value="<?php echo e($dateFrom); ?>" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                        <div>
                            <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-400"><?php echo e($dateToLabel); ?></label>
                            <input type="date" name="date_to" value="<?php echo e($dateTo); ?>" class="w-full rounded-lg border-slate-200 text-sm focus:border-indigo-500 focus:ring-indigo-500" />
                        </div>
                    </div>
                    <button type="submit" class="mt-3 w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-700">Apply dates</button>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showParty): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'customer_id','label' => 'Party','placeholder' => 'All parties','searchPlaceholder' => 'Type party or initials...','options' => $toOpts($customers),'value' => request('customer_id'),'required' => $partyRequired]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'customer_id','label' => 'Party','placeholder' => 'All parties','search-placeholder' => 'Type party or initials...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($customers)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('customer_id')),'required' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($partyRequired)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSupplier): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'supplier_id','label' => 'Supplier','placeholder' => 'All suppliers','searchPlaceholder' => 'Type supplier...','options' => $toOpts($suppliers),'value' => request('supplier_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'supplier_id','label' => 'Supplier','placeholder' => 'All suppliers','search-placeholder' => 'Type supplier...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($suppliers)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('supplier_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showSalesperson): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'salesperson_id','label' => 'Salesperson','placeholder' => 'All salespersons','searchPlaceholder' => 'Type name...','options' => $salespersonOptions,'value' => request('salesperson_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'salesperson_id','label' => 'Salesperson','placeholder' => 'All salespersons','search-placeholder' => 'Type name...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($salespersonOptions),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('salesperson_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showStatus): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'status','label' => 'Status','placeholder' => 'All statuses','searchPlaceholder' => 'Type status...','options' => $statusOptions,'value' => request('status')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'status','label' => 'Status','placeholder' => 'All statuses','search-placeholder' => 'Type status...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($statusOptions),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('status'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showBranch): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'branch_id','label' => 'Branch','placeholder' => 'All branches','searchPlaceholder' => 'Type branch...','options' => $toOpts($branches),'value' => request('branch_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'branch_id','label' => 'Branch','placeholder' => 'All branches','search-placeholder' => 'Type branch...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($branches)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('branch_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showBrand): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'brand_id','label' => 'Brand','placeholder' => 'All brands','searchPlaceholder' => 'Type brand...','options' => $toOpts($brands),'value' => request('brand_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'brand_id','label' => 'Brand','placeholder' => 'All brands','search-placeholder' => 'Type brand...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($brands)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('brand_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showCategory): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'category_id','label' => 'Category','placeholder' => 'All categories','searchPlaceholder' => 'Type category...','options' => $toOpts($categories),'value' => request('category_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'category_id','label' => 'Category','placeholder' => 'All categories','search-placeholder' => 'Type category...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($categories)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('category_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showProduct): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'product_id','label' => 'Item','placeholder' => 'All items','searchPlaceholder' => 'Type item / SKU...','options' => $productOptions,'value' => request('product_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'product_id','label' => 'Item','placeholder' => 'All items','search-placeholder' => 'Type item / SKU...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($productOptions),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('product_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showWarehouse): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'warehouse_id','label' => 'Warehouse','placeholder' => 'All warehouses','searchPlaceholder' => 'Type warehouse...','options' => $toOpts($warehouses),'value' => request('warehouse_id')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'warehouse_id','label' => 'Warehouse','placeholder' => 'All warehouses','search-placeholder' => 'Type warehouse...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($toOpts($warehouses)),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('warehouse_id'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showFulfilment): ?>
            <?php if (isset($component)) { $__componentOriginala7607cd1870da88d8bf6501a3106da08 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginala7607cd1870da88d8bf6501a3106da08 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.report-filter-chip','data' => ['name' => 'fulfilment_mode','label' => 'Fulfilment','placeholder' => 'All modes','searchPlaceholder' => 'Type mode...','options' => [['id' => 'warehouse', 'name' => 'Warehouse', 'code' => null], ['id' => 'van', 'name' => 'Van', 'code' => null]],'value' => request('fulfilment_mode')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.report-filter-chip'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'fulfilment_mode','label' => 'Fulfilment','placeholder' => 'All modes','search-placeholder' => 'Type mode...','options' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([['id' => 'warehouse', 'name' => 'Warehouse', 'code' => null], ['id' => 'van', 'name' => 'Van', 'code' => null]]),'value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(request('fulfilment_mode'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $attributes = $__attributesOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__attributesOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
<?php if (isset($__componentOriginala7607cd1870da88d8bf6501a3106da08)): ?>
<?php $component = $__componentOriginala7607cd1870da88d8bf6501a3106da08; ?>
<?php unset($__componentOriginala7607cd1870da88d8bf6501a3106da08); ?>
<?php endif; ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showBackOrder): ?>
            <label class="<?php echo e($chipClass); ?> cursor-pointer <?php echo e(request()->boolean('back_order_only') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : ''); ?>">
                <input type="checkbox" name="back_order_only" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" <?php if(request()->boolean('back_order_only')): echo 'checked'; endif; ?> onchange="this.form.requestSubmit()">
                Back-order
            </label>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($showLowStock): ?>
            <label class="<?php echo e($chipClass); ?> cursor-pointer <?php echo e(request()->boolean('low_only') ? 'border-indigo-300 bg-indigo-50 text-indigo-700' : ''); ?>">
                <input type="checkbox" name="low_only" value="1" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" <?php if(request()->boolean('low_only')): echo 'checked'; endif; ?> onchange="this.form.requestSubmit()">
                Low stock
            </label>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php echo e($slot); ?>


        
        <div class="ml-auto flex items-center gap-2 min-w-[200px] flex-1 sm:flex-none sm:w-64">
            <div class="relative w-full">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </span>
                <input
                    type="search"
                    name="search"
                    x-model="search"
                    @input="liveFilter()"
                    @keydown.enter="$el.form.requestSubmit()"
                    placeholder="<?php echo e(\App\Support\TitleCase::format($searchPlaceholder)); ?>"
                    autocomplete="off"
                    class="block w-full rounded-lg border-slate-200 bg-white py-2 pl-9 pr-8 text-sm shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"
                />
                <button type="button" x-show="search.length" x-cloak @click="clearSearch()" class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600" title="Clear">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <a href="<?php echo e($resetUrl); ?>" class="shrink-0 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-600 hover:bg-slate-50" title="Reset">Reset</a>
        </div>
    </form>
</div>

<?php if (! $__env->hasRenderedOnce('b8fd6890-d3b7-46e0-944a-7564cfd5e14e')): $__env->markAsRenderedOnce('b8fd6890-d3b7-46e0-944a-7564cfd5e14e'); ?>
<?php $__env->startPush('scripts'); ?>
<script>
function dmsReportFilters(config) {
    return {
        search: config.search || '',
        init() { this.$nextTick(() => this.liveFilter()); },
        clearSearch() { this.search = ''; this.liveFilter(); },
        liveFilter() {
            const q = (this.search || '').trim().toLowerCase();
            const tokens = q.split(/\s+/).filter(Boolean);
            document.querySelectorAll('[data-report-table] tbody tr').forEach((row) => {
                if (row.querySelector('[data-report-empty]') || row.hasAttribute('data-report-empty')) return;
                const hay = (row.getAttribute('data-search') || row.textContent || '').toLowerCase().replace(/\s+/g, ' ');
                const show = tokens.length === 0 || tokens.every((t) => hay.includes(t) || this.initialsMatch(hay, t));
                row.style.display = show ? '' : 'none';
            });
        },
        initialsMatch(hay, token) {
            const words = hay.split(/[^a-z0-9]+/).filter(Boolean);
            const initials = words.map((w) => w.charAt(0)).join('');
            if (initials.startsWith(token)) return true;
            for (let i = 0; i < words.length; i++) {
                let built = '';
                for (let j = i; j < words.length && built.length < token.length; j++) built += words[j].charAt(0);
                if (built.startsWith(token)) return true;
            }
            return words.some((w) => w.startsWith(token));
        }
    };
}
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/components/ui/report-filters.blade.php ENDPATH**/ ?>