@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Price' : 'Add Price')
@section('content')
@php
    $isEdit = $item->exists;
    $productOptions = $products->map(function ($p) {
        $mrp = 0.0;
        if ((float) $p->calculation_mrp > 0) {
            $mrp = (float) $p->calculation_mrp;
        } elseif ($p->relationLoaded('productUoms') && ($baseUomPu = $p->productUoms->firstWhere('is_base', true)) && (float) $baseUomPu->mrp > 0) {
            $mrp = (float) $baseUomPu->mrp;
        } elseif ((float) ($p->selling_price ?? 0) > 0) {
            $mrp = (float) $p->selling_price;
        }

        $productTypeName = $p->productType?->name ?: ($p->product_type ?: null);

        $validUoms = collect();
        if ($p->baseUom) {
            $validUoms->push([
                'id' => (string) $p->baseUom->id,
                'code' => $p->baseUom->code,
                'name' => $p->baseUom->name,
                'is_base' => true,
                'label' => 'Base UOM',
            ]);
        }
        if ($p->alternateUom && (int) $p->alternate_uom_id !== (int) $p->base_uom_id) {
            $validUoms->push([
                'id' => (string) $p->alternateUom->id,
                'code' => $p->alternateUom->code,
                'name' => $p->alternateUom->name,
                'is_base' => false,
                'label' => 'Alternate UOM',
            ]);
        }
        if ($p->relationLoaded('productUoms')) {
            foreach ($p->productUoms as $pu) {
                if ($pu->uom && ! $validUoms->contains('id', (string) $pu->uom->id)) {
                    $validUoms->push([
                        'id' => (string) $pu->uom->id,
                        'code' => $pu->uom->code,
                        'name' => $pu->uom->name,
                        'is_base' => (bool) $pu->is_base,
                        'label' => $pu->label ?: ($pu->is_base ? 'Base UOM' : 'Unit'),
                    ]);
                }
            }
        }

        return [
            'id' => $p->id,
            'name' => $p->name,
            'sku' => $p->sku ?: '',
            'part_number' => $p->sku ?: '',
            'model' => $p->model ?: '',
            'series' => $p->series ?: '',
            'brand_id' => $p->brand_id ? (string) $p->brand_id : '',
            'brand_name' => $p->brand?->name ?: '',
            'category_id' => $p->category_id ? (string) $p->category_id : '',
            'category_name' => $p->category?->name ?: '',
            'sub_category_id' => $p->sub_category_id ? (string) $p->sub_category_id : '',
            'sub_category_name' => $p->subCategory?->name ?: '',
            'product_type_id' => $p->product_type_id ? (string) $p->product_type_id : '',
            'product_type_name' => $productTypeName ?: '',
            'base_uom_id' => $p->base_uom_id ? (string) $p->base_uom_id : '',
            'base_uom_code' => $p->baseUom?->code ?: '',
            'base_uom_name' => $p->baseUom?->name ?: '',
            'hsn_code' => $p->hsn_code ?: '',
            'tax_rate' => $p->tax_rate !== null ? (float) $p->tax_rate : 0.0,
            'mrp' => $mrp > 0 ? $mrp : null,
            'selling_price' => $p->selling_price !== null ? (float) $p->selling_price : null,
            'uoms' => $validUoms->values()->all(),
        ];
    })->values();

    $gstMode = function ($value): string {
        return in_array((string) $value, ['1', 'true', 'inclusive'], true) ? 'inclusive' : 'exclusive';
    };

    $uomOptions = $uoms->map(fn ($u) => [
        'id' => $u->id,
        'code' => $u->code,
        'name' => $u->name,
    ])->values();

    $brandOptions = $brands->map(fn ($b) => [
        'id' => $b->id,
        'name' => $b->name,
    ])->values();

    $editDealerPrice = old('dealer_price', $item->dealer_price_entered ?? $item->dealer_price ?? $item->entered_rate ?? $item->rate ?? '');
    $editDealerGstMode = $gstMode(old('dealer_price_gst_mode', $item->dealer_price_gst_mode ?? ($item->rate_includes_gst ? 'inclusive' : 'exclusive')));
    $editRetailPrice = old('retail_price', $item->retail_price_entered ?? $item->retail_price ?? '');
    $editRetailGstMode = $gstMode(old('retail_price_gst_mode', $item->retail_price_gst_mode ?? 'exclusive'));

    $defaultDealerGstMode = 'exclusive';
    $defaultRetailGstMode = 'exclusive';
    $defaultMinQty = '';

    $oldLines = old('lines');
    if (is_array($oldLines) && count($oldLines)) {
        $initialLines = collect($oldLines)->map(function ($row) use ($gstMode, $defaultDealerGstMode, $defaultRetailGstMode) {
            return [
                'product_id' => (string) ($row['product_id'] ?? ''),
                'uom_id' => (string) ($row['uom_id'] ?? ''),
                'dealer_price' => $row['dealer_price'] ?? ($row['rate'] ?? ''),
                'dealer_price_gst_mode' => $gstMode($row['dealer_price_gst_mode'] ?? $row['rate_includes_gst'] ?? $defaultDealerGstMode),
                'retail_price' => $row['retail_price'] ?? '',
                'retail_price_gst_mode' => $gstMode($row['retail_price_gst_mode'] ?? $defaultRetailGstMode),
                'min_qty' => $row['min_qty'] ?? '',
                'searchQuery' => '',
                'isOpen' => false,
            ];
        })->values()->all();
    } else {
        $initialLines = [[
            'product_id' => '',
            'uom_id' => '',
            'dealer_price' => '',
            'dealer_price_gst_mode' => $defaultDealerGstMode,
            'retail_price' => '',
            'retail_price_gst_mode' => $defaultRetailGstMode,
            'min_qty' => ($defaultMinQty !== null && $defaultMinQty !== '') ? (string) $defaultMinQty : '',
            'searchQuery' => '',
            'isOpen' => false,
        ]];
    }

    $serverLineErrors = collect($errors->getMessages())
        ->filter(function ($messages, $key) {
            return str_starts_with($key, 'lines.');
        })
        ->mapWithKeys(function ($messages, $key) {
            $parts = explode('.', $key);
            if (count($parts) < 3) {
                return [];
            }

            return [$parts[1].'.'.$parts[2] => $messages[0] ?? ''];
        })
        ->all();
@endphp

<x-ui.page-header :title="$isEdit ? 'Edit Price' : 'Add Price'">
    <x-slot name="actions">
        @if(!$isEdit)
            <button type="button"
                    @click="openCopyModal()"
                    data-copy-button
                    class="inline-flex items-center gap-1.5 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 shadow-2xs hover:bg-indigo-100 transition cursor-pointer">
                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
                <span>Copy from Existing</span>
            </button>
        @endif
        <x-ui.button variant="secondary" :href="route('masters.price-masters.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div x-data="priceMasterForm({
        isEdit: {{ $isEdit ? 'true' : 'false' }},
        type: {{ json_encode((string) old('type', $item->type ?? 'customer_type')) }},
        name: {{ json_encode(old('name', $item->name ?? '')) }},
        customerTypeId: {{ json_encode((string) old('customer_type_id', $item->customer_type_id ?? '')) }},
        brandId: {{ json_encode((string) old('brand_id', $item->brand_id ?? '')) }},
        knownTypeNames: {{ json_encode($customerTypes->pluck('name')->map(fn ($n) => mb_strtolower(trim($n)))->values()) }},
        brands: {{ json_encode($brandOptions) }},
        products: {{ json_encode($productOptions, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }},
        uoms: {{ json_encode($uomOptions) }},
        lines: {{ json_encode($initialLines) }},
        editProductId: {{ json_encode((string) old('product_id', $item->product_id ?? '')) }},
        editUomId: {{ json_encode((string) old('uom_id', $item->uom_id ?? '')) }},
        editDealerPrice: {{ json_encode($editDealerPrice) }},
        editDealerGstMode: {{ json_encode($editDealerGstMode) }},
        editRetailPrice: {{ json_encode($editRetailPrice) }},
        editRetailGstMode: {{ json_encode($editRetailGstMode) }},
        editMinQty: {{ json_encode(old('min_qty', $item->min_qty ?? '')) }},
    })" class="space-y-5 max-w-5xl">

        <form method="POST"
              action="{{ $isEdit ? route('masters.price-masters.update', $item) : route('masters.price-masters.store') }}"
              class="space-y-5">
            @csrf
            @if($isEdit)
                @method('PUT')
            @endif

            {{-- 1. Price List Type Selector --}}
            @if($isEdit)
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Price List Type</label>
                    <input type="hidden" name="type" :value="type">
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-semibold"
                         :class="type === 'brand' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'">
                        <span x-text="type === 'brand' ? 'Brand Wise' : 'Customer Type Wise'"></span>
                    </div>
                </div>
            @else
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Price List Type</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-w-md">
                        <label class="relative flex cursor-pointer rounded-xl border p-3 shadow-xs focus:outline-none transition"
                               :class="type === 'customer_type' ? 'border-indigo-600 ring-2 ring-indigo-600 bg-indigo-50/40 text-indigo-900' : 'border-gray-300 bg-white hover:bg-slate-50 text-gray-700'">
                            <input type="radio" name="type" value="customer_type" x-model="type" @change="handleTypeChange('customer_type')" class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                                      :class="type === 'customer_type' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white'">
                                    <span class="h-2 w-2 rounded-full bg-white" x-show="type === 'customer_type'"></span>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold">Customer Type Wise</p>
                                    <p class="text-[11px] text-gray-500">Tier pricing by customer type</p>
                                </div>
                            </div>
                        </label>

                        <label class="relative flex cursor-pointer rounded-xl border p-3 shadow-xs focus:outline-none transition"
                               :class="type === 'brand' ? 'border-indigo-600 ring-2 ring-indigo-600 bg-indigo-50/40 text-indigo-900' : 'border-gray-300 bg-white hover:bg-slate-50 text-gray-700'">
                            <input type="radio" name="type" value="brand" x-model="type" @change="handleTypeChange('brand')" class="sr-only">
                            <div class="flex items-center gap-2.5">
                                <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border"
                                      :class="type === 'brand' ? 'border-indigo-600 bg-indigo-600 text-white' : 'border-gray-300 bg-white'">
                                    <span class="h-2 w-2 rounded-full bg-white" x-show="type === 'brand'"></span>
                                </span>
                                <div>
                                    <p class="text-xs font-semibold">Brand Wise</p>
                                    <p class="text-[11px] text-gray-500">Brand-specific product rates</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    @error('type')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror
                </div>
            @endif

            {{-- Optional Price List Name / Reference --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Price List Name (Optional)</label>
                <input type="text"
                       id="name"
                       name="name"
                       x-model="priceListName"
                       placeholder="e.g. FY 2026-27 Promotional Rate"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('name')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 2. Customer Type Selector (shown when type === 'customer_type') --}}
            <div x-show="type === 'customer_type'">
                <div class="flex items-center justify-between mb-1">
                    <label for="customer_type_id" class="block text-sm font-medium text-gray-700">Customer Type *</label>
                    <button type="button"
                            @click="openTypeModal()"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1 hover:underline focus:outline-none">
                        + Create
                    </button>
                </div>
                <select name="customer_type_id"
                        id="customer_type_id"
                        x-ref="typeSelect"
                        x-model="selectedCustomerTypeId"
                        @change="previousCustomerTypeId = selectedCustomerTypeId"
                        :required="type === 'customer_type'"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Customer Type</option>
                    @foreach($customerTypes as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                    @endforeach
                    <template x-for="t in customTypes" :key="t.id">
                        <option :value="t.id" x-text="t.name"></option>
                    </template>
                </select>
                @error('customer_type_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- 3. Brand Selector (shown when type === 'brand') --}}
            <div x-show="type === 'brand'" x-cloak>
                <label for="brand_id" class="block text-sm font-medium text-gray-700 mb-1">Brand *</label>
                <select name="brand_id"
                        id="brand_id"
                        x-model="selectedBrandId"
                        @change="handleBrandChange()"
                        :required="type === 'brand'"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Brand</option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
                @error('brand_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            @if($isEdit)
                {{-- Single-row edit --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Product *</label>
                    <div class="relative" @click.outside="editProductOpen = false">
                        <input type="hidden" name="product_id" :value="editProductId" required>
                        <input type="text"
                               x-model="editProductQuery"
                               @focus="editProductOpen = true"
                               @input="editProductOpen = true; editProductId = ''"
                               :placeholder="type === 'brand' ? 'Search products of this brand...' : 'Search product name or SKU...'"
                               autocomplete="off"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8">
                        <button type="button" x-show="editProductId" @click="clearEditProduct()"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                        <div x-show="editProductOpen && filteredEditProducts.length > 0"
                             x-cloak
                             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                            <template x-for="prod in filteredEditProducts" :key="prod.id">
                                <div @click="selectEditProduct(prod)"
                                     class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                    <span class="font-medium text-slate-800" x-text="prod.name"></span>
                                    <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                    @error('product_id')
                        <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    {{-- Auto-fetched Product Master Details in Edit Mode --}}
                    <template x-if="editProductId && !getProductDetails(editProductId)">
                        <div class="mt-3 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs flex items-center gap-2 text-indigo-600">
                            <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Loading product details from Product Master...</span>
                        </div>
                    </template>
                    <template x-if="editProductId && getProductDetails(editProductId)">
                        <div class="mt-3 p-3.5 bg-slate-50/90 rounded-lg border border-slate-200">
                            <div class="flex items-center justify-between pb-2 mb-2.5 border-b border-slate-200/80">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-xs font-semibold text-slate-800">Product Master Details</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div x-show="isEditLoadingDetails" class="inline-flex items-center gap-1 text-[11px] text-indigo-600 font-medium">
                                        <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Fetching live details...</span>
                                    </div>
                                    <span x-show="!isEditLoadingDetails" class="text-[11px] text-slate-400">Auto-fetched from Product Master</span>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-2.5 text-xs">
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Brand</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).brand_name && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).brand_name || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Category</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).category_name && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).category_name || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Subcategory</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).sub_category_name && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).sub_category_name || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">SKU / Part Number</span>
                                    <span class="font-medium text-slate-700 font-mono" :class="!getProductDetails(editProductId).sku && 'text-slate-400 italic font-sans'" x-text="getProductDetails(editProductId).sku || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Model Number</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).model && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).model || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Product Type</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).product_type_name && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).product_type_name || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">HSN Code</span>
                                    <span class="font-medium text-slate-700 font-mono" :class="!getProductDetails(editProductId).hsn_code && 'text-slate-400 italic font-sans'" x-text="getProductDetails(editProductId).hsn_code || 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">GST Rate</span>
                                    <span class="font-medium text-slate-700" x-text="getProductDetails(editProductId).tax_rate !== null && getProductDetails(editProductId).tax_rate !== undefined ? `${getProductDetails(editProductId).tax_rate}%` : 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">MRP</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).mrp && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).mrp ? `₹${money(getProductDetails(editProductId).mrp)}` : 'Not available'"></span>
                                </div>
                                <div>
                                    <span class="block text-[10px] uppercase font-semibold text-slate-400">Base UOM</span>
                                    <span class="font-medium text-slate-700" :class="!getProductDetails(editProductId).base_uom_name && !getProductDetails(editProductId).base_uom_code && 'text-slate-400 italic'" x-text="getProductDetails(editProductId).base_uom_code ? (getProductDetails(editProductId).base_uom_name ? `${getProductDetails(editProductId).base_uom_name} (${getProductDetails(editProductId).base_uom_code})` : getProductDetails(editProductId).base_uom_code) : (getProductDetails(editProductId).base_uom_name || 'Not available')"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- Dual Pricing: Dealer & Retail Pricing Cards --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Dealer Pricing --}}
                    <div class="p-4 rounded-xl border border-indigo-100 bg-indigo-50/20 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-indigo-900">Dealer Price</label>
                            <div class="flex items-center gap-2.5 text-xs text-slate-600">
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="edit_dealer_gst_choice" value="exclusive" x-model="editDealerGstMode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Excl. GST</span>
                                </label>
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="edit_dealer_gst_choice" value="inclusive" x-model="editDealerGstMode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span>Incl. GST</span>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="dealer_price_gst_mode" :value="editDealerGstMode">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-sm">₹</span>
                            <input type="number" step="0.01" min="0" name="dealer_price" x-model="editDealerPrice"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 pl-7 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        @error('dealer_price')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror

                        {{-- Calculation Breakdown --}}
                        <template x-if="editDealerPricing()">
                            <div class="mt-2 p-2.5 bg-white rounded-lg border border-slate-200 text-[11px] space-y-1">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Tax-Exclusive Price:</span>
                                    <span class="font-semibold text-slate-800">₹<span x-text="editDealerPricing().base"></span></span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Tax Amount (<span x-text="editDealerPricing().taxPercent"></span>% GST):</span>
                                    <span class="font-semibold text-slate-800">₹<span x-text="editDealerPricing().gst"></span></span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-100 font-semibold text-slate-900">
                                    <span>Tax-Inclusive Price:</span>
                                    <span class="text-indigo-700">₹<span x-text="editDealerPricing().inclusive"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Retail Pricing --}}
                    <div class="p-4 rounded-xl border border-emerald-100 bg-emerald-50/20 space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <label class="block text-xs font-semibold uppercase tracking-wider text-emerald-900">Retail Price</label>
                            <div class="flex items-center gap-2.5 text-xs text-slate-600">
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="edit_retail_gst_choice" value="exclusive" x-model="editRetailGstMode" class="border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>Excl. GST</span>
                                </label>
                                <label class="inline-flex items-center gap-1 cursor-pointer">
                                    <input type="radio" name="edit_retail_gst_choice" value="inclusive" x-model="editRetailGstMode" class="border-gray-300 text-emerald-600 focus:ring-emerald-500">
                                    <span>Incl. GST</span>
                                </label>
                            </div>
                        </div>
                        <input type="hidden" name="retail_price_gst_mode" :value="editRetailGstMode">
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-sm">₹</span>
                            <input type="number" step="0.01" min="0" name="retail_price" x-model="editRetailPrice"
                                   placeholder="0.00"
                                   class="block w-full rounded-lg border-gray-300 pl-7 text-sm font-medium focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        @error('retail_price')
                            <p class="text-xs text-rose-600">{{ $message }}</p>
                        @enderror

                        {{-- Calculation Breakdown --}}
                        <template x-if="editRetailPricing()">
                            <div class="mt-2 p-2.5 bg-white rounded-lg border border-slate-200 text-[11px] space-y-1">
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Tax-Exclusive Price:</span>
                                    <span class="font-semibold text-slate-800">₹<span x-text="editRetailPricing().base"></span></span>
                                </div>
                                <div class="flex items-center justify-between text-slate-600">
                                    <span>Tax Amount (<span x-text="editRetailPricing().taxPercent"></span>% GST):</span>
                                    <span class="font-semibold text-slate-800">₹<span x-text="editRetailPricing().gst"></span></span>
                                </div>
                                <div class="flex items-center justify-between pt-1 border-t border-slate-100 font-semibold text-slate-900">
                                    <span>Tax-Inclusive Price:</span>
                                    <span class="text-emerald-700">₹<span x-text="editRetailPricing().inclusive"></span></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Hidden fallback sync for legacy / backward compatibility --}}
                <input type="hidden" name="rate" :value="editDealerPrice || editRetailPrice || ''">
                <input type="hidden" name="rate_includes_gst" :value="editDealerGstMode === 'inclusive' ? '1' : '0'">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">UOM *</label>
                        <select name="uom_id" id="edit_uom_id" x-model="editUomId" required
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select UOM</option>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}">{{ $u->code }}{{ $u->name && $u->name !== $u->code ? ' - ' . $u->name : '' }}</option>
                            @endforeach
                        </select>
                        <template x-if="editProductId && getProductUomOptions(editProductId).length > 0">
                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                                <span class="text-slate-400 font-medium">Product Units:</span>
                                <template x-for="pu in getProductUomOptions(editProductId)" :key="pu.id">
                                    <button type="button"
                                            @click="editUomId = String(pu.id)"
                                            class="px-2 py-0.5 rounded border text-[11px] font-medium transition cursor-pointer"
                                            :class="String(editUomId) === String(pu.id)
                                                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-semibold ring-1 ring-indigo-200'
                                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                        <span x-text="pu.code"></span>
                                        <span x-show="pu.is_base" class="text-[9px] text-indigo-500 font-bold ml-0.5">(Base)</span>
                                        <span x-show="!pu.is_base && pu.label" class="text-[9px] text-slate-400 ml-0.5" x-text="`(${pu.label})`"></span>
                                    </button>
                                </template>
                            </div>
                        </template>
                        <template x-if="editProductId && !getProductDetails(editProductId)?.base_uom_id && getProductUomOptions(editProductId).length === 0">
                            <div class="mt-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 flex items-center justify-between">
                                <span>No Base UOM configured for this product in Product Master.</span>
                                @can('masters.edit')
                                <a :href="`/masters/products/${editProductId}/edit`" target="_blank" class="text-indigo-600 hover:underline font-semibold text-[11px] ml-2">Configure &rarr;</a>
                                @endcan
                            </div>
                        </template>
                        @error('uom_id')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Min Qty (Optional)</label>
                        <input type="number" step="0.01" min="0" name="min_qty" x-model="editMinQty"
                               placeholder="e.g. 10"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('min_qty')
                            <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @else
                {{-- Multi-product create --}}

                {{-- Customer Type Wise Section --}}
                <div x-show="type === 'customer_type'" class="space-y-3">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h3 class="text-sm font-semibold text-slate-700">Products</h3>
                            <p class="text-xs text-slate-500 mt-0.5">The unit fills in from the product. Enter Dealer and/or Retail prices with independent GST settings.</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="openCopyModal()"
                                    class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shrink-0 cursor-pointer">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                </svg>
                                <span>Copy Pricing</span>
                            </button>
                            <button type="button" @click="addLine()"
                                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-indigo-500 transition shrink-0 shadow-xs cursor-pointer">
                                + Add Product
                            </button>
                        </div>
                    </div>

                    @error('lines')
                        <p class="text-xs text-rose-600">{{ $message }}</p>
                    @enderror

                    <div class="space-y-3">
                        <template x-for="(line, idx) in lines" :key="idx">
                            <div class="rounded-xl border border-slate-200 bg-slate-50/50 p-4 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-500" x-text="`Product #${idx + 1}`"></span>
                                    <button type="button" x-show="lines.length > 1" @click="removeLine(idx)"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 transition">Remove</button>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div class="md:col-span-2 relative" @click.outside="line.isOpen = false">
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Product *</label>
                                        <input type="hidden" :name="`lines[${idx}][product_id]`" :value="line.product_id">
                                        <div class="relative">
                                            <input type="text"
                                                   x-model="line.searchQuery"
                                                   @focus="line.isOpen = true"
                                                   @input="line.isOpen = true; line.product_id = ''"
                                                   :placeholder="type === 'brand' ? (selectedBrandId ? 'Search products of this brand...' : 'Please select a brand above first') : 'Search product name or SKU...'"
                                                   :disabled="type === 'brand' && !selectedBrandId"
                                                   autocomplete="off"
                                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-8 disabled:bg-slate-100 disabled:cursor-not-allowed">
                                            <button type="button" x-show="line.product_id" @click="clearLineProduct(idx)"
                                                    class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold">&times;</button>
                                        </div>
                                        <div x-show="line.isOpen && filteredProducts(line).length > 0"
                                             x-cloak
                                             class="absolute z-50 left-0 right-0 mt-1 max-h-60 overflow-y-auto bg-white rounded-lg shadow-xl border border-slate-200 divide-y divide-slate-100">
                                            <template x-for="prod in filteredProducts(line)" :key="prod.id">
                                                <div @click="selectLineProduct(idx, prod)"
                                                     class="p-2.5 hover:bg-indigo-50 cursor-pointer text-xs flex items-center justify-between">
                                                    <span class="font-medium text-slate-800" x-text="prod.name"></span>
                                                    <span class="font-mono text-[11px] text-slate-500" x-text="prod.sku"></span>
                                                </div>
                                            </template>
                                        </div>
                                        <template x-if="lineErrors[idx]?.product_id">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].product_id"></p>
                                        </template>

                                        {{-- Auto-fetched Product Master Details in Create Line --}}
                                        <template x-if="line.product_id && !getProductDetails(line.product_id)">
                                            <div class="mt-2.5 p-3 bg-slate-50 rounded-lg border border-slate-200 text-xs flex items-center gap-2 text-indigo-600">
                                                <svg class="animate-spin h-3.5 w-3.5 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                                <span>Loading product details from Product Master...</span>
                                            </div>
                                        </template>
                                        <template x-if="line.product_id && getProductDetails(line.product_id)">
                                            <div class="mt-2.5 p-3 bg-white rounded-lg border border-slate-200 shadow-2xs">
                                                <div class="flex items-center justify-between pb-1.5 mb-2 border-b border-slate-100">
                                                    <div class="flex items-center gap-1.5">
                                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                        </svg>
                                                        <span class="text-xs font-semibold text-slate-700">Product Master Details</span>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <div x-show="line.isLoadingDetails" class="inline-flex items-center gap-1 text-[10px] text-indigo-600 font-medium">
                                                            <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                            </svg>
                                                            <span>Fetching live details...</span>
                                                        </div>
                                                        <span x-show="!line.isLoadingDetails" class="text-[10px] text-slate-400">Auto-fetched</span>
                                                    </div>
                                                </div>

                                                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-2 text-xs">
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Brand</span>
                                                        <span class="font-medium text-slate-700 truncate block" :class="!getProductDetails(line.product_id).brand_name && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).brand_name || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Category</span>
                                                        <span class="font-medium text-slate-700 truncate block" :class="!getProductDetails(line.product_id).category_name && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).category_name || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Subcategory</span>
                                                        <span class="font-medium text-slate-700 truncate block" :class="!getProductDetails(line.product_id).sub_category_name && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).sub_category_name || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">SKU / Part No</span>
                                                        <span class="font-medium text-slate-700 font-mono truncate block" :class="!getProductDetails(line.product_id).sku && 'text-slate-400 italic font-sans'" x-text="getProductDetails(line.product_id).sku || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Model</span>
                                                        <span class="font-medium text-slate-700 truncate block" :class="!getProductDetails(line.product_id).model && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).model || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Product Type</span>
                                                        <span class="font-medium text-slate-700 truncate block" :class="!getProductDetails(line.product_id).product_type_name && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).product_type_name || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">HSN</span>
                                                        <span class="font-medium text-slate-700 font-mono" :class="!getProductDetails(line.product_id).hsn_code && 'text-slate-400 italic font-sans'" x-text="getProductDetails(line.product_id).hsn_code || 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">GST</span>
                                                        <span class="font-medium text-slate-700" x-text="getProductDetails(line.product_id).tax_rate !== null && getProductDetails(line.product_id).tax_rate !== undefined ? `${getProductDetails(line.product_id).tax_rate}%` : 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">MRP</span>
                                                        <span class="font-medium text-slate-700" :class="!getProductDetails(line.product_id).mrp && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).mrp ? `₹${money(getProductDetails(line.product_id).mrp)}` : 'Not available'"></span>
                                                    </div>
                                                    <div>
                                                        <span class="block text-[10px] uppercase font-semibold text-slate-400">Base UOM</span>
                                                        <span class="font-medium text-slate-700" :class="!getProductDetails(line.product_id).base_uom_name && !getProductDetails(line.product_id).base_uom_code && 'text-slate-400 italic'" x-text="getProductDetails(line.product_id).base_uom_code ? (getProductDetails(line.product_id).base_uom_name ? `${getProductDetails(line.product_id).base_uom_name} (${getProductDetails(line.product_id).base_uom_code})` : getProductDetails(line.product_id).base_uom_code) : (getProductDetails(line.product_id).base_uom_name || 'Not available')"></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Dual Pricing: Dealer & Retail Pricing Cards for Line --}}
                                    <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        {{-- Dealer Price --}}
                                        <div class="p-3.5 rounded-lg border border-indigo-100 bg-white space-y-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                                <label class="block text-xs font-semibold uppercase tracking-wider text-indigo-900">Dealer Price</label>
                                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                                        <input type="radio" :name="`lines[${idx}][dealer_gst_choice]`" value="exclusive" x-model="line.dealer_price_gst_mode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-[11px]">Excl. GST</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                                        <input type="radio" :name="`lines[${idx}][dealer_gst_choice]`" value="inclusive" x-model="line.dealer_price_gst_mode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                                        <span class="text-[11px]">Incl. GST</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="hidden" :name="`lines[${idx}][dealer_price_gst_mode]`" :value="line.dealer_price_gst_mode">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                                <input type="number" step="0.01" min="0" :name="`lines[${idx}][dealer_price]`" x-model="line.dealer_price"
                                                       placeholder="0.00"
                                                       class="block w-full rounded-lg border-gray-300 pl-7 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <template x-if="lineErrors[idx]?.dealer_price">
                                                <p class="text-xs text-rose-600" x-text="lineErrors[idx].dealer_price"></p>
                                            </template>

                                            {{-- Dealer Calculation Breakdown --}}
                                            <template x-if="lineDealerPricing(line)">
                                                <div class="mt-1.5 p-2 bg-slate-50 rounded-md border border-slate-200/80 text-[10px] space-y-0.5">
                                                    <div class="flex items-center justify-between text-slate-600">
                                                        <span>Tax-Exclusive Price:</span>
                                                        <span class="font-semibold text-slate-800">₹<span x-text="lineDealerPricing(line).base"></span></span>
                                                    </div>
                                                    <div class="flex items-center justify-between text-slate-600">
                                                        <span>Tax Amount (<span x-text="lineDealerPricing(line).taxPercent"></span>% GST):</span>
                                                        <span class="font-semibold text-slate-800">₹<span x-text="lineDealerPricing(line).gst"></span></span>
                                                    </div>
                                                    <div class="flex items-center justify-between pt-0.5 border-t border-slate-200 font-semibold text-slate-900">
                                                        <span>Tax-Inclusive Price:</span>
                                                        <span class="text-indigo-700">₹<span x-text="lineDealerPricing(line).inclusive"></span></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>

                                        {{-- Retail Price --}}
                                        <div class="p-3.5 rounded-lg border border-emerald-100 bg-white space-y-2.5">
                                            <div class="flex flex-wrap items-center justify-between gap-1.5">
                                                <label class="block text-xs font-semibold uppercase tracking-wider text-emerald-900">Retail Price</label>
                                                <div class="flex items-center gap-2 text-xs text-slate-600">
                                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                                        <input type="radio" :name="`lines[${idx}][retail_gst_choice]`" value="exclusive" x-model="line.retail_price_gst_mode" class="border-gray-300 text-emerald-600 focus:ring-indigo-500">
                                                        <span class="text-[11px]">Excl. GST</span>
                                                    </label>
                                                    <label class="inline-flex items-center gap-1 cursor-pointer">
                                                        <input type="radio" :name="`lines[${idx}][retail_gst_choice]`" value="inclusive" x-model="line.retail_price_gst_mode" class="border-gray-300 text-emerald-600 focus:ring-indigo-500">
                                                        <span class="text-[11px]">Incl. GST</span>
                                                    </label>
                                                </div>
                                            </div>
                                            <input type="hidden" :name="`lines[${idx}][retail_price_gst_mode]`" :value="line.retail_price_gst_mode">
                                            <div class="relative">
                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                                <input type="number" step="0.01" min="0" :name="`lines[${idx}][retail_price]`" x-model="line.retail_price"
                                                       placeholder="0.00"
                                                       class="block w-full rounded-lg border-gray-300 pl-7 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500">
                                            </div>
                                            <template x-if="lineErrors[idx]?.retail_price">
                                                <p class="text-xs text-rose-600" x-text="lineErrors[idx].retail_price"></p>
                                            </template>

                                            {{-- Retail Calculation Breakdown --}}
                                            <template x-if="lineRetailPricing(line)">
                                                <div class="mt-1.5 p-2 bg-slate-50 rounded-md border border-slate-200/80 text-[10px] space-y-0.5">
                                                    <div class="flex items-center justify-between text-slate-600">
                                                        <span>Tax-Exclusive Price:</span>
                                                        <span class="font-semibold text-slate-800">₹<span x-text="lineRetailPricing(line).base"></span></span>
                                                    </div>
                                                    <div class="flex items-center justify-between text-slate-600">
                                                        <span>Tax Amount (<span x-text="lineRetailPricing(line).taxPercent"></span>% GST):</span>
                                                        <span class="font-semibold text-slate-800">₹<span x-text="lineRetailPricing(line).gst"></span></span>
                                                    </div>
                                                    <div class="flex items-center justify-between pt-0.5 border-t border-slate-200 font-semibold text-slate-900">
                                                        <span>Tax-Inclusive Price:</span>
                                                        <span class="text-emerald-700">₹<span x-text="lineRetailPricing(line).inclusive"></span></span>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Backward compatibility sync --}}
                                    <input type="hidden" :name="`lines[${idx}][rate]`" :value="line.dealer_price || line.retail_price || ''">
                                    <input type="hidden" :name="`lines[${idx}][rate_includes_gst]`" :value="line.dealer_price_gst_mode === 'inclusive' ? '1' : '0'">

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1">UOM *</label>
                                        <select :name="`lines[${idx}][uom_id]`" x-model="line.uom_id" required
                                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">Select UOM</option>
                                            @foreach($uoms as $u)
                                                <option value="{{ $u->id }}">{{ $u->code }}{{ $u->name && $u->name !== $u->code ? ' - ' . $u->name : '' }}</option>
                                            @endforeach
                                        </select>
                                        <template x-if="line.product_id && getProductUomOptions(line.product_id).length > 0">
                                            <div class="mt-1.5 flex flex-wrap items-center gap-1.5 text-[11px]">
                                                <span class="text-slate-400 font-medium">Product Units:</span>
                                                <template x-for="pu in getProductUomOptions(line.product_id)" :key="pu.id">
                                                    <button type="button"
                                                            @click="line.uom_id = String(pu.id)"
                                                            class="px-2 py-0.5 rounded border text-[11px] font-medium transition cursor-pointer"
                                                            :class="String(line.uom_id) === String(pu.id)
                                                                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-semibold ring-1 ring-indigo-200'
                                                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                                        <span x-text="pu.code"></span>
                                                        <span x-show="pu.is_base" class="text-[9px] text-indigo-500 font-bold ml-0.5">(Base)</span>
                                                        <span x-show="!pu.is_base && pu.label" class="text-[9px] text-slate-400 ml-0.5" x-text="`(${pu.label})`"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="line.product_id && !getProductDetails(line.product_id)?.base_uom_id && getProductUomOptions(line.product_id).length === 0">
                                            <div class="mt-1.5 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded p-2 flex items-center justify-between">
                                                <span>No Base UOM configured for this product in Product Master.</span>
                                                @can('masters.edit')
                                                <a :href="`/masters/products/${line.product_id}/edit`" target="_blank" class="text-indigo-600 hover:underline font-semibold text-[11px] ml-2">Configure &rarr;</a>
                                                @endcan
                                            </div>
                                        </template>
                                        <template x-if="lineErrors[idx]?.uom_id">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].uom_id"></p>
                                        </template>
                                    </div>

                                    <div>
                                        <label class="block text-xs font-medium text-slate-700 mb-1">Min Qty (Optional)</label>
                                        <input type="number" step="0.01" min="0" :name="`lines[${idx}][min_qty]`" x-model="line.min_qty"
                                               placeholder="e.g. 10"
                                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <template x-if="lineErrors[idx]?.min_qty">
                                            <p class="mt-1 text-xs text-rose-600" x-text="lineErrors[idx].min_qty"></p>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Brand Wise Section --}}
                <div x-show="type === 'brand'" x-cloak class="space-y-6">
                    {{-- 1. Brand Selection Prompt when no brand is selected --}}
                    <div x-show="!selectedBrandId" class="p-8 text-center bg-slate-50 border-2 border-dashed border-slate-200 rounded-2xl">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 mb-3">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <h4 class="text-sm font-semibold text-slate-800">Please Select a Brand Above</h4>
                        <p class="text-xs text-slate-500 mt-1 max-w-sm mx-auto">Choose a brand to view all its catalog products, select products individually or in bulk, and configure their dealer and retail pricing.</p>
                    </div>

                    {{-- 2. Brand Products Catalog Display (when brand is selected) --}}
                    <div x-show="selectedBrandId" class="space-y-4">
                        <div class="bg-white rounded-xl border border-slate-200 shadow-xs overflow-hidden">
                            {{-- Catalog Header & Counts Toolbar --}}
                            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-800">Brand Products Catalog</h3>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-indigo-100 text-indigo-700"
                                              x-text="`${selectedBrandProductsCount} selected`"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">Select products individually using checkboxes or select all to configure prices.</p>
                                </div>

                                {{-- Live Counts & Global Bulk Actions --}}
                                <div class="flex flex-wrap items-center gap-2">
                                    <div class="text-[11px] text-slate-600 bg-white border border-slate-200 rounded-lg px-2.5 py-1">
                                        <span>Total: <strong class="text-slate-900" x-text="totalBrandProductsCount"></strong></span>
                                        <span class="text-slate-300 mx-1">|</span>
                                        <span>Matching: <strong class="text-slate-900" x-text="matchingBrandProductsCount"></strong></span>
                                    </div>
                                    <button type="button"
                                            @click="selectAllBrandProducts()"
                                            :disabled="totalBrandProductsCount === 0"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-slate-300 text-slate-700 hover:bg-slate-50 disabled:opacity-40 transition cursor-pointer">
                                        Select All (<span x-text="totalBrandProductsCount"></span>)
                                    </button>
                                    <button type="button"
                                            @click="deselectAllBrandProducts()"
                                            :disabled="selectedBrandProductsCount === 0"
                                            class="px-2.5 py-1 rounded-lg text-xs font-medium bg-white border border-slate-300 text-rose-600 hover:bg-rose-50 disabled:opacity-40 transition cursor-pointer">
                                        Deselect All
                                    </button>
                                    <button type="button"
                                            @click="openCopyModal()"
                                            class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-indigo-50 border border-indigo-200 text-indigo-700 hover:bg-indigo-100 transition cursor-pointer inline-flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                                        </svg>
                                        <span>Copy from Existing</span>
                                    </button>
                                </div>
                            </div>

                            {{-- Search Filter & Pagination Controls --}}
                            <div class="p-3 bg-white border-b border-slate-100 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2.5">
                                <div class="relative flex-1 max-w-md">
                                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                    </svg>
                                    <input type="text"
                                           x-model="brandSearchQuery"
                                           @input="brandPage = 1"
                                           placeholder="Search products by name, SKU, model, category..."
                                           class="block w-full rounded-lg border-gray-300 pl-9 pr-8 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                    <button type="button" x-show="brandSearchQuery" @click="brandSearchQuery = ''; brandPage = 1"
                                            class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 font-bold text-xs">&times;</button>
                                </div>

                                <div class="flex items-center gap-2 self-end sm:self-auto">
                                    <label class="text-[11px] text-slate-500 whitespace-nowrap">Show:</label>
                                    <select x-model.number="brandPerPage" @change="brandPage = 1"
                                            class="rounded-lg border-gray-300 text-xs py-1 pl-2 pr-6 focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="10">10 / page</option>
                                        <option value="25">25 / page</option>
                                        <option value="50">50 / page</option>
                                        <option value="1000">All</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Loading Spinner --}}
                            <div x-show="isLoadingBrandProducts" class="py-12 text-center">
                                <svg class="animate-spin h-6 w-6 text-indigo-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <p class="text-xs text-indigo-700 font-medium">Fetching brand products from database...</p>
                            </div>

                            {{-- Error State --}}
                            <div x-show="!isLoadingBrandProducts && brandProductsError" x-cloak class="p-4 bg-rose-50 text-rose-700 text-xs text-center" x-text="brandProductsError"></div>

                            {{-- Empty State (No products for brand) --}}
                            <div x-show="!isLoadingBrandProducts && !brandProductsError && totalBrandProductsCount === 0" x-cloak class="py-10 text-center text-xs text-slate-500">
                                <p class="font-medium text-slate-700">No active products found for this brand.</p>
                                <p class="mt-1 text-slate-400">Add products under Masters &rarr; Products with this brand selected.</p>
                            </div>

                            {{-- Empty State (Search filter yields 0 matches) --}}
                            <div x-show="!isLoadingBrandProducts && totalBrandProductsCount > 0 && matchingBrandProductsCount === 0" x-cloak class="py-8 text-center text-xs text-slate-500">
                                <p>No products match your search "<strong x-text="brandSearchQuery"></strong>".</p>
                                <button type="button" @click="brandSearchQuery = ''; brandPage = 1" class="mt-2 text-indigo-600 hover:underline font-medium">Clear search</button>
                            </div>

                            {{-- Catalog Table --}}
                            <div x-show="!isLoadingBrandProducts && matchingBrandProductsCount > 0" class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                    <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                                        <tr>
                                            <th scope="col" class="w-10 px-3 py-2.5 text-center">
                                                <input type="checkbox"
                                                       :checked="isAllMatchingSelected && matchingBrandProductsCount > 0"
                                                       @click="toggleSelectAllMatching()"
                                                       title="Select/Deselect all matching products"
                                                       class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                            </th>
                                            <th scope="col" class="px-3 py-2.5">Product Name</th>
                                            <th scope="col" class="px-3 py-2.5">SKU / Part No</th>
                                            <th scope="col" class="px-3 py-2.5">Category</th>
                                            <th scope="col" class="px-3 py-2.5 text-right">MRP</th>
                                            <th scope="col" class="px-3 py-2.5 text-center">GST %</th>
                                            <th scope="col" class="px-3 py-2.5 text-center">Base UOM</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-slate-100 bg-white">
                                        <template x-for="prod in pagedBrandProducts" :key="prod.id">
                                            <tr @click="toggleProductSelection(prod)"
                                                class="cursor-pointer transition hover:bg-indigo-50/40"
                                                :class="isProductSelected(prod.id) ? 'bg-indigo-50/50 font-medium' : ''">
                                                <td class="w-10 px-3 py-2.5 text-center" @click.stop>
                                                    <input type="checkbox"
                                                           :checked="isProductSelected(prod.id)"
                                                           @click.stop="toggleProductSelection(prod)"
                                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer">
                                                </td>
                                                <td class="px-3 py-2.5">
                                                    <div class="text-slate-900 font-medium" x-text="prod.name"></div>
                                                    <div x-show="prod.model" class="text-[10px] text-slate-500" x-text="`Model: ${prod.model}`"></div>
                                                </td>
                                                <td class="px-3 py-2.5 font-mono text-[11px] text-slate-600" x-text="prod.sku || '—'"></td>
                                                <td class="px-3 py-2.5 text-slate-600">
                                                    <span x-text="prod.category_name || '—'"></span>
                                                    <span x-show="prod.sub_category_name" class="text-slate-400 text-[10px]" x-text="` / ${prod.sub_category_name}`"></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-right text-slate-900 font-mono">
                                                    <span x-show="prod.mrp">₹<span x-text="money(prod.mrp)"></span></span>
                                                    <span x-show="!prod.mrp" class="text-slate-400 italic font-sans">—</span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center text-slate-700">
                                                    <span x-text="prod.tax_rate !== null && prod.tax_rate !== undefined ? `${prod.tax_rate}%` : '—'"></span>
                                                </td>
                                                <td class="px-3 py-2.5 text-center text-slate-700">
                                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-slate-100 text-slate-700"
                                                          x-text="prod.base_uom_code || prod.base_uom_name || '—'"></span>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            {{-- Pagination Footer --}}
                            <div x-show="!isLoadingBrandProducts && matchingBrandProductsCount > 0 && brandTotalPages > 1"
                                 class="p-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-600">
                                <div>
                                    Showing page <span class="font-semibold text-slate-800" x-text="brandPage"></span> of <span class="font-semibold text-slate-800" x-text="brandTotalPages"></span>
                                    (<span x-text="matchingBrandProductsCount"></span> matching products)
                                </div>
                                <div class="flex items-center gap-1">
                                    <button type="button"
                                            @click="brandPage = Math.max(1, brandPage - 1)"
                                            :disabled="brandPage <= 1"
                                            class="px-2.5 py-1 rounded border border-slate-300 bg-white text-xs disabled:opacity-40 hover:bg-slate-50 transition cursor-pointer">
                                        &larr; Prev
                                    </button>
                                    <button type="button"
                                            @click="brandPage = Math.min(brandTotalPages, brandPage + 1)"
                                            :disabled="brandPage >= brandTotalPages"
                                            class="px-2.5 py-1 rounded border border-slate-300 bg-white text-xs disabled:opacity-40 hover:bg-slate-50 transition cursor-pointer">
                                        Next &rarr;
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- 3. Selected Products Pricing Section --}}
                        <div class="space-y-4">
                            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 pt-2">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <h3 class="text-sm font-semibold text-slate-800">Configure Pricing for Selected Products</h3>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700"
                                              x-text="`${selectedBrandProductsCount} selected`"></span>
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">Enter Dealer Price and Retail Price for each selected product, or use the Bulk Pricing tool below.</p>
                                </div>

                                {{-- Bulk Pricing Toggle Button --}}
                                <button type="button"
                                        x-show="lines.length > 0"
                                        @click="bulkPricingOpen = !bulkPricingOpen"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border text-xs font-semibold transition cursor-pointer"
                                        :class="bulkPricingOpen ? 'bg-indigo-600 text-white border-indigo-600 shadow-xs' : 'bg-white text-indigo-600 border-indigo-200 hover:bg-indigo-50'">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span x-text="bulkPricingOpen ? 'Hide Bulk Pricing Tool' : 'Bulk Pricing Tool'"></span>
                                </button>
                            </div>

                            @error('lines')
                                <div class="p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs font-medium text-rose-700">
                                    {{ $message }}
                                </div>
                            @enderror

                            {{-- Bulk Pricing Drawer / Panel --}}
                            <div x-show="bulkPricingOpen && lines.length > 0"
                                 x-transition
                                 class="p-4 bg-gradient-to-r from-indigo-50/70 via-purple-50/40 to-slate-50 rounded-xl border border-indigo-200 shadow-2xs space-y-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-1.5">
                                        <span class="text-base">⚡</span>
                                        <h4 class="text-xs font-bold uppercase tracking-wider text-indigo-900">Bulk Pricing Toolbar</h4>
                                        <span class="text-[11px] text-slate-500">— Apply common pricing across all <span class="font-semibold text-slate-800" x-text="lines.length"></span> selected products</span>
                                    </div>
                                    <button type="button" @click="bulkPricingOpen = false" class="text-slate-400 hover:text-slate-600 text-xs font-bold cursor-pointer">&times;</button>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    {{-- Bulk Dealer Price --}}
                                    <div class="p-3 bg-white rounded-lg border border-indigo-100 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-indigo-950 uppercase">Bulk Dealer Price</label>
                                            <div class="flex items-center gap-1 text-[10px] text-slate-600">
                                                <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                    <input type="radio" value="exclusive" x-model="bulkDealerGstMode" class="text-indigo-600 focus:ring-indigo-500 scale-75">
                                                    <span>Excl</span>
                                                </label>
                                                <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                    <input type="radio" value="inclusive" x-model="bulkDealerGstMode" class="text-indigo-600 focus:ring-indigo-500 scale-75">
                                                    <span>Incl</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                            <input type="number" step="0.01" min="0" x-model="bulkDealerPrice" placeholder="0.00"
                                                   class="block w-full rounded-md border-gray-300 pl-6 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>

                                    {{-- Bulk Retail Price --}}
                                    <div class="p-3 bg-white rounded-lg border border-emerald-100 space-y-2">
                                        <div class="flex items-center justify-between">
                                            <label class="block text-[11px] font-semibold text-emerald-950 uppercase">Bulk Retail Price</label>
                                            <div class="flex items-center gap-1 text-[10px] text-slate-600">
                                                <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                    <input type="radio" value="exclusive" x-model="bulkRetailGstMode" class="text-emerald-600 focus:ring-indigo-500 scale-75">
                                                    <span>Excl</span>
                                                </label>
                                                <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                    <input type="radio" value="inclusive" x-model="bulkRetailGstMode" class="text-emerald-600 focus:ring-indigo-500 scale-75">
                                                    <span>Incl</span>
                                                </label>
                                            </div>
                                        </div>
                                        <div class="relative">
                                            <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                            <input type="number" step="0.01" min="0" x-model="bulkRetailPrice" placeholder="0.00"
                                                   class="block w-full rounded-md border-gray-300 pl-6 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                    </div>

                                    {{-- Bulk Min Qty & Apply Button --}}
                                    <div class="p-3 bg-white rounded-lg border border-slate-200 flex flex-col justify-between gap-2">
                                        <div>
                                            <label class="block text-[11px] font-semibold text-slate-700 uppercase mb-1">Bulk Min Qty (Optional)</label>
                                            <input type="number" step="0.01" min="0" x-model="bulkMinQty" placeholder="e.g. 10"
                                                   class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                        </div>
                                        <button type="button"
                                                @click="applyBulkPricing()"
                                                class="w-full py-1.5 px-3 rounded-md bg-indigo-600 text-white font-semibold text-xs hover:bg-indigo-700 transition flex items-center justify-center gap-1.5 cursor-pointer">
                                            <span>Apply to All Selected (<span x-text="lines.length"></span>)</span>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="bulkAppliedToast" x-transition class="p-2 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-md text-xs font-semibold text-center">
                                    ✓ Bulk pricing successfully applied to all selected products!
                                </div>
                            </div>

                            {{-- Empty State (No products selected) --}}
                            <div x-show="lines.length === 0" class="p-8 text-center bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-500">
                                <p class="font-medium text-slate-700">No products selected yet.</p>
                                <p class="mt-1 text-slate-400">Select one or more products using the checkboxes in the Brand Catalog above to enter their pricing.</p>
                            </div>

                            {{-- Selected Products Pricing Table --}}
                            <div x-show="lines.length > 0" class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xs">
                                <div class="p-3.5 bg-slate-50 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Selected Products Pricing Table</span>
                                        <span class="px-2 py-0.5 rounded-full text-[11px] font-semibold bg-indigo-100 text-indigo-700" x-text="`${lines.length} configured`"></span>
                                    </div>
                                    <p class="text-[11px] text-slate-500">Product details are read-only from Product Master. Enter Dealer and/or Retail prices.</p>
                                </div>

                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                                        <thead class="bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider text-[10px]">
                                            <tr>
                                                <th scope="col" class="w-8 px-3 py-2.5 text-center">#</th>
                                                <th scope="col" class="px-3 py-2.5 min-w-[260px]">Product Master Details (Read-Only)</th>
                                                <th scope="col" class="px-3 py-2.5 min-w-[210px] bg-indigo-50/40">Dealer Price</th>
                                                <th scope="col" class="px-3 py-2.5 min-w-[210px] bg-emerald-50/40">Retail Price</th>
                                                <th scope="col" class="px-3 py-2.5 min-w-[140px]">UOM</th>
                                                <th scope="col" class="px-3 py-2.5 w-24">Min Qty</th>
                                                <th scope="col" class="w-10 px-2 py-2.5 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            <template x-for="(line, idx) in lines" :key="line.product_id">
                                                <tr class="hover:bg-slate-50/60 transition align-top">
                                                    {{-- Hidden inputs for form submit --}}
                                                    <input type="hidden" :name="`lines[${idx}][product_id]`" :value="line.product_id">
                                                    <input type="hidden" :name="`lines[${idx}][dealer_price_gst_mode]`" :value="line.dealer_price_gst_mode">
                                                    <input type="hidden" :name="`lines[${idx}][retail_price_gst_mode]`" :value="line.retail_price_gst_mode">
                                                    <input type="hidden" :name="`lines[${idx}][rate]`" :value="line.dealer_price || line.retail_price || ''">
                                                    <input type="hidden" :name="`lines[${idx}][rate_includes_gst]`" :value="line.dealer_price_gst_mode === 'inclusive' ? '1' : '0'">

                                                    {{-- Line number --}}
                                                    <td class="px-3 py-3 text-center">
                                                        <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-100 text-slate-600 font-bold text-[10px]"
                                                              x-text="idx + 1"></span>
                                                    </td>

                                                    {{-- Product Details Column (Read-Only) --}}
                                                    <td class="px-3 py-3">
                                                        <div class="font-semibold text-slate-900 text-xs" x-text="getProductDetails(line.product_id)?.name || line.searchQuery || 'Product'"></div>
                                                        
                                                        {{-- Read-only Details Badges --}}
                                                        <div class="mt-1 flex flex-wrap items-center gap-1 text-[10px]">
                                                            <span x-show="getProductDetails(line.product_id)?.sku" class="font-mono bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded" x-text="`SKU: ${getProductDetails(line.product_id).sku}`"></span>
                                                            <span x-show="getProductDetails(line.product_id)?.model" class="bg-slate-100 text-slate-700 px-1.5 py-0.5 rounded" x-text="`Model: ${getProductDetails(line.product_id).model}`"></span>
                                                            <span x-show="getProductDetails(line.product_id)?.category_name" class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-medium" x-text="getProductDetails(line.product_id).category_name"></span>
                                                            <span x-show="getProductDetails(line.product_id)?.sub_category_name" class="bg-indigo-50 text-indigo-700 px-1.5 py-0.5 rounded font-medium" x-text="getProductDetails(line.product_id).sub_category_name"></span>
                                                            <span x-show="getProductDetails(line.product_id)?.product_type_name" class="bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded" x-text="getProductDetails(line.product_id).product_type_name"></span>
                                                        </div>

                                                        <div class="mt-1 flex flex-wrap items-center gap-2 text-[10px] text-slate-500">
                                                            <span x-show="getProductDetails(line.product_id)?.hsn_code">HSN: <strong class="text-slate-700 font-mono" x-text="getProductDetails(line.product_id).hsn_code"></strong></span>
                                                            <span>GST: <strong class="text-slate-700" x-text="getProductDetails(line.product_id)?.tax_rate !== null && getProductDetails(line.product_id)?.tax_rate !== undefined ? `${getProductDetails(line.product_id).tax_rate}%` : '0%'"></strong></span>
                                                            <span x-show="getProductDetails(line.product_id)?.mrp">MRP: <strong class="text-slate-700">₹<span x-text="money(getProductDetails(line.product_id).mrp)"></span></strong></span>
                                                            <span>Base: <strong class="text-slate-700" x-text="getProductDetails(line.product_id)?.base_uom_code || getProductDetails(line.product_id)?.base_uom_name || '—'"></strong></span>
                                                        </div>
                                                    </td>

                                                    {{-- Dealer Price Column --}}
                                                    <td class="px-3 py-3 bg-indigo-50/20">
                                                        <div class="space-y-1.5">
                                                            <div class="flex items-center justify-between text-[10px]">
                                                                <span class="font-semibold text-indigo-900 uppercase tracking-wide">Dealer</span>
                                                                <div class="flex items-center gap-1.5 text-slate-600">
                                                                    <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                                        <input type="radio" :name="`lines[${idx}][brand_dealer_choice]`" value="exclusive" x-model="line.dealer_price_gst_mode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500 scale-75">
                                                                        <span>Excl</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                                        <input type="radio" :name="`lines[${idx}][brand_dealer_choice]`" value="inclusive" x-model="line.dealer_price_gst_mode" class="border-gray-300 text-indigo-600 focus:ring-indigo-500 scale-75">
                                                                        <span>Incl</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="relative">
                                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                                                <input type="number" step="0.01" min="0" :name="`lines[${idx}][dealer_price]`" x-model="line.dealer_price"
                                                                       placeholder="0.00"
                                                                       class="block w-full rounded-md border-gray-300 pl-6 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                                            </div>
                                                            <template x-if="lineErrors[idx]?.dealer_price">
                                                                <p class="text-[10px] text-rose-600" x-text="lineErrors[idx].dealer_price"></p>
                                                            </template>

                                                            {{-- Live Calculation Breakdown --}}
                                                            <template x-if="lineDealerPricing(line)">
                                                                <div class="p-1.5 bg-white rounded border border-indigo-100 text-[10px] space-y-0.5 leading-tight">
                                                                    <div class="flex justify-between text-slate-500">
                                                                        <span>Excl:</span>
                                                                        <span class="font-medium text-slate-800">₹<span x-text="lineDealerPricing(line).base"></span></span>
                                                                    </div>
                                                                    <div class="flex justify-between text-slate-500">
                                                                        <span>Tax (<span x-text="lineDealerPricing(line).taxPercent"></span>%):</span>
                                                                        <span class="font-medium text-slate-800">₹<span x-text="lineDealerPricing(line).gst"></span></span>
                                                                    </div>
                                                                    <div class="flex justify-between pt-0.5 border-t border-slate-100 font-semibold text-indigo-700">
                                                                        <span>Total:</span>
                                                                        <span>₹<span x-text="lineDealerPricing(line).inclusive"></span></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </td>

                                                    {{-- Retail Price Column --}}
                                                    <td class="px-3 py-3 bg-emerald-50/20">
                                                        <div class="space-y-1.5">
                                                            <div class="flex items-center justify-between text-[10px]">
                                                                <span class="font-semibold text-emerald-900 uppercase tracking-wide">Retail</span>
                                                                <div class="flex items-center gap-1.5 text-slate-600">
                                                                    <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                                        <input type="radio" :name="`lines[${idx}][brand_retail_choice]`" value="exclusive" x-model="line.retail_price_gst_mode" class="border-gray-300 text-emerald-600 focus:ring-indigo-500 scale-75">
                                                                        <span>Excl</span>
                                                                    </label>
                                                                    <label class="inline-flex items-center gap-0.5 cursor-pointer">
                                                                        <input type="radio" :name="`lines[${idx}][brand_retail_choice]`" value="inclusive" x-model="line.retail_price_gst_mode" class="border-gray-300 text-emerald-600 focus:ring-indigo-500 scale-75">
                                                                        <span>Incl</span>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                            <div class="relative">
                                                                <span class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 font-semibold text-xs">₹</span>
                                                                <input type="number" step="0.01" min="0" :name="`lines[${idx}][retail_price]`" x-model="line.retail_price"
                                                                       placeholder="0.00"
                                                                       class="block w-full rounded-md border-gray-300 pl-6 text-xs font-medium focus:border-indigo-500 focus:ring-indigo-500 bg-white">
                                                            </div>
                                                            <template x-if="lineErrors[idx]?.retail_price">
                                                                <p class="text-[10px] text-rose-600" x-text="lineErrors[idx].retail_price"></p>
                                                            </template>

                                                            {{-- Live Calculation Breakdown --}}
                                                            <template x-if="lineRetailPricing(line)">
                                                                <div class="p-1.5 bg-white rounded border border-emerald-100 text-[10px] space-y-0.5 leading-tight">
                                                                    <div class="flex justify-between text-slate-500">
                                                                        <span>Excl:</span>
                                                                        <span class="font-medium text-slate-800">₹<span x-text="lineRetailPricing(line).base"></span></span>
                                                                    </div>
                                                                    <div class="flex justify-between text-slate-500">
                                                                        <span>Tax (<span x-text="lineRetailPricing(line).taxPercent"></span>%):</span>
                                                                        <span class="font-medium text-slate-800">₹<span x-text="lineRetailPricing(line).gst"></span></span>
                                                                    </div>
                                                                    <div class="flex justify-between pt-0.5 border-t border-slate-100 font-semibold text-emerald-700">
                                                                        <span>Total:</span>
                                                                        <span>₹<span x-text="lineRetailPricing(line).inclusive"></span></span>
                                                                    </div>
                                                                </div>
                                                            </template>
                                                        </div>
                                                    </td>

                                                    {{-- UOM Column --}}
                                                    <td class="px-3 py-3">
                                                        <select :name="`lines[${idx}][uom_id]`" x-model="line.uom_id" required
                                                                class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                            <option value="">Select UOM</option>
                                                            @foreach($uoms as $u)
                                                                <option value="{{ $u->id }}">{{ $u->code }}{{ $u->name && $u->name !== $u->code ? ' - ' . $u->name : '' }}</option>
                                                            @endforeach
                                                        </select>
                                                        <template x-if="line.product_id && getProductUomOptions(line.product_id).length > 0">
                                                            <div class="mt-1 flex flex-wrap items-center gap-1 text-[10px]">
                                                                <template x-for="pu in getProductUomOptions(line.product_id)" :key="pu.id">
                                                                    <button type="button"
                                                                            @click="line.uom_id = String(pu.id)"
                                                                            class="px-1.5 py-0.5 rounded border text-[10px] font-medium transition cursor-pointer"
                                                                            :class="String(line.uom_id) === String(pu.id)
                                                                                ? 'bg-indigo-50 border-indigo-300 text-indigo-700 font-semibold'
                                                                                : 'bg-white border-slate-200 text-slate-600 hover:bg-slate-50'">
                                                                        <span x-text="pu.code"></span>
                                                                        <span x-show="pu.is_base" class="text-[8px] text-indigo-500 font-bold ml-0.5">(Base)</span>
                                                                    </button>
                                                                </template>
                                                            </div>
                                                        </template>
                                                        <template x-if="lineErrors[idx]?.uom_id">
                                                            <p class="mt-1 text-[10px] text-rose-600" x-text="lineErrors[idx].uom_id"></p>
                                                        </template>
                                                    </td>

                                                    {{-- Min Qty Column --}}
                                                    <td class="px-3 py-3">
                                                        <input type="number" step="0.01" min="0" :name="`lines[${idx}][min_qty]`" x-model="line.min_qty"
                                                               placeholder="e.g. 10"
                                                               class="block w-full rounded-md border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                                        <template x-if="lineErrors[idx]?.min_qty">
                                                            <p class="mt-1 text-[10px] text-rose-600" x-text="lineErrors[idx].min_qty"></p>
                                                        </template>
                                                    </td>

                                                    {{-- Action Column --}}
                                                    <td class="px-2 py-3 text-center">
                                                        <button type="button"
                                                                @click="removeBrandLine(line.product_id)"
                                                                title="Remove this product from price list"
                                                                class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition cursor-pointer font-bold text-sm">
                                                            &times;
                                                        </button>
                                                    </td>
                                                </tr>
                                            </template>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <x-ui.button type="submit" variant="primary">Save</x-ui.button>
        </form>

        {{-- Create Customer Type Modal --}}
        <div x-show="typeModalOpen"
             x-cloak
             class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             @keydown.escape.window="closeTypeModal()">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div x-show="typeModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 transition-opacity bg-slate-900/50 backdrop-blur-sm"
                     @click="closeTypeModal()"></div>

                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

                <div x-show="typeModalOpen"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative inline-block w-full max-w-md p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8"
                     @click.stop>

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-base font-semibold text-slate-800">Create Customer Type</h3>
                        <button type="button" @click="closeTypeModal()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div x-show="typeError" x-cloak class="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg text-xs font-medium text-red-700" x-text="typeError"></div>
                    <div x-show="typeSuccess" x-cloak class="mt-4 p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs font-medium text-emerald-700" x-text="typeSuccess"></div>

                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Name *</label>
                            <input type="text"
                                   x-ref="typeInput"
                                   x-model="newTypeName"
                                   :disabled="typeSaving"
                                   @keydown.enter.prevent="saveType()"
                                   placeholder="e.g. Retailer, Super Stockist"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100">
                        </div>
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" @click="closeTypeModal()" :disabled="typeSaving"
                                    class="px-4 py-2 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 disabled:opacity-50">
                                Cancel
                            </button>
                            <button type="button" @click="saveType()" :disabled="typeSaving"
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50">
                                <span x-text="typeSaving ? 'Saving...' : 'Save'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Copy from Existing Price List Modal -->
    <div x-show="copyModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         aria-labelledby="copy-modal-title">
        <div class="min-h-screen px-4 text-center">
            <div x-show="copyModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 @click="copyModalOpen = false"></div>

            <span class="inline-block h-screen align-middle" aria-hidden="true">&#8203;</span>

            <div x-show="copyModalOpen"
                 x-transition:enter="ease-out duration-200"
                 x-transition:enter-start="opacity-0 scale-95"
                 x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="ease-in duration-150"
                 x-transition:leave-start="opacity-100 scale-100"
                 x-transition:leave-end="opacity-0 scale-95"
                 class="inline-block w-full max-w-3xl my-8 text-left align-middle transition-all transform bg-white shadow-2xl rounded-2xl overflow-hidden border border-slate-200">

                <div class="px-6 py-4.5 bg-gradient-to-r from-slate-50 to-indigo-50/40 border-b border-slate-200 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center shadow-xs">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                            </svg>
                        </div>
                        <div>
                            <h3 id="copy-modal-title" class="text-base font-bold text-slate-900">Copy Pricing from Existing Price List</h3>
                            <p class="text-xs text-slate-500 mt-0.5">Quickly import product rates from an existing Customer Type or Brand price list</p>
                        </div>
                    </div>
                    <button type="button" @click="copyModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-5 max-h-[calc(85vh-130px)] overflow-y-auto">
                    {{-- Source Selection --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 p-4 bg-slate-50 rounded-xl border border-slate-200">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Source Price List Type</label>
                            <select x-model="copySourceType"
                                    @change="handleCopyTypeChange()"
                                    class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="customer_type">Customer Type Wise</option>
                                <option value="brand">Brand Wise</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                <span x-text="copySourceType === 'brand' ? 'Select Source Brand *' : 'Select Source Customer Type *'"></span>
                            </label>
                            <select x-model="copySourceId"
                                    @change="loadCopyPreview()"
                                    :disabled="copyLoadingOptions"
                                    class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100">
                                <option value="">Choose source list...</option>
                                <template x-if="copySourceType === 'customer_type'">
                                    <template x-for="ct in copyCustomerTypes" :key="ct.id">
                                        <option :value="ct.id" x-text="ct.label"></option>
                                    </template>
                                </template>
                                <template x-if="copySourceType === 'brand'">
                                    <template x-for="b in copyBrands" :key="b.id">
                                        <option :value="b.id" x-text="b.label"></option>
                                    </template>
                                </template>
                            </select>
                        </div>
                    </div>

                    {{-- Loading Indicator --}}
                    <div x-show="copyLoadingPreview" class="py-12 text-center">
                        <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto mb-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-xs text-slate-500">Loading products and rates from source...</p>
                    </div>

                    {{-- Empty State before selection --}}
                    <div x-show="!copyLoadingPreview && !copySourceId" class="py-10 text-center border-2 border-dashed border-slate-200 rounded-xl">
                        <p class="text-xs text-slate-500">Select a source above to preview and import its pricing configuration.</p>
                    </div>

                    {{-- Preview Table --}}
                    <div x-show="!copyLoadingPreview && copyPreviewItems.length > 0" class="space-y-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-bold uppercase tracking-wider text-slate-800">Preview Products & Rates</span>
                                <span class="text-xs bg-indigo-100 text-indigo-800 px-2 py-0.5 rounded-full font-semibold"
                                      x-text="`${selectedCopyCount} of ${copyPreviewItems.length} selected`"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <button type="button"
                                        @click="toggleAllCopyItems(true)"
                                        class="text-[11px] font-medium text-indigo-600 hover:text-indigo-800 cursor-pointer">Select All</button>
                                <span class="text-slate-300">|</span>
                                <button type="button"
                                        @click="toggleAllCopyItems(false)"
                                        class="text-[11px] font-medium text-slate-500 hover:text-slate-700 cursor-pointer">Deselect All</button>
                            </div>
                        </div>

                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-72 overflow-y-auto">
                            <table class="min-w-full divide-y divide-slate-200 text-xs">
                                <thead class="bg-slate-50 font-semibold text-slate-600 sticky top-0">
                                    <tr>
                                        <th class="px-3 py-2 text-left w-10">
                                            <input type="checkbox"
                                                   :checked="selectedCopyCount === copyPreviewItems.length && copyPreviewItems.length > 0"
                                                   @change="toggleAllCopyItems($event.target.checked)"
                                                   class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </th>
                                        <th class="px-3 py-2 text-left">Product</th>
                                        <th class="px-3 py-2 text-left">Unit</th>
                                        <th class="px-3 py-2 text-right">Dealer Price</th>
                                        <th class="px-3 py-2 text-right">Retail Price</th>
                                        <th class="px-3 py-2 text-right">General Rate</th>
                                        <th class="px-3 py-2 text-right">Min Qty</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="(item, idx) in copyPreviewItems" :key="item.price_master_id || idx">
                                        <tr class="hover:bg-slate-50/75 transition">
                                            <td class="px-3 py-2">
                                                <input type="checkbox"
                                                       x-model="copySelectedItems[idx]"
                                                       class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                            </td>
                                            <td class="px-3 py-2">
                                                <div class="font-medium text-slate-900" x-text="item.product_name"></div>
                                                <div class="text-[10px] font-mono text-slate-400" x-show="item.sku" x-text="item.sku"></div>
                                            </td>
                                            <td class="px-3 py-2 text-slate-600" x-text="item.uom_code || '—'"></td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <template x-if="item.dealer_price !== null && item.dealer_price !== undefined">
                                                    <div>
                                                        <span class="font-bold text-indigo-700" x-text="`₹${Number(item.dealer_price).toFixed(2)}`"></span>
                                                        <span class="text-[10px] text-slate-400 block" x-text="`(${item.dealer_price_gst_mode === 'inclusive' ? 'Inc.' : 'Exc.'} GST)`"></span>
                                                    </div>
                                                </template>
                                                <template x-if="item.dealer_price === null || item.dealer_price === undefined">
                                                    <span class="text-slate-400">—</span>
                                                </template>
                                            </td>
                                            <td class="px-3 py-2 text-right whitespace-nowrap">
                                                <template x-if="item.retail_price !== null && item.retail_price !== undefined">
                                                    <div>
                                                        <span class="font-bold text-emerald-700" x-text="`₹${Number(item.retail_price).toFixed(2)}`"></span>
                                                        <span class="text-[10px] text-slate-400 block" x-text="`(${item.retail_price_gst_mode === 'inclusive' ? 'Inc.' : 'Exc.'} GST)`"></span>
                                                    </div>
                                                </template>
                                                <template x-if="item.retail_price === null || item.retail_price === undefined">
                                                    <span class="text-slate-400">—</span>
                                                </template>
                                            </td>
                                            <td class="px-3 py-2 text-right text-slate-700 whitespace-nowrap"
                                                x-text="item.rate !== null && item.rate !== undefined ? `₹${Number(item.rate).toFixed(2)}` : '—'"></td>
                                            <td class="px-3 py-2 text-right text-slate-600" x-text="item.min_qty || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
                    <button type="button"
                            @click="copyModalOpen = false"
                            class="px-4 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 transition shadow-2xs">
                        Cancel
                    </button>

                    <button type="button"
                            @click="applyCopiedPricing()"
                            :disabled="selectedCopyCount === 0"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-500 transition shadow-sm disabled:opacity-40 cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Apply Copied Pricing (<span x-text="selectedCopyCount"></span>)</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</x-ui.card>

@push('scripts')
<script>
function priceMasterForm(config) {
    const serverLineErrors = @json($serverLineErrors);

    return {
        isEdit: !!config.isEdit,
        type: String(config.type || 'customer_type'),
        priceListName: String(config.name || ''),
        selectedBrandId: String(config.brandId || ''),
        previousBrandId: String(config.brandId || ''),
        brands: config.brands || [],
        products: config.products || [],
        uoms: config.uoms || [],
        lines: (config.lines || []).map((line) => ({
            product_id: String(line.product_id || ''),
            uom_id: String(line.uom_id || ''),
            dealer_price: line.dealer_price ?? (line.rate ?? ''),
            dealer_price_gst_mode: line.dealer_price_gst_mode === 'inclusive' ? 'inclusive' : 'exclusive',
            retail_price: line.retail_price ?? '',
            retail_price_gst_mode: line.retail_price_gst_mode === 'inclusive' ? 'inclusive' : 'exclusive',
            min_qty: line.min_qty ?? '',
            searchQuery: line.searchQuery || '',
            isOpen: false,
            isLoadingDetails: false,
        })),
        selectedCustomerTypeId: String(config.customerTypeId || ''),
        previousCustomerTypeId: String(config.customerTypeId || ''),
        customTypes: [],
        knownTypeNames: config.knownTypeNames || [],
        typeModalOpen: false,
        newTypeName: '',
        typeSaving: false,
        typeError: '',
        typeSuccess: '',
        lineErrors: {},

        editProductId: String(config.editProductId || ''),
        editProductQuery: '',
        editProductOpen: false,
        isEditLoadingDetails: false,
        editUomId: String(config.editUomId || ''),
        editDealerPrice: config.editDealerPrice ?? (config.editRate ?? ''),
        editDealerGstMode: config.editDealerGstMode === 'inclusive' ? 'inclusive' : 'exclusive',
        editRetailPrice: config.editRetailPrice ?? '',
        editRetailGstMode: config.editRetailGstMode === 'inclusive' ? 'inclusive' : 'exclusive',
        editMinQty: config.editMinQty ?? '',

        brandProductsList: [],
        isLoadingBrandProducts: false,
        brandProductsError: '',
        brandSearchQuery: '',
        brandPage: 1,
        brandPerPage: 10,
        selectedProductIds: [],
        bulkPricingOpen: false,
        bulkDealerPrice: '',
        bulkDealerGstMode: 'exclusive',
        bulkRetailPrice: '',
        bulkRetailGstMode: 'exclusive',
        bulkMinQty: '',
        bulkAppliedToast: false,

        copyModalOpen: false,
        copyLoadingOptions: false,
        copyLoadingPreview: false,
        copyCustomerTypes: [],
        copyBrands: [],
        copySourceType: 'customer_type',
        copySourceId: '',
        copyPreviewItems: [],
        copySelectedItems: {},

        get selectedCopyCount() {
            return Object.values(this.copySelectedItems).filter(Boolean).length;
        },

        init() {
            if (this.type === 'brand') {
                this.selectedProductIds = this.lines.map(l => String(l.product_id)).filter(Boolean);
                if (this.selectedBrandId) {
                    this.loadBrandProducts();
                }
            }

            // Hydrate product search labels after old() / edit load
            this.lines.forEach((line) => {
                if (line.product_id) {
                    const prod = this.getProductDetails(line.product_id);
                    if (prod) {
                        line.searchQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
                        if (!line.uom_id && prod.base_uom_id) {
                            line.uom_id = String(prod.base_uom_id);
                        }
                    }
                    this.fetchProductDetails(line.product_id, line);
                }
            });

            if (this.editProductId) {
                const prod = this.getProductDetails(this.editProductId);
                if (prod) {
                    this.editProductQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
                    if (!this.editUomId && prod.base_uom_id) {
                        this.editUomId = String(prod.base_uom_id);
                    }
                }
                this.fetchProductDetails(this.editProductId);
            }

            // Map server validation errors into lineErrors[idx][field]
            const mapped = {};
            Object.keys(serverLineErrors || {}).forEach((key) => {
                const [idx, field] = key.split('.');
                if (!mapped[idx]) mapped[idx] = {};
                mapped[idx][field] = serverLineErrors[key];
            });
            this.lineErrors = mapped;
        },

        getProductDetails(productId) {
            if (!productId) return null;
            return this.products.find(p => String(p.id) === String(productId)) || null;
        },

        getProductUomOptions(productId) {
            const prod = this.getProductDetails(productId);
            if (!prod) return [];
            if (Array.isArray(prod.uoms) && prod.uoms.length > 0) {
                return prod.uoms;
            }
            const list = [];
            if (prod.base_uom_id) {
                const masterUom = this.uoms.find(u => String(u.id) === String(prod.base_uom_id));
                list.push({
                    id: String(prod.base_uom_id),
                    code: prod.base_uom_code || masterUom?.code || 'Base',
                    name: prod.base_uom_name || masterUom?.name || 'Base UOM',
                    is_base: true,
                    label: 'Base UOM',
                });
            }
            return list;
        },

        async fetchProductDetails(productId, line = null) {
            if (!productId) return null;
            if (line) line.isLoadingDetails = true;
            if (this.isEdit && String(this.editProductId) === String(productId)) {
                this.isEditLoadingDetails = true;
            }

            try {
                const response = await fetch(`/masters/price-masters/products/${productId}/details`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const data = await response.json();
                    if (data.success && data.data) {
                        const fetched = data.data;
                        const idx = this.products.findIndex(p => String(p.id) === String(productId));
                        if (idx !== -1) {
                            this.products[idx] = { ...this.products[idx], ...fetched };
                        } else {
                            this.products.push(fetched);
                        }
                        if (line && (!line.uom_id || line.uom_id === '') && fetched.base_uom_id) {
                            line.uom_id = String(fetched.base_uom_id);
                        }
                        if (this.isEdit && (!this.editUomId || this.editUomId === '') && fetched.base_uom_id) {
                            this.editUomId = String(fetched.base_uom_id);
                        }
                        return fetched;
                    }
                }
            } catch (e) {
                console.error('Error fetching product details:', e);
            } finally {
                if (line) line.isLoadingDetails = false;
                if (this.isEdit && String(this.editProductId) === String(productId)) {
                    this.isEditLoadingDetails = false;
                }
            }
            return this.getProductDetails(productId);
        },

        get brandProducts() {
            if (!this.selectedBrandId) return [];
            return this.products.filter(p => String(p.brand_id) === String(this.selectedBrandId));
        },

        get hasProductsForSelectedBrand() {
            if (!this.selectedBrandId) return true;
            return this.brandProducts.length > 0;
        },

        handleTypeChange(newType) {
            if (this.lines.length > 0 && this.lines.some(l => l.product_id)) {
                if (!confirm('Changing the price list type will reset the products you have selected. Do you want to continue?')) {
                    this.$nextTick(() => { this.type = this.type === 'brand' ? 'customer_type' : 'brand'; });
                    return;
                }
            }
            this.type = newType;
            if (newType === 'customer_type') {
                this.selectedProductIds = [];
                this.lines = [{
                    product_id: '',
                    uom_id: '',
                    dealer_price: '',
                    dealer_price_gst_mode: 'exclusive',
                    retail_price: '',
                    retail_price_gst_mode: 'exclusive',
                    min_qty: '',
                    searchQuery: '',
                    isOpen: false,
                    isLoadingDetails: false,
                }];
            } else {
                this.lines = [];
                this.selectedProductIds = [];
                if (this.selectedBrandId) {
                    this.loadBrandProducts();
                }
            }
        },

        handleBrandChange() {
            const hasProductsSelected = this.lines.some(l => l.product_id);
            if (hasProductsSelected && this.previousBrandId && this.previousBrandId !== this.selectedBrandId) {
                if (confirm('Changing the brand will clear the products you have selected. Do you want to continue?')) {
                    this.lines = [];
                    this.selectedProductIds = [];
                    this.brandSearchQuery = '';
                    this.brandPage = 1;
                    this.previousBrandId = this.selectedBrandId;
                    this.loadBrandProducts();
                } else {
                    this.selectedBrandId = this.previousBrandId;
                }
            } else {
                this.lines = [];
                this.selectedProductIds = [];
                this.brandSearchQuery = '';
                this.brandPage = 1;
                this.previousBrandId = this.selectedBrandId;
                if (this.selectedBrandId) {
                    this.loadBrandProducts();
                } else {
                    this.brandProductsList = [];
                }
            }
        },

        async loadBrandProducts() {
            if (!this.selectedBrandId) {
                this.brandProductsList = [];
                return;
            }
            this.isLoadingBrandProducts = true;
            this.brandProductsError = '';
            try {
                const response = await fetch(`/masters/price-masters/brands/${this.selectedBrandId}/products`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (response.ok) {
                    const result = await response.json();
                    if (result.success && Array.isArray(result.data)) {
                        this.brandProductsList = result.data;
                        result.data.forEach(p => {
                            const existingIdx = this.products.findIndex(prod => String(prod.id) === String(p.id));
                            if (existingIdx !== -1) {
                                this.products[existingIdx] = { ...this.products[existingIdx], ...p };
                            } else {
                                this.products.push(p);
                            }
                        });
                    } else {
                        this.brandProductsError = result.message || 'Failed to load brand products.';
                    }
                } else {
                    this.brandProductsError = 'Server error loading brand products.';
                }
            } catch (e) {
                console.error('Error loading brand products:', e);
                this.brandProductsError = 'Network error loading brand products.';
            } finally {
                this.isLoadingBrandProducts = false;
            }
        },

        get filteredBrandProducts() {
            const q = (this.brandSearchQuery || '').toLowerCase().trim();
            if (!q) {
                return this.brandProductsList;
            }
            return this.brandProductsList.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.sku && String(p.sku).toLowerCase().includes(q)) ||
                (p.model && String(p.model).toLowerCase().includes(q)) ||
                (p.category_name && p.category_name.toLowerCase().includes(q)) ||
                (p.sub_category_name && p.sub_category_name.toLowerCase().includes(q))
            );
        },

        get totalBrandProductsCount() {
            return this.brandProductsList.length;
        },

        get matchingBrandProductsCount() {
            return this.filteredBrandProducts.length;
        },

        get selectedBrandProductsCount() {
            return this.selectedProductIds.length;
        },

        get brandTotalPages() {
            if (this.brandPerPage <= 0) return 1;
            return Math.max(1, Math.ceil(this.filteredBrandProducts.length / this.brandPerPage));
        },

        get pagedBrandProducts() {
            if (this.brandPerPage <= 0) return this.filteredBrandProducts;
            const start = (this.brandPage - 1) * this.brandPerPage;
            return this.filteredBrandProducts.slice(start, start + this.brandPerPage);
        },

        isProductSelected(productId) {
            return this.selectedProductIds.includes(String(productId));
        },

        get isAllMatchingSelected() {
            const list = this.filteredBrandProducts;
            if (list.length === 0) return false;
            return list.every(p => this.isProductSelected(p.id));
        },

        toggleProductSelection(prod) {
            const pid = String(prod.id);
            const idx = this.selectedProductIds.indexOf(pid);
            if (idx === -1) {
                this.selectedProductIds.push(pid);
                this.lines.push({
                    product_id: pid,
                    uom_id: prod.base_uom_id ? String(prod.base_uom_id) : '',
                    dealer_price: this.bulkDealerPrice || '',
                    dealer_price_gst_mode: this.bulkDealerGstMode || 'exclusive',
                    retail_price: this.bulkRetailPrice || '',
                    retail_price_gst_mode: this.bulkRetailGstMode || 'exclusive',
                    min_qty: this.bulkMinQty || '',
                    searchQuery: `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`,
                    isOpen: false,
                    isLoadingDetails: false,
                });
            } else {
                this.selectedProductIds.splice(idx, 1);
                this.lines = this.lines.filter(l => String(l.product_id) !== pid);
            }
        },

        toggleSelectAllMatching() {
            const list = this.filteredBrandProducts;
            if (this.isAllMatchingSelected) {
                const matchingIds = list.map(p => String(p.id));
                this.selectedProductIds = this.selectedProductIds.filter(id => !matchingIds.includes(id));
                this.lines = this.lines.filter(l => !matchingIds.includes(String(l.product_id)));
            } else {
                list.forEach(prod => {
                    const pid = String(prod.id);
                    if (!this.selectedProductIds.includes(pid)) {
                        this.selectedProductIds.push(pid);
                        this.lines.push({
                            product_id: pid,
                            uom_id: prod.base_uom_id ? String(prod.base_uom_id) : '',
                            dealer_price: this.bulkDealerPrice || '',
                            dealer_price_gst_mode: this.bulkDealerGstMode || 'exclusive',
                            retail_price: this.bulkRetailPrice || '',
                            retail_price_gst_mode: this.bulkRetailGstMode || 'exclusive',
                            min_qty: this.bulkMinQty || '',
                            searchQuery: `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`,
                            isOpen: false,
                            isLoadingDetails: false,
                        });
                    }
                });
            }
        },

        selectAllBrandProducts() {
            this.brandProductsList.forEach(prod => {
                const pid = String(prod.id);
                if (!this.selectedProductIds.includes(pid)) {
                    this.selectedProductIds.push(pid);
                    this.lines.push({
                        product_id: pid,
                        uom_id: prod.base_uom_id ? String(prod.base_uom_id) : '',
                        dealer_price: this.bulkDealerPrice || '',
                        dealer_price_gst_mode: this.bulkDealerGstMode || 'exclusive',
                        retail_price: this.bulkRetailPrice || '',
                        retail_price_gst_mode: this.bulkRetailGstMode || 'exclusive',
                        min_qty: this.bulkMinQty || '',
                        searchQuery: `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`,
                        isOpen: false,
                        isLoadingDetails: false,
                    });
                }
            });
        },

        deselectAllBrandProducts() {
            this.selectedProductIds = [];
            this.lines = [];
        },

        removeBrandLine(productId) {
            const pid = String(productId);
            this.selectedProductIds = this.selectedProductIds.filter(id => id !== pid);
            this.lines = this.lines.filter(l => String(l.product_id) !== pid);
        },

        applyBulkPricing() {
            if (this.lines.length === 0) return;
            this.lines.forEach(line => {
                if (this.bulkDealerPrice !== '' && this.bulkDealerPrice !== null) {
                    line.dealer_price = this.bulkDealerPrice;
                    line.dealer_price_gst_mode = this.bulkDealerGstMode;
                }
                if (this.bulkRetailPrice !== '' && this.bulkRetailPrice !== null) {
                    line.retail_price = this.bulkRetailPrice;
                    line.retail_price_gst_mode = this.bulkRetailGstMode;
                }
                if (this.bulkMinQty !== '' && this.bulkMinQty !== null) {
                    line.min_qty = this.bulkMinQty;
                }
            });
            this.bulkAppliedToast = true;
            setTimeout(() => {
                this.bulkAppliedToast = false;
            }, 3000);
        },

        openTypeModal() {
            this.typeModalOpen = true;
            this.newTypeName = '';
            this.typeError = '';
            this.typeSuccess = '';
            this.$nextTick(() => this.$refs.typeInput?.focus());
        },
        closeTypeModal() {
            this.typeModalOpen = false;
            this.typeError = '';
            this.typeSuccess = '';
        },
        async saveType() {
            const name = (this.newTypeName || '').trim();
            if (!name) {
                this.typeError = 'Name is required.';
                return;
            }
            if (this.knownTypeNames.includes(name.toLowerCase())) {
                this.typeError = 'A customer type with this name already exists.';
                return;
            }

            this.typeSaving = true;
            this.typeError = '';
            this.typeSuccess = '';

            try {
                const response = await fetch("{{ route('masters.customer-types.quick-store') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ name })
                });
                const data = await response.json();

                if (!response.ok || !data.success) {
                    this.typeError = data.message || 'Failed to save customer type.';
                    this.typeSaving = false;
                    return;
                }

                const newId = String(data.item.id);
                if (!this.customTypes.some(t => String(t.id) === newId)) {
                    this.customTypes.push({ id: data.item.id, name: data.item.name });
                }
                this.knownTypeNames.push(name.toLowerCase());
                this.typeSuccess = data.message || 'Created successfully.';
                this.selectedCustomerTypeId = newId;
                this.previousCustomerTypeId = newId;

                setTimeout(() => this.closeTypeModal(), 600);
            } catch (e) {
                this.typeError = 'Unable to save customer type. Please try again.';
            } finally {
                this.typeSaving = false;
            }
        },

        async openCopyModal() {
            this.copyModalOpen = true;
            this.copySourceType = (this.type === 'brand') ? 'brand' : 'customer_type';
            this.copySourceId = '';
            this.copyPreviewItems = [];
            this.copySelectedItems = {};
            await this.loadCopyOptions();
        },

        async loadCopyOptions() {
            this.copyLoadingOptions = true;
            try {
                const response = await fetch(`{{ route('masters.price-masters.copy-options') }}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const json = await response.json();
                if (response.ok && json.success) {
                    this.copyCustomerTypes = json.customer_types || [];
                    this.copyBrands = json.brands || [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.copyLoadingOptions = false;
            }
        },

        handleCopyTypeChange() {
            this.copySourceId = '';
            this.copyPreviewItems = [];
            this.copySelectedItems = {};
        },

        async loadCopyPreview() {
            if (!this.copySourceId) {
                this.copyPreviewItems = [];
                this.copySelectedItems = {};
                return;
            }
            this.copyLoadingPreview = true;
            this.copyPreviewItems = [];
            this.copySelectedItems = {};
            try {
                const url = `{{ route('masters.price-masters.copy-source') }}?type=${encodeURIComponent(this.copySourceType)}&source_id=${encodeURIComponent(this.copySourceId)}`;
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const json = await response.json();
                if (response.ok && json.success) {
                    this.copyPreviewItems = json.items || [];
                    const selected = {};
                    this.copyPreviewItems.forEach((_, idx) => { selected[idx] = true; });
                    this.copySelectedItems = selected;
                } else {
                    alert(json.message || 'No records found to copy.');
                }
            } catch (e) {
                console.error(e);
                alert('Error loading copy preview.');
            } finally {
                this.copyLoadingPreview = false;
            }
        },

        toggleAllCopyItems(checked) {
            const next = {};
            this.copyPreviewItems.forEach((_, idx) => { next[idx] = !!checked; });
            this.copySelectedItems = next;
        },

        applyCopiedPricing() {
            const selectedIndices = Object.keys(this.copySelectedItems).filter(idx => this.copySelectedItems[idx]);
            if (selectedIndices.length === 0) {
                alert('Please select at least one product to copy.');
                return;
            }

            const itemsToCopy = selectedIndices.map(idx => this.copyPreviewItems[idx]).filter(Boolean);

            if (this.type === 'customer_type') {
                if (this.lines.length === 1 && !this.lines[0].product_id) {
                    this.lines = [];
                }

                itemsToCopy.forEach(item => {
                    const existingProd = this.getProductDetails(item.product_id);
                    if (!existingProd) {
                        this.products.push({
                            id: item.product_id,
                            name: item.product_name,
                            sku: item.sku || '',
                            part_number: item.sku || '',
                            brand_name: item.brand_name || '',
                            category_name: item.category_name || '',
                            sub_category_name: item.sub_category_name || '',
                            product_type_name: item.product_type_name || '',
                            hsn_code: item.hsn_code || '',
                            tax_rate: item.tax_rate ?? 0,
                            mrp: item.mrp || null,
                            base_uom_id: String(item.uom_id || ''),
                            base_uom_code: item.uom_code || '',
                            base_uom_name: item.uom_name || '',
                            uoms: item.uom_id ? [{ id: String(item.uom_id), code: item.uom_code || '', name: item.uom_name || '', is_base: true }] : [],
                        });
                    }

                    const existingLine = this.lines.find(l => String(l.product_id) === String(item.product_id));
                    if (existingLine) {
                        existingLine.dealer_price = item.dealer_price !== null && item.dealer_price !== undefined ? item.dealer_price : '';
                        existingLine.dealer_price_gst_mode = item.dealer_price_gst_mode || 'exclusive';
                        existingLine.retail_price = item.retail_price !== null && item.retail_price !== undefined ? item.retail_price : '';
                        existingLine.retail_price_gst_mode = item.retail_price_gst_mode || 'exclusive';
                        existingLine.min_qty = item.min_qty !== null && item.min_qty !== undefined ? item.min_qty : '';
                        if (item.uom_id) existingLine.uom_id = String(item.uom_id);
                    } else {
                        this.lines.push({
                            product_id: String(item.product_id),
                            uom_id: String(item.uom_id || ''),
                            dealer_price: item.dealer_price !== null && item.dealer_price !== undefined ? item.dealer_price : '',
                            dealer_price_gst_mode: item.dealer_price_gst_mode || 'exclusive',
                            retail_price: item.retail_price !== null && item.retail_price !== undefined ? item.retail_price : '',
                            retail_price_gst_mode: item.retail_price_gst_mode || 'exclusive',
                            min_qty: item.min_qty !== null && item.min_qty !== undefined ? item.min_qty : '',
                            searchQuery: `${item.product_name}${item.sku ? ' (' + item.sku + ')' : ''}`,
                            isOpen: false,
                            isLoadingDetails: false,
                        });
                    }
                });
            } else if (this.type === 'brand') {
                itemsToCopy.forEach(item => {
                    const existingLine = this.lines.find(l => String(l.product_id) === String(item.product_id));
                    if (existingLine) {
                        existingLine.dealer_price = item.dealer_price !== null && item.dealer_price !== undefined ? item.dealer_price : '';
                        existingLine.dealer_price_gst_mode = item.dealer_price_gst_mode || 'exclusive';
                        existingLine.retail_price = item.retail_price !== null && item.retail_price !== undefined ? item.retail_price : '';
                        existingLine.retail_price_gst_mode = item.retail_price_gst_mode || 'exclusive';
                        existingLine.min_qty = item.min_qty !== null && item.min_qty !== undefined ? item.min_qty : '';
                        if (item.uom_id) existingLine.uom_id = String(item.uom_id);
                    } else {
                        this.lines.push({
                            product_id: String(item.product_id),
                            uom_id: String(item.uom_id || ''),
                            dealer_price: item.dealer_price !== null && item.dealer_price !== undefined ? item.dealer_price : '',
                            dealer_price_gst_mode: item.dealer_price_gst_mode || 'exclusive',
                            retail_price: item.retail_price !== null && item.retail_price !== undefined ? item.retail_price : '',
                            retail_price_gst_mode: item.retail_price_gst_mode || 'exclusive',
                            min_qty: item.min_qty !== null && item.min_qty !== undefined ? item.min_qty : '',
                            searchQuery: `${item.product_name}${item.sku ? ' (' + item.sku + ')' : ''}`,
                            isOpen: false,
                            isLoadingDetails: false,
                        });
                    }
                    if (!this.selectedProductIds.includes(String(item.product_id))) {
                        this.selectedProductIds.push(String(item.product_id));
                    }
                });
            }

            this.copyModalOpen = false;
        },

        addLine() {
            this.lines.push({
                product_id: '',
                uom_id: '',
                dealer_price: '',
                dealer_price_gst_mode: 'exclusive',
                retail_price: '',
                retail_price_gst_mode: 'exclusive',
                min_qty: '',
                searchQuery: '',
                isOpen: false,
                isLoadingDetails: false,
            });
        },
        removeLine(idx) {
            if (this.lines.length <= 1) return;
            this.lines.splice(idx, 1);
        },
        filteredProducts(line) {
            const pool = (this.type === 'brand') ? this.brandProducts : this.products;
            const q = (line.searchQuery || '').toLowerCase().trim();
            // Selected product keeps the label in the input; show browse list until user types (clears product_id).
            if (!q || line.product_id) {
                return pool.slice(0, 40);
            }
            return pool.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.sku && String(p.sku).toLowerCase().includes(q))
            ).slice(0, 40);
        },
        selectLineProduct(idx, prod) {
            const line = this.lines[idx];
            if (!line) return;
            line.product_id = String(prod.id);
            line.searchQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
            line.isOpen = false;
            line.uom_id = prod.base_uom_id ? String(prod.base_uom_id) : '';
            this.fetchProductDetails(prod.id, line);
        },
        clearLineProduct(idx) {
            const line = this.lines[idx];
            if (!line) return;
            line.product_id = '';
            line.uom_id = '';
            line.searchQuery = '';
            line.isOpen = false;
            line.isLoadingDetails = false;
        },

        get filteredEditProducts() {
            const pool = (this.type === 'brand') ? this.brandProducts : this.products;
            const q = (this.editProductQuery || '').toLowerCase().trim();
            if (!q || this.editProductId) {
                return pool.slice(0, 40);
            }
            return pool.filter(p =>
                (p.name && p.name.toLowerCase().includes(q)) ||
                (p.sku && String(p.sku).toLowerCase().includes(q))
            ).slice(0, 40);
        },
        selectEditProduct(prod) {
            this.editProductId = String(prod.id);
            this.editProductQuery = `${prod.name}${prod.sku ? ' (' + prod.sku + ')' : ''}`;
            this.editProductOpen = false;
            this.editUomId = prod.base_uom_id ? String(prod.base_uom_id) : '';
            this.fetchProductDetails(prod.id);
        },
        productById(id) {
            return this.products.find(p => String(p.id) === String(id)) || null;
        },
        taxPercent(productId) {
            const tax = parseFloat(this.productById(productId)?.tax_rate);
            return Number.isFinite(tax) && tax > 0 ? tax : 0;
        },
        money(value) {
            const amount = Number(value);
            if (!Number.isFinite(amount)) return '0.00';
            return (Math.round((amount + Number.EPSILON) * 100) / 100).toFixed(2);
        },
        calculatePricing(entered, mode, productId) {
            const amount = parseFloat(entered);
            if (!Number.isFinite(amount) || amount < 0) {
                return null;
            }
            const tax = this.taxPercent(productId);
            const isInclusive = mode === 'inclusive';
            let base, gst, inclusive;

            if (isInclusive && tax > 0) {
                base = amount * 100 / (100 + tax);
                gst = amount - base;
                inclusive = amount;
            } else {
                base = amount;
                gst = base * tax / 100;
                inclusive = base + gst;
            }

            return {
                entered: this.money(amount),
                base: this.money(base),
                gst: this.money(gst),
                inclusive: this.money(inclusive),
                taxPercent: tax,
                mode: isInclusive ? 'inclusive' : 'exclusive',
            };
        },
        lineDealerPricing(line) {
            return this.calculatePricing(line?.dealer_price, line?.dealer_price_gst_mode, line?.product_id);
        },
        lineRetailPricing(line) {
            return this.calculatePricing(line?.retail_price, line?.retail_price_gst_mode, line?.product_id);
        },
        editDealerPricing() {
            return this.calculatePricing(this.editDealerPrice, this.editDealerGstMode, this.editProductId);
        },
        editRetailPricing() {
            return this.calculatePricing(this.editRetailPrice, this.editRetailGstMode, this.editProductId);
        },
        splitRate(entered, mode, productId) {
            const res = this.calculatePricing(entered, mode, productId);
            return res ? { gst: res.gst, final: res.inclusive } : { gst: '0.00', final: '0.00' };
        },
        lineSplit(line) {
            return this.splitRate(line?.dealer_price || line?.retail_price || line?.rate, line?.dealer_price_gst_mode || line?.gst_mode, line?.product_id);
        },
        editSplit() {
            return this.splitRate(this.editDealerPrice || this.editRetailPrice || this.editRate, this.editDealerGstMode || this.editGstMode, this.editProductId);
        },
        clearEditProduct() {
            this.editProductId = '';
            this.editUomId = '';
            this.editProductQuery = '';
            this.editProductOpen = false;
            this.isEditLoadingDetails = false;
        },
    };
}
</script>
@endpush
@endsection
