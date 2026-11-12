<?php
    $conflict = \App\Domains\Sync\Support\SyncLinks::conflictOnPage();
?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($conflict): ?>
    <div class="sync-conflict" role="dialog" aria-modal="true" aria-labelledby="sync-conflict-title">
        <article>
            <h2 id="sync-conflict-title">This record differs in Books</h2>
            <p><?php echo e($conflict->last_error ?: 'DMS and Books both changed this record. Choose which copy to keep.'); ?></p>
            <p>A posted document is left as it is.</p>
            <div>
                <form method="POST" action="<?php echo e(route('books.tally.sync.resolve')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="link_id" value="<?php echo e($conflict->id); ?>">
                    <input type="hidden" name="choice" value="dms">
                    <button type="submit">Keep DMS</button>
                </form>
                <form method="POST" action="<?php echo e(route('books.tally.sync.resolve')); ?>">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="link_id" value="<?php echo e($conflict->id); ?>">
                    <input type="hidden" name="choice" value="books">
                    <button type="submit">Keep Books</button>
                </form>
            </div>
        </article>
    </div>
    <style>
        .sync-conflict { position: fixed; inset: 0; z-index: 80; display: flex; align-items: center; justify-content: center; background: rgba(15, 23, 42, .45); padding: 1rem; }
        .sync-conflict article { width: min(28rem, 100%); background: #fff; color: #0f172a; border-radius: 12px; padding: 1.25rem 1.25rem 1rem; box-shadow: 0 20px 40px rgba(15, 23, 42, .2); }
        .sync-conflict h2 { margin: 0 0 .5rem; font-size: 1.05rem; }
        .sync-conflict p { margin: 0 0 .75rem; font-size: .92rem; line-height: 1.45; }
        .sync-conflict div { display: flex; gap: .5rem; }
        .sync-conflict form { margin: 0; }
        .sync-conflict button { border: 0; border-radius: 8px; background: #0f766e; color: #fff; font-weight: 600; padding: .55rem .9rem; cursor: pointer; }
        .sync-conflict form:last-child button { background: #1e293b; }
    </style>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php /**PATH D:\xampp\htdocs\DMS\resources\views/partials/sync-conflict.blade.php ENDPATH**/ ?>