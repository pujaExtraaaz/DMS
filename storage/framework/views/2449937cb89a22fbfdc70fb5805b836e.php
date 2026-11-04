<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'search' => request('search', request('q', '')),
    'searchPlaceholder' => 'Search...',
    'searchName' => 'search',
    'resetUrl' => url()->current(),
    'hasActiveFilters' => false,
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
    'search' => request('search', request('q', '')),
    'searchPlaceholder' => 'Search...',
    'searchName' => 'search',
    'resetUrl' => url()->current(),
    'hasActiveFilters' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $searchTerm = (string) $search;
    // Check if any filter/search is active
    $queryParams = request()->except(['page']);
    $isActive = $hasActiveFilters || !empty($searchTerm) || count(array_filter($queryParams, fn($v) => $v !== null && $v !== '')) > 0;
?>

<div <?php echo e($attributes->merge(['class' => 'dms-table-toolbar mb-4 space-y-3'])); ?>>
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        
        <div class="flex flex-wrap items-center gap-2.5 flex-1 min-w-0">
            
            <div
                class="relative flex-1 min-w-[200px] max-w-md"
                x-data="{
                    query: '<?php echo e(addslashes($searchTerm)); ?>',
                    clear() {
                        this.query = '';
                        const input = this.$refs.searchInput;
                        input.value = '';
                        input.dispatchEvent(new Event('dms:search', { bubbles: true }));
                    }
                }"
            >
                <input
                    x-ref="searchInput"
                    type="text"
                    name="<?php echo e($searchName); ?>"
                    x-model="query"
                    data-dynamic-search
                    placeholder="<?php echo e(\App\Support\TitleCase::format($searchPlaceholder)); ?>"
                    autocomplete="off"
                    class="block w-full rounded-lg border-slate-300 pl-3 pr-9 text-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm transition"
                />

                
                <button
                    type="button"
                    x-show="query.length > 0"
                    x-cloak
                    @click="clear()"
                    class="absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400 hover:text-slate-600 transition"
                    title="Clear Search"
                >
                    <svg class="h-4 w-4 rounded-full p-0.5 hover:bg-slate-100" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($filters)): ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php echo e($filters); ?>

                </div>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>

            
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($isActive): ?>
                <a
                    href="<?php echo e($resetUrl); ?>"
                    data-reset-filters
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 shadow-sm transition"
                    title="Reset All Filters And Search"
                >
                    <svg class="h-3.5 w-3.5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99" />
                    </svg>
                    <span>Reset</span>
                </a>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>

        
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(isset($actions)): ?>
            <div class="flex items-center gap-2 shrink-0">
                <?php echo e($actions); ?>

            </div>
        <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </div>
</div>

<?php /**PATH D:\xampp\htdocs\DMS\resources\views/components/ui/table-toolbar.blade.php ENDPATH**/ ?>