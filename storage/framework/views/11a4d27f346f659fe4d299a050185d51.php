<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['title' => null]));

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

foreach (array_filter((['title' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>
<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <meta name="theme-color" content="#0f4c81">
    <title><?php echo e($title ? $title.' — ' : ''); ?><?php echo e(config('app.name')); ?> · Books</title>

    <link rel="manifest" href="<?php echo e(asset('manifest.webmanifest')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(asset('icons/apple-touch-icon.png')); ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo e(asset('icons/icon-192.png')); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo e(asset('icons/icon-512.png')); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
    <link rel="stylesheet" href="<?php echo e(asset('css/tally-books.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/books-dms.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('css/pwa-mobile.css')); ?>">
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="font-sans antialiased bg-slate-100 text-slate-900 overflow-hidden dms-pwa books-shell" data-home="<?php echo e(tally_route('books.tally.dashboard')); ?>">
    <a class="skip" href="#content">Skip to content</a>
    <div
        x-data="{
            sidebarOpen: false,
            sidebarCollapsed: <?php echo e(books_show_aside() ? 'true' : 'localStorage.getItem(\'dmsSidebarCollapsed\') === \'true\''); ?>,
            sidebarAnimate: false,
            booksAsideCollapsed: false,
            hasBooksAside: <?php echo e(books_show_aside() ? 'true' : 'false'); ?>,
            toggleCollapsed() {
                this.sidebarAnimate = true;
                this.booksAsideCollapsed = false;
                this.sidebarCollapsed = !this.sidebarCollapsed;
                localStorage.setItem('dmsSidebarCollapsed', this.sidebarCollapsed ? 'true' : 'false');
            },
            init() {
                this.booksAsideCollapsed = false;
                const stored = localStorage.getItem('dmsSidebarCollapsed');
                if (this.hasBooksAside) {
                    this.sidebarCollapsed = stored === null ? true : stored === 'true';
                } else {
                    this.sidebarCollapsed = stored === 'true';
                    this.sidebarAnimate = true;
                }
            }
        }"
        class="flex h-[100dvh] w-full overflow-hidden"
        id="app"
    >
        <?php echo $__env->make('partials.sidebar', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(books_show_aside()): ?>
            <?php echo $__env->make('partials.books-aside', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

        <div class="relative z-0 flex flex-1 flex-col min-w-0 h-[100dvh] overflow-hidden bg-slate-100">
            <header class="shrink-0 z-40 bg-white/95 backdrop-blur border-b border-slate-200/80 shadow-sm pt-[env(safe-area-inset-top)] overflow-visible">
                <div class="flex items-center justify-between h-14 sm:h-16 px-3 sm:px-6 lg:px-8 gap-3 overflow-visible">
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
                            <p class="hidden sm:block text-xs font-medium uppercase tracking-wider text-slate-400">10 · Books</p>
                            <h1 class="truncate text-base sm:text-lg font-semibold text-slate-900"><?php echo e($title ?: 'Books'); ?></h1>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 min-w-0 overflow-visible relative z-50">
                        <?php if (isset($component)) { $__componentOriginalea5b3383bcd54889d7b8dcb642ba128f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalea5b3383bcd54889d7b8dcb642ba128f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.shell.topbar','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::shell.topbar'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalea5b3383bcd54889d7b8dcb642ba128f)): ?>
<?php $attributes = $__attributesOriginalea5b3383bcd54889d7b8dcb642ba128f; ?>
<?php unset($__attributesOriginalea5b3383bcd54889d7b8dcb642ba128f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalea5b3383bcd54889d7b8dcb642ba128f)): ?>
<?php $component = $__componentOriginalea5b3383bcd54889d7b8dcb642ba128f; ?>
<?php unset($__componentOriginalea5b3383bcd54889d7b8dcb642ba128f); ?>
<?php endif; ?>
                        <?php
                            $roleName = Auth::user()->roles->first()?->name ?? 'User';
                            $roleLabel = str($roleName)->replace('-', ' ')->title();
                        ?>
                        <span class="hidden md:inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-1 text-xs font-semibold text-indigo-700"><?php echo e($roleLabel); ?></span>
                    </div>
                </div>
            </header>

            <main class="flex-1 overflow-y-auto overflow-x-hidden dms-content">
                <div class="px-3 sm:px-6 lg:px-8 py-4 sm:py-6 space-y-4 sm:space-y-6 max-w-[1600px] mx-auto w-full pb-24 lg:pb-6">
                    <?php if (isset($component)) { $__componentOriginal5a66076a97f03720758fb1666b2f7875 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5a66076a97f03720758fb1666b2f7875 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.shell.breadcrumbs','data' => ['items' => $breadcrumbs]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::shell.breadcrumbs'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['items' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($breadcrumbs)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5a66076a97f03720758fb1666b2f7875)): ?>
<?php $attributes = $__attributesOriginal5a66076a97f03720758fb1666b2f7875; ?>
<?php unset($__attributesOriginal5a66076a97f03720758fb1666b2f7875); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5a66076a97f03720758fb1666b2f7875)): ?>
<?php $component = $__componentOriginal5a66076a97f03720758fb1666b2f7875; ?>
<?php unset($__componentOriginal5a66076a97f03720758fb1666b2f7875); ?>
<?php endif; ?>

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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(session('warning')): ?>
                        <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'warning','message' => session('warning')]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'warning','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(session('warning'))]); ?>
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
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['company_id', 'branch_id', 'financial_year_id']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $contextField): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__errorArgs = [$contextField];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <?php if (isset($component)) { $__componentOriginal746de018ded8594083eb43be3f1332e1 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal746de018ded8594083eb43be3f1332e1 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.ui.alert','data' => ['type' => 'error','message' => $message]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('ui.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'error','message' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($message)]); ?>
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
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>

                    <div id="content" class="content books-dms">
                        <?php echo e($slot); ?>

                    </div>
                </div>
            </main>

            <?php echo $__env->make('partials.mobile-bottom-nav', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
        </div>
    </div>

    <?php if (isset($component)) { $__componentOriginal2f6e3e8711afea6b43f898827cc20b2f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal2f6e3e8711afea6b43f898827cc20b2f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.shell.global-search','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::shell.global-search'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal2f6e3e8711afea6b43f898827cc20b2f)): ?>
<?php $attributes = $__attributesOriginal2f6e3e8711afea6b43f898827cc20b2f; ?>
<?php unset($__attributesOriginal2f6e3e8711afea6b43f898827cc20b2f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal2f6e3e8711afea6b43f898827cc20b2f)): ?>
<?php $component = $__componentOriginal2f6e3e8711afea6b43f898827cc20b2f; ?>
<?php unset($__componentOriginal2f6e3e8711afea6b43f898827cc20b2f); ?>
<?php endif; ?>
    <?php if (isset($component)) { $__componentOriginal61daad9547d7df3afd19b7b4be4d3115 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal61daad9547d7df3afd19b7b4be4d3115 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'tally::components.shell.shortcut-help','data' => ['shortcuts' => $shortcuts]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('tally::shell.shortcut-help'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['shortcuts' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($shortcuts)]); ?>
<?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::processComponentKey($component); ?>

<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal61daad9547d7df3afd19b7b4be4d3115)): ?>
<?php $attributes = $__attributesOriginal61daad9547d7df3afd19b7b4be4d3115; ?>
<?php unset($__attributesOriginal61daad9547d7df3afd19b7b4be4d3115); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal61daad9547d7df3afd19b7b4be4d3115)): ?>
<?php $component = $__componentOriginal61daad9547d7df3afd19b7b4be4d3115; ?>
<?php unset($__componentOriginal61daad9547d7df3afd19b7b4be4d3115); ?>
<?php endif; ?>
    <div id="go-to-dialog" class="dialog" hidden>
        <div class="dialog-card tally-goto" role="dialog" aria-modal="true" aria-labelledby="go-to-title">
            <header class="dialog-head">
                <h2 id="go-to-title">Go To</h2>
                <button class="icon-btn" type="button" data-close-dialog>Close</button>
            </header>
            <div class="tally-goto-list">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = config('navigation.sections'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $section): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <p class="gateway-group"><?php echo e($section['label']); ?></p>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($section['route']) && tally_route_has($section['route'])): ?>
                        <a href="<?php echo e(tally_route($section['route'])); ?>"><?php echo e($section['label']); ?></a>
                    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = $section['children'] ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $child): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(! empty($child['route']) && tally_route_has($child['route'])): ?>
                            <a href="<?php echo e(tally_route($child['route'], $child['params'] ?? [])); ?>"><?php echo e($child['label']); ?></a>
                        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
                    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    </div>
    <div id="calculator-dialog" class="dialog" hidden>
        <div class="dialog-card" role="dialog" aria-modal="true" aria-labelledby="calculator-title">
            <header class="dialog-head">
                <h2 id="calculator-title">Calculator</h2>
                <button class="icon-btn" type="button" data-close-dialog>Close</button>
            </header>
            <input id="calculator-display" class="input calc-display" value="0" readonly>
            <div class="calc-grid">
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__currentLoopData = ['7', '8', '9', '/', '4', '5', '6', '*', '1', '2', '3', '-', '0', '.', '=', '+', 'c']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $calcKey): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                    <button class="btn" type="button" data-calc="<?php echo e($calcKey); ?>"><?php echo e($calcKey === 'c' ? 'C' : $calcKey); ?></button>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
            </div>
        </div>
    </div>
    <?php
        $working = app(\Tally\Context\WorkingCalendar::class)->present($workspace->company(), auth()->user(), $workspace->financialYear());
    ?>
    <div id="date-dialog" class="dialog" hidden>
        <div class="dialog-card" role="dialog" aria-modal="true" aria-labelledby="date-dialog-title">
            <header class="dialog-head">
                <h2 id="date-dialog-title">Date</h2>
                <button class="icon-btn" type="button" data-close-date>Close</button>
            </header>
            <div data-date-choice>
                <button class="btn" type="button" data-choose-date>Change Date</button>
                <button class="btn" type="button" data-choose-period>Change Period</button>
            </div>
            <form data-date-form method="POST" action="<?php echo e(tally_route('books.tally.context.date')); ?>" hidden>
                <?php echo csrf_field(); ?>
                <label class="field">
                    <span>Date</span>
                    <input class="input" type="date" name="as_on" value="<?php echo e($working['date']); ?>" <?php if($working['min']): ?> min="<?php echo e($working['min']); ?>" max="<?php echo e($working['max']); ?>" <?php endif; ?> required>
                </label>
                <button class="btn btn-primary" type="submit">Accept</button>
            </form>
            <form data-period-form method="POST" action="<?php echo e(tally_route('books.tally.context.period')); ?>" hidden>
                <?php echo csrf_field(); ?>
                <label class="field">
                    <span>From</span>
                    <input class="input" type="date" name="period_from" value="<?php echo e($working['from']); ?>" <?php if($working['min']): ?> min="<?php echo e($working['min']); ?>" max="<?php echo e($working['max']); ?>" <?php endif; ?> required>
                </label>
                <label class="field">
                    <span>To</span>
                    <input class="input" type="date" name="period_to" value="<?php echo e($working['to']); ?>" <?php if($working['min']): ?> min="<?php echo e($working['min']); ?>" max="<?php echo e($working['max']); ?>" <?php endif; ?> required>
                </label>
                <button class="btn btn-primary" type="submit">Accept</button>
            </form>
        </div>
    </div>
    <script type="application/json" id="shell-config"><?php echo json_encode(['shortcuts' => $shortcuts, 'search' => $searchIndex, 'searchUrl' => tally_route('books.tally.search.records')]) ?></script>
    <script type="module" src="<?php echo e(asset('js/tally-books.js')); ?>"></script>
    <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

    <script src="<?php echo e(asset('js/dynamic-tables.js')); ?>?v=20261028" defer></script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
    <?php echo $__env->make('partials.sync-conflict', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
</body>
</html>
<?php /**PATH D:\xampp\htdocs\dms\resources\views/tally/components/layouts/app.blade.php ENDPATH**/ ?>