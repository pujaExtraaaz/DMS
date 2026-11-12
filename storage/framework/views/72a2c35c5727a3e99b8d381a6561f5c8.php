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
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 flex items-center justify-between">
        <div><?php echo e(session('status')); ?></div>
        <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-900 text-xs font-semibold">&times;</button>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800 flex items-center justify-between">
        <div><?php echo e(session('error')); ?></div>
        <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-900 text-xs font-semibold">&times;</button>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($summary): ?>
    <?php if (isset($component)) { $__componentOriginaldae4cd48acb67888a4631e1ba48f2f93 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginaldae4cd48acb67888a4631e1ba48f2f93 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.card','data' => ['class' => 'mb-6 border-slate-300 shadow-sm','title' => 'Last import result — '.e(ucfirst(str_replace('-', ' ', $summary['type']))).'']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.card'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'mb-6 border-slate-300 shadow-sm','title' => 'Last import result — '.e(ucfirst(str_replace('-', ' ', $summary['type']))).'']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

        <div class="flex items-center justify-between mb-3">
            <span class="text-xs text-slate-500 font-medium">Batch summary metrics</span>
            <form action="<?php echo e(route('masters.bulk-import.clear-summary')); ?>" method="POST" class="inline">
                <?php echo csrf_field(); ?>
                <button type="submit" class="text-xs text-slate-500 hover:text-slate-800 underline">Dismiss summary</button>
            </form>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4 text-sm">
            <div class="rounded-lg bg-emerald-50 border border-emerald-200 px-3 py-2.5">
                <span class="text-xs font-medium uppercase tracking-wider text-emerald-700">Created</span>
                <div class="text-xl font-bold text-emerald-800"><?php echo e($summary['created']); ?></div>
                <span class="text-[11px] text-emerald-600">New records added</span>
            </div>
            <div class="rounded-lg bg-indigo-50 border border-indigo-200 px-3 py-2.5">
                <span class="text-xs font-medium uppercase tracking-wider text-indigo-700">Updated</span>
                <div class="text-xl font-bold text-indigo-800"><?php echo e($summary['updated']); ?></div>
                <span class="text-[11px] text-indigo-600">Existing records modified</span>
            </div>
            <div class="rounded-lg bg-amber-50 border border-amber-200 px-3 py-2.5">
                <span class="text-xs font-medium uppercase tracking-wider text-amber-700">Skipped</span>
                <div class="text-xl font-bold text-amber-800"><?php echo e($summary['skipped']); ?></div>
                <span class="text-[11px] text-amber-600">Unchanged records</span>
            </div>
            <div class="rounded-lg bg-rose-50 border border-rose-200 px-3 py-2.5">
                <span class="text-xs font-medium uppercase tracking-wider text-rose-700">Failed</span>
                <div class="text-xl font-bold text-rose-800"><?php echo e($summary['failed'] ?? count($summary['errors'])); ?></div>
                <span class="text-[11px] text-rose-600">Errors encountered</span>
            </div>
        </div>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['missing_uoms'])): ?>
            <div class="mb-5 rounded-xl border border-amber-300 bg-amber-50/70 p-4">
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-amber-200 pb-3 mb-3">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-amber-200 text-amber-800 font-bold text-xs">!</span>
                        <h4 class="text-sm font-semibold text-amber-900">Unresolved UOM Values Detected</h4>
                        <span class="rounded bg-amber-200 px-2 py-0.5 text-xs font-semibold text-amber-800">
                            <?php echo e(count($summary['missing_uoms'])); ?> missing UOM<?php echo e(count($summary['missing_uoms']) > 1 ? 's' : ''); ?>

                        </span>
                    </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['pending_file'])): ?>
                        <form action="<?php echo e(route('masters.bulk-import.retry')); ?>" method="POST" class="inline">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                Retry Import Now
                            </button>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                </div>

                <p class="text-xs text-amber-800 mb-3">
                    The imported rows below contain UOM values that could not be matched automatically. Map them to an existing UOM in your master, or add them as new units. Once mapped or created, click <strong>Retry Import Now</strong> to import the failed rows.
                </p>

                <div class="space-y-3">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $summary['missing_uoms']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rawUom => $m): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <div class="rounded-lg border border-amber-200 bg-white p-3 shadow-xs">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-2 pb-2 border-b border-slate-100">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-slate-500">Imported Value:</span>
                                    <span class="inline-block rounded bg-rose-100 font-mono text-xs font-bold text-rose-800 px-2 py-0.5">
                                        "<?php echo e($m['imported_uom']); ?>"
                                    </span>
                                    <span class="text-xs text-slate-500">
                                        (<?php echo e($m['count']); ?> product<?php echo e($m['count'] > 1 ? 's' : ''); ?> affected)
                                    </span>
                                </div>
                                <div class="text-[11px] text-slate-400">
                                    Sample rows: <?php echo e(implode(', ', $m['sample_rows'] ?? [])); ?>

                                </div>
                            </div>

                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-3 text-xs">
                                <!-- Option 1: Map to existing UOM -->
                                <div class="rounded border border-slate-200 bg-slate-50/70 p-2.5">
                                    <div class="font-semibold text-slate-700 mb-1.5 flex items-center gap-1">
                                        <span>Option A: Map to Existing UOM</span>
                                    </div>
                                    <form action="<?php echo e(route('masters.bulk-import.map-uom')); ?>" method="POST" class="flex flex-wrap items-center gap-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="imported_uom" value="<?php echo e($m['imported_uom']); ?>">
                                        <select name="uom_id" required class="flex-1 rounded border-slate-300 text-xs py-1 px-2 focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Choose target UOM --</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $allUoms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $uom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                                <option value="<?php echo e($uom->id); ?>"><?php echo e($uom->name); ?> (<?php echo e($uom->code); ?>)</option>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                                        </select>
                                        <button type="submit" class="rounded bg-indigo-600 px-2.5 py-1 font-semibold text-white hover:bg-indigo-700">
                                            Save Mapping
                                        </button>
                                    </form>
                                </div>

                                <!-- Option 2: Add to UOM Master -->
                                <div class="rounded border border-slate-200 bg-slate-50/70 p-2.5">
                                    <div class="font-semibold text-slate-700 mb-1.5 flex items-center gap-1">
                                        <span>Option B: Add to UOM Master</span>
                                    </div>
                                    <form action="<?php echo e(route('masters.bulk-import.add-uom')); ?>" method="POST" class="flex flex-wrap items-center gap-2">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="imported_uom" value="<?php echo e($m['imported_uom']); ?>">
                                        <input type="text" name="name" value="<?php echo e($m['suggested_name'] ?? ''); ?>" placeholder="Name (e.g. Job)" required class="w-32 rounded border-slate-300 text-xs py-1 px-2">
                                        <input type="text" name="code" value="<?php echo e($m['suggested_code'] ?? ''); ?>" placeholder="Code (e.g. JOB)" required class="w-24 uppercase rounded border-slate-300 text-xs py-1 px-2">
                                        <button type="submit" class="rounded bg-emerald-600 px-2.5 py-1 font-semibold text-white hover:bg-emerald-700">
                                            Create in Master
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['errors'])): ?>
            <div class="mt-4">
                <div class="flex items-center justify-between mb-2">
                    <h4 class="text-xs font-semibold uppercase tracking-wider text-slate-700">Failure Details</h4>
                    <div class="flex items-center gap-2">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['pending_file'])): ?>
                            <form action="<?php echo e(route('masters.bulk-import.retry')); ?>" method="POST" class="inline">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-700">
                                    🔄 Retry Failed Import
                                </button>
                            </form>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($summary['errors_file'])): ?>
                            <a href="<?php echo e(route('masters.bulk-import.errors', ['filename' => basename($summary['errors_file'])])); ?>" class="inline-flex items-center gap-1.5 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-rose-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Download Error CSV
                            </a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    </div>
                </div>

                <div class="overflow-x-auto max-h-72 border rounded-lg bg-white">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-100 sticky top-0 text-slate-700">
                            <tr>
                                <th class="px-3 py-2 text-left font-semibold">Row Number</th>
                                <th class="px-3 py-2 text-left font-semibold">Product Identifier</th>
                                <th class="px-3 py-2 text-left font-semibold">Imported UOM</th>
                                <th class="px-3 py-2 text-left font-semibold">Field</th>
                                <th class="px-3 py-2 text-left font-semibold">Reason for Failure</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $summary['errors']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $err): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                <tr class="hover:bg-slate-50">
                                    <td class="px-3 py-2 font-mono text-slate-700"><?php echo e($err['row'] ?? '-'); ?></td>
                                    <td class="px-3 py-2 font-medium text-slate-800"><?php echo e($err['product_identifier'] ?? 'N/A'); ?></td>
                                    <td class="px-3 py-2 font-mono text-slate-600">
                                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($err['imported_uom']) && $err['imported_uom'] !== 'N/A'): ?>
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 text-[11px] font-semibold text-slate-700"><?php echo e($err['imported_uom']); ?></span>
                                        <?php else: ?>
                                            <span class="text-slate-400">-</span>
                                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                    </td>
                                    <td class="px-3 py-2 font-mono text-slate-500"><?php echo e($err['field'] ?? '-'); ?></td>
                                    <td class="px-3 py-2 text-rose-700"><?php echo e($err['message'] ?? '-'); ?></td>
                                </tr>
                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                        </tbody>
                    </table>
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
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($uomMappings->isNotEmpty()): ?>
    <div x-data="{ open: false }" class="mb-6 rounded-xl border border-slate-200 bg-white shadow-xs">
        <button type="button" @click="open = !open" class="flex w-full items-center justify-between p-4 text-left">
            <div class="flex items-center gap-2">
                <span class="text-sm font-semibold text-slate-800">Saved UOM Mappings (<?php echo e($uomMappings->count()); ?>)</span>
                <span class="rounded bg-indigo-100 px-2 py-0.5 text-xs text-indigo-700">Auto-resolved during bulk import</span>
            </div>
            <span class="text-xs font-semibold text-indigo-600" x-text="open ? 'Hide' : 'Show'">Show</span>
        </button>
        <div x-show="open" class="border-t border-slate-100 p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 text-xs">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $uomMappings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $mapping): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <div class="flex items-center justify-between rounded border border-slate-200 bg-slate-50 px-3 py-2">
                        <div>
                            <span class="font-mono font-bold text-slate-700">"<?php echo e($mapping->imported_uom); ?>"</span>
                            <span class="text-slate-400 mx-1">&rarr;</span>
                            <span class="font-semibold text-indigo-700"><?php echo e($mapping->uom?->name); ?> (<?php echo e($mapping->uom?->code); ?>)</span>
                        </div>
                        <form action="<?php echo e(route('masters.bulk-import.delete-uom-mapping', $mapping)); ?>" method="POST" onsubmit="return confirm('Remove this UOM mapping?');">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold px-1">&times;</button>
                        </form>
                    </div>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    </div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

<p class="mb-4 text-xs text-slate-500">
    Recommended order: Companies &rarr; Branches &rarr; Warehouses &rarr; Financial Years &rarr; UOMs &rarr; Brands &rarr; Categories &rarr; Sub Categories &rarr; Product Types &rarr; Products &rarr; Parties &rarr; Price List.
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
        'products' => ['Products', 'Match on Part Number. Normalizes UOMs (PCS, PCS (PCS-PIECES), PIECES, etc.). An existing product is updated only when something changed, otherwise it is skipped.'],
        'parties' => ['Parties (Customers &amp; Suppliers)', 'Match on Code, else GSTIN, else Name — re-import updates instead of duplicating. Classification, area and route auto-created.'],
        'price-master' => ['Price List', 'Match on Customer Type + Part Number + UOM + Min Qty. Supports rate_includes_gst and normalized UOMs.'],
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

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\DMS\resources\views/masters/bulk-import/index.blade.php ENDPATH**/ ?>