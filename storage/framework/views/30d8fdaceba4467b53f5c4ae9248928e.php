<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0f4c81">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="DMS">
    <meta name="application-name" content="DMS">
    <meta name="format-detection" content="telephone=no">

    <title><?php echo $__env->yieldContent('title', config('app.name', 'DMS')); ?></title>

    <link rel="manifest" href="<?php echo e(asset('manifest.webmanifest')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('icons/apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('icons/icon-192.png')); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo e(asset('icons/icon-512.png')); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php if (isset($component)) { $__componentOriginalbc748dc00b05cca704641fe6d2156d6b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbc748dc00b05cca704641fe6d2156d6b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.vite-assets','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('vite-assets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbc748dc00b05cca704641fe6d2156d6b)): ?>
<?php $attributes = $__attributesOriginalbc748dc00b05cca704641fe6d2156d6b; ?>
<?php unset($__attributesOriginalbc748dc00b05cca704641fe6d2156d6b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbc748dc00b05cca704641fe6d2156d6b)): ?>
<?php $component = $__componentOriginalbc748dc00b05cca704641fe6d2156d6b; ?>
<?php unset($__componentOriginalbc748dc00b05cca704641fe6d2156d6b); ?>
<?php endif; ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/pwa-mobile.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/phone-directory.css')); ?>?v=20261116c">
    <style>
        input.uppercase, textarea.uppercase { text-transform: uppercase; }
        /* Cap runaway SVGs without forcing every icon/chevron to the same size */
        .dms-sidebar svg,
        header svg,
        button svg,
        a svg {
            max-width: 1.5rem;
            max-height: 1.5rem;
            flex-shrink: 0;
        }
        .dms-sidebar {
            font-size: 13px;
        }
        .dms-sidebar .sidebar-section-item > div > button {
            min-height: 2rem;
        }
        .dms-sidebar .sidebar-chevron {
            width: 0.875rem !important;
            height: 0.875rem !important;
            max-width: 0.875rem !important;
            max-height: 0.875rem !important;
        }
        .dms-sidebar .sidebar-section-icon {
            width: 1rem !important;
            height: 1rem !important;
            max-width: 1rem !important;
            max-height: 1rem !important;
        }
        .dms-sidebar .sidebar-item-icon {
            width: 1.15rem !important;
            height: 1.15rem !important;
            max-width: 1.15rem !important;
            max-height: 1.15rem !important;
        }
    </style>
    <?php echo $__env->yieldPushContent('styles'); ?>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden dms-pwa">
    <div
        x-data="{
            sidebarOpen: false,
            // Keep DMS sidebar expanded when Accounting Books aside opens (secondary panel).
            sidebarCollapsed: <?php echo e(books_show_aside() ? 'false' : 'localStorage.getItem(\'dmsSidebarCollapsed\') === \'true\''); ?>,
            sidebarAnimate: false,
            booksAsideCollapsed: false,
            hasBooksAside: <?php echo e(books_show_aside() ? 'true' : 'false'); ?>,
            installPrompt: null,
            showInstall: false,
            tooltipText: '',
            tooltipTop: 0,
            tooltipLeft: 0,
            tooltipVisible: false,
            showTooltip(el, text) {
                if (!this.sidebarCollapsed || !text) return;
                const rect = el.getBoundingClientRect();
                this.tooltipText = text;
                this.tooltipTop = rect.top + (rect.height / 2);
                this.tooltipLeft = rect.right + 12;
                this.tooltipVisible = true;
            },
            hideTooltip() {
                this.tooltipVisible = false;
            },
            toggleCollapsed() {
                this.sidebarAnimate = true;
                this.booksAsideCollapsed = false;
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('dmsSidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
            },
            init() {
                this.booksAsideCollapsed = false;
                if (this.hasBooksAside) {
                    // Primary nav stays labeled; books-aside is the secondary panel.
                    this.sidebarCollapsed = false;
                } else {
                    this.sidebarCollapsed = localStorage.getItem('dmsSidebarCollapsed') === 'true';
                    this.sidebarAnimate = true;
                }

                window.addEventListener('beforeinstallprompt', (e) => {
                    e.preventDefault();
                    this.installPrompt = e;
                    this.showInstall = true;
                });
            },
            async installApp() {
                if (!this.installPrompt) return;
                this.installPrompt.prompt();
                await this.installPrompt.userChoice;
                this.installPrompt = null;
                this.showInstall = false;
            }
        }"
        class="flex h-[100dvh] w-full overflow-hidden"
    >
        <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(books_show_aside()): ?>
            <?php echo $__env->make('partials.books-aside', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="relative flex flex-1 flex-col min-w-0 h-[100dvh] overflow-hidden bg-slate-100">
            <header class="shrink-0 z-30 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm pt-[env(safe-area-inset-top)]">
                <div class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-2 sm:gap-3 min-w-0">
                        <button
                            type="button"
                            @click="sidebarOpen = true"
                            class="lg:hidden inline-flex items-center justify-center rounded-lg p-2.5 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        >
                            <span class="sr-only">Open menu</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>

                        <button
                            type="button"
                            @click="toggleCollapsed()"
                            class="hidden lg:inline-flex items-center justify-center rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition"
                        >
                            <span class="sr-only">Toggle sidebar</span>
                            <svg
                                class="h-5 w-5 transition-transform duration-300"
                                :class="sidebarCollapsed ? 'rotate-180' : ''"
                                fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
                            >
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                            </svg>
                        </button>

                        <div class="min-w-0">
                            <?php if (! empty(trim($__env->yieldContent('breadcrumbs')))): ?>
                                <?php echo $__env->yieldContent('breadcrumbs'); ?>
                            <?php else: ?>
                                <p class="hidden sm:block text-xs font-medium uppercase tracking-wider text-slate-400">Overview</p>
                                <h1 class="truncate text-base sm:text-lg font-semibold text-slate-900"><?php echo e(\App\Support\TitleCase::format($__env->yieldContent('title') ?: 'Dashboard')); ?></h1>
                            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <?php echo $__env->make('partials.period-switcher', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <button
                            type="button"
                            x-show="showInstall"
                            x-cloak
                            @click="installApp()"
                            class="hidden sm:inline-flex items-center rounded-lg bg-teal-600 px-3 py-1.5 text-xs font-semibold text-white"
                        >
                            Install App
                        </button>

                        <div class="hidden md:flex items-center rounded-lg bg-slate-50 border border-slate-200 px-3 py-1.5">
                            <input type="search" placeholder="Search..." class="w-40 lg:w-52 bg-transparent border-0 text-sm text-slate-700 placeholder:text-slate-400 focus:ring-0 p-0" />
                        </div>

                        <?php
                            $roleName = Auth::user()->roles->first()?->name ?? 'User';
                            $roleLabel = str($roleName)->replace('-', ' ')->title();
                        ?>
                        <?php if (isset($component)) { $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.badge','data' => ['variant' => 'primary','class' => 'hidden xs:inline-flex sm:inline-flex']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.badge'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['variant' => 'primary','class' => 'hidden xs:inline-flex sm:inline-flex']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>
<?php echo e($roleLabel); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $attributes = $__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__attributesOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4)): ?>
<?php $component = $__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4; ?>
<?php unset($__componentOriginalab7baa01105b3dfe1e0cf1dfc58879b4); ?>
<?php endif; ?>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto overflow-x-hidden dms-content">
                <div class="px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4 sm:space-y-6 max-w-[1600px] mx-auto w-full pb-24 lg:pb-6">
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('status')): ?>
                        <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'success','message' => session('status')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'success','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('status'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('error')): ?>
                        <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'error','message' => session('error')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('error'))]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($errors->any()): ?>
                        <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'error']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error']); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

                            <ul class="list-disc list-inside space-y-1 text-sm">
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                                    <li><?php echo e($error); ?></li>
                                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                            </ul>
                         <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $attributes = $__attributesOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__attributesOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal746de018ded8594083eb43be3f1332e1)): ?>
<?php $component = $__componentOriginal746de018ded8594083eb43be3f1332e1; ?>
<?php unset($__componentOriginal746de018ded8594083eb43be3f1332e1); ?>
<?php endif; ?>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

                    <div class="sm:hidden" x-show="showInstall" x-cloak>
                        <button type="button" @click="installApp()" class="w-full rounded-xl bg-teal-600 px-4 py-3 text-sm font-semibold text-white shadow">
                            Install DMS on this phone
                        </button>
                    </div>

                    <?php echo $__env->yieldContent('content'); ?>
                </div>
            </main>

            <?php echo $__env->make('partials.mobile-bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <script>
        (function() {
            function registerGlobalAlpineTooltips() {
                if (typeof window.Alpine !== 'undefined' && typeof Alpine.data === 'function') {
                    if (!window.__DMS_ALPINE_TOOLTIPS_REGISTERED__) {
                        window.__DMS_ALPINE_TOOLTIPS_REGISTERED__ = true;
                        Alpine.data('tooltip', (defaultText = '') => ({
                            tooltipVisible: false,
                            tooltipText: defaultText,
                            showTooltip(text) {
                                if (text) this.tooltipText = text;
                                this.tooltipVisible = true;
                            },
                            hideTooltip() {
                                this.tooltipVisible = false;
                            },
                            toggleTooltip(text) {
                                if (text) this.tooltipText = text;
                                this.tooltipVisible = !this.tooltipVisible;
                            }
                        }));
                    }
                }
            }

            if (typeof window.Alpine !== 'undefined') {
                registerGlobalAlpineTooltips();
            } else {
                document.addEventListener('alpine:init', registerGlobalAlpineTooltips);
            }
        })();
    </script>
    <script src="<?php echo e(asset('js/dynamic-tables.js')); ?>?v=20261105" defer></script>
    <script src="<?php echo e(asset('js/title-case.js')); ?>" defer></script>
    <script src="<?php echo e(asset('js/searchable-select.js')); ?>?v=20261028b" defer></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->make('partials.sync-conflict', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/layouts/dms.blade.php ENDPATH**/ ?>