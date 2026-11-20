<?php $__env->startSection('title', 'Guide'); ?>
<?php $__env->startSection('content'); ?>
<?php
    $tones = [
        1 => '#4f46e5',
        2 => '#7c3aed',
        3 => '#0284c7',
        4 => '#0f766e',
        5 => '#059669',
        6 => '#d97706',
        7 => '#e11d48',
        8 => '#ea580c',
        9 => '#db2777',
        10 => '#c026d3',
        11 => '#0f172a',
        12 => '#475569',
        13 => '#0e7490',
    ];
    $moduleCount = count($modules);
?>

<style>
    .gd { --gd-ink: #0f172a; --gd-muted: #64748b; color: var(--gd-ink); }
    .gd-purpose, .gd-step p {
        margin: 0;
        text-align: justify;
        text-justify: inter-word;
        hyphens: auto;
        line-height: 1.75;
    }
    .gd-nav-grid { display: grid; gap: 8px; grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .gd-nav {
        position: sticky; top: 0; z-index: 20;
        background: rgba(255,255,255,.96);
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 8px;
        box-shadow: 0 8px 24px rgba(15, 23, 42, .06);
        backdrop-filter: blur(8px);
    }
    .gd-nav-grid a {
        display: flex; flex-direction: column; justify-content: center; align-items: center;
        gap: 1px; min-height: 44px;
        border-radius: 12px; text-decoration: none;
        background: #f8fafc; color: #334155;
        padding: 6px 4px; text-align: center;
    }
    .gd-nav-grid a small { font-size: 10px; font-weight: 700; letter-spacing: .08em; color: var(--gd); }
    .gd-nav-grid a span { font-size: 11px; font-weight: 650; line-height: 1.25; }
    .gd-nav-grid a.is-on { background: var(--gd); color: #fff; box-shadow: 0 6px 14px rgba(15, 23, 42, .12); }
    .gd-nav-grid a.is-on small { color: rgba(255,255,255,.8); }
    .gd-stack { display: flex; flex-direction: column; gap: 16px; }
    .gd-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .04);
        scroll-margin-top: 8px;
    }
    .gd-card-head {
        display: grid;
        grid-template-columns: auto 1fr auto;
        gap: 14px;
        align-items: center;
        padding: 18px 22px;
        background: linear-gradient(90deg, color-mix(in srgb, var(--gd) 12%, white), #fff 42%);
        border-bottom: 1px solid #f1f5f9;
        border-left: 5px solid var(--gd);
    }
    .gd-badge {
        width: 52px; height: 52px; border-radius: 16px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gd); color: #fff; font-size: 18px; font-weight: 750;
        box-shadow: 0 8px 16px color-mix(in srgb, var(--gd) 35%, transparent);
    }
    .gd-card-head p { margin: 0; font-size: 11px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; color: var(--gd); }
    .gd-card-head h3 { margin: 2px 0 0; font-size: 20px; line-height: 1.2; font-weight: 700; }
    .gd-count {
        font-size: 12px; font-weight: 650; color: #475569;
        background: #fff; border: 1px solid #e2e8f0; border-radius: 999px;
        padding: 6px 10px; white-space: nowrap;
    }
    .gd-body { padding: 18px 22px 20px; }
    .gd-purpose { font-size: 14.5px; color: #334155; }
    .gd-label {
        margin: 18px 0 0;
        font-size: 11px; font-weight: 700; letter-spacing: .16em;
        text-transform: uppercase; color: #94a3b8;
    }
    .gd-steps { list-style: none; margin: 14px 0 0; padding: 0; }
    .gd-step { display: grid; grid-template-columns: 32px minmax(0, 1fr); gap: 12px; }
    .gd-rail { display: flex; flex-direction: column; align-items: center; }
    .gd-dot {
        width: 32px; height: 32px; border-radius: 999px;
        display: flex; align-items: center; justify-content: center;
        background: var(--gd); color: #fff; font-size: 13px; font-weight: 700; flex: none;
    }
    .gd-line { width: 2px; flex: 1; min-height: 12px; margin: 4px 0; background: color-mix(in srgb, var(--gd) 28%, white); }
    .gd-step p { padding-bottom: 14px; font-size: 14.5px; color: #1e293b; }
    .gd-chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 12px; padding: 0; list-style: none; }
    .gd-chips li {
        border-radius: 999px;
        padding: 5px 12px;
        font-size: 12px; font-weight: 650;
        color: var(--gd);
        background: color-mix(in srgb, var(--gd) 10%, white);
        border: 1px solid color-mix(in srgb, var(--gd) 22%, white);
    }
    .gd-section { margin-top: 22px; padding-top: 16px; border-top: 1px solid #f1f5f9; }
    .gd-section-top {
        display: flex; flex-wrap: wrap; align-items: flex-start;
        justify-content: space-between; gap: 10px;
    }
    .gd-section h4 { margin: 4px 0 0; font-size: 16px; line-height: 1.3; font-weight: 700; color: #0f172a; }
    .gd-section .gd-purpose { margin-top: 8px; }
    .gd-open {
        display: inline-flex; align-items: center; gap: 6px;
        font-size: 12px; font-weight: 700; text-decoration: none;
        color: var(--gd);
        background: color-mix(in srgb, var(--gd) 10%, white);
        border: 1px solid color-mix(in srgb, var(--gd) 24%, white);
        border-radius: 999px; padding: 6px 12px; white-space: nowrap;
    }
    .gd-open:hover { background: color-mix(in srgb, var(--gd) 16%, white); }
    @media (min-width: 720px) {
        .gd-nav-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    }
    @media (min-width: 1100px) {
        .gd-nav-grid { grid-template-columns: repeat(7, minmax(0, 1fr)); }
    }
    @media (min-width: 1400px) {
        .gd-nav-grid { grid-template-columns: repeat(<?php echo e(min($moduleCount, 13)); ?>, minmax(0, 1fr)); }
    }
    @media (max-width: 640px) {
        .gd-body { padding-left: 16px; padding-right: 16px; }
        .gd-card-head { grid-template-columns: auto 1fr; padding: 16px; }
        .gd-count { grid-column: 2; justify-self: start; }
    }
</style>

<div
    class="gd"
    id="guide-root"
    x-data="{
        active: <?php echo e((int) ($modules[0]['number'] ?? 1)); ?>,
        scrolling: false,
        goTo(num, event) {
            if (event) event.preventDefault();
            const scroller = document.querySelector('main.dms-content');
            const target = document.getElementById('module-' + num);
            const nav = this.$el.querySelector('.gd-nav');
            if (!scroller || !target) return;
            this.active = num;
            this.scrolling = true;
            const navH = nav ? nav.getBoundingClientRect().height : 0;
            const top = target.getBoundingClientRect().top - scroller.getBoundingClientRect().top + scroller.scrollTop - navH - 10;
            scroller.scrollTo({ top: Math.max(0, top), behavior: 'smooth' });
            history.replaceState(null, '', '#module-' + num);
            clearTimeout(this._scrollUnlock);
            this._scrollUnlock = setTimeout(() => { this.scrolling = false; }, 500);
        }
    }"
>
    <div class="gd-stack">
        <nav class="gd-nav" aria-label="Guide modules">
            <div class="gd-nav-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <a href="#module-<?php echo e($module['number']); ?>"
                       style="--gd: <?php echo e($tones[$module['number']] ?? '#475569'); ?>"
                       @click="goTo(<?php echo e($module['number']); ?>, $event)"
                       :class="active === <?php echo e($module['number']); ?> ? 'is-on' : ''">
                        <small><?php echo e(str_pad((string) $module['number'], 2, '0', STR_PAD_LEFT)); ?></small>
                        <span><?php echo e($module['title']); ?></span>
                    </a>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </nav>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $modules; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $module): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
            <?php $tone = $tones[$module['number']] ?? '#475569'; ?>
            <section class="gd-card" id="module-<?php echo e($module['number']); ?>" data-guide-module="<?php echo e($module['number']); ?>" style="--gd: <?php echo e($tone); ?>">
                <header class="gd-card-head">
                    <span class="gd-badge"><?php echo e(str_pad((string) $module['number'], 2, '0', STR_PAD_LEFT)); ?></span>
                    <div>
                        <p>Module <?php echo e($module['number']); ?></p>
                        <h3><?php echo e($module['title']); ?></h3>
                    </div>
                    <span class="gd-count"><?php echo e(count($module['sections'] ?? [])); ?> screens</span>
                </header>
                <div class="gd-body">
                    <p class="gd-purpose"><?php echo e($module['purpose']); ?></p>
                    <p class="gd-label">Open these screens</p>
                    <ul class="gd-chips">
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $module['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                            <li><?php echo e($section['title']); ?></li>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                    </ul>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $module['sections']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php
                            $openRoute = $section['route'] ?? null;
                            $canOpen = is_string($openRoute) && $openRoute !== '' && \Illuminate\Support\Facades\Route::has($openRoute);
                        ?>
                        <section class="gd-section">
                            <div class="gd-section-top">
                                <div>
                                    <p class="gd-label" style="margin:0">Screen</p>
                                    <h4><?php echo e($section['title']); ?></h4>
                                </div>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($canOpen): ?>
                                    <a class="gd-open" href="<?php echo e(route($openRoute)); ?>">Open screen →</a>
                                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                            </div>
                            <p class="gd-purpose"><?php echo e($section['summary']); ?></p>
                            <ol class="gd-steps">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $section['steps']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li class="gd-step">
                                        <div class="gd-rail">
                                            <span class="gd-dot"><?php echo e($index + 1); ?></span>
                                            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! $loop->last): ?>
                                                <span class="gd-line"></span>
                                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                                        </div>
                                        <p><?php echo e($step); ?></p>
                                    </li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ol>
                        </section>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                </div>
            </section>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    function bindGuideNav() {
        const root = document.getElementById('guide-root');
        const scroller = document.querySelector('main.dms-content');
        const sections = Array.prototype.slice.call(document.querySelectorAll('[data-guide-module]'));
        if (!root || !scroller || !sections.length || !window.Alpine) return;

        const data = Alpine.$data(root);
        if (!data) return;

        const syncActive = function () {
            if (data.scrolling) return;
            const nav = root.querySelector('.gd-nav');
            const line = scroller.getBoundingClientRect().top + (nav ? nav.getBoundingClientRect().height : 0) + 12;
            let current = Number(sections[0].getAttribute('data-guide-module'));
            sections.forEach(function (section) {
                if (section.getBoundingClientRect().top <= line) {
                    current = Number(section.getAttribute('data-guide-module'));
                }
            });
            if (data.active !== current) data.active = current;
        };

        if (scroller.__guideNavScroll) scroller.removeEventListener('scroll', scroller.__guideNavScroll);
        scroller.__guideNavScroll = syncActive;
        scroller.addEventListener('scroll', syncActive, { passive: true });

        // Deep-link on load: #module-N
        const hash = (location.hash || '').match(/^#module-(\d+)$/);
        if (hash) {
            requestAnimationFrame(function () {
                data.goTo(Number(hash[1]));
            });
        } else {
            syncActive();
        }
    }
    document.addEventListener('alpine:initialized', bindGuideNav);
    if (window.Alpine) bindGuideNav();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.dms', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\xampp\htdocs\dms\resources\views/guide/index.blade.php ENDPATH**/ ?>