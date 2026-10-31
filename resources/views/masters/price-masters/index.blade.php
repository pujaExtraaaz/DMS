@extends('layouts.dms')
@section('title', 'Price Master')
@section('content')
<div id="listing-container" data-dynamic-container x-data="priceMasterIndex()">
    <x-ui.page-header title="Price Master">
        <x-slot name="actions">
            <x-ui.export-menu />
            <x-ui.button variant="primary" :href="route('masters.price-masters.create')">+ Add Price</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search Product, Customer Type, Brand, Name..."
            :reset-url="route('masters.price-masters.index')"
        >
            <x-slot name="filters">
                <select
                    name="type"
                    data-dynamic-filter
                    aria-label="Filter by Price List Type"
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Type: All</option>
                    <option value="customer_type" @selected(request('type') === 'customer_type')>Customer Type Wise</option>
                    <option value="brand" @selected(request('type') === 'brand')>Brand Wise</option>
                </select>

                <select
                    name="customer_type_id"
                    data-dynamic-filter
                    aria-label="Filter by Customer Type"
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Customer Type: All</option>
                    @foreach($customerTypes as $ct)
                        <option value="{{ $ct->id }}" @selected(request('customer_type_id') == $ct->id)>{{ $ct->name }}</option>
                    @endforeach
                </select>

                <select
                    name="brand_id"
                    data-dynamic-filter
                    aria-label="Filter by Brand"
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Brand: All</option>
                    @foreach($brands ?? [] as $b)
                        <option value="{{ $b->id }}" @selected(request('brand_id') == $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>

                <select
                    name="product_id"
                    data-dynamic-filter
                    aria-label="Filter by Product"
                    class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                >
                    <option value="">Product: All</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected(request('product_id') == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
            </x-slot>
        </x-ui.table-toolbar>

        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <x-ui.sortable-th column="type" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Type
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">
                        Customer Type / Brand
                    </th>
                    <x-ui.sortable-th column="product" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')">
                        Product Name
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">
                        SKU / Part No
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">
                        Category
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500">
                        Unit
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">
                        Dealer Price
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">
                        Retail Price
                    </th>
                    <x-ui.sortable-th column="rate" :current-sort="$sort ?? request('sort', 'product')" :current-direction="$direction ?? request('direction', 'asc')" align="right">
                        General Rate
                    </x-ui.sortable-th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">
                        Min Qty
                    </th>
                    <th class="px-6 py-3 text-right text-xs font-semibold text-gray-500">
                        Actions
                    </th>
                </tr>
            </x-slot>
            @forelse($items as $item)
                @php
                    $pricing = $item->pricingSummary();
                    $dealerPricing = $item->dealerPricingSummary();
                    $retailPricing = $item->retailPricingSummary();
                @endphp
                <tr class="hover:bg-slate-50/75 transition-colors">
                    {{-- 1. Type --}}
                    <td class="px-6 py-4 text-xs whitespace-nowrap">
                        @if($item->isBrandWise())
                            <span class="inline-flex items-center rounded-full bg-purple-50 px-2.5 py-0.5 text-xs font-medium text-purple-700 border border-purple-200/60">
                                Brand Wise
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-700 border border-blue-200/60">
                                Customer Type
                            </span>
                        @endif
                    </td>

                    {{-- 2. Customer Type / Brand --}}
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        <div>{{ $item->isBrandWise() ? ($item->brand?->name ?: '—') : ($item->customerType?->name ?: '—') }}</div>
                        @if($item->name)
                            <div class="text-[11px] text-slate-400 font-normal mt-0.5">{{ $item->name }}</div>
                        @endif
                    </td>

                    {{-- 3. Product Name --}}
                    <td class="px-6 py-4 text-sm font-medium text-slate-900">
                        <div>{{ $item->product?->name ?: '—' }}</div>
                    </td>

                    {{-- 4. SKU / Part No --}}
                    <td class="px-6 py-4 text-sm font-mono text-slate-600 whitespace-nowrap">
                        {{ $item->product?->sku ?: '—' }}
                    </td>

                    {{-- 5. Category --}}
                    <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                        {{ $item->product?->category?->name ?: '—' }}
                    </td>

                    {{-- 6. Unit --}}
                    <td class="px-6 py-4 text-sm text-slate-600 whitespace-nowrap">
                        {{ $item->uom?->code ?: ($item->uom?->name ?: '—') }}
                    </td>

                    {{-- 7. Dealer Price --}}
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-900 whitespace-nowrap">
                        @if($dealerPricing['entered'] !== null)
                            <div class="font-bold text-indigo-700">₹{{ number_format($dealerPricing['entered'], 2) }}</div>
                            <div class="text-[10px] text-slate-500 font-normal">({{ $dealerPricing['gst_mode'] === 'inclusive' ? 'Inc.' : 'Exc.' }} GST)</div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- 8. Retail Price --}}
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-900 whitespace-nowrap">
                        @if($retailPricing['entered'] !== null)
                            <div class="font-bold text-emerald-700">₹{{ number_format($retailPricing['entered'], 2) }}</div>
                            <div class="text-[10px] text-slate-500 font-normal">({{ $retailPricing['gst_mode'] === 'inclusive' ? 'Inc.' : 'Exc.' }} GST)</div>
                        @else
                            <span class="text-slate-400">—</span>
                        @endif
                    </td>

                    {{-- 9. General Rate --}}
                    <td class="px-6 py-4 text-sm text-right font-medium text-slate-900 whitespace-nowrap">
                        <div>₹{{ number_format($pricing['entered'], 2) }}</div>
                        <div class="mt-0.5 text-[11px] font-normal text-slate-500">
                            {{ $pricing['includes_gst'] ? 'Inc. GST' : 'Exc. GST' }}
                        </div>
                    </td>

                    {{-- 10. Min Qty --}}
                    <td class="px-6 py-4 text-sm text-right text-slate-600 whitespace-nowrap">
                        {{ $item->min_qty !== null ? $item->min_qty : '—' }}
                    </td>

                    {{-- 11. Actions --}}
                    <td class="px-6 py-4 text-right whitespace-nowrap">
                        <div class="flex items-center justify-end gap-1.5">
                            <a href="{{ route('masters.price-masters.show', $item) }}"
                               data-view-price="{{ $item->id }}"
                               class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-slate-700 hover:text-indigo-600 hover:bg-slate-100 transition border border-slate-200/80 shadow-2xs"
                               title="View Details">
                                <svg class="w-3.5 h-3.5 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>View</span>
                            </a>
                            <button type="button"
                                    @click="openEditModal({{ $item->id }})"
                                    data-edit-price="{{ $item->id }}"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-indigo-700 hover:text-indigo-800 hover:bg-indigo-50 transition border border-indigo-200/80 shadow-2xs"
                                    title="Edit Price">
                                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <span>Edit</span>
                            </button>
                            <button type="button"
                                    @click="promptDelete({
                                        id: '{{ $item->id }}',
                                        type: '{{ $item->isBrandWise() ? 'Brand Wise' : 'Customer Type' }}',
                                        target: '{{ addslashes($item->isBrandWise() ? ($item->brand?->name ?? '—') : ($item->customerType?->name ?? '—')) }}',
                                        customerType: '{{ addslashes($item->isBrandWise() ? ($item->brand?->name ?? '—') : ($item->customerType?->name ?? '—')) }}',
                                        product: '{{ addslashes($item->product?->name ?? '—') }}',
                                        unit: '{{ addslashes($item->uom?->code ?? '—') }}',
                                        rate: '₹{{ number_format($pricing['entered'], 2) }}',
                                        minQty: '{{ $item->min_qty !== null ? $item->min_qty : '—' }}'
                                    })"
                                    data-delete-price="{{ $item->id }}"
                                    data-delete-price-master="{{ $item->id }}"
                                    class="inline-flex items-center gap-1 rounded-md px-2 py-1 text-xs font-semibold text-red-600 hover:text-red-700 hover:bg-red-50 transition border border-transparent hover:border-red-200"
                                    title="Delete Price">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                                <span>Delete</span>
                            </button>
                            <form id="delete-form-{{ $item->id }}" method="POST" action="{{ route('masters.price-masters.destroy', $item) }}" class="hidden">
                                @csrf
                                @method('DELETE')
                            </form>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="p-0">
                        <x-ui.empty-state
                            title="No prices found"
                            description="Try clearing search or filters to see more results."
                            class="border-0 rounded-none py-10"
                        >
                            <x-slot name="action">
                                <a href="{{ route('masters.price-masters.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                    Reset Filters
                                </a>
                            </x-slot>
                        </x-ui.empty-state>
                    </td>
                </tr>
            @endforelse
        </x-ui.table>

        @if($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-ui.card>

    <!-- View Details Modal -->
    <div x-show="showViewModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="view-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="closeViewModal()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="closeViewModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showViewModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full border border-slate-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-white/10 flex items-center justify-center backdrop-blur-sm border border-white/10">
                            <svg class="h-5 w-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="text-base font-semibold text-white" id="view-modal-title">Price List Product Details</h3>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                                      :class="viewData?.is_brand_wise ? 'bg-purple-500/20 text-purple-200 border border-purple-400/30' : 'bg-blue-500/20 text-blue-200 border border-blue-400/30'"
                                      x-text="viewData?.type_label"></span>
                            </div>
                            <p class="text-xs text-slate-300 mt-0.5">Read-only master attributes and saved pricing breakdown</p>
                        </div>
                    </div>
                    <button type="button" @click="closeViewModal()" class="text-slate-400 hover:text-white rounded-lg p-1.5 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="isLoadingView" class="py-16 text-center">
                    <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-xs text-slate-500 mt-2">Loading product & pricing details...</p>
                </div>

                <!-- Modal Content -->
                <div x-show="!isLoadingView && viewData" class="p-6 space-y-5 max-h-[calc(85vh-140px)] overflow-y-auto">
                    <!-- Product Master Attributes (Auto-fetched) -->
                    <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-4">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-200/80">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-6 w-6 rounded-md bg-indigo-100 text-indigo-700 items-center justify-center text-xs font-bold">1</span>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Product Master Attributes</h4>
                            </div>
                            <span class="inline-flex items-center gap-1 rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-600 border border-slate-200">
                                Auto-fetched Reference
                            </span>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div class="col-span-2">
                                <span class="text-slate-500 block text-[11px]">Product Name</span>
                                <span class="font-semibold text-slate-900 text-sm" x-text="viewData?.product?.name || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">SKU / Part Number</span>
                                <span class="font-mono font-medium text-slate-800" x-text="viewData?.product?.sku || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Model / Series</span>
                                <span class="text-slate-800" x-text="[viewData?.product?.model, viewData?.product?.series].filter(Boolean).join(' / ') || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Brand</span>
                                <span class="font-medium text-slate-800" x-text="viewData?.product?.brand_name || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Category / Subcategory</span>
                                <span class="text-slate-800" x-text="[viewData?.product?.category_name, viewData?.product?.sub_category_name].filter(Boolean).join(' › ') || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Product Type</span>
                                <span class="text-slate-800" x-text="viewData?.product?.product_type_name || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">HSN Code</span>
                                <span class="font-mono text-slate-800" x-text="viewData?.product?.hsn_code || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Tax Rate (GST)</span>
                                <span class="font-semibold text-indigo-700" x-text="(viewData?.product?.tax_rate ?? 0) + '%'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">MRP (Product Master)</span>
                                <span class="font-bold text-slate-900" x-text="viewData?.product?.mrp ? '₹' + Number(viewData.product.mrp).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Base UOM</span>
                                <span class="font-medium text-slate-800" x-text="viewData?.product?.base_uom_name || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Selling Price</span>
                                <span class="text-slate-800" x-text="viewData?.product?.selling_price ? '₹' + Number(viewData.product.selling_price).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '—'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Price List Assignment -->
                    <div class="rounded-xl border border-slate-200 bg-white p-4">
                        <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex h-6 w-6 rounded-md bg-purple-100 text-purple-700 items-center justify-center text-xs font-bold">2</span>
                                <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Price List Assignment</h4>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                            <div>
                                <span class="text-slate-500 block text-[11px]">List Type</span>
                                <span class="font-semibold text-slate-900" x-text="viewData?.type_label"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]" x-text="viewData?.is_brand_wise ? 'Brand' : 'Customer Type'"></span>
                                <span class="font-semibold text-slate-900" x-text="viewData?.target_name"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Price List Name</span>
                                <span class="text-slate-800" x-text="viewData?.name || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Applied Unit (UOM)</span>
                                <span class="font-semibold text-slate-900" x-text="viewData?.uom_code || '—'"></span>
                            </div>
                            <div>
                                <span class="text-slate-500 block text-[11px]">Minimum Order Qty</span>
                                <span class="text-slate-800" x-text="viewData?.min_qty !== null ? viewData.min_qty : 'None (1)'"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Saved Pricing Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Dealer Pricing Breakdown -->
                        <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4">
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-indigo-100">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">Dealer Pricing</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                      :class="viewData?.dealer_price_gst_mode === 'inclusive' ? 'bg-indigo-200 text-indigo-800' : 'bg-slate-200 text-slate-800'"
                                      x-text="viewData?.dealer_price_gst_mode === 'inclusive' ? 'GST Inclusive' : 'GST Exclusive'"></span>
                            </div>
                            <template x-if="viewData?.dealer_price !== null">
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600">Entered Price:</span>
                                        <span class="font-bold text-sm text-slate-900" x-text="'₹' + Number(viewData?.dealer_price_entered || viewData?.dealer_price).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>Base Price (Excl. GST):</span>
                                        <span class="font-mono" x-text="'₹' + Number(viewData?.dealer_pricing?.exclusive ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>GST Amount:</span>
                                        <span class="font-mono text-indigo-700" x-text="'₹' + Number(viewData?.dealer_pricing?.gst ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-indigo-100 text-slate-900 font-semibold">
                                        <span>Final Payable (Incl. GST):</span>
                                        <span class="font-bold text-indigo-950" x-text="'₹' + Number(viewData?.dealer_pricing?.final ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="viewData?.dealer_price === null">
                                <p class="text-xs text-slate-400 italic py-3 text-center">No dealer price entered</p>
                            </template>
                        </div>

                        <!-- Retail Pricing Breakdown -->
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                            <div class="flex items-center justify-between mb-3 pb-2 border-b border-emerald-100">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-900">Retail Pricing</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                      :class="viewData?.retail_price_gst_mode === 'inclusive' ? 'bg-emerald-200 text-emerald-800' : 'bg-slate-200 text-slate-800'"
                                      x-text="viewData?.retail_price_gst_mode === 'inclusive' ? 'GST Inclusive' : 'GST Exclusive'"></span>
                            </div>
                            <template x-if="viewData?.retail_price !== null">
                                <div class="space-y-2 text-xs">
                                    <div class="flex justify-between items-center">
                                        <span class="text-slate-600">Entered Price:</span>
                                        <span class="font-bold text-sm text-slate-900" x-text="'₹' + Number(viewData?.retail_price_entered || viewData?.retail_price).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>Base Price (Excl. GST):</span>
                                        <span class="font-mono" x-text="'₹' + Number(viewData?.retail_pricing?.exclusive ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span>GST Amount:</span>
                                        <span class="font-mono text-emerald-700" x-text="'₹' + Number(viewData?.retail_pricing?.gst ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                    <div class="flex justify-between items-center pt-2 border-t border-emerald-100 text-slate-900 font-semibold">
                                        <span>Final Payable (Incl. GST):</span>
                                        <span class="font-bold text-emerald-950" x-text="'₹' + Number(viewData?.retail_pricing?.final ?? 0).toLocaleString('en-IN', {minimumFractionDigits: 2})"></span>
                                    </div>
                                </div>
                            </template>
                            <template x-if="viewData?.retail_price === null">
                                <p class="text-xs text-slate-400 italic py-3 text-center">No retail price entered</p>
                            </template>
                        </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="text-[11px] text-slate-400 flex justify-between items-center pt-2 border-t border-slate-100">
                        <span>Created: <span x-text="viewData?.created_at || '—'"></span></span>
                        <span>Last Updated: <span x-text="viewData?.updated_at || '—'"></span></span>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-between border-t border-slate-100">
                    <button type="button"
                            @click="switchToEditFromView()"
                            class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Quick Edit</span>
                    </button>
                    <button type="button"
                            @click="closeViewModal()"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Edit Modal -->
    <div x-show="showEditModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="edit-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="closeEditModal()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="closeEditModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showEditModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full border border-slate-200">
                
                <!-- Modal Header -->
                <div class="bg-gradient-to-r from-slate-900 via-indigo-950 to-slate-900 px-6 py-4 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-9 w-9 rounded-xl bg-indigo-500/20 flex items-center justify-center border border-indigo-400/30">
                            <svg class="h-5 w-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-semibold text-white" id="edit-modal-title">Edit Product Pricing</h3>
                            <p class="text-xs text-slate-300 mt-0.5">Update prices, GST treatment, UOM and min quantity</p>
                        </div>
                    </div>
                    <button type="button" @click="closeEditModal()" class="text-slate-400 hover:text-white rounded-lg p-1.5 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Loading State -->
                <div x-show="isLoadingEdit" class="py-16 text-center">
                    <svg class="animate-spin h-8 w-8 text-indigo-600 mx-auto" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <p class="text-xs text-slate-500 mt-2">Loading pricing record...</p>
                </div>

                <!-- Modal Content Form -->
                <form x-show="!isLoadingEdit && editForm" @submit.prevent="submitEdit()">
                    <div class="p-6 space-y-4 max-h-[calc(85vh-140px)] overflow-y-auto">
                        <!-- Error Notice -->
                        <div x-show="editErrors.general" x-cloak class="rounded-lg bg-red-50 p-3 text-xs text-red-700 border border-red-200">
                            <span x-text="editErrors.general"></span>
                        </div>

                        <!-- Read-Only Product Reference Banner -->
                        <div class="rounded-xl border border-slate-200 bg-slate-50/80 p-3.5 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-900 text-sm" x-text="editForm ? editForm.product_name : ''"></span>
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold"
                                      :class="editForm && editForm.is_brand_wise ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'"
                                      x-text="editForm ? editForm.target_name : ''"></span>
                            </div>
                            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-xs text-slate-600">
                                <div><span class="text-slate-400">SKU:</span> <span class="font-mono" x-text="editForm ? editForm.sku || '—' : ''"></span></div>
                                <div><span class="text-slate-400">Brand:</span> <span x-text="editForm ? editForm.brand_name || '—' : ''"></span></div>
                                <div><span class="text-slate-400">HSN:</span> <span class="font-mono" x-text="editForm ? editForm.hsn_code || '—' : ''"></span></div>
                                <div><span class="text-slate-400">GST:</span> <span class="font-semibold text-indigo-700" x-text="editForm ? editForm.tax_rate + '%' : '0%'"></span></div>
                                <div><span class="text-slate-400">MRP:</span> <span class="font-semibold text-slate-800" x-text="editForm && editForm.mrp ? '₹' + Number(editForm.mrp).toLocaleString('en-IN') : '—'"></span></div>
                            </div>
                        </div>

                        <!-- UOM and Min Qty Selection -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Unit of Measurement (UOM) <span class="text-red-500">*</span>
                                </label>
                                <select x-model="editForm.uom_id"
                                        class="w-full rounded-lg border-slate-300 py-1.5 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                                        :class="editErrors.uom_id ? 'border-red-500' : ''">
                                    <template x-for="uom in (editForm ? editForm.uoms : [])" :key="uom.id">
                                        <option :value="uom.id" x-text="uom.code + (uom.name ? ' — ' + uom.name : '') + (uom.label ? ' (' + uom.label + ')' : '')"></option>
                                    </template>
                                </select>
                                <p x-show="editErrors.uom_id" x-text="editErrors.uom_id" class="text-[11px] text-red-600 mt-1"></p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">
                                    Minimum Order Quantity
                                </label>
                                <input type="number"
                                       min="0"
                                       step="1"
                                       x-model="editForm.min_qty"
                                       placeholder="Leave blank for any"
                                       class="w-full rounded-lg border-slate-300 py-1.5 px-3 text-xs text-slate-800 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                            </div>
                        </div>

                        <!-- Dealer Price Input & GST Mode -->
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/30 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">Dealer Price</span>
                                <!-- GST Mode Toggle -->
                                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs shadow-2xs">
                                    <button type="button"
                                            @click="editForm.dealer_price_gst_mode = 'exclusive'"
                                            class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition"
                                            :class="editForm && editForm.dealer_price_gst_mode === 'exclusive' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                        Excl. GST
                                    </button>
                                    <button type="button"
                                            @click="editForm.dealer_price_gst_mode = 'inclusive'"
                                            class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition"
                                            :class="editForm && editForm.dealer_price_gst_mode === 'inclusive' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                        Incl. GST
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-medium">₹</span>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="editForm.dealer_price"
                                           placeholder="0.00"
                                           class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-slate-300 text-slate-900 font-semibold focus:border-indigo-500 focus:ring-indigo-500 shadow-sm" />
                                </div>
                                <p x-show="editErrors.dealer_price" x-text="editErrors.dealer_price" class="text-[11px] text-red-600 mt-1"></p>
                            </div>

                            <!-- Live Breakdown -->
                            <div class="flex items-center justify-between text-[11px] text-slate-600 bg-white/80 rounded-lg px-3 py-1.5 border border-indigo-100/60">
                                <span>Base: <strong class="text-slate-900">₹<span x-text="calculateBreakdown(editForm?.dealer_price, editForm?.dealer_price_gst_mode).exclusive"></span></strong></span>
                                <span>GST (<span x-text="editForm?.tax_rate || 0"></span>%): <strong class="text-indigo-700">₹<span x-text="calculateBreakdown(editForm?.dealer_price, editForm?.dealer_price_gst_mode).gst"></span></strong></span>
                                <span>Total: <strong class="text-slate-900">₹<span x-text="calculateBreakdown(editForm?.dealer_price, editForm?.dealer_price_gst_mode).final"></span></strong></span>
                            </div>
                        </div>

                        <!-- Retail Price Input & GST Mode -->
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/30 p-4 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold uppercase tracking-wider text-emerald-900">Retail Price</span>
                                <!-- GST Mode Toggle -->
                                <div class="inline-flex rounded-lg border border-slate-200 bg-white p-0.5 text-xs shadow-2xs">
                                    <button type="button"
                                            @click="editForm.retail_price_gst_mode = 'exclusive'"
                                            class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition"
                                            :class="editForm && editForm.retail_price_gst_mode === 'exclusive' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                        Excl. GST
                                    </button>
                                    <button type="button"
                                            @click="editForm.retail_price_gst_mode = 'inclusive'"
                                            class="px-2.5 py-1 rounded-md text-[11px] font-semibold transition"
                                            :class="editForm && editForm.retail_price_gst_mode === 'inclusive' ? 'bg-emerald-600 text-white shadow-xs' : 'text-slate-600 hover:text-slate-900'">
                                        Incl. GST
                                    </button>
                                </div>
                            </div>

                            <div>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-medium">₹</span>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           x-model="editForm.retail_price"
                                           placeholder="0.00"
                                           class="w-full pl-7 pr-3 py-1.5 text-xs rounded-lg border-slate-300 text-slate-900 font-semibold focus:border-emerald-500 focus:ring-emerald-500 shadow-sm" />
                                </div>
                                <p x-show="editErrors.retail_price" x-text="editErrors.retail_price" class="text-[11px] text-red-600 mt-1"></p>
                            </div>

                            <!-- Live Breakdown -->
                            <div class="flex items-center justify-between text-[11px] text-slate-600 bg-white/80 rounded-lg px-3 py-1.5 border border-emerald-100/60">
                                <span>Base: <strong class="text-slate-900">₹<span x-text="calculateBreakdown(editForm?.retail_price, editForm?.retail_price_gst_mode).exclusive"></span></strong></span>
                                <span>GST (<span x-text="editForm?.tax_rate || 0"></span>%): <strong class="text-emerald-700">₹<span x-text="calculateBreakdown(editForm?.retail_price, editForm?.retail_price_gst_mode).gst"></span></strong></span>
                                <span>Total: <strong class="text-slate-900">₹<span x-text="calculateBreakdown(editForm?.retail_price, editForm?.retail_price_gst_mode).final"></span></strong></span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-slate-50 px-6 py-3.5 flex items-center justify-between border-t border-slate-100">
                        <a :href="'{{ url('masters/price-masters') }}/' + (editForm ? editForm.id : '') + '/edit'"
                           class="text-xs text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-1 font-medium">
                            <span>Open Full Edit Page</span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="closeEditModal()"
                                    :disabled="isSavingEdit"
                                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                                Cancel
                            </button>
                            <button type="submit"
                                    :disabled="isSavingEdit"
                                    class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-700 transition disabled:opacity-50">
                                <span x-show="!isSavingEdit">Save Changes</span>
                                <span x-show="isSavingEdit" class="inline-flex items-center gap-1.5">
                                    <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelDelete()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Backdrop -->
            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelDelete()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showDeleteModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Price Record</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this price record? This action cannot be undone. Historical sales orders and invoices will keep their recorded prices intact.</p>

                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-2 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium" x-show="deletingRecord.type !== 'Brand Wise'">Customer Type:</span>
                                        <span class="font-medium" x-show="deletingRecord.type === 'Brand Wise'" x-cloak>Brand:</span>
                                        <span class="font-semibold text-slate-900" x-text="deletingRecord.target || deletingRecord.customerType"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product:</span>
                                        <span class="font-semibold text-slate-900" x-text="deletingRecord.product"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Unit:</span>
                                        <span class="font-mono text-slate-700" x-text="deletingRecord.unit"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Rate:</span>
                                        <span class="font-semibold text-slate-900" x-text="deletingRecord.rate"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Min Qty:</span>
                                        <span class="text-slate-700" x-text="deletingRecord.minQty"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isDeleting">Confirm Delete</span>
                        <span x-show="isDeleting" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isDeleting"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div x-show="notification.show"
         x-cloak
         x-transition
         class="fixed bottom-4 right-4 z-50 max-w-sm rounded-lg p-4 shadow-lg text-white text-sm"
         :class="notification.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        <div class="flex items-center gap-2">
            <span x-text="notification.message"></span>
            <button type="button" @click="notification.show = false" class="ml-auto text-white/80 hover:text-white">&times;</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function priceMasterIndex() {
    return {
        defaultViewData() {
            return {
                id: null,
                type: '',
                type_label: '',
                is_brand_wise: false,
                target_name: '',
                name: '',
                uom_code: '',
                min_qty: null,
                dealer_price: null,
                dealer_price_entered: null,
                dealer_price_gst_mode: 'exclusive',
                dealer_pricing: { exclusive: 0, gst: 0, final: 0 },
                retail_price: null,
                retail_price_entered: null,
                retail_price_gst_mode: 'exclusive',
                retail_pricing: { exclusive: 0, gst: 0, final: 0 },
                product: null,
                created_at: '',
                updated_at: '',
            };
        },

        defaultEditForm() {
            return {
                id: null,
                type: '',
                is_brand_wise: false,
                target_name: '',
                product_id: '',
                product_name: '',
                sku: '',
                brand_name: '',
                hsn_code: '',
                tax_rate: 0,
                mrp: null,
                uom_id: '',
                uoms: [],
                min_qty: '',
                dealer_price: '',
                dealer_price_gst_mode: 'exclusive',
                retail_price: '',
                retail_price_gst_mode: 'exclusive',
            };
        },

        showViewModal: false,
        isLoadingView: false,
        viewData: {
            id: null,
            type: '',
            type_label: '',
            is_brand_wise: false,
            target_name: '',
            name: '',
            uom_code: '',
            min_qty: null,
            dealer_price: null,
            dealer_price_entered: null,
            dealer_price_gst_mode: 'exclusive',
            dealer_pricing: { exclusive: 0, gst: 0, final: 0 },
            retail_price: null,
            retail_price_entered: null,
            retail_price_gst_mode: 'exclusive',
            retail_pricing: { exclusive: 0, gst: 0, final: 0 },
            product: null,
            created_at: '',
            updated_at: '',
        },

        showEditModal: false,
        isLoadingEdit: false,
        isSavingEdit: false,
        editForm: {
            id: null,
            type: '',
            is_brand_wise: false,
            target_name: '',
            product_id: '',
            product_name: '',
            sku: '',
            brand_name: '',
            hsn_code: '',
            tax_rate: 0,
            mrp: null,
            uom_id: '',
            uoms: [],
            min_qty: '',
            dealer_price: '',
            dealer_price_gst_mode: 'exclusive',
            retail_price: '',
            retail_price_gst_mode: 'exclusive',
        },
        editErrors: {},

        showDeleteModal: false,
        isDeleting: false,
        deletingRecord: {
            id: null,
            type: '',
            target: '',
            customerType: '',
            product: '',
            unit: '',
            rate: '',
            minQty: ''
        },
        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        async openViewModal(id) {
            this.isLoadingView = true;
            this.viewData = this.defaultViewData();
            this.showViewModal = true;

            try {
                const response = await fetch(`{{ url('masters/price-masters') }}/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const json = await response.json().catch(() => ({}));
                if (response.ok && json.success && json.data) {
                    this.viewData = Object.assign(this.defaultViewData(), json.data);
                } else {
                    this.showNotification('error', json.message || 'Failed to load details.');
                    this.showViewModal = false;
                }
            } catch (e) {
                console.error(e);
                this.showNotification('error', 'Error loading price record details.');
                this.showViewModal = false;
            } finally {
                this.isLoadingView = false;
            }
        },

        closeViewModal() {
            this.showViewModal = false;
            this.viewData = this.defaultViewData();
        },

        switchToEditFromView() {
            if (!this.viewData?.id) return;
            const id = this.viewData.id;
            this.closeViewModal();
            this.openEditModal(id);
        },

        async openEditModal(id) {
            this.isLoadingEdit = true;
            this.editForm = this.defaultEditForm();
            this.editErrors = {};
            this.showEditModal = true;

            try {
                const response = await fetch(`{{ url('masters/price-masters') }}/${id}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });
                const json = await response.json().catch(() => ({}));
                if (response.ok && json.success && json.data) {
                    const d = json.data;
                    const prod = d.product || {};
                    this.editForm = {
                        id: d.id ?? null,
                        type: d.type ?? '',
                        is_brand_wise: Boolean(d.is_brand_wise),
                        target_name: d.target_name ?? '',
                        product_id: d.product_id ?? '',
                        product_name: prod.name ?? 'Product',
                        sku: prod.sku ?? '',
                        brand_name: prod.brand_name ?? d.brand_name ?? '',
                        hsn_code: prod.hsn_code ?? '',
                        tax_rate: Number(prod.tax_rate ?? 0),
                        mrp: prod.mrp ?? null,
                        uom_id: d.uom_id ? String(d.uom_id) : '',
                        uoms: Array.isArray(prod.uoms) && prod.uoms.length > 0
                            ? prod.uoms
                            : (d.uom_id ? [{ id: String(d.uom_id), code: d.uom_code || '', name: d.uom_name || '' }] : []),
                        min_qty: d.min_qty !== null && d.min_qty !== undefined ? d.min_qty : '',
                        dealer_price: d.dealer_price_entered !== null && d.dealer_price_entered !== undefined
                            ? d.dealer_price_entered
                            : (d.dealer_price !== null && d.dealer_price !== undefined ? d.dealer_price : ''),
                        dealer_price_gst_mode: (d.dealer_price_gst_mode || 'exclusive').toLowerCase(),
                        retail_price: d.retail_price_entered !== null && d.retail_price_entered !== undefined
                            ? d.retail_price_entered
                            : (d.retail_price !== null && d.retail_price !== undefined ? d.retail_price : ''),
                        retail_price_gst_mode: (d.retail_price_gst_mode || 'exclusive').toLowerCase(),
                    };
                } else {
                    this.showNotification('error', json.message || 'Failed to load price record for editing.');
                    this.showEditModal = false;
                }
            } catch (e) {
                console.error(e);
                this.showNotification('error', 'Error loading price record for editing.');
                this.showEditModal = false;
            } finally {
                this.isLoadingEdit = false;
            }
        },

        closeEditModal() {
            if (this.isSavingEdit) return;
            this.showEditModal = false;
            this.editForm = this.defaultEditForm();
            this.editErrors = {};
        },

        calculateBreakdown(price, gstMode) {
            const val = parseFloat(price);
            if (isNaN(val) || val <= 0) {
                return { exclusive: '0.00', gst: '0.00', final: '0.00' };
            }
            const taxRate = parseFloat(this.editForm?.tax_rate || 0);
            let exclusive = 0;
            let gst = 0;
            let final = 0;

            if (gstMode === 'inclusive') {
                final = val;
                if (taxRate > 0) {
                    exclusive = val / (1 + (taxRate / 100));
                    gst = final - exclusive;
                } else {
                    exclusive = val;
                    gst = 0;
                }
            } else {
                exclusive = val;
                gst = (val * taxRate) / 100;
                final = exclusive + gst;
            }

            return {
                exclusive: exclusive.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                gst: gst.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 }),
                final: final.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            };
        },

        async submitEdit() {
            if (!this.editForm?.id || this.isSavingEdit) return;
            this.editErrors = {};

            const hasDealer = this.editForm.dealer_price !== '' && this.editForm.dealer_price !== null && !isNaN(parseFloat(this.editForm.dealer_price));
            const hasRetail = this.editForm.retail_price !== '' && this.editForm.retail_price !== null && !isNaN(parseFloat(this.editForm.retail_price));

            if (!hasDealer && !hasRetail) {
                this.editErrors.general = 'Please enter at least one price (Dealer Price or Retail Price).';
                return;
            }

            this.isSavingEdit = true;
            const url = `{{ url('masters/price-masters') }}/${this.editForm.id}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            const payload = {
                type: this.editForm.type,
                product_id: this.editForm.product_id,
                uom_id: this.editForm.uom_id,
                min_qty: this.editForm.min_qty !== '' && this.editForm.min_qty !== null ? this.editForm.min_qty : null,
                dealer_price: hasDealer ? parseFloat(this.editForm.dealer_price) : null,
                dealer_price_gst_mode: this.editForm.dealer_price_gst_mode,
                retail_price: hasRetail ? parseFloat(this.editForm.retail_price) : null,
                retail_price_gst_mode: this.editForm.retail_price_gst_mode,
            };

            try {
                const response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(payload)
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showEditModal = false;
                    this.showNotification('success', data.message || 'Price record updated successfully.');
                    await this.refreshList();
                } else if (response.status === 422) {
                    if (data.errors) {
                        for (const [key, msgs] of Object.entries(data.errors)) {
                            this.editErrors[key] = Array.isArray(msgs) ? msgs[0] : msgs;
                        }
                        if (!this.editErrors.general && data.message) {
                            this.editErrors.general = data.message;
                        }
                    } else {
                        this.editErrors.general = data.message || 'Validation error occurred.';
                    }
                } else {
                    this.editErrors.general = data.message || 'Failed to update price record.';
                }
            } catch (err) {
                console.error(err);
                this.editErrors.general = 'Network error occurred while updating price record.';
            } finally {
                this.isSavingEdit = false;
            }
        },

        promptDelete(record) {
            this.deletingRecord = { ...record };
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isDeleting) return;
            this.showDeleteModal = false;
            this.deletingRecord = {
                id: null,
                type: '',
                target: '',
                customerType: '',
                product: '',
                unit: '',
                rate: '',
                minQty: ''
            };
        },

        async executeDelete() {
            if (!this.deletingRecord.id || this.isDeleting) return;
            this.isDeleting = true;

            const url = `{{ url('masters/price-masters') }}/${this.deletingRecord.id}`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showDeleteModal = false;
                    this.showNotification('success', data.message || 'Price record deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete price record.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting price record.');
            } finally {
                this.isDeleting = false;
            }
        },

        async refreshList() {
            try {
                const res = await fetch(window.location.href, {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!res.ok) {
                    window.location.reload();
                    return;
                }
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newContainer = doc.querySelector('#listing-container');
                const currentContainer = document.querySelector('#listing-container');

                if (newContainer && currentContainer) {
                    const currentTableWrapper = currentContainer.querySelector('.overflow-x-auto');
                    const newTableWrapper = newContainer.querySelector('.overflow-x-auto');
                    if (currentTableWrapper && newTableWrapper) {
                        currentTableWrapper.innerHTML = newTableWrapper.innerHTML;
                        if (window.Alpine && typeof Alpine.initTree === 'function') {
                            Alpine.initTree(currentTableWrapper);
                        }
                    }

                    const currentPagination = currentContainer.querySelector('.mt-4');
                    const newPagination = newContainer.querySelector('.mt-4');
                    if (currentPagination && newPagination) {
                        currentPagination.innerHTML = newPagination.innerHTML;
                    } else if (!currentPagination && newPagination) {
                        currentTableWrapper.insertAdjacentElement('afterend', newPagination);
                    } else if (currentPagination && !newPagination) {
                        currentPagination.remove();
                    }
                } else {
                    window.location.reload();
                }
            } catch (e) {
                console.error('Failed to refresh table dynamic container:', e);
                window.location.reload();
            }
        },

        showNotification(type, message) {
            this.notification.type = type;
            this.notification.message = message;
            this.notification.show = true;
            setTimeout(() => {
                this.notification.show = false;
            }, 4500);
        }
    };
}
</script>
@endpush
@endsection
