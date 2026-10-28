@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Product' : 'Create Product')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Product' : 'Create Product'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.products.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

@php
    $primaryAlt = $item->alternateUom ?? ($item->exists ? $item->productUoms->firstWhere('is_base', false) : null);
    $extraAltUoms = old('product_uoms', $item->exists ? $item->productUoms->where('is_base', false)->reject(fn($u) => (int) $u->uom_id === (int) ($primaryAlt?->uom_id ?? 0))->map(fn($u) => [
        'uom_id' => $u->uom_id,
        'label' => $u->label,
        'conversion_factor' => (float) $u->conversion_factor,
        'selling_price' => $u->selling_price,
        'trade_price' => $u->trade_price,
        'purchase_price' => $u->purchase_price,
        'mrp' => $u->mrp,
        'is_default_sales' => (bool) $u->is_default_sales,
    ])->values()->toArray() : []);
    $uomOptions = $uoms->map(fn($u) => ['id' => (string) $u->id, 'name' => $u->name.' ('.$u->code.')', 'code' => (string) $u->code])->values()->toArray();
    $altNotApplicable = old('alternate_uom_not_applicable');
    if ($altNotApplicable === null) {
        $altNotApplicable = $item->exists && blank($item->alternate_uom_id) && blank($primaryAlt?->uom_id);
    } else {
        $altNotApplicable = (string) $altNotApplicable === '1';
    }
    $dimensionsNotApplicable = old('dimensions_not_applicable');
    if ($dimensionsNotApplicable === null) {
        $dimensionsNotApplicable = $item->exists && blank($item->height_cm) && blank($item->length_cm) && blank($item->width_cm) && blank($item->net_weight);
    } else {
        $dimensionsNotApplicable = (string) $dimensionsNotApplicable === '1';
    }
    $skuNotApplicable = old('sku_not_applicable');
    if ($skuNotApplicable === null) {
        $skuNotApplicable = $item->exists && blank($item->sku);
    } else {
        $skuNotApplicable = (string) $skuNotApplicable === '1';
    }
    $supplyTypes = \App\Domains\Master\Models\Product::SUPPLY_TYPES;
    $costingMethods = \App\Domains\Master\Models\Product::COSTING_METHODS;
    $marketMethods = \App\Domains\Master\Models\Product::MARKET_VALUATION_METHODS;
    $behaviourLabels = \App\Domains\Master\Models\Product::BEHAVIOUR_FLAGS;
    $provideBehaviour = (string) old('provide_behaviour', ($item->provide_behaviour ?? false) ? '1' : '0') === '1' ? '1' : '0';
    $behaviourDefaults = [];
    foreach (array_keys($behaviourLabels) as $behaviourField) {
        $behaviourDefaults[$behaviourField] = (string) old($behaviourField, ($item->{$behaviourField} ?? false) ? '1' : '0') === '1' ? '1' : '0';
    }
@endphp

<form method="POST"
      action="{{ $item->exists ? route('masters.products.update', $item) : route('masters.products.store') }}"
      class="max-w-5xl mx-auto space-y-6 pb-12"
      enctype="multipart/form-data"
      x-data="productCatalogManager({
          uoms: @js($uomOptions),
          brands: @js($brands->map(fn($b) => ['id' => (string) $b->id, 'name' => $b->name])->values()),
          categories: @js($categories->map(fn($c) => ['id' => (string) $c->id, 'brand_id' => $c->brand_id ? (string) $c->brand_id : null, 'name' => $c->name])->values()),
          subCategories: @js($subCategories->map(fn($sc) => ['id' => (string) $sc->id, 'category_id' => $sc->category_id ? (string) $sc->category_id : null, 'name' => $sc->name])->values()),
          initialBaseUomId: '{{ old('base_uom_id', $item->base_uom_id ?? '') }}',
          initialAltUomId: '{{ old('alternate_uom_id', $item->alternate_uom_id ?? ($primaryAlt?->uom_id ?? '')) }}',
          initialConversionFactor: '{{ old('conversion_factor', $item->conversion_factor ?? ($primaryAlt?->conversion_factor ?? 1)) }}',
          initialBrandId: '{{ old('brand_id', $item->brand_id ?? '') }}',
          initialCategoryId: '{{ old('category_id', $item->category_id ?? '') }}',
          initialSubCategoryId: '{{ old('sub_category_id', $item->sub_category_id ?? '') }}',
          initialPurchasePrice: '{{ old('purchase_price', $item->purchase_price ?? '') }}',
          initialTradePrice: '{{ old('trade_price', $item->trade_price ?? '') }}',
          initialSellingPrice: '{{ old('selling_price', $item->selling_price ?? '') }}',
          initialMrp: '{{ old('calculation_mrp', $item->calculation_mrp ?? '') }}',
          initialTaxRate: '{{ old('tax_rate', $item->exists ? $item->tax_rate : '') }}',
          initialPurchaseTaxBasis: '{{ old('purchase_price_tax_basis', $item->purchase_price_tax_basis ?? 'exclusive') }}',
          initialAltPurchasePrice: '{{ old('alt_purchase_price', $item->alt_purchase_price ?? ($primaryAlt?->purchase_price ?? '')) }}',
          initialAltTradePrice: '{{ old('alt_trade_price', $item->alt_trade_price ?? ($primaryAlt?->trade_price ?? '')) }}',
          initialAltSellingPrice: '{{ old('alt_selling_price', $item->alt_selling_price ?? ($primaryAlt?->selling_price ?? '')) }}',
          initialAltMrp: '{{ old('alt_calculation_mrp', $item->alt_calculation_mrp ?? ($primaryAlt?->mrp ?? '')) }}',
          initialAltNotApplicable: @js((bool) $altNotApplicable),
          categoryQuickUrl: @js(route('masters.categories.quick-store')),
          subCategoryQuickUrl: @js(route('masters.sub-categories.quick-store')),
          productTypeQuickUrl: @js(route('masters.product-types.quick-store')),
          brandQuickUrl: @js(route('masters.brands.quick-store')),
          companies: @js($companies->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values()),
      })"
      @keydown.escape.window="closeTopCreate()">
    @csrf @if($item->exists) @method('PUT') @endif
    <input type="hidden" name="has_dimensions" value="1">

    <!-- SECTION A: PRODUCT IDENTITY -->
    <x-ui.card title="Product Identity" description="Basic product information, identification codes, units of measurement, and categorization.">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <!-- Product Name -->
            <x-ui.input name="name" label="Product Name" :value="old('name', $item->name)" placeholder="Enter product name" required />

            <!-- Part Number (formerly SKU) -->
            <div class="space-y-1" x-data="{ skuNa: @js((bool) $skuNotApplicable) }">
                <div class="flex items-center justify-between">
                    <label for="sku" class="block text-sm font-medium text-gray-700">Part Number <span class="text-red-500" x-show="!skuNa">*</span></label>
                    <label class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-700">
                        <input type="checkbox" name="sku_not_applicable" value="1" x-model="skuNa" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Not Applicable
                    </label>
                </div>
                <input type="text" id="sku" name="sku" :disabled="skuNa" :required="!skuNa" value="{{ old('sku', $item->sku) }}" placeholder="Enter Part Number" class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-50 disabled:text-slate-400">
                @error('sku') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <!-- Model -->
            <x-ui.input name="model" label="Model" :value="old('model', $item->model ?? $item->models)" placeholder="E.G. T14 Gen 4, Sm-S928b" />

            <!-- Base UOM (Searchable Dropdown) -->
            <div class="space-y-2">
                <div class="space-y-1 relative" @click.outside="closeUomDropdown()">
                    <label for="base_uom_id_search_input" class="block text-sm font-medium text-gray-700">
                        Base UOM <span class="text-red-500">*</span>
                    </label>
                    <input type="hidden" name="base_uom_id" :value="selectedBaseUomId" required>
                    <div class="relative">
                        <input type="text"
                               id="base_uom_id_search_input"
                               x-model="uomSearchQuery"
                               @focus="openUomDropdown()"
                               @input="openUomDropdown()"
                               @keydown.escape="closeUomDropdown()"
                               @keydown.enter.prevent="if (uomIsOpen && filteredUoms.length > 0) { selectUom(filteredUoms[0]); }"
                               placeholder="Select Base UOM"
                               autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 @error('base_uom_id') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">

                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                            <button type="button"
                                    x-show="selectedBaseUomId"
                                    @click="clearUom()"
                                    title="Clear selection"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <button type="button"
                                    @click="uomIsOpen ? closeUomDropdown() : openUomDropdown()"
                                    tabindex="-1"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': uomIsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown List -->
                    <div x-show="uomIsOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                         style="display: none;">
                        <template x-for="u in filteredUoms" :key="u.id">
                            <div @click="selectUom(u)"
                                 :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(selectedBaseUomId) === String(u.id) }"
                                 class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                                <span class="font-medium" x-text="u.name"></span>
                                <span x-show="u.code" class="font-mono text-[11px] text-slate-400" x-text="u.code"></span>
                            </div>
                        </template>
                        <div x-show="filteredUoms.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                            No units of measurement found
                        </div>
                    </div>
                    @error('base_uom_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Base UOM Explanation / Balancing Card -->
                <div class="p-3 rounded-lg border border-slate-200 bg-slate-50/70 text-xs space-y-1">
                    <div class="flex items-center gap-1.5 font-semibold text-slate-700">
                        <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span>Primary Stock Unit (<span class="font-mono text-indigo-700 font-bold" x-text="baseUomCode || 'BASE'"></span>)</span>
                    </div>
                    <p class="text-[11px] text-slate-500 leading-snug">
                        Inventory tracking, stock ledger, and warehouse balances are always maintained in Base UOM.
                    </p>
                </div>
            </div>

            <!-- Alternative UOM (Searchable Dropdown) & Inline Conversion -->
            <div class="space-y-2">
                <div class="space-y-1 relative" @click.outside="closeAltUomDropdown()">
                    <div class="flex items-center justify-between">
                        <label for="alternate_uom_id_search_input" class="block text-sm font-medium text-gray-700">
                            Alternative UOM <span class="text-red-500">*</span>
                        </label>
                        <span x-show="selectedBaseUomId" class="text-[11px] text-slate-500 font-medium">Distinct from Base UOM</span>
                    </div>
                    <input type="hidden" name="alternate_uom_not_applicable" :value="altUomNotApplicable ? 1 : 0">
                    <input type="hidden" name="alternate_uom_id" :value="altUomNotApplicable ? '' : selectedAltUomId" :required="!altUomNotApplicable">
                    <div class="relative">
                        <input type="text"
                               id="alternate_uom_id_search_input"
                               x-model="altUomSearchQuery"
                               @focus="openAltUomDropdown()"
                               @input="openAltUomDropdown()"
                               @keydown.escape="closeAltUomDropdown()"
                               @keydown.enter.prevent="if (altUomIsOpen && filteredAltUoms.length > 0) { selectAltUom(filteredAltUoms[0]); }"
                               placeholder="Select Alternative UOM"
                               autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 @error('alternate_uom_id') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">

                        <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                            <button type="button"
                                    x-show="selectedAltUomId"
                                    @click="clearAltUom()"
                                    title="Clear selection"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </button>
                            <button type="button"
                                    @click="altUomIsOpen ? closeAltUomDropdown() : openAltUomDropdown()"
                                    tabindex="-1"
                                    class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': altUomIsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>
                    </div>

                    <!-- Dropdown List -->
                    <div x-show="altUomIsOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                         style="display: none;">
                        <div x-show="!altUomSearchQuery || 'not applicable'.includes((altUomSearchQuery || '').toLowerCase().trim())"
                             @click="selectAltUomNotApplicable()"
                             :class="{ 'bg-indigo-50 text-indigo-900': altUomNotApplicable }"
                             class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs font-semibold text-indigo-700">
                            Not Applicable
                        </div>
                        <template x-for="u in filteredAltUoms" :key="u.id">
                            <div @click="selectAltUom(u)"
                                 :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(selectedAltUomId) === String(u.id) }"
                                 class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                                <span class="font-medium" x-text="u.name"></span>
                                <span x-show="u.code" class="font-mono text-[11px] text-slate-400" x-text="u.code"></span>
                            </div>
                        </template>
                        <div x-show="filteredAltUoms.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                            No alternative units available
                        </div>
                    </div>
                    @error('alternate_uom_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Conversion Quantity Field & Dynamic Conversion Display -->
                <div class="p-3 rounded-lg border border-indigo-100 bg-indigo-50/40 space-y-2" x-show="!altUomNotApplicable">
                    <div class="flex items-center justify-between">
                        <label for="conversion_factor" class="block text-xs font-semibold text-slate-700">
                            Conversion Quantity <span class="text-red-500">*</span>
                        </label>
                        <span class="text-[11px] text-slate-500 font-mono">1 <span x-text="altUomCode || 'ROLL'"></span> = ? <span x-text="baseUomCode || 'MTR'"></span></span>
                    </div>
                    <div class="relative">
                        <input type="number"
                               id="conversion_factor"
                               name="conversion_factor"
                               x-model="conversionFactor"
                               @input="onConversionFactorChange()"
                               step="0.0001"
                               min="0.0001"
                               :required="!altUomNotApplicable"
                               placeholder="E.G. 100"
                               class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-24 @error('conversion_factor') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono">
                            <span x-text="baseUomCode || 'BASE'"></span> / <span x-text="altUomCode || 'ALT'"></span>
                        </div>
                    </div>
                    @error('conversion_factor')
                        <p class="text-xs text-red-600 mt-0.5">{{ $message }}</p>
                    @enderror

                    <!-- Dynamic Conversion Display Banner -->
                    <div class="px-2.5 py-1.5 rounded-md bg-white border border-indigo-200 text-xs font-semibold text-indigo-900 flex items-center justify-between shadow-2xs">
                        <span class="flex items-center gap-1.5 truncate">
                            <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                            <span class="truncate" x-text="'Conversion: 1 ' + (altUomCode || 'ROLL') + ' = ' + (conversionFactor || 100) + ' ' + (baseUomCode || 'MTR')">
                                Conversion: 1 ROLL = 100 MTR
                            </span>
                        </span>
                        <span class="text-[10px] text-slate-400 shrink-0">Inventory in <strong class="text-indigo-700" x-text="baseUomCode || 'BASE'"></strong></span>
                    </div>
                </div>
            </div>

            <!-- Brand (Searchable Dropdown) -->
            <div class="space-y-1 relative" @click.outside="closeBrandDropdown()">
                <div class="flex items-center justify-between">
                    <label for="brand_id_search_input" class="block text-sm font-medium text-gray-700">Brand</label>
                    <button type="button" @click="openBrandCreate('product')" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                </div>
                <input type="hidden" name="brand_id" :value="selectedBrandId">
                <div class="relative">
                    <input type="text"
                           id="brand_id_search_input"
                           x-model="brandSearchQuery"
                           @focus="openBrandDropdown()"
                           @input="openBrandDropdown()"
                           @keydown.escape="closeBrandDropdown()"
                           @keydown.enter.prevent="if (brandIsOpen && filteredBrands.length > 0) { selectBrand(filteredBrands[0]); }"
                           placeholder="Select Brand"
                           autocomplete="off"
                           class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 @error('brand_id') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">

                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                        <button type="button"
                                x-show="selectedBrandId"
                                @click="clearBrand()"
                                title="Clear selection"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <button type="button"
                                @click="brandIsOpen ? closeBrandDropdown() : openBrandDropdown()"
                                tabindex="-1"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': brandIsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Dropdown List -->
                <div x-show="brandIsOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                     style="display: none;">
                    <template x-for="b in filteredBrands" :key="b.id">
                        <div @click="selectBrand(b)"
                             :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(selectedBrandId) === String(b.id) }"
                             class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                            <span class="font-medium" x-text="b.name"></span>
                            <span x-show="b.code" class="font-mono text-[11px] text-slate-400" x-text="b.code"></span>
                        </div>
                    </template>
                    <div x-show="filteredBrands.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                        No brands found
                    </div>
                    <button type="button" @click.stop="openBrandCreate('product')" class="w-full px-3 py-2 text-left text-xs font-semibold text-indigo-700 hover:bg-indigo-50">+ Create Brand</button>
                </div>
                @error('brand_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Category (Searchable Dropdown - Dependent on Brand) -->
            <div class="space-y-1 relative" @click.outside="closeCategoryDropdown()">
                <div class="flex items-center justify-between">
                    <label for="category_id_search_input" class="block text-sm font-medium text-gray-700">
                        Category
                    </label>
                    <div class="flex items-center gap-3">
                        <span x-show="selectedBrandId" class="text-[11px] text-indigo-600 font-medium">Filtered by Brand</span>
                        <button type="button" @click="openCategoryCreate()" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                    </div>
                </div>
                <input type="hidden" name="category_id" :value="selectedCategoryId">
                <div class="relative">
                    <input type="text"
                           id="category_id_search_input"
                           x-model="categorySearchQuery"
                           @focus="openCategoryDropdown()"
                           @input="openCategoryDropdown()"
                           @keydown.escape="closeCategoryDropdown()"
                           @keydown.enter.prevent="if (categoryIsOpen && filteredCategories.length > 0) { selectCategory(filteredCategories[0]); }"
                           placeholder="Select Category"
                           autocomplete="off"
                           class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 @error('category_id') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">

                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                        <button type="button"
                                x-show="selectedCategoryId"
                                @click="clearCategory(true)"
                                title="Clear selection"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <button type="button"
                                @click="categoryIsOpen ? closeCategoryDropdown() : openCategoryDropdown()"
                                tabindex="-1"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': categoryIsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Dropdown List -->
                <div x-show="categoryIsOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                     style="display: none;">
                    <template x-for="c in filteredCategories" :key="c.id">
                        <div @click="selectCategory(c)"
                             :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(selectedCategoryId) === String(c.id) }"
                             class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                            <span class="font-medium" x-text="c.name"></span>
                            <span x-show="c.code" class="font-mono text-[11px] text-slate-400" x-text="c.code"></span>
                        </div>
                    </template>
                    <div x-show="filteredCategories.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                        No categories found<span x-show="selectedBrandId"> for selected brand</span>
                    </div>
                    <button type="button" @click.stop="openCategoryCreate('product')" class="w-full px-3 py-2 text-left text-xs font-semibold text-indigo-700 hover:bg-indigo-50">+ Create Category</button>
                </div>

                @error('category_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Sub Category (Searchable Dropdown - Dependent on Category) -->
            <div class="space-y-1 relative" @click.outside="closeSubCategoryDropdown()">
                <div class="flex items-center justify-between">
                    <label for="sub_category_id_search_input" class="block text-sm font-medium text-gray-700">
                        Sub Category
                    </label>
                    <div class="flex items-center gap-3">
                        <span x-show="selectedCategoryId" class="text-[11px] text-indigo-600 font-medium">Filtered by Category</span>
                        <button type="button" @click="openSubCategoryCreate()" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                    </div>
                </div>
                <input type="hidden" name="sub_category_id" :value="selectedSubCategoryId">
                <div class="relative">
                    <input type="text"
                           id="sub_category_id_search_input"
                           x-model="subCategorySearchQuery"
                           @focus="openSubCategoryDropdown()"
                           @input="openSubCategoryDropdown()"
                           @keydown.escape="closeSubCategoryDropdown()"
                           @keydown.enter.prevent="if (subCategoryIsOpen && filteredSubCategories.length > 0) { selectSubCategory(filteredSubCategories[0]); }"
                           placeholder="Select Sub Category"
                           autocomplete="off"
                           class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-16 @error('sub_category_id') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">

                    <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                        <button type="button"
                                x-show="selectedSubCategoryId"
                                @click="clearSubCategory()"
                                title="Clear selection"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                        <button type="button"
                                @click="subCategoryIsOpen ? closeSubCategoryDropdown() : openSubCategoryDropdown()"
                                tabindex="-1"
                                class="p-1 text-slate-400 hover:text-slate-600 rounded">
                            <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': subCategoryIsOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>
                </div>

                <!-- Dropdown List -->
                <div x-show="subCategoryIsOpen"
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                     style="display: none;">
                    <template x-for="sc in filteredSubCategories" :key="sc.id">
                        <div @click="selectSubCategory(sc)"
                             :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(selectedSubCategoryId) === String(sc.id) }"
                             class="p-2.5 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                            <span class="font-medium" x-text="sc.name"></span>
                            <span x-show="sc.code" class="font-mono text-[11px] text-slate-400" x-text="sc.code"></span>
                        </div>
                    </template>
                    <div x-show="filteredSubCategories.length === 0" class="p-3 text-center text-xs text-slate-500 italic">
                        No subcategories found<span x-show="selectedCategoryId"> for selected category</span>
                    </div>
                    <button type="button" @click.stop="openSubCategoryCreate()" class="w-full px-3 py-2 text-left text-xs font-semibold text-indigo-700 hover:bg-indigo-50">+ Create Sub Category</button>
                </div>

                @error('sub_category_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1 md:col-span-2">
                <div class="flex items-center justify-between">
                    <label for="product_type_id" class="block text-sm font-medium text-gray-700">Product Type</label>
                    <button type="button" @click="openProductTypeCreate()" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                </div>
                <select name="product_type_id" id="product_type_id" class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select product type</option>
                    @foreach($productTypes ?? [] as $pt)
                        <option value="{{ $pt->id }}" @selected(old('product_type_id', $item->product_type_id) == $pt->id || old('product_type', $item->product_type) == $pt->name)>
                            {{ $pt->name }}@if($pt->track_serial) — serial numbers @endif
                        </option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500">Use the brand, category, and sub-category above, then choose the product type. The list includes every active type.</p>

                @error('product_type_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div class="space-y-1">
                <label for="supply_type" class="block text-sm font-medium text-gray-700">Type of Supply <span class="text-red-500">*</span></label>
                <select name="supply_type" id="supply_type" required class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach($supplyTypes as $value => $label)
                        <option value="{{ $value }}" @selected(old('supply_type', $item->supply_type ?? 'goods') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="text-[11px] text-slate-500">Whether this product is capital goods, goods, or services.</p>
                @error('supply_type')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- HSN Code -->
            <x-ui.input name="hsn_code" label="HSN Code" :value="old('hsn_code', $item->hsn_code)" placeholder="E.G. 85285200" />

            <!-- Tracking Type (Full Width spanning 2 columns) -->
            @php
                $rawTracking = old('tracking_type', $item->tracking_type ?? 'none');
                if (is_array($rawTracking)) {
                    $selectedTracking = $rawTracking;
                } elseif (is_string($rawTracking) && filled($rawTracking) && $rawTracking !== 'none') {
                    if (str_starts_with($rawTracking, '[') && str_ends_with($rawTracking, ']')) {
                        $selectedTracking = json_decode($rawTracking, true) ?: [];
                    } else {
                        $selectedTracking = array_map('trim', explode(',', $rawTracking));
                    }
                } else {
                    $selectedTracking = [];
                }
                $selectedTracking = array_values(array_filter($selectedTracking, fn($t) => $t !== 'none'));
            @endphp
            <div class="md:col-span-2 space-y-1" x-data="productTrackingManager(@json($selectedTracking))">
                <label class="flex items-start gap-3 rounded-xl border border-indigo-200 bg-indigo-50 p-3 cursor-pointer">
                    <input type="checkbox" name="tracking_type[]" value="serial" @checked(in_array('serial', $selectedTracking, true)) class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                    <span>
                        <span class="block text-sm font-semibold text-slate-800">Track serial numbers</span>
                        <span class="block text-xs text-slate-600">Saved on this product. Purchase invoices then show a serial number box for this item without turning it on again.</span>
                    </span>
                </label>
                <label class="block text-sm font-medium text-slate-700">Tracking Type</label>
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50 space-y-3">
                    <!-- Search input for tracking type -->
                    <div class="relative">
                        <input type="text"
                               x-model="searchQuery"
                               placeholder="Search tracking options..."
                               class="block w-full rounded-lg border-gray-200 text-xs py-2 px-3 placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-8 bg-white">
                        <button type="button"
                                x-show="searchQuery"
                                @click="searchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 text-sm font-bold">&times;</button>
                    </div>

                    <div class="flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-sm font-semibold text-slate-800 cursor-pointer select-none">
                            <input type="checkbox"
                                   x-ref="selectAllCheckbox"
                                   :checked="allSelected"
                                   x-effect="if ($refs.selectAllCheckbox) $refs.selectAllCheckbox.indeterminate = isIndeterminate"
                                   @change="toggleSelectAll()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                            Select All
                        </label>
                        <span class="text-xs text-slate-400">Select applicable inventory tracking methods</span>
                    </div>

                    <div class="flex flex-wrap gap-6 pt-3 border-t border-slate-200">
                        <template x-for="opt in filteredOptions" :key="opt.value">
                            <label class="flex items-center gap-2.5 text-sm text-slate-700 font-medium cursor-pointer select-none">
                                <input type="checkbox"
                                       name="tracking_type[]"
                                       :value="opt.value"
                                       :checked="selected.includes(opt.value)"
                                       @change="toggleOption(opt.value)"
                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
                                <span x-text="opt.label"></span>
                            </label>
                        </template>
                        <div x-show="filteredOptions.length === 0" class="text-xs text-slate-400 italic">
                            No matching tracking options found
                        </div>
                    </div>
                </div>
                @error('tracking_type')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </x-ui.card>

    <!-- SECTION: PRODUCT DIMENSIONS & WEIGHT -->
    <x-ui.card title="Product Dimensions & Weight" description="Physical dimensions in Centimeters and Inches with bidirectional real-time calculation, plus package net weight.">
        <div x-data="productDimensionsManager({
            initialHeightCm: '{{ old('height_cm', $item->height_cm ?? '') }}',
            initialHeightIn: '{{ old('height_in', $item->height_in ?? '') }}',
            initialLengthCm: '{{ old('length_cm', $item->length_cm ?? '') }}',
            initialLengthIn: '{{ old('length_in', $item->length_in ?? '') }}',
            initialWidthCm: '{{ old('width_cm', $item->width_cm ?? '') }}',
            initialWidthIn: '{{ old('width_in', $item->width_in ?? '') }}',
            initialNetWeight: '{{ old('net_weight', $item->net_weight ?? '') }}',
            initialWeightUnit: '{{ old('weight_unit', $item->weight_unit ?? 'KG') }}',
            initialNotApplicable: @js((bool) $dimensionsNotApplicable)
        })" class="space-y-6">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="dimensions_not_applicable" value="1" x-model="notApplicable" @change="onNotApplicableChange()" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                Not Applicable
            </label>
            <p class="text-xs text-slate-500 -mt-3" x-show="notApplicable" x-cloak>Height, length, width, and net weight are hidden for this product.</p>

            <div x-show="!notApplicable" x-transition @if($dimensionsNotApplicable) style="display:none" @endif>
            <!-- 3 Dimension Columns: Height, Length, Width -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                <!-- Height Dimension Panel -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                        <span class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4" />
                            </svg>
                            Height <span class="text-red-500">*</span>
                        </span>
                        <span class="text-[11px] font-mono text-slate-400 font-medium">Vertical</span>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Height (CM) -->
                        <div>
                            <label for="height_cm" class="block text-xs font-semibold text-slate-700 mb-1">
                                Height (CM) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="height_cm"
                                       name="height_cm"
                                       x-model="heightCm"
                                       @input="onCmInput('height')"
                                       step="0.01"
                                       min="0.01"
                                       :required="!notApplicable"
                                       :disabled="notApplicable"
                                       placeholder="e.g. 68.00"
                                       class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('height_cm') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    CM
                                </div>
                            </div>
                            @error('height_cm')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Height (Inches) -->
                        <div>
                            <label for="height_in" class="block text-xs font-semibold text-slate-600 mb-1">
                                Height (Inches)
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="height_in"
                                       name="height_in"
                                       x-model="heightIn"
                                       @input="onInchesInput('height')"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="e.g. 26.77"
                                       class="block w-full rounded-lg border-gray-300 bg-white/80 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('height_in') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    in
                                </div>
                            </div>
                            @error('height_in')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-1 text-[11px] text-slate-500 font-mono flex items-center justify-between" x-show="heightCm || heightIn">
                        <span>Conversion:</span>
                        <span class="font-semibold text-indigo-700" x-text="(heightCm || '0') + ' CM ⇄ ' + (heightIn || '0') + ' in'"></span>
                    </div>
                </div>

                <!-- Length Dimension Panel -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                        <span class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4" />
                            </svg>
                            Length <span class="text-red-500">*</span>
                        </span>
                        <span class="text-[11px] font-mono text-slate-400 font-medium">Horizontal</span>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Length (CM) -->
                        <div>
                            <label for="length_cm" class="block text-xs font-semibold text-slate-700 mb-1">
                                Length (CM) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="length_cm"
                                       name="length_cm"
                                       x-model="lengthCm"
                                       @input="onCmInput('length')"
                                       step="0.01"
                                       min="0.01"
                                       :required="!notApplicable"
                                       :disabled="notApplicable"
                                       placeholder="e.g. 107.00"
                                       class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('length_cm') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    CM
                                </div>
                            </div>
                            @error('length_cm')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Length (Inches) -->
                        <div>
                            <label for="length_in" class="block text-xs font-semibold text-slate-600 mb-1">
                                Length (Inches)
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="length_in"
                                       name="length_in"
                                       x-model="lengthIn"
                                       @input="onInchesInput('length')"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="e.g. 42.13"
                                       class="block w-full rounded-lg border-gray-300 bg-white/80 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('length_in') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    in
                                </div>
                            </div>
                            @error('length_in')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-1 text-[11px] text-slate-500 font-mono flex items-center justify-between" x-show="lengthCm || lengthIn">
                        <span>Conversion:</span>
                        <span class="font-semibold text-indigo-700" x-text="(lengthCm || '0') + ' CM ⇄ ' + (lengthIn || '0') + ' in'"></span>
                    </div>
                </div>

                <!-- Width Dimension Panel -->
                <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50 space-y-3">
                    <div class="flex items-center justify-between border-b border-slate-200/80 pb-2">
                        <span class="text-sm font-semibold text-slate-800 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" />
                            </svg>
                            Width <span class="text-red-500">*</span>
                        </span>
                        <span class="text-[11px] font-mono text-slate-400 font-medium">Depth</span>
                    </div>

                    <div class="space-y-2.5">
                        <!-- Width (CM) -->
                        <div>
                            <label for="width_cm" class="block text-xs font-semibold text-slate-700 mb-1">
                                Width (CM) <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="width_cm"
                                       name="width_cm"
                                       x-model="widthCm"
                                       @input="onCmInput('width')"
                                       step="0.01"
                                       min="0.01"
                                       :required="!notApplicable"
                                       :disabled="notApplicable"
                                       placeholder="e.g. 14.00"
                                       class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('width_cm') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    CM
                                </div>
                            </div>
                            @error('width_cm')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Width (Inches) -->
                        <div>
                            <label for="width_in" class="block text-xs font-semibold text-slate-600 mb-1">
                                Width (Inches)
                            </label>
                            <div class="relative">
                                <input type="number"
                                       id="width_in"
                                       name="width_in"
                                       x-model="widthIn"
                                       @input="onInchesInput('width')"
                                       step="0.01"
                                       min="0.01"
                                       placeholder="e.g. 5.51"
                                       class="block w-full rounded-lg border-gray-300 bg-white/80 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-12 @error('width_in') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                                <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-mono font-semibold">
                                    in
                                </div>
                            </div>
                            @error('width_in')
                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-1 text-[11px] text-slate-500 font-mono flex items-center justify-between" x-show="widthCm || widthIn">
                        <span>Conversion:</span>
                        <span class="font-semibold text-indigo-700" x-text="(widthCm || '0') + ' CM ⇄ ' + (widthIn || '0') + ' in'"></span>
                    </div>
                </div>
            </div>

            <!-- Net Weight Section & Live Physical Profile -->
            <div class="pt-4 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-5 items-center">
                <!-- Net Weight with Unit Selector -->
                <div>
                    <label for="net_weight" class="block text-xs font-semibold text-slate-700 mb-1">
                        Net Weight <span class="text-red-500">*</span>
                    </label>
                    <div class="flex rounded-lg shadow-2xs">
                        <div class="relative flex-1">
                            <input type="number"
                                   id="net_weight"
                                   name="net_weight"
                                   x-model="netWeight"
                                   step="0.001"
                                   min="0.001"
                                   :required="!notApplicable"
                                   :disabled="notApplicable"
                                   placeholder="e.g. 2.500"
                                   class="block w-full rounded-l-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('net_weight') border-red-300 text-red-900 focus:border-red-500 focus:ring-red-500 @enderror">
                        </div>
                        <select id="weight_unit"
                                name="weight_unit"
                                x-model="weightUnit"
                                class="inline-flex items-center -ml-px rounded-r-lg border border-l-0 border-gray-300 bg-slate-50 px-3.5 text-xs font-semibold text-slate-700 hover:bg-slate-100 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="KG">KG (Kilograms)</option>
                            <option value="Grams">Grams (g)</option>
                        </select>
                    </div>
                    <p class="text-[11px] text-slate-500 mt-1">Package net weight used for logistics, freight, and shipping calculations.</p>
                    @error('net_weight')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                    @error('weight_unit')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Live Dimension & Weight Summary Banner -->
                <div class="p-3.5 rounded-xl border border-indigo-100 bg-indigo-50/50 flex flex-col justify-center space-y-1">
                    <span class="text-xs font-semibold text-indigo-950 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                        </svg>
                        Product Physical Profile
                    </span>
                    <div class="text-xs text-slate-600 space-y-0.5 font-mono">
                        <div>
                            <span class="text-slate-400">Dimensions:</span>
                            <span class="font-medium text-slate-800" x-text="(lengthCm || '-') + ' × ' + (widthCm || '-') + ' × ' + (heightCm || '-') + ' CM'"></span>
                            <span class="text-slate-400" x-show="lengthIn || widthIn || heightIn" x-text="'(' + (lengthIn || '-') + ' × ' + (widthIn || '-') + ' × ' + (heightIn || '-') + ' in)'"></span>
                        </div>
                        <div>
                            <span class="text-slate-400">Net Weight:</span>
                            <span class="font-medium text-slate-800" x-text="(netWeight ? (netWeight + ' ' + weightUnit) : 'Not specified')"></span>
                        </div>
                    </div>
                </div>
            </div>
            </div>
        </div>
    </x-ui.card>

    <!-- SECTION B: PRICING & TAXES -->
    <x-ui.card title="Pricing & Taxes" description="Configure tax rate and unit-wise pricing for base and alternative units. Alternative prices auto-derive from Base &times; Conversion Quantity with manual override support.">
        <div class="space-y-5">
            <!-- Tax Rate Header Row -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/60">
                <div>
                    <span class="text-sm font-semibold text-slate-800">GST / Tax Configuration</span>
                    <p class="text-xs text-slate-500 mt-0.5">Applicable tax rate for domestic sales and purchases.</p>
                </div>
                <div class="w-full sm:w-48">
                    <label for="tax_rate" class="block text-xs font-semibold text-slate-700 mb-1">GST Rate (%) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number"
                               id="tax_rate"
                               name="tax_rate"
                               x-model="taxRate"
                               step="0.01"
                               min="0"
                               max="100"
                               required
                               placeholder="e.g. 18.00"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8 bg-white">
                        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none text-xs text-slate-400 font-semibold">%</div>
                    </div>
                </div>
            </div>

            <!-- Balanced Dual Pricing Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                <!-- Base UOM Pricing Card -->
                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-2xs space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                                Base Unit
                            </span>
                            <span class="text-sm font-bold text-slate-800 font-mono" x-text="baseUomCode || 'BASE'"></span>
                        </div>
                        <span class="text-xs text-slate-400">Inventory balance currency</span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Purchase Price</label>
                            <input type="number"
                                   name="purchase_price"
                                   x-model="purchasePrice"
                                   @input="onBasePriceChange('purchase')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('purchase_price') border-red-300 @enderror">
                            @error('purchase_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Trade Price</label>
                            <input type="number"
                                   name="trade_price"
                                   x-model="tradePrice"
                                   @input="onBasePriceChange('trade')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('trade_price') border-red-300 @enderror">
                            @error('trade_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Selling Price</label>
                            <input type="number"
                                   name="selling_price"
                                   x-model="sellingPrice"
                                   @input="onBasePriceChange('selling')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('selling_price') border-red-300 @enderror">
                            @error('selling_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">MRP</label>
                            <input type="number"
                                   name="calculation_mrp"
                                   x-model="mrp"
                                   @input="onBasePriceChange('mrp')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('calculation_mrp') border-red-300 @enderror">
                            @error('calculation_mrp') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="rounded-lg border border-slate-200 bg-slate-50/70 p-3 space-y-2">
                        <p class="text-xs font-semibold text-slate-800">Purchase price is</p>
                        <div class="flex flex-wrap gap-4">
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="purchase_price_tax_basis" value="exclusive" x-model="purchaseTaxBasis" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Exclusive Tax
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                                <input type="radio" name="purchase_price_tax_basis" value="inclusive" x-model="purchaseTaxBasis" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                Inclusive Tax
                            </label>
                        </div>
                        <p class="text-[11px] text-slate-500" x-show="purchaseTaxBasis === 'exclusive'">This purchase price is before tax and is used as the inventory cost.</p>
                        <p class="text-[11px] text-slate-600" x-show="purchaseTaxBasis === 'inclusive'" x-cloak>
                            This purchase price includes tax. Inventory cost excluding tax:
                            <span class="font-semibold text-indigo-700" x-text="exclusivePurchaseCost()"></span>
                        </p>
                        @error('purchase_price_tax_basis') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Alternative UOM Pricing Card -->
                <div class="rounded-xl border border-indigo-100 bg-indigo-50/20 p-5 shadow-2xs space-y-4" x-show="!altUomNotApplicable" x-cloak>
                    <div class="flex items-center justify-between border-b border-indigo-100 pb-3">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                Alternative Unit
                            </span>
                            <span class="text-sm font-bold text-slate-800 font-mono" x-text="altUomCode || 'ALT'"></span>
                        </div>
                        <button type="button"
                                @click="recalculateAltPrices(true)"
                                title="Recalculate all alternative prices as Base &times; Conversion Factor"
                                class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                            <span>Recalculate from Base</span>
                        </button>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">Purchase Price</label>
                                <span x-show="isOverridden.purchase" class="text-[10px] text-amber-600 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Overridden</span>
                            </div>
                            <input type="number"
                                   name="alt_purchase_price"
                                   x-model="altPurchasePrice"
                                   @input="onAltPriceInput('purchase')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('alt_purchase_price') border-red-300 @enderror bg-white">
                            @error('alt_purchase_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                            <p class="text-[11px] text-slate-500 mt-1">Uses the same tax basis as the base purchase price.</p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">Trade Price</label>
                                <span x-show="isOverridden.trade" class="text-[10px] text-amber-600 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Overridden</span>
                            </div>
                            <input type="number"
                                   name="alt_trade_price"
                                   x-model="altTradePrice"
                                   @input="onAltPriceInput('trade')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('alt_trade_price') border-red-300 @enderror bg-white">
                            @error('alt_trade_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">Selling Price</label>
                                <span x-show="isOverridden.selling" class="text-[10px] text-amber-600 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Overridden</span>
                            </div>
                            <input type="number"
                                   name="alt_selling_price"
                                   x-model="altSellingPrice"
                                   @input="onAltPriceInput('selling')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('alt_selling_price') border-red-300 @enderror bg-white">
                            @error('alt_selling_price') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <label class="block text-xs font-semibold text-slate-700">MRP</label>
                                <span x-show="isOverridden.mrp" class="text-[10px] text-amber-600 font-bold bg-amber-50 px-1.5 py-0.5 rounded border border-amber-200">Overridden</span>
                            </div>
                            <input type="number"
                                   name="alt_calculation_mrp"
                                   x-model="altMrp"
                                   @input="onAltPriceInput('mrp')"
                                   step="0.01"
                                   min="0"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 @error('alt_calculation_mrp') border-red-300 @enderror bg-white">
                            @error('alt_calculation_mrp') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="pt-1">
                        <p class="text-[11px] text-slate-500">Auto-derives from Base Price &times; Conversion Quantity (<span class="font-semibold text-indigo-700" x-text="conversionFactor || 1"></span>) unless edited.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="Costing and Inventory Behaviour" description="Choose how this product is costed and valued, and whether stock movements follow special behaviour.">
        <div class="space-y-5" x-data="{ provideBehaviour: @js($provideBehaviour), flags: @js($behaviourDefaults) }">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="costing_method" class="block text-xs font-semibold text-slate-700 mb-1">Costing Method <span class="text-red-500">*</span></label>
                    <select id="costing_method" name="costing_method" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        @foreach($costingMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('costing_method', $item->costing_method ?? 'fifo') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Issues and stock value follow this method. Standard cost uses the exclusive purchase price.</p>
                    @error('costing_method') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="market_valuation_method" class="block text-xs font-semibold text-slate-700 mb-1">Market Valuation Method <span class="text-red-500">*</span></label>
                    <select id="market_valuation_method" name="market_valuation_method" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                        @foreach($marketMethods as $value => $label)
                            <option value="{{ $value }}" @selected(old('market_valuation_method', $item->market_valuation_method ?? 'avg_price') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    <p class="text-[11px] text-slate-500 mt-1">Used for the market value of stock on hand. Last sale price updates when this item is sold.</p>
                    @error('market_valuation_method') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="rounded-xl border border-slate-200 p-4 space-y-3">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div>
                        <p class="text-sm font-semibold text-slate-800">Provide behaviour options</p>
                        <p class="text-xs text-slate-500">Turn this on to apply the stock rules below.</p>
                    </div>
                    <div class="flex items-center gap-4 text-sm">
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="provide_behaviour" value="1" x-model="provideBehaviour" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            Yes
                        </label>
                        <label class="inline-flex items-center gap-1.5">
                            <input type="radio" name="provide_behaviour" value="0" x-model="provideBehaviour" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            No
                        </label>
                    </div>
                </div>

                <div x-show="provideBehaviour === '1'" x-cloak class="space-y-2 pt-1">
                    @foreach($behaviourLabels as $field => $label)
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 rounded-lg border border-slate-200 bg-slate-50/60 px-3 py-2">
                            <span class="text-sm text-slate-800">{{ $label }}</span>
                            <div class="flex items-center gap-4 text-sm">
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="radio" name="{{ $field }}" value="1" x-model="flags.{{ $field }}" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    Yes
                                </label>
                                <label class="inline-flex items-center gap-1.5">
                                    <input type="radio" name="{{ $field }}" value="0" x-model="flags.{{ $field }}" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    No
                                </label>
                            </div>
                        </div>
                    @endforeach
                    <div class="text-[11px] text-slate-600 space-y-1 pt-1">
                        <p x-show="flags.treat_purchases_as_consumed === '1'">Purchases of this item are received and immediately consumed, so on-hand stock does not increase.</p>
                        <p x-show="flags.treat_sales_as_new_manufacture === '1'">Each sale manufactures the sold quantity first, so existing stock stays unchanged.</p>
                        <p x-show="flags.treat_rejections_inward_as_scrap === '1'">Goods received back are scrapped and do not increase saleable stock.</p>
                        <p x-show="flags.ignore_physical_count_diff === '1'">A physical recount does not change the book quantity.</p>
                        <p x-show="flags.ignore_negative_balances === '1'">Stock may go below zero, and that negative quantity is left out of valuation.</p>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <!-- SECTION C: ADDITIONAL PACKAGING UNITS (Optional 3rd+ Wholesale Units) -->
    <div x-data='@json(["rows" => $extraAltUoms, "uoms" => $uomOptions, "isOpen" => count($extraAltUoms) > 0])' class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between bg-slate-50/50">
            <div>
                <button type="button"
                        @click="isOpen = !isOpen"
                        class="text-left group flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400 group-hover:text-indigo-600 transition-transform duration-200" :class="{ 'rotate-90': isOpen || rows.length > 0 }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                    <div>
                        <h3 class="text-base font-semibold text-gray-900 group-hover:text-indigo-600 transition-colors">Additional Packaging Units</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Optional 3rd+ wholesale packaging units (e.g. Cartoon, Pallet)</p>
                    </div>
                    <span x-show="rows.length > 0" class="ml-2 px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold" x-text="rows.length"></span>
                </button>
            </div>
            <button type="button"
                    x-show="isOpen || rows.length > 0"
                    @click='rows.push({ uom_id:"", label:"", conversion_factor:1, selling_price:null, trade_price:null, purchase_price:null, mrp:null, is_default_sales:false })'
                    class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-colors">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                <span>Add Unit</span>
            </button>
        </div>

        <div x-show="isOpen || rows.length > 0" class="p-6 space-y-4" style="display: none;">
            <p class="text-xs text-slate-500">Base UOM and Primary Alternative UOM are configured above. Define additional packaging units with their conversion factors and prices below if needed.</p>
            <template x-for="(row, idx) in rows" :key="idx">
                <div class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end rounded-xl border border-slate-200 p-4 bg-slate-50/30">
                    <div class="md:col-span-2 relative" x-data="searchableUnitRow(uoms, row.uom_id, (val) => { row.uom_id = val; })">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Unit</label>
                        <input type="hidden" :name="`product_uoms[${idx}][uom_id]`" :value="row.uom_id">
                        <div class="relative" @click.outside="close()">
                            <input type="text"
                                   x-model="search"
                                   @focus="open()"
                                   @input="open()"
                                   @keydown.escape="close()"
                                   @keydown.enter.prevent="selectFirst()"
                                   placeholder="Select unit..."
                                   autocomplete="off"
                                   class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm placeholder:text-gray-400 focus:border-indigo-500 focus:ring-indigo-500 pr-14 bg-white">
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2 gap-1">
                                <button type="button"
                                        x-show="row.uom_id"
                                        @click="clear()"
                                        title="Clear"
                                        class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                                <button type="button"
                                        @click="isOpen ? close() : open()"
                                        tabindex="-1"
                                        class="p-1 text-slate-400 hover:text-slate-600 rounded">
                                    <svg class="w-3.5 h-3.5 transition-transform duration-200" :class="{ 'rotate-180': isOpen }" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>
                            <div x-show="isOpen"
                                 x-transition:enter="transition ease-out duration-100"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-75"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute z-50 left-0 right-0 mt-1 max-h-48 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100"
                                 style="display: none;">
                                <template x-for="u in filtered" :key="u.id">
                                    <div @click="select(u)"
                                         :class="{ 'bg-indigo-50 text-indigo-900 font-semibold': String(row.uom_id) === String(u.id) }"
                                         class="p-2 hover:bg-indigo-50 hover:text-indigo-900 cursor-pointer text-xs flex items-center justify-between transition-colors">
                                        <span class="font-medium" x-text="u.name"></span>
                                    </div>
                                </template>
                                <div x-show="filtered.length === 0" class="p-2.5 text-center text-xs text-slate-400 italic">
                                    No units found
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Label</label>
                        <input type="text" :name="`product_uoms[${idx}][label]`" x-model="row.label" placeholder="E.G. Box Of 12" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Factor</label>
                        <input type="number" step="0.0001" :name="`product_uoms[${idx}][conversion_factor]`" x-model="row.conversion_factor" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Selling</label>
                        <input type="number" step="0.01" :name="`product_uoms[${idx}][selling_price]`" x-model="row.selling_price" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Trade</label>
                        <input type="number" step="0.01" :name="`product_uoms[${idx}][trade_price]`" x-model="row.trade_price" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Purchase</label>
                        <input type="number" step="0.01" :name="`product_uoms[${idx}][purchase_price]`" x-model="row.purchase_price" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">MRP</label>
                        <input type="number" step="0.01" :name="`product_uoms[${idx}][mrp]`" x-model="row.mrp" class="block w-full rounded-lg border-gray-300 text-sm bg-white">
                    </div>
                    <div class="md:col-span-8 flex items-center justify-between pt-1">
                        <label class="flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                            <input type="hidden" :name="`product_uoms[${idx}][is_default_sales]`" value="0">
                            <input type="checkbox" :name="`product_uoms[${idx}][is_default_sales]`" value="1" x-model="row.is_default_sales" class="rounded border-gray-300 text-indigo-600">
                            Default sales unit
                        </label>
                        <button type="button" @click="rows.splice(idx, 1)" class="text-xs font-semibold text-red-600 hover:text-red-800 transition-colors">Remove unit</button>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <!-- SECTION D: INVENTORY & STOCK LEVELS -->
    <x-ui.card title="Inventory & Stock Levels" description="Define minimum stock thresholds and reorder triggers for automated inventory replenishment.">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <x-ui.input name="min_stock" label="Min Stock" type="number" step="0.0001" :value="old('min_stock', $item->min_stock ?? 0)" placeholder="0.00" />
            <x-ui.input name="reorder_level" label="Reorder Level" type="number" step="0.0001" :value="old('reorder_level', $item->reorder_level ?? 0)" placeholder="0.00" />
        </div>
    </x-ui.card>

    <!-- SECTION E: MEDIA & SPECIFICATIONS -->
    <x-ui.card title="Media & Specifications" description="Attach product catalog URL, imagery, and detailed specifications.">
        <div class="space-y-5">
            <!-- Catalog Link -->
            <x-ui.input name="catalog_link" label="Catalog Link" :value="old('catalog_link', $item->catalog_link)" placeholder="https://..." />

            <!-- Product Image -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Product Image</label>
                @if($item->image_path)
                    <div class="mb-3 flex items-center gap-4 p-3 rounded-lg border border-slate-200 bg-slate-50/50">
                        <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}" class="h-16 w-16 rounded-lg object-cover border border-slate-200 shadow-2xs">
                        <div>
                            <p class="text-xs font-semibold text-slate-700">Current Product Image</p>
                            <label class="flex items-center gap-2 text-xs text-red-600 mt-1 cursor-pointer font-medium">
                                <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600"> Remove image
                            </label>
                        </div>
                    </div>
                @endif
                <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                <p class="mt-1 text-xs text-slate-500">JPG/PNG up to 4MB. Used on quotations, invoices, and product catalogs.</p>
            </div>

            <!-- Specification -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1.5">Specification</label>
                <textarea name="specification" rows="4" placeholder="Enter product specifications, technical details, dimensions, etc..." class="block w-full rounded-lg border-gray-300 shadow-2xs text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('specification', $item->specification) }}</textarea>
            </div>

            <!-- Warranty -->
            <div x-data="warrantyPreview()" x-init="$nextTick(() => sync(document.getElementById('warranty')))">
                <x-ui.input name="warranty"
                            label="Warranty"
                            :value="old('warranty', $item->warranty)"
                            placeholder="Enter warranty period (e.g., 1 Year)"
                            help="Flexible warranty period or terms (e.g., 6 Months, 1 Year, 2 Years, No Warranty)."
                            @input="text = $event.target.value" />
                <p class="text-xs text-indigo-700" x-show="summary()" x-cloak x-text="summary()"></p>
            </div>
        </div>
    </x-ui.card>

    {{-- Legacy hidden fields — kept for backward compatibility, no longer surfaced in the form --}}
    <input type="hidden" name="description" value="{{ old('description', $item->description ?? '') }}">
    <input type="hidden" name="lifespan_days" value="{{ old('lifespan_days', $item->lifespan_days ?? '') }}">
    <input type="hidden" name="warranty_months" value="{{ old('warranty_months', $item->warranty_months ?? '') }}">
    <input type="hidden" name="warranty_terms" value="{{ old('warranty_terms', $item->warranty_terms ?? '') }}">
    <input type="hidden" name="sender_warranty_months" value="{{ old('sender_warranty_months', $item->sender_warranty_months ?? '') }}">
    <input type="hidden" name="sender_warranty_terms" value="{{ old('sender_warranty_terms', $item->sender_warranty_terms ?? '') }}">
    <input type="hidden" name="customer_warranty_months" value="{{ old('customer_warranty_months', $item->customer_warranty_months ?? '') }}">
    <input type="hidden" name="customer_warranty_terms" value="{{ old('customer_warranty_terms', $item->customer_warranty_terms ?? '') }}">
    <input type="hidden" name="discount_percent" value="{{ old('discount_percent', $item->discount_percent ?? 0) }}">
    <input type="hidden" name="aging_threshold_days" value="{{ old('aging_threshold_days', $item->aging_threshold_days ?? '') }}">
    <input type="hidden" name="credit_period_days" value="{{ old('credit_period_days', $item->credit_period_days ?? '') }}">
    <input type="hidden" name="payment_period_days" value="{{ old('payment_period_days', $item->payment_period_days ?? '') }}">

    <!-- SECTION F: STATUS & ACTIONS BAR -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 sticky bottom-4 z-20 backdrop-blur-md bg-white/95">
        <label class="flex items-center gap-2.5 text-sm font-semibold text-slate-700 cursor-pointer select-none">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4">
            <span>Active</span>
            <span class="text-xs font-normal text-slate-500">(Item is available for sales and purchase transactions)</span>
        </label>
        <div class="flex items-center gap-3">
            <x-ui.button variant="secondary" :href="route('masters.products.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">{{ $item->exists ? 'Update Product' : 'Save Product' }}</x-ui.button>
        </div>
    </div>
    @include('masters.partials.catalog-quick-modals')

    <div x-show="subCategoryCreateOpen" x-cloak class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="subcategory-create-title" >
        <div class="fixed inset-0 bg-slate-900/60" @click="subCategoryCreateOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative w-full max-w-xl rounded-xl border border-slate-200 bg-white shadow-2xl" @click.stop>
                <div class="flex items-center justify-between border-b border-slate-100 px-6 py-4">
                    <h3 id="subcategory-create-title" class="text-base font-semibold text-slate-900">Create Sub Category</h3>
                    <button type="button" @click="subCategoryCreateOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div class="space-y-1">
                        <div class="flex items-center justify-between">
                            <label for="product_subcategory_category" class="block text-sm font-medium text-gray-700">Category <span class="text-red-500">*</span></label>
                            <button type="button" @click="openCategoryCreate('subcategory')" class="text-[11px] font-semibold text-indigo-700 hover:text-indigo-900">+ Create</button>
                        </div>
                        <select id="product_subcategory_category" @change="subCategoryCreateCategoryId = $event.target.value" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select</option>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="subcategory_quick_name" class="block text-sm font-medium text-gray-700">Name <span class="text-red-500">*</span></label>
                        <input id="subcategory_quick_name" type="text" x-model="subCategoryCreateName" @keydown.enter.prevent="saveSubCategory()" maxlength="255" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" x-model="subCategoryCreateActive" class="rounded border-gray-300 text-indigo-600">
                        Active
                    </label>
                    <p class="text-xs text-red-600" x-show="subCategoryCreateError" x-text="subCategoryCreateError"></p>
                </div>
                <div class="px-6 pb-5">
                    <button type="button" @click="saveSubCategory()" :disabled="subCategoryCreateSaving" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500 disabled:opacity-50">Save</button>
                </div>
            </div>
        </div>
    </div>

    <div x-show="productTypeCreateOpen" x-cloak class="fixed inset-0 z-[70] overflow-y-auto" role="dialog" aria-modal="true" aria-labelledby="product-type-create-title" >
        <div class="fixed inset-0 bg-slate-900/60" @click="productTypeCreateOpen = false"></div>
        <div class="relative flex min-h-full items-center justify-center p-4">
            <div class="relative flex max-h-[85vh] w-full max-w-[700px] flex-col overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-2xl" @click.stop>
                <div class="flex shrink-0 items-center justify-between border-b border-slate-100 bg-slate-50/80 px-6 py-4">
                    <div>
                        <h3 id="product-type-create-title" class="text-sm font-bold text-slate-900 sm:text-base">Add New Product Type</h3>
                        <p class="text-xs text-slate-500">Provide product classification details below.</p>
                    </div>
                    <button type="button" @click="productTypeCreateOpen = false" class="rounded-lg p-1.5 text-slate-400 hover:bg-slate-100 hover:text-slate-600" title="Close">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="flex-1 space-y-4 overflow-y-auto px-6 py-5">
                    <div>
                        <label for="product_type_quick_name" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Product Type Name <span class="text-red-500">*</span></label>
                        <input id="product_type_quick_name" type="text" x-model="productTypeCreateName" @blur="applyProductTypeTitleCase()" @keydown.enter.prevent="saveProductType()" maxlength="100" placeholder="e.g. Finished Goods, Spare Part, Hardware" class="block w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm shadow-2xs placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-1 text-[11px] text-slate-500">Names are automatically formatted to Title Case (e.g. Finished Goods, Spare Part).</p>
                    </div>
                    <div>
                        <label for="product_type_quick_description" class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-slate-700">Description <span class="text-xs font-normal lowercase text-slate-400">(optional)</span></label>
                        <textarea id="product_type_quick_description" x-model="productTypeCreateDescription" rows="3" maxlength="1000" placeholder="Enter short description or purpose for this product type..." class="block w-full rounded-xl border-slate-200 px-3.5 py-2.5 text-sm shadow-2xs placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3">
                        <input type="checkbox" x-model="productTypeCreateTrackSerial" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        <span>
                            <span class="block text-sm font-semibold text-slate-800">Track serial numbers</span>
                            <span class="block text-xs text-slate-500">Purchase invoices ask for a serial number on every product of this type.</span>
                        </span>
                    </label>
                    <div class="border-t border-slate-100 pt-2">
                        <label class="inline-flex cursor-pointer select-none items-center gap-3">
                            <input type="checkbox" x-model="productTypeCreateActive" class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                            <span>
                                <span class="text-sm font-semibold text-slate-800">Active</span>
                                <span class="block text-xs text-slate-500">Active types can be assigned to new and existing products.</span>
                            </span>
                        </label>
                    </div>
                    <p class="text-xs font-medium text-red-600" x-show="productTypeCreateError" x-text="productTypeCreateError"></p>
                </div>
                <div class="flex shrink-0 items-center justify-end gap-3 border-t border-slate-100 bg-slate-50/80 px-6 py-4">
                    <button type="button" @click="productTypeCreateOpen = false" :disabled="productTypeCreateSaving" class="rounded-xl border border-slate-200 px-4 py-2.5 text-xs font-semibold text-slate-700 hover:bg-slate-100">Cancel</button>
                    <button type="button" @click="saveProductType()" :disabled="productTypeCreateSaving" class="rounded-xl bg-indigo-600 px-5 py-2.5 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500 disabled:opacity-50" x-text="productTypeCreateSaving ? 'Saving...' : 'Create Product Type'"></button>
                </div>
            </div>
        </div>
    </div>
</form>

@if(isset($priceHistory) && $priceHistory->count())
    <div class="max-w-5xl mx-auto mt-6">
        <x-ui.card title="Price change history">
            <x-ui.table>
                <x-slot name="head">
                    <tr>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Effective From</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Effective To</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">UOM</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">MRP</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Purchase</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Trade</th>
                        <th class="px-4 py-2 text-right text-xs font-semibold text-gray-500">Selling</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">Source</th>
                        <th class="px-4 py-2 text-left text-xs font-semibold text-gray-500">By</th>
                    </tr>
                </x-slot>
                @foreach($priceHistory as $h)
                    <tr>
                        <td class="px-4 py-2 text-sm">{{ optional($h->effective_from)->format('d M Y') }}</td>
                        <td class="px-4 py-2 text-sm">{{ optional($h->effective_to)->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm">{{ $h->uom?->code ?? '—' }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ number_format((float) $h->mrp, 2) }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ number_format((float) $h->purchase_price, 2) }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ number_format((float) $h->trade_price, 2) }}</td>
                        <td class="px-4 py-2 text-sm text-right">{{ number_format((float) $h->selling_price, 2) }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $h->source ?? '—' }}</td>
                        <td class="px-4 py-2 text-xs text-slate-500">{{ $h->creator?->name ?? 'system' }}</td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>
    </div>
@endif
@endsection

@push('scripts')
<script>
function warrantyPreview() {
    return {
        text: '',
        sync(el) {
            this.text = el ? el.value : '';
        },
        parsedMonths() {
            const text = (this.text || '').trim();
            if (!text || /\bno\s+warranty\b/i.test(text)) return null;
            let months = 0;
            let matched = false;
            let match = text.match(/(\d+(?:\.\d+)?)\s*(?:years?|yrs?)\b/i);
            if (match) { months += Math.round(parseFloat(match[1]) * 12); matched = true; }
            match = text.match(/(\d+(?:\.\d+)?)\s*(?:months?|mons?)\b/i);
            if (match) { months += Math.round(parseFloat(match[1])); matched = true; }
            match = text.match(/(\d+(?:\.\d+)?)\s*days?\b/i);
            if (match) { months += Math.max(1, Math.round(parseFloat(match[1]) / 30)); matched = true; }
            return matched && months > 0 ? months : null;
        },
        summary() {
            const text = (this.text || '').trim();
            if (!text) return '';
            if (/\bno\s+warranty\b/i.test(text)) return 'No warranty period.';
            const months = this.parsedMonths();
            if (!months) return 'Enter a period such as 1 year, 6 months, or 3 years.';
            const years = Math.floor(months / 12);
            const remainder = months % 12;
            let length = months + (months === 1 ? ' month' : ' months');
            if (years && remainder) {
                length = years + (years === 1 ? ' year ' : ' years ') + remainder + (remainder === 1 ? ' month' : ' months') + ' (' + months + ' months)';
            } else if (years) {
                length = years + (years === 1 ? ' year' : ' years') + ' (' + months + ' months)';
            }
            const expiry = new Date();
            expiry.setMonth(expiry.getMonth() + months);
            const dd = String(expiry.getDate()).padStart(2, '0');
            const mm = String(expiry.getMonth() + 1).padStart(2, '0');
            return length + '. Sample expiry from today: ' + dd + '-' + mm + '-' + expiry.getFullYear() + '.';
        },
    };
}
function searchableUnitRow(uoms, initialId, onChange) {
    return {
        uoms: uoms || [],
        selectedId: initialId || '',
        isOpen: false,
        search: '',
        init() {
            if (this.selectedId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedId));
                if (found) this.search = found.name;
            }
        },
        get filtered() {
            if (!this.search || !this.search.trim()) return this.uoms;
            const q = this.search.toLowerCase().trim();
            return this.uoms.filter(u => u.name && u.name.toLowerCase().includes(q));
        },
        open() {
            this.isOpen = true;
        },
        close() {
            this.isOpen = false;
            if (this.selectedId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedId));
                this.search = found ? found.name : '';
            } else {
                this.search = '';
            }
        },
        select(u) {
            this.selectedId = u.id;
            this.search = u.name;
            this.isOpen = false;
            onChange(u.id);
        },
        clear() {
            this.selectedId = '';
            this.search = '';
            this.isOpen = false;
            onChange('');
        },
        selectFirst() {
            if (this.filtered.length > 0) {
                this.select(this.filtered[0]);
            }
        }
    };
}

function productTrackingManager(selected) {
    return {
        options: [
            { value: 'batch', label: 'Batch-wise' }
        ],
        selected: selected || [],
        searchQuery: '',
        get filteredOptions() {
            if (!this.searchQuery || !this.searchQuery.trim()) {
                return this.options;
            }
            const q = this.searchQuery.toLowerCase().trim();
            return this.options.filter(function (opt) {
                return opt.label.toLowerCase().includes(q) || opt.value.toLowerCase().includes(q);
            });
        },
        get allSelected() {
            const visible = this.filteredOptions;
            return visible.length > 0 && visible.every(function (opt) { return this.selected.includes(opt.value); }, this);
        },
        get isIndeterminate() {
            const visible = this.filteredOptions;
            const count = visible.filter(function (opt) { return this.selected.includes(opt.value); }, this).length;
            return count > 0 && count < visible.length;
        },
        toggleSelectAll() {
            const visibleValues = this.filteredOptions.map(function (o) { return o.value; });
            if (this.allSelected) {
                this.selected = this.selected.filter(function (val) { return visibleValues.indexOf(val) === -1; });
            } else {
                this.selected = Array.from(new Set(this.selected.concat(visibleValues)));
            }
        },
        toggleOption(val) {
            if (this.selected.includes(val)) {
                this.selected = this.selected.filter(function (v) { return v !== val; });
            } else {
                this.selected = this.selected.concat([val]);
            }
        }
    };
}
function productCatalogManager(config) {
    return {
        uoms: config.uoms || [],
        brands: config.brands || [],
        categories: config.categories || [],
        subCategories: config.subCategories || [],

        // Selection IDs & values
        selectedBaseUomId: config.initialBaseUomId || '',
        selectedAltUomId: config.initialAltUomId || '',
        altUomNotApplicable: !!config.initialAltNotApplicable,
        categoryQuickUrl: config.categoryQuickUrl,
        subCategoryQuickUrl: config.subCategoryQuickUrl,
        productTypeQuickUrl: config.productTypeQuickUrl,
        brandQuickUrl: config.brandQuickUrl,
        companies: config.companies || [],
        brandCreateOpen: false,
        brandCreateContext: 'product',
        brandCreateName: '',
        brandCreateDetail: '',
        brandCreateCompanyId: '',
        brandCreateActive: true,
        brandCreateError: '',
        brandCreateSaving: false,
        categoryCreateContext: 'product',
        categoryCreateOpen: false,
        categoryCreateBrandId: '',
        categoryCreateName: '',
        categoryCreateActive: true,
        categoryCreateError: '',
        categoryCreateSaving: false,
        subCategoryCreateOpen: false,
        subCategoryCreateCategoryId: '',
        subCategoryCreateName: '',
        subCategoryCreateActive: true,
        subCategoryCreateError: '',
        subCategoryCreateSaving: false,
        productTypeCreateOpen: false,
        productTypeCreateName: '',
        productTypeCreateDescription: '',
        productTypeCreateTrackSerial: false,
        productTypeCreateActive: true,
        productTypeCreateError: '',
        productTypeCreateSaving: false,
        conversionFactor: config.initialConversionFactor || 1,
        selectedBrandId: config.initialBrandId || '',
        selectedCategoryId: config.initialCategoryId || '',
        selectedSubCategoryId: config.initialSubCategoryId || '',

        // Prices - Base UOM
        purchasePrice: config.initialPurchasePrice || '',
        tradePrice: config.initialTradePrice || '',
        sellingPrice: config.initialSellingPrice || '',
        mrp: config.initialMrp || '',
        taxRate: config.initialTaxRate || '',
        purchaseTaxBasis: config.initialPurchaseTaxBasis || 'exclusive',

        // Prices - Alternative UOM
        altPurchasePrice: config.initialAltPurchasePrice || '',
        altTradePrice: config.initialAltTradePrice || '',
        altSellingPrice: config.initialAltSellingPrice || '',
        altMrp: config.initialAltMrp || '',

        // Manual override flags
        isOverridden: {
            purchase: false,
            trade: false,
            selling: false,
            mrp: false
        },

        // Display search strings
        uomSearchQuery: '',
        altUomSearchQuery: '',
        brandSearchQuery: '',
        categorySearchQuery: '',
        subCategorySearchQuery: '',

        // Dropdown open states
        uomIsOpen: false,
        altUomIsOpen: false,
        brandIsOpen: false,
        categoryIsOpen: false,
        subCategoryIsOpen: false,

        exclusivePurchaseCost() {
            const price = parseFloat(this.purchasePrice);
            if (isNaN(price) || price < 0) {
                return '0.00';
            }
            if (this.purchaseTaxBasis !== 'inclusive') {
                return price.toFixed(2);
            }
            const rate = parseFloat(this.taxRate);
            if (isNaN(rate) || rate <= 0) {
                return price.toFixed(2);
            }
            return (price / (1 + rate / 100)).toFixed(2);
        },

        init() {
            // Preload labels for initial selections
            if (this.selectedBaseUomId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedBaseUomId));
                if (found) this.uomSearchQuery = found.name;
            }
            if (this.altUomNotApplicable) {
                this.selectedAltUomId = '';
                this.altUomSearchQuery = 'Not Applicable';
            } else if (this.selectedAltUomId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedAltUomId));
                if (found) this.altUomSearchQuery = found.name;
            }
            if (this.selectedBrandId) {
                const found = this.brands.find(b => String(b.id) === String(this.selectedBrandId));
                if (found) this.brandSearchQuery = found.name;
            }
            if (this.selectedCategoryId) {
                const found = this.categories.find(c => String(c.id) === String(this.selectedCategoryId));
                if (found) this.categorySearchQuery = found.name;
            }
            if (this.selectedSubCategoryId) {
                const found = this.subCategories.find(s => String(s.id) === String(this.selectedSubCategoryId));
                if (found) this.subCategorySearchQuery = found.name;
            }

            // Detect pre-existing manual overrides or auto-fill if alt prices were empty
            ['purchase', 'trade', 'selling', 'mrp'].forEach(field => {
                const baseVal = this.getBasePrice(field);
                const altVal = this.getAltPrice(field);
                if (altVal !== '' && altVal !== null && altVal !== undefined) {
                    const expected = this.calcAltPrice(baseVal);
                    if (expected !== '' && parseFloat(altVal) !== parseFloat(expected)) {
                        this.isOverridden[field] = true;
                    }
                } else if (baseVal !== '' && baseVal !== null && baseVal !== undefined) {
                    this.setAltPrice(field, this.calcAltPrice(baseVal));
                }
            });
        },

        // --- UNIT CODES & LABELS ---
        get baseUomCode() {
            const u = this.uoms.find(item => String(item.id) === String(this.selectedBaseUomId));
            return u ? (u.code || u.name) : '';
        },
        get altUomCode() {
            const u = this.uoms.find(item => String(item.id) === String(this.selectedAltUomId));
            return u ? (u.code || u.name) : '';
        },

        // --- PRICING HELPERS & CALCULATIONS ---
        getBasePrice(field) {
            if (field === 'purchase') return this.purchasePrice;
            if (field === 'trade') return this.tradePrice;
            if (field === 'selling') return this.sellingPrice;
            if (field === 'mrp') return this.mrp;
            return '';
        },
        getAltPrice(field) {
            if (field === 'purchase') return this.altPurchasePrice;
            if (field === 'trade') return this.altTradePrice;
            if (field === 'selling') return this.altSellingPrice;
            if (field === 'mrp') return this.altMrp;
            return '';
        },
        setAltPrice(field, val) {
            if (field === 'purchase') this.altPurchasePrice = val;
            if (field === 'trade') this.altTradePrice = val;
            if (field === 'selling') this.altSellingPrice = val;
            if (field === 'mrp') this.altMrp = val;
        },
        calcAltPrice(baseVal) {
            const base = parseFloat(baseVal);
            const factor = parseFloat(this.conversionFactor);
            if (!isNaN(base) && !isNaN(factor) && factor > 0) {
                return (Math.round(base * factor * 100) / 100).toFixed(2);
            }
            return '';
        },
        onBasePriceChange(field) {
            if (!this.isOverridden[field]) {
                const baseVal = this.getBasePrice(field);
                this.setAltPrice(field, this.calcAltPrice(baseVal));
            }
        },
        onConversionFactorChange() {
            ['purchase', 'trade', 'selling', 'mrp'].forEach(field => {
                if (!this.isOverridden[field]) {
                    const baseVal = this.getBasePrice(field);
                    this.setAltPrice(field, this.calcAltPrice(baseVal));
                }
            });
        },
        onAltPriceInput(field) {
            this.isOverridden[field] = true;
        },
        recalculateAltPrices(resetOverrides = false) {
            if (resetOverrides) {
                this.isOverridden = { purchase: false, trade: false, selling: false, mrp: false };
            }
            ['purchase', 'trade', 'selling', 'mrp'].forEach(field => {
                const baseVal = this.getBasePrice(field);
                this.setAltPrice(field, this.calcAltPrice(baseVal));
            });
        },

        // --- BASE UOM ---
        get filteredUoms() {
            if (!this.uomSearchQuery || !this.uomSearchQuery.trim()) {
                return this.uoms;
            }
            const q = this.uomSearchQuery.toLowerCase().trim();
            return this.uoms.filter(u => {
                return (u.name && u.name.toLowerCase().includes(q)) ||
                       (u.code && u.code.toLowerCase().includes(q));
            });
        },
        openUomDropdown() {
            this.uomIsOpen = true;
        },
        closeUomDropdown() {
            this.uomIsOpen = false;
            if (this.selectedBaseUomId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedBaseUomId));
                this.uomSearchQuery = found ? found.name : '';
            } else {
                this.uomSearchQuery = '';
            }
        },
        selectUom(u) {
            this.selectedBaseUomId = String(u.id);
            this.uomSearchQuery = u.name;
            this.uomIsOpen = false;
            // Prevent duplicate selection: if Alt UOM is the same, clear Alt UOM
            if (String(this.selectedAltUomId) === String(u.id)) {
                this.clearAltUom();
            }
        },
        clearUom() {
            this.selectedBaseUomId = '';
            this.uomSearchQuery = '';
            this.uomIsOpen = false;
        },

        // --- ALTERNATIVE UOM ---
        get availableAltUoms() {
            return this.uoms.filter(u => String(u.id) !== String(this.selectedBaseUomId));
        },
        get filteredAltUoms() {
            const list = this.availableAltUoms;
            if (!this.altUomSearchQuery || !this.altUomSearchQuery.trim()) {
                return list;
            }
            const q = this.altUomSearchQuery.toLowerCase().trim();
            return list.filter(u => {
                return (u.name && u.name.toLowerCase().includes(q)) ||
                       (u.code && u.code.toLowerCase().includes(q));
            });
        },
        openAltUomDropdown() {
            if (this.altUomNotApplicable || this.altUomSearchQuery === 'Not Applicable') {
                this.altUomSearchQuery = '';
            }
            this.altUomIsOpen = true;
        },
        closeAltUomDropdown() {
            this.altUomIsOpen = false;
            if (this.altUomNotApplicable) {
                this.altUomSearchQuery = 'Not Applicable';
                return;
            }
            if (this.selectedAltUomId) {
                const found = this.uoms.find(u => String(u.id) === String(this.selectedAltUomId));
                this.altUomSearchQuery = found ? found.name : '';
            } else {
                this.altUomSearchQuery = '';
            }
        },
        selectAltUomNotApplicable() {
            this.altUomNotApplicable = true;
            this.selectedAltUomId = '';
            this.altUomSearchQuery = 'Not Applicable';
            this.conversionFactor = '';
            this.altUomIsOpen = false;
        },
        selectAltUom(u) {
            this.altUomNotApplicable = false;
            this.selectedAltUomId = String(u.id);
            this.altUomSearchQuery = u.name;
            this.altUomIsOpen = false;
            if (!this.conversionFactor) {
                this.conversionFactor = 1;
            }
        },
        clearAltUom() {
            this.altUomNotApplicable = false;
            this.selectedAltUomId = '';
            this.altUomSearchQuery = '';
            this.altUomIsOpen = false;
        },

        // --- BRAND ---
        get filteredBrands() {
            if (!this.brandSearchQuery || !this.brandSearchQuery.trim()) {
                return this.brands;
            }
            const q = this.brandSearchQuery.toLowerCase().trim();
            return this.brands.filter(b => {
                return (b.name && b.name.toLowerCase().includes(q)) ||
                       (b.code && b.code.toLowerCase().includes(q));
            });
        },
        openBrandDropdown() {
            this.brandIsOpen = true;
        },
        closeBrandDropdown() {
            this.brandIsOpen = false;
            if (this.selectedBrandId) {
                const found = this.brands.find(b => String(b.id) === String(this.selectedBrandId));
                this.brandSearchQuery = found ? found.name : '';
            } else {
                this.brandSearchQuery = '';
            }
        },
        selectBrand(b) {
            this.selectedBrandId = String(b.id);
            this.brandSearchQuery = b.name;
            this.brandIsOpen = false;

            // Dependent check: if selected Category does not belong to this new Brand, reset Category & SubCategory
            if (this.selectedCategoryId) {
                const currentCat = this.categories.find(c => String(c.id) === String(this.selectedCategoryId));
                if (currentCat && currentCat.brand_id && String(currentCat.brand_id) !== String(b.id)) {
                    this.clearCategory(true);
                }
            }
        },
        clearBrand() {
            this.selectedBrandId = '';
            this.brandSearchQuery = '';
            this.brandIsOpen = false;
        },

        // --- CATEGORY ---
        get availableCategories() {
            if (!this.selectedBrandId) {
                return this.categories;
            }
            return this.categories.filter(c => !c.brand_id || String(c.brand_id) === String(this.selectedBrandId));
        },
        get filteredCategories() {
            const list = this.availableCategories;
            if (!this.categorySearchQuery || !this.categorySearchQuery.trim()) {
                return list;
            }
            const q = this.categorySearchQuery.toLowerCase().trim();
            return list.filter(c => {
                return (c.name && c.name.toLowerCase().includes(q)) ||
                       (c.code && c.code.toLowerCase().includes(q));
            });
        },
        openCategoryDropdown() {
            this.categoryIsOpen = true;
        },
        closeCategoryDropdown() {
            this.categoryIsOpen = false;
            if (this.selectedCategoryId) {
                const found = this.categories.find(c => String(c.id) === String(this.selectedCategoryId));
                this.categorySearchQuery = found ? found.name : '';
            } else {
                this.categorySearchQuery = '';
            }
        },
        selectCategory(c) {
            this.selectedCategoryId = String(c.id);
            this.categorySearchQuery = c.name;
            this.categoryIsOpen = false;

            // Auto-select parent Brand if category has brand_id and Brand not yet set
            if (!this.selectedBrandId && c.brand_id) {
                const parentBrand = this.brands.find(b => String(b.id) === String(c.brand_id));
                if (parentBrand) {
                    this.selectedBrandId = String(parentBrand.id);
                    this.brandSearchQuery = parentBrand.name;
                }
            }

            // Dependent check: if selected SubCategory does not belong to this new Category, reset SubCategory
            if (this.selectedSubCategoryId) {
                const currentSub = this.subCategories.find(s => String(s.id) === String(this.selectedSubCategoryId));
                if (currentSub && currentSub.category_id && String(currentSub.category_id) !== String(c.id)) {
                    this.clearSubCategory();
                }
            }
        },
        clearCategory(clearSub = true) {
            this.selectedCategoryId = '';
            this.categorySearchQuery = '';
            this.categoryIsOpen = false;
            if (clearSub) {
                this.clearSubCategory();
            }
        },

        // --- SUB CATEGORY ---
        get availableSubCategories() {
            if (!this.selectedCategoryId) {
                return this.subCategories;
            }
            return this.subCategories.filter(sc => !sc.category_id || String(sc.category_id) === String(this.selectedCategoryId));
        },
        get filteredSubCategories() {
            const list = this.availableSubCategories;
            if (!this.subCategorySearchQuery || !this.subCategorySearchQuery.trim()) {
                return list;
            }
            const q = this.subCategorySearchQuery.toLowerCase().trim();
            return list.filter(sc => {
                return (sc.name && sc.name.toLowerCase().includes(q)) ||
                       (sc.code && sc.code.toLowerCase().includes(q));
            });
        },
        openSubCategoryDropdown() {
            this.subCategoryIsOpen = true;
        },
        closeSubCategoryDropdown() {
            this.subCategoryIsOpen = false;
            if (this.selectedSubCategoryId) {
                const found = this.subCategories.find(sc => String(sc.id) === String(this.selectedSubCategoryId));
                this.subCategorySearchQuery = found ? found.name : '';
            } else {
                this.subCategorySearchQuery = '';
            }
        },
        selectSubCategory(sc) {
            this.selectedSubCategoryId = String(sc.id);
            this.subCategorySearchQuery = sc.name;
            this.subCategoryIsOpen = false;

            // Auto-select parent Category if not yet set
            if (!this.selectedCategoryId && sc.category_id) {
                const parentCat = this.categories.find(c => String(c.id) === String(sc.category_id));
                if (parentCat) {
                    this.selectCategory(parentCat);
                }
            }
        },
        clearSubCategory() {
            this.selectedSubCategoryId = '';
            this.subCategorySearchQuery = '';
            this.subCategoryIsOpen = false;
        },
        async postJson(url, payload) {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });
            const data = await response.json().catch(() => ({}));
            return { ok: response.ok, data };
        },
        closeTopCreate() {
            if (this.brandCreateOpen) { this.brandCreateOpen = false; return; }
            if (this.categoryCreateOpen) { this.categoryCreateOpen = false; return; }
            if (this.productTypeCreateOpen) { this.productTypeCreateOpen = false; return; }
            if (this.subCategoryCreateOpen) { this.subCategoryCreateOpen = false; }
        },
        fillSelect(selectId, items, selected) {
            const select = document.getElementById(selectId);
            if (!select) return;
            select.replaceChildren();
            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select';
            select.appendChild(placeholder);
            (items || []).forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                select.appendChild(option);
            });
            select.value = selected ? String(selected) : '';
            select.dispatchEvent(new Event('change', { bubbles: true }));
        },
        openBrandCreate(context) {
            this.brandCreateContext = context || 'product';
            this.brandCreateOpen = true;
            this.brandCreateError = '';
            this.brandCreateName = '';
            this.brandCreateDetail = '';
            this.brandCreateActive = true;
            this.brandCreateCompanyId = this.companies[0] ? String(this.companies[0].id) : '';
            this.brandIsOpen = false;
            this.fillSelect('catalog_brand_company', this.companies, this.brandCreateCompanyId);
        },
        async saveBrand() {
            const name = (this.brandCreateName || '').trim();
            if (!name) {
                this.brandCreateError = 'Brand Name is required.';
                return;
            }
            this.brandCreateSaving = true;
            this.brandCreateError = '';
            const result = await this.postJson(this.brandQuickUrl, {
                name,
                detail: this.brandCreateDetail || '',
                company_id: this.brandCreateCompanyId || null,
                is_active: !!this.brandCreateActive,
            });
            this.brandCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.brandCreateError = result.data.message || 'Could not create the brand.';
                return;
            }
            const item = result.data.item;
            if (!this.brands.some(b => String(b.id) === String(item.id))) {
                this.brands.push(item);
            }
            if (this.brandCreateContext === 'product') {
                this.selectBrand(item);
            }
            this.categoryCreateBrandId = String(item.id);
            this.fillSelect('catalog_category_brand', this.brands, this.categoryCreateBrandId);
            this.brandCreateName = '';
            this.brandCreateDetail = '';
            this.brandCreateOpen = false;
        },
        openCategoryCreate(context) {
            this.categoryCreateContext = context || 'product';
            this.categoryCreateOpen = true;
            this.categoryCreateError = '';
            this.categoryCreateBrandId = this.selectedBrandId || '';
            this.categoryCreateName = '';
            this.categoryCreateActive = true;
            if (this.categoryCreateContext !== 'subcategory') {
                this.categoryIsOpen = false;
            }
            this.fillSelect('catalog_category_brand', this.brands, this.categoryCreateBrandId);
        },
        async saveCategory() {
            const name = (this.categoryCreateName || '').trim();
            if (!name) {
                this.categoryCreateError = 'Name is required.';
                return;
            }
            this.categoryCreateSaving = true;
            this.categoryCreateError = '';
            const result = await this.postJson(this.categoryQuickUrl, {
                name,
                brand_id: this.categoryCreateBrandId || null,
                is_active: !!this.categoryCreateActive,
            });
            this.categoryCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.categoryCreateError = result.data.message || 'Could not create the category.';
                return;
            }
            const item = result.data.item;
            if (!this.categories.some(c => String(c.id) === String(item.id))) {
                this.categories.push(item);
            }
            if (item.brand_id) {
                const brand = this.brands.find(b => String(b.id) === String(item.brand_id));
                if (brand) {
                    this.selectedBrandId = String(brand.id);
                    this.brandSearchQuery = brand.name;
                }
            }
            this.selectCategory(item);
            this.subCategoryCreateCategoryId = String(item.id);
            if (this.subCategoryCreateOpen) {
                this.fillSelect('product_subcategory_category', this.categories, item.id);
            }
            this.categoryCreateName = '';
            this.categoryCreateOpen = false;
        },
        openSubCategoryCreate() {
            this.subCategoryCreateOpen = true;
            this.subCategoryCreateError = '';
            this.subCategoryCreateCategoryId = this.selectedCategoryId || '';
            this.subCategoryCreateName = '';
            this.subCategoryCreateActive = true;
            this.subCategoryIsOpen = false;
            this.fillSelect('product_subcategory_category', this.categories, this.subCategoryCreateCategoryId);
        },
        async saveSubCategory() {
            const name = (this.subCategoryCreateName || '').trim();
            if (!this.subCategoryCreateCategoryId) {
                this.subCategoryCreateError = 'Category is required.';
                return;
            }
            if (!name) {
                this.subCategoryCreateError = 'Name is required.';
                return;
            }
            this.subCategoryCreateSaving = true;
            this.subCategoryCreateError = '';
            const result = await this.postJson(this.subCategoryQuickUrl, {
                name,
                category_id: this.subCategoryCreateCategoryId,
                is_active: !!this.subCategoryCreateActive,
            });
            this.subCategoryCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.subCategoryCreateError = result.data.message || 'Could not create the sub category.';
                return;
            }
            const item = result.data.item;
            if (!this.subCategories.some(sc => String(sc.id) === String(item.id))) {
                this.subCategories.push(item);
            }
            const parent = this.categories.find(c => String(c.id) === String(item.category_id));
            if (parent) {
                this.selectCategory(parent);
            }
            this.selectSubCategory(item);
            this.subCategoryCreateName = '';
            this.subCategoryCreateOpen = false;
        },
        openProductTypeCreate() {
            this.productTypeCreateOpen = true;
            this.productTypeCreateError = '';
            this.productTypeCreateName = '';
            this.productTypeCreateDescription = '';
            this.productTypeCreateTrackSerial = false;
            this.productTypeCreateActive = true;
        },
        applyProductTypeTitleCase() {
            const value = this.productTypeCreateName || '';
            this.productTypeCreateName = value.replace(/\b\w+/g, function (word) {
                return word.charAt(0).toUpperCase() + word.substr(1).toLowerCase();
            });
        },
        async saveProductType() {
            const name = (this.productTypeCreateName || '').trim();
            if (!name) {
                this.productTypeCreateError = 'Product Type Name is required.';
                return;
            }
            if (name.length > 100) {
                this.productTypeCreateError = 'Product Type Name cannot exceed 100 characters.';
                return;
            }
            this.productTypeCreateSaving = true;
            this.productTypeCreateError = '';
            const result = await this.postJson(this.productTypeQuickUrl, {
                name,
                description: this.productTypeCreateDescription || '',
                track_serial: !!this.productTypeCreateTrackSerial,
                is_active: !!this.productTypeCreateActive,
            });
            this.productTypeCreateSaving = false;
            if (!result.ok || !result.data.success) {
                this.productTypeCreateError = result.data.message || 'Could not create the product type.';
                return;
            }
            const item = result.data.item;
            const select = document.getElementById('product_type_id');
            if (select && !Array.from(select.options).some(opt => String(opt.value) === String(item.id))) {
                const option = document.createElement('option');
                option.value = item.id;
                option.textContent = item.name + (this.productTypeCreateTrackSerial ? ' — serial numbers' : '');
                select.appendChild(option);
            }
            if (select) {
                select.value = String(item.id);
            }
            this.productTypeCreateName = '';
            this.productTypeCreateDescription = '';
            this.productTypeCreateOpen = false;
        }
    };
}

function productDimensionsManager(config) {
    return {
        heightCm: config.initialHeightCm || '',
        heightIn: config.initialHeightIn || '',
        lengthCm: config.initialLengthCm || '',
        lengthIn: config.initialLengthIn || '',
        widthCm: config.initialWidthCm || '',
        widthIn: config.initialWidthIn || '',
        netWeight: config.initialNetWeight || '',
        weightUnit: config.initialWeightUnit || 'KG',
        notApplicable: !!config.initialNotApplicable,

        onNotApplicableChange() {
            if (this.notApplicable) {
                this.heightCm = '';
                this.heightIn = '';
                this.lengthCm = '';
                this.lengthIn = '';
                this.widthCm = '';
                this.widthIn = '';
                this.netWeight = '';
            }
        },

        init() {
            // Auto-compute inches if CM exists but inches is empty
            if (this.heightCm && !this.heightIn) {
                this.heightIn = this.cmToIn(this.heightCm);
            }
            if (this.lengthCm && !this.lengthIn) {
                this.lengthIn = this.cmToIn(this.lengthCm);
            }
            if (this.widthCm && !this.widthIn) {
                this.widthIn = this.cmToIn(this.widthCm);
            }

            // Auto-compute CM if inches exists but CM is empty
            if (this.heightIn && !this.heightCm) {
                this.heightCm = this.inToCm(this.heightIn);
            }
            if (this.lengthIn && !this.lengthCm) {
                this.lengthCm = this.inToCm(this.lengthIn);
            }
            if (this.widthIn && !this.widthCm) {
                this.widthCm = this.inToCm(this.widthIn);
            }
        },

        cmToIn(cm) {
            const val = parseFloat(cm);
            if (isNaN(val) || val <= 0) return '';
            return (Math.round((val / 2.54) * 100) / 100).toFixed(2);
        },

        inToCm(inches) {
            const val = parseFloat(inches);
            if (isNaN(val) || val <= 0) return '';
            return (Math.round((val * 2.54) * 100) / 100).toFixed(2);
        },

        onCmInput(dim) {
            if (dim === 'height') {
                this.heightIn = this.cmToIn(this.heightCm);
            } else if (dim === 'length') {
                this.lengthIn = this.cmToIn(this.lengthCm);
            } else if (dim === 'width') {
                this.widthIn = this.cmToIn(this.widthCm);
            }
        },

        onInchesInput(dim) {
            if (dim === 'height') {
                this.heightCm = this.inToCm(this.heightIn);
            } else if (dim === 'length') {
                this.lengthCm = this.inToCm(this.lengthIn);
            } else if (dim === 'width') {
                this.widthCm = this.inToCm(this.widthIn);
            }
        }
    };
}
</script>
@endpush