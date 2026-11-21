<?php if (isset($component)) { $__componentOriginal080152657857d1dbaf2f99a367fcc097 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal080152657857d1dbaf2f99a367fcc097 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.layouts.app','data' => ['title' => $voucher->voucher_number]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::layouts.app'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($voucher->voucher_number)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

    <?php if (isset($component)) { $__componentOriginal87808ea0cbb2319ecb2073e7eee9dd91 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal87808ea0cbb2319ecb2073e7eee9dd91 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.shell.page','data' => ['title' => $voucher->voucher_number,'section' => 'Transactions','description' => $voucher->voucher_type->label().' · '.$voucher->voucher_date->format('d M Y')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::shell.page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($voucher->voucher_number),'section' => 'Transactions','description' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($voucher->voucher_type->label().' · '.$voucher->voucher_date->format('d M Y'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

         <?php $__env->slot('actions', null, []); ?> 
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($voucher->invoice || in_array($voucher->voucher_type, [\Tally\Accounting\VoucherType::Sales, \Tally\Accounting\VoucherType::Purchase], true))): ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->isDraft()): ?>
                    <a class="btn" href="<?php echo e(tally_route('books.tally.vouchers.edit', $voucher)); ?>">Edit</a>
                    <form method="POST" action="<?php echo e(tally_route('books.tally.vouchers.post', $voucher)); ?>">
                        <?php echo csrf_field(); ?>
                        <button class="btn btn-primary" type="submit">Post voucher</button>
                    </form>
                    <form method="POST" action="<?php echo e(tally_route('books.tally.vouchers.destroy', $voucher)); ?>" onsubmit="return confirm('Delete this draft?');">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('DELETE'); ?>
                        <button class="btn" type="submit">Delete draft</button>
                    </form>
                <?php elseif($voucher->isPosted()): ?>
                    <a class="btn" href="<?php echo e(tally_route('books.tally.vouchers.entry', $voucher)); ?>">Alter</a>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if (! ($voucher->reversed_voucher_id)): ?>
                        <form method="POST" action="<?php echo e(tally_route('books.tally.vouchers.reverse', $voucher)); ?>" onsubmit="return confirm('Post a reversing voucher with swapped debit/credit?');">
                            <?php echo csrf_field(); ?>
                            <button class="btn" type="submit">Reverse</button>
                        </form>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <a class="btn" href="<?php echo e(tally_route('books.tally.vouchers.correct', $voucher)); ?>">Correcting entry</a>
                    <form method="POST" action="<?php echo e(tally_route('books.tally.vouchers.cancel', $voucher)); ?>" onsubmit="return confirm('Cancel this voucher? It will stay in the books as cancelled.');">
                        <?php echo csrf_field(); ?>
                        <button class="btn" type="submit">Cancel voucher</button>
                    </form>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <a class="btn" href="<?php echo e(tally_route('books.tally.vouchers.print', $voucher)); ?>" target="_blank">Print</a>
            <a class="btn" href="<?php echo e(tally_route('books.tally.vouchers.pdf', $voucher)); ?>">Download PDF</a>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($previousVoucher): ?>
                <a class="btn" rel="prev" href="<?php echo e(tally_route('books.tally.vouchers.show', $previousVoucher)); ?>">Previous</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($nextVoucher): ?>
                <a class="btn" rel="next" href="<?php echo e(tally_route('books.tally.vouchers.show', $nextVoucher)); ?>">Next</a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
         <?php $__env->endSlot(); ?>

        <div class="form-grid">
            <p><span class="muted">Status</span><br><span class="<?php echo \Illuminate\Support\Arr::toCssClasses(['tag', 'is-bad' => $voucher->isCancelled()]); ?>"><?php echo e($voucher->status->label()); ?></span></p>
            <p><span class="muted">Reference</span><br><?php echo e($voucher->reference_number ?: '—'); ?></p>
            <p class="span-2"><span class="muted">Narration</span><br><?php echo e($voucher->narration ?: '—'); ?></p>
        </div>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->invoice): ?>
            <p class="form-note"><a href="<?php echo e(tally_route($voucher->invoice->kind->routeName('show'), $voucher->invoice)); ?>">Open <?php echo e(strtolower($voucher->invoice->kind->documentLabel())); ?></a></p>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Ledger</th>
                        <th class="money">Debit</th>
                        <th class="money">Credit</th>
                        <th>Narration</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $voucher->entries; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $entry): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <tr>
                            <td><?php echo e($entry->ledger->name); ?></td>
                            <td class="money"><?php echo e($entry->debit); ?></td>
                            <td class="money"><?php echo e($entry->credit); ?></td>
                            <td><?php echo e($entry->narration ?: '—'); ?></td>
                            <td><?php echo e($entry->reference ?: '—'); ?></td>
                        </tr>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th class="money"><?php echo e($voucher->total_debit); ?></th>
                        <th class="money"><?php echo e($voucher->total_credit); ?></th>
                        <th colspan="2"></th>
                    </tr>
                </tfoot>
            </table>
        </div>

        <dl class="audit">
            <div><dt>Created by</dt><dd><?php echo e($voucher->creator->name ?? '—'); ?></dd></div>
            <div><dt>Created</dt><dd><?php echo e($voucher->created_at?->format('d M Y H:i')); ?></dd></div>
            <div><dt>Posted</dt><dd><?php echo e($voucher->posted_at?->format('d M Y H:i') ?? '—'); ?></dd></div>
            <div><dt>Cancelled</dt><dd><?php echo e($voucher->cancelled_at?->format('d M Y H:i') ?? '—'); ?></dd></div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->reversed_voucher_id): ?>
                <div><dt>Reversed by</dt><dd><a href="<?php echo e(tally_route('books.tally.vouchers.show', $voucher->reversed_voucher_id)); ?>">#<?php echo e($voucher->reversed_voucher_id); ?></a></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($voucher->corrected_voucher_id): ?>
                <div><dt>Corrects</dt><dd><a href="<?php echo e(tally_route('books.tally.vouchers.show', $voucher->corrected_voucher_id)); ?>">#<?php echo e($voucher->corrected_voucher_id); ?></a></dd></div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </dl>
     <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal87808ea0cbb2319ecb2073e7eee9dd91)): ?>
<?php $attributes = $__attributesOriginal87808ea0cbb2319ecb2073e7eee9dd91; ?>
<?php unset($__attributesOriginal87808ea0cbb2319ecb2073e7eee9dd91); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal87808ea0cbb2319ecb2073e7eee9dd91)): ?>
<?php $component = $__componentOriginal87808ea0cbb2319ecb2073e7eee9dd91; ?>
<?php unset($__componentOriginal87808ea0cbb2319ecb2073e7eee9dd91); ?>
<?php endif; ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal080152657857d1dbaf2f99a367fcc097)): ?>
<?php $attributes = $__attributesOriginal080152657857d1dbaf2f99a367fcc097; ?>
<?php unset($__attributesOriginal080152657857d1dbaf2f99a367fcc097); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal080152657857d1dbaf2f99a367fcc097)): ?>
<?php $component = $__componentOriginal080152657857d1dbaf2f99a367fcc097; ?>
<?php unset($__componentOriginal080152657857d1dbaf2f99a367fcc097); ?>
<?php endif; ?>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/tally/vouchers/show.blade.php ENDPATH**/ ?>