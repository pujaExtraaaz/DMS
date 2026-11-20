@extends('layouts.dms')
@section('title', 'Products')
@section('content')
@php
    $currentListingUrl = request()->getPathInfo().(request()->getQueryString() ? '?'.request()->getQueryString() : '');
@endphp
<div id="listing-container" data-dynamic-container x-data="productIndex()">
    <x-ui.page-header title="Products">
        <x-slot name="actions">
            <div class="relative inline-block text-left" x-data="{ exportOpen: false }">
                <button
                    type="button"
                    @click="exportOpen = !exportOpen"
                    class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-medium text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-indigo-500/20 active:bg-slate-100 transition-colors shadow-xs"
                    title="Export Products"
                    data-export-button
                >
                    <svg class="h-3.5 w-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Export</span>
                    <svg class="h-3 w-3 text-slate-400 transition-transform duration-150" :class="exportOpen ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                {{-- Export Dropdown --}}
                <div
                    x-show="exportOpen"
                    x-cloak
                    @click.away="exportOpen = false"
                    class="absolute left-0 mt-1.5 z-50 w-48 rounded-xl bg-white p-1.5 shadow-xl border border-slate-200/90 ring-1 ring-slate-900/5 focus:outline-none"
                >
                    <a
                        href="{{ request()->fullUrlWithQuery(['export' => 'xlsx']) }}"
                        @click="exportOpen = false"
                        data-export-format="xlsx"
                        class="flex items-center gap-2.5 px-3 py-1.5 text-xs font-medium text-slate-700 hover:text-emerald-700 hover:bg-emerald-50/70 rounded-lg transition-colors"
                    >
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-emerald-100/70 text-emerald-600">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </span>
                        <span>Quick Excel (.xlsx)</span>
                    </a>
                    <a
                        href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                        @click="exportOpen = false"
                        data-export-format="csv"
                        class="flex items-center gap-2.5 px-3 py-1.5 text-xs font-medium text-slate-700 hover:text-indigo-700 hover:bg-indigo-50/70 rounded-lg transition-colors"
                    >
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-indigo-100/70 text-indigo-600">
                            <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7v10M12 7v10M16 7v10M3 7h18M3 17h18"/></svg>
                        </span>
                        <span>Quick CSV (.csv)</span>
                    </a>
                    <a
                        href="{{ request()->fullUrlWithQuery(['export' => 'pdf']) }}"
                        @click="exportOpen = false"
                        data-export-format="pdf"
                        class="flex items-center gap-2.5 px-3 py-1.5 text-xs font-medium text-slate-700 hover:text-rose-700 hover:bg-rose-50/70 rounded-lg transition-colors"
                    >
                        <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded bg-rose-100/70 text-rose-600">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        </span>
                        <span>Quick PDF (.pdf)</span>
                    </a>
                </div>
            </div>
            <x-ui.button variant="primary" :href="route('masters.products.create', ['return_url' => $currentListingUrl])" data-preserve-state>+ Add Product</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if(isset($bannerProduct) && $bannerProduct)
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 mb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-start sm:items-center gap-3">
                <div class="h-9 w-9 shrink-0 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" /></svg>
                </div>
                <div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <span class="text-xs font-bold text-emerald-900">Recently Created Product:</span>
                        <span class="text-xs font-semibold text-emerald-800">{{ $bannerProduct->name }}</span>
                        @if($bannerProduct->sku)
                            <span class="font-mono text-[11px] text-emerald-700 bg-emerald-100/60 px-1.5 py-0.5 rounded">Part: {{ $bannerProduct->sku }}</span>
                        @endif
                    </div>
                    <p class="text-[11px] text-emerald-700 mt-0.5">
                        Product was created successfully. Your previously selected filters and page are preserved below.
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-2 self-end sm:self-auto shrink-0">
                <x-ui.button variant="secondary" size="sm" :href="route('masters.products.show', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])" data-preserve-state>View</x-ui.button>
                <x-ui.button variant="secondary" size="sm" :href="route('masters.products.edit', ['product' => $bannerProduct, 'return_url' => $currentListingUrl])" data-preserve-state>Edit</x-ui.button>
            </div>
        </div>
    @endif

    <x-ui.card>
        @if(($serialMatches ?? collect())->isNotEmpty())
            <div class="mb-6 space-y-4">
                @foreach($serialMatches as $serial)
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50/40 p-4">
                        <p class="mb-3 text-sm font-semibold text-indigo-900">Serial {{ $serial->serial_number }}</p>
                        @include('inventory.serials.partials.warranty-result', ['serial' => $serial])
                    </div>
                @endforeach
            </div>
        @endif

        <x-ui.table-toolbar
            :search="$search ?? request('search')"
            search-placeholder="Search by name or part number (starts with)..."
            :reset-url="route('masters.products.index')"
        >
            <x-slot name="filters">
                <div
                    class="flex flex-wrap items-center gap-2"
                    data-hierarchical-filter-group
                    data-all-categories='@json($allCategoriesData ?? [])'
                    data-all-subcategories='@json($allSubCategoriesData ?? [])'
                >
                    <select
                        name="brand_id"
                        id="filter-brand-id"
                        data-dynamic-filter
                        aria-label="Filter by Brand"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Brand: All</option>
                        @foreach($brands ?? [] as $b)
                            <option value="{{ $b->id }}" @selected((string) ($brandId ?? request('brand_id')) === (string) $b->id)>{{ $b->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="category_id"
                        id="filter-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Category"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Category: All</option>
                        @foreach($categories ?? [] as $cat)
                            <option value="{{ $cat->id }}" @selected((string) ($categoryId ?? request('category_id')) === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="sub_category_id"
                        id="filter-sub-category-id"
                        data-dynamic-filter
                        aria-label="Filter by Subcategory"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Subcategory: All</option>
                        @foreach($subCategories ?? [] as $sc)
                            <option value="{{ $sc->id }}" @selected((string) ($subCategoryId ?? request('sub_category_id', request('subcategory_id'))) === (string) $sc->id)>{{ $sc->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="product_type"
                        data-dynamic-filter
                        aria-label="Filter by Product Type"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="">Product Type: All</option>
                        @foreach($productTypes ?? [] as $pt)
                            <option value="{{ $pt->name }}" @selected(request('product_type') == $pt->name || request('product_type') == $pt->id)>{{ $pt->name }}</option>
                        @endforeach
                    </select>

                    <select
                        name="status"
                        data-dynamic-filter
                        aria-label="Filter by Visibility / Status"
                        class="rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs text-slate-700 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm"
                    >
                        <option value="" @selected(request('status') === '' || request('status') === 'all_visible')>All Visible Products</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                        @if($canViewHidden ?? false)
                            <option value="hidden" @selected(request('status') === 'hidden')>Hidden</option>
                        @endif
                    </select>
                </div>
            </x-slot>
        </x-ui.table-toolbar>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <x-ui.sortable-th column="serial_no" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Serial No.
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="name" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Name
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="sku" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Part Number
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="model" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Models
                        </x-ui.sortable-th>
                        <x-ui.sortable-th column="product_type" :current-sort="$sort ?? request('sort', 'name')" :current-direction="$direction ?? request('direction', 'asc')">
                            Product Type
                        </x-ui.sortable-th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Unit</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="Physical stock available across all warehouses">Available Stock</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="In-transit stock incoming from suppliers">In-Transit Stock</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold tracking-wider text-slate-500" title="Total expected stock (Available + In-Transit)">Total Expected Stock</th>
                        <th class="px-3 py-3 text-left text-xs font-semibold tracking-wider text-slate-500" title="Next expected delivery date from suppliers">Next Expected Delivery</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Tax</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold tracking-wider text-slate-500">Warranty</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($items as $item)
                        @php
                            $isNew = (string) $item->id === (string) ($newProductId ?? request('new_product_id'));
                            $isUpdated = (string) $item->id === (string) ($updatedProductId ?? request('updated_product_id'));
                        @endphp
                        <tr class="{{ $isNew ? 'bg-emerald-50/70 ring-1 ring-emerald-300' : ($isUpdated ? 'bg-indigo-50/70 ring-1 ring-indigo-300' : 'hover:bg-slate-50/75') }} transition-colors"
                            @if($isNew || $isUpdated) x-init="$nextTick(() => { $el.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); })" @endif>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->serial_no ?: '—' }}</td>
                            <td class="px-4 py-3 font-medium text-slate-900">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <a href="{{ route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl]) }}" data-preserve-state class="hover:text-indigo-700 hover:underline">{{ $item->name }}</a>
                                    @if($isNew)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-emerald-100 text-emerald-800">New</span>
                                    @elseif($isUpdated)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-indigo-100 text-indigo-800">Updated</span>
                                    @endif
                                    @if($item->is_hidden)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-semibold bg-amber-50 text-amber-900 border border-amber-300 shadow-xs" title="Hidden Product">
                                            <svg class="w-3 h-3 text-amber-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18"/></svg>
                                            Hidden Product
                                        </span>
                                    @endif
                                    @if(! $item->is_active)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border border-slate-300 shadow-xs" title="Inactive Product">
                                            Inactive Product
                                        </span>
                                    @endif
                                </div>
                                @if($item->brand || $item->category || $item->subCategory)
                                    <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                        {{ collect([$item->brand?->name, $item->category?->name, $item->subCategory?->name])->filter()->join(' · ') }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $item->sku }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->models ?: ($item->model ?: '—') }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->product_type ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $item->baseUom?->code ?? '—' }}</td>
                            @php
                                $stock = $stockSummary[$item->id] ?? [
                                    'physical_stock' => 0.0,
                                    'in_transit_stock' => 0.0,
                                    'total_expected_stock' => 0.0,
                                    'next_expected_delivery_date' => null,
                                    'next_expected_delivery_date_formatted' => '—',
                                    'is_delivery_overdue' => false,
                                    'warehouses' => [],
                                    'invoices' => [],
                                ];
                                $pPhys = (float) $stock['physical_stock'];
                                $pTransit = (float) $stock['in_transit_stock'];
                                $pTotal = (float) $stock['total_expected_stock'];
                                $pNextDate = $stock['next_expected_delivery_date_formatted'] ?? '—';
                                $pOverdue = $stock['is_delivery_overdue'] ?? false;
                            @endphp
                            <td class="px-3 py-3 text-right font-mono font-medium text-slate-800">
                                @if($pPhys > 0)
                                    <span class="text-emerald-700 font-bold">{{ number_format($pPhys, 2) }}</span>
                                @else
                                    <span class="text-slate-400">0.00</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono">
                                @if($pTransit > 0)
                                    <button type="button"
                                            @click="openStockDetailModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', @js($stock), 'invoices')"
                                            class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-indigo-50 border border-indigo-200 text-indigo-700 font-bold hover:bg-indigo-100 hover:border-indigo-300 transition text-xs"
                                            title="Click to view incoming purchase deliveries">
                                        <span>{{ number_format($pTransit, 2) }}</span>
                                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    </button>
                                @else
                                    <span class="text-slate-300">0.00</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right font-mono font-bold text-slate-900">
                                {{ number_format($pTotal, 2) }}
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-xs">
                                @if($pTransit > 0 && $pNextDate !== '—')
                                    <div class="flex items-center gap-1">
                                        <span class="font-medium {{ $pOverdue ? 'text-rose-600 font-semibold' : 'text-slate-700' }}">
                                            {{ $pNextDate }}
                                        </span>
                                        @if($pOverdue)
                                            <span class="px-1 py-0.2 rounded text-[9px] font-bold bg-rose-100 text-rose-700 uppercase">Overdue</span>
                                        @endif
                                    </div>
                                @elseif($pTransit > 0)
                                    <span class="text-slate-400 italic text-[11px]">Date not set</span>
                                @else
                                    <span class="text-slate-300">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-700 font-medium">{{ $item->tax_rate }}%</td>
                            <td class="px-4 py-3 text-slate-700">{{ $item->warranty ?: 'No warranty' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button"
                                            @click="openStockDetailModal({{ $item->id }}, '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', @js($stock), 'warehouses')"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 shadow-2xs px-2 py-1 text-xs"
                                            title="View Warehouse-wise Stock & Incoming Deliveries"
                                            aria-label="View Stock Details">
                                        <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span>Stock</span>
                                    </button>
                                    <x-ui.button variant="secondary" size="sm" :href="route('masters.products.show', ['product' => $item, 'return_url' => $currentListingUrl])" title="View Product" aria-label="View Product" data-preserve-state>View</x-ui.button>
                                    <x-ui.button variant="secondary" size="sm" :href="route('masters.products.edit', ['product' => $item, 'return_url' => $currentListingUrl])" title="Edit Product" aria-label="Edit Product" data-preserve-state>Edit</x-ui.button>

                                    @if($item->is_hidden)
                                        @if($canUnhideProduct ?? false)
                                            <button type="button"
                                                    @click="promptUnhide('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', '{{ addslashes($item->serial_no ?: '—') }}')"
                                                    :disabled="isProcessing"
                                                    class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-emerald-600 text-white hover:bg-emerald-700 focus:ring-emerald-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                    title="Unhide Product"
                                                    aria-label="Unhide Product"
                                                    data-unhide-product="{{ $item->id }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                                <span>Unhide</span>
                                            </button>
                                        @endif
                                    @else
                                        @if($canHideProduct ?? false)
                                            <button type="button"
                                                    @click="promptHide('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', '{{ addslashes($item->serial_no ?: '—') }}')"
                                                    :disabled="isProcessing"
                                                    class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-amber-600 text-white hover:bg-amber-700 focus:ring-amber-500 shadow-xs px-2.5 py-1.5 text-xs"
                                                    title="Hide Product"
                                                    aria-label="Hide Product"
                                                    data-hide-product="{{ $item->id }}">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                                                </svg>
                                                <span>Hide</span>
                                            </button>
                                        @endif
                                    @endif

                                    <button type="button"
                                            @click="promptDelete('{{ $item->id }}', '{{ addslashes($item->name) }}', '{{ addslashes($item->sku) }}', '{{ addslashes($item->serial_no ?: '—') }}')"
                                            :disabled="isProcessing"
                                            class="inline-flex items-center gap-1 font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-40 disabled:cursor-not-allowed bg-red-600 text-white hover:bg-red-700 focus:ring-red-500 shadow-xs px-2.5 py-1.5 text-xs"
                                            title="Delete Product"
                                            aria-label="Delete Product"
                                            data-delete-product="{{ $item->id }}">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="14" class="p-0">
                                <x-ui.empty-state
                                    title="No matching products found"
                                    description="Try clearing search or filters to see more results."
                                    class="border-0 rounded-none py-10"
                                >
                                    <x-slot name="action">
                                        <a href="{{ route('masters.products.index') }}" data-reset-filters class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm hover:bg-indigo-500">
                                            Reset Filters
                                        </a>
                                    </x-slot>
                                </x-ui.empty-state>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($items->hasPages())
            <div class="mt-4">{{ $items->links() }}</div>
        @endif
    </x-ui.card>

    <!-- Hide Confirmation Modal -->
    <div x-show="showHideModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="hide-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelHide()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showHideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelHide()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showHideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="hide-modal-title">Hide Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to hide this product? It will be removed from the regular product listing and excluded from new transaction dropdowns by default. It will not be deleted, and all historical records, serials, and transactions remain intact.
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeHide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-amber-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-amber-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Hide Product</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Hiding...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelHide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unhide Confirmation Modal -->
    <div x-show="showUnhideModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="unhide-modal-title" role="dialog" aria-modal="true"
         @keydown.escape.window="cancelUnhide()">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showUnhideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 aria-hidden="true"
                 @click="cancelUnhide()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showUnhideModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-100">
                <div class="bg-white px-6 pt-6 pb-4">
                    <div class="sm:flex sm:items-start gap-4">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-emerald-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:text-left flex-1">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="unhide-modal-title">Unhide Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-600">
                                    Are you sure you want to restore this product to the regular product listing? It will become visible in the normal product list and available in dropdowns.
                                </p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeUnhide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-emerald-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Unhide Product</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Restoring...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelUnhide()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
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
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modal-title">Delete Product</h3>
                            <div class="mt-2 space-y-3">
                                <p class="text-xs text-slate-500">Are you sure you want to delete this product? This action cannot be undone.</p>
                                
                                <div class="bg-slate-50 rounded-xl p-3 border border-slate-100 space-y-1.5 text-xs text-left">
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Product Name:</span>
                                        <span class="font-semibold text-slate-900" x-text="targetProductName"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Part Number:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSku"></span>
                                    </div>
                                    <div class="flex justify-between items-center text-slate-600">
                                        <span class="font-medium">Serial No.:</span>
                                        <span class="font-mono text-slate-700" x-text="targetProductSerial"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-3.5 flex flex-row-reverse gap-2.5 border-t border-slate-100">
                    <button type="button"
                            @click="executeDelete()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white shadow-sm hover:bg-red-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                        <span x-show="!isProcessing">Delete</span>
                        <span x-show="isProcessing" class="inline-flex items-center gap-1.5">
                            <svg class="animate-spin h-3.5 w-3.5 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Deleting...
                        </span>
                    </button>
                    <button type="button"
                            @click="cancelDelete()"
                            :disabled="isProcessing"
                            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Details Modal (Warehouse Breakdown & In-Transit Invoices) -->
    <div x-show="showStockModal"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         role="dialog"
         aria-modal="true"
         @keydown.escape.window="closeStockModal()">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="showStockModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 transition-opacity bg-slate-900/60 backdrop-blur-xs"
                 @click="closeStockModal()"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="showStockModal"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="relative inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white rounded-2xl shadow-xl sm:my-8 border border-slate-100"
                 @click.stop>

                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                    <div>
                        <h3 class="text-base font-bold text-slate-800 flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            </span>
                            <span>Product Stock & In-Transit Deliveries</span>
                        </h3>
                        <p class="text-xs text-slate-500 mt-1">
                            <strong class="text-slate-800" x-text="stockModalProduct.name"></strong>
                            <span class="text-slate-400 mx-1">|</span>
                            <span class="font-mono text-slate-600">Part No: <span x-text="stockModalProduct.sku"></span></span>
                        </p>
                    </div>
                    <button type="button" @click="closeStockModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <!-- Stock Summary Metric Cards -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 my-4">
                    <div class="p-3 rounded-xl bg-emerald-50/70 border border-emerald-100">
                        <div class="text-[11px] font-semibold text-emerald-700 uppercase tracking-wider">Physical Available</div>
                        <div class="text-lg font-bold font-mono text-emerald-900 mt-0.5" x-text="formatQty(stockModalData.physical_stock)"></div>
                        <div class="text-[10px] text-emerald-600 mt-0.5">Ready for dispatch</div>
                    </div>
                    <div class="p-3 rounded-xl bg-indigo-50/70 border border-indigo-100">
                        <div class="text-[11px] font-semibold text-indigo-700 uppercase tracking-wider">In-Transit Stock</div>
                        <div class="text-lg font-bold font-mono text-indigo-900 mt-0.5" x-text="formatQty(stockModalData.in_transit_stock)"></div>
                        <div class="text-[10px] text-indigo-600 mt-0.5">Purchased, on the way</div>
                    </div>
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200">
                        <div class="text-[11px] font-semibold text-slate-700 uppercase tracking-wider">Total Expected</div>
                        <div class="text-lg font-bold font-mono text-slate-900 mt-0.5" x-text="formatQty(stockModalData.total_expected_stock)"></div>
                        <div class="text-[10px] text-slate-500 mt-0.5">Physical + In-Transit</div>
                    </div>
                    <div class="p-3 rounded-xl border"
                         :class="stockModalData.is_delivery_overdue ? 'bg-rose-50/70 border-rose-200' : 'bg-purple-50/70 border-purple-100'">
                        <div class="text-[11px] font-semibold uppercase tracking-wider"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-700' : 'text-purple-700'">
                            Next Expected Date
                        </div>
                        <div class="text-sm font-bold mt-1"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-900' : 'text-purple-900'"
                             x-text="stockModalData.next_expected_delivery_date_formatted || '—'"></div>
                        <div class="text-[10px] mt-0.5 font-medium"
                             :class="stockModalData.is_delivery_overdue ? 'text-rose-600 font-semibold' : 'text-purple-600'"
                             x-text="stockModalData.is_delivery_overdue ? 'Delivery Overdue' : (stockModalData.in_transit_stock > 0 ? 'Earliest delivery' : 'No shipments')">
                        </div>
                    </div>
                </div>

                <!-- Tabs: Warehouses vs Incoming Deliveries -->
                <div class="flex items-center gap-2 border-b border-slate-200 mb-3">
                    <button type="button"
                            @click="stockModalTab = 'warehouses'"
                            class="px-4 py-2 text-xs font-semibold border-b-2 transition -mb-px flex items-center gap-1.5"
                            :class="stockModalTab === 'warehouses' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                        <span>Warehouse Breakdown</span>
                    </button>
                    <button type="button"
                            @click="stockModalTab = 'invoices'"
                            class="px-4 py-2 text-xs font-semibold border-b-2 transition -mb-px flex items-center gap-1.5"
                            :class="stockModalTab === 'invoices' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16V6a1 1 0 00-1-1H4a1 1 0 00-1 1v10a1 1 0 001 1h1m8-1a1 1 0 01-1 1H9m4-1V8a1 1 0 011-1h2.586a1 1 0 01.707.293l3.414 3.414a1 1 0 01.293.707V16a1 1 0 01-1 1h-1m-6-1a1 1 0 001 1h1M5 17a2 2 0 104 0m-4 0a2 2 0 114 0m6 0a2 2 0 104 0m-4 0a2 2 0 114 0"/></svg>
                        <span>Incoming Deliveries (In-Transit)</span>
                        <span class="px-1.5 py-0.2 rounded-full text-[10px] font-bold"
                              :class="(stockModalData.invoices && stockModalData.invoices.length > 0) ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-500'"
                              x-text="(stockModalData.invoices && stockModalData.invoices.length) || 0"></span>
                    </button>
                </div>

                <!-- Tab 1: Warehouse Breakdown -->
                <div x-show="stockModalTab === 'warehouses'">
                    <template x-if="getWarehouseList().length > 0">
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="p-2.5">Warehouse</th>
                                        <th class="p-2.5 text-right">Physical Available</th>
                                        <th class="p-2.5 text-right">In-Transit</th>
                                        <th class="p-2.5 text-right">Total Expected</th>
                                        <th class="p-2.5">Next Delivery Date</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="w in getWarehouseList()" :key="w.warehouse_id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="p-2.5 font-medium text-slate-800 flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                                <span x-text="w.warehouse_name"></span>
                                            </td>
                                            <td class="p-2.5 text-right font-mono font-semibold"
                                                :class="w.physical_stock > 0 ? 'text-emerald-700' : 'text-slate-400'"
                                                x-text="formatQty(w.physical_stock)"></td>
                                            <td class="p-2.5 text-right font-mono font-semibold"
                                                :class="w.in_transit_stock > 0 ? 'text-indigo-700' : 'text-slate-300'"
                                                x-text="formatQty(w.in_transit_stock)"></td>
                                            <td class="p-2.5 text-right font-mono font-bold text-slate-900"
                                                x-text="formatQty(w.total_expected_stock)"></td>
                                            <td class="p-2.5 text-slate-700" x-text="w.next_delivery_date || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="getWarehouseList().length === 0">
                        <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <p class="text-xs">No warehouse stock or pending shipments recorded for this product.</p>
                        </div>
                    </template>
                </div>

                <!-- Tab 2: Incoming In-Transit Invoices -->
                <div x-show="stockModalTab === 'invoices'">
                    <template x-if="stockModalData.invoices && stockModalData.invoices.length > 0">
                        <div class="border border-slate-200 rounded-xl overflow-hidden max-h-80 overflow-y-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead class="bg-slate-50 text-slate-600 font-semibold border-b border-slate-200 sticky top-0">
                                    <tr>
                                        <th class="p-2.5">Invoice #</th>
                                        <th class="p-2.5">Supplier</th>
                                        <th class="p-2.5">Warehouse</th>
                                        <th class="p-2.5 text-right">Ordered</th>
                                        <th class="p-2.5 text-right">Received</th>
                                        <th class="p-2.5 text-right">In Transit</th>
                                        <th class="p-2.5">Expected Delivery</th>
                                        <th class="p-2.5">Status</th>
                                        <th class="p-2.5 text-right">Action</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    <template x-for="inv in stockModalData.invoices" :key="inv.item_id">
                                        <tr class="hover:bg-slate-50/70">
                                            <td class="p-2.5 font-mono font-semibold text-slate-800" x-text="inv.invoice_no"></td>
                                            <td class="p-2.5 text-slate-700" x-text="inv.supplier_name"></td>
                                            <td class="p-2.5 text-slate-700" x-text="inv.warehouse_name"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-600" x-text="formatQty(inv.ordered_qty)"></td>
                                            <td class="p-2.5 text-right font-mono text-slate-600" x-text="formatQty(inv.received_qty)"></td>
                                            <td class="p-2.5 text-right font-mono font-bold text-indigo-700 bg-indigo-50/40" x-text="formatQty(inv.in_transit_qty)"></td>
                                            <td class="p-2.5 whitespace-nowrap">
                                                <div class="flex items-center gap-1">
                                                    <span class="font-medium"
                                                          :class="inv.is_overdue ? 'text-rose-600 font-semibold' : 'text-slate-800'"
                                                          x-text="inv.expected_delivery_date || 'Not specified'"></span>
                                                    <span x-show="inv.is_overdue"
                                                          class="px-1 py-0.2 text-[9px] font-bold rounded bg-rose-50 text-rose-700 border border-rose-200 uppercase">Overdue</span>
                                                </div>
                                            </td>
                                            <td class="p-2.5">
                                                <span class="px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                                      :class="inv.material_receipt_status === 'partially_received' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200'"
                                                      x-text="inv.status_label"></span>
                                            </td>
                                            <td class="p-2.5 text-right">
                                                <a :href="`{{ url('purchases/purchase-invoices') }}/${inv.invoice_id}`"
                                                   target="_blank"
                                                   class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                                    <span>View</span>
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!stockModalData.invoices || stockModalData.invoices.length === 0">
                        <div class="py-8 text-center text-slate-400 border border-dashed border-slate-200 rounded-xl">
                            <p class="text-xs">No incoming in-transit purchase invoices for this product.</p>
                        </div>
                    </template>
                </div>

                <!-- Modal Footer -->
                <div class="mt-5 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400 italic">In-transit stock is tracked from pending purchase invoices and cannot be allocated for sales until received.</span>
                    <button type="button"
                            @click="closeStockModal()"
                            class="px-4 py-1.5 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 transition">
                        Close
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
function productIndex() {
    return {
        showHideModal: false,
        showUnhideModal: false,
        showDeleteModal: false,
        isProcessing: false,
        isDeleting: false,
        targetProductId: null,
        targetProductName: '',
        targetProductSku: '',
        targetProductSerial: '',

        // Stock Details Modal state
        showStockModal: false,
        stockModalTab: 'warehouses',
        stockModalProduct: { id: null, name: '', sku: '' },
        stockModalData: {
            physical_stock: 0,
            in_transit_stock: 0,
            total_expected_stock: 0,
            next_expected_delivery_date: null,
            next_expected_delivery_date_formatted: '—',
            is_delivery_overdue: false,
            warehouses: {},
            invoices: []
        },

        openStockDetailModal(id, name, sku, stockData, initialTab = 'warehouses') {
            this.stockModalProduct = { id, name, sku };
            this.stockModalData = stockData || {
                physical_stock: 0,
                in_transit_stock: 0,
                total_expected_stock: 0,
                next_expected_delivery_date: null,
                next_expected_delivery_date_formatted: '—',
                is_delivery_overdue: false,
                warehouses: {},
                invoices: []
            };
            this.stockModalTab = initialTab;
            this.showStockModal = true;
        },

        closeStockModal() {
            this.showStockModal = false;
        },

        getWarehouseList() {
            if (!this.stockModalData || !this.stockModalData.warehouses) return [];
            return Object.values(this.stockModalData.warehouses);
        },

        formatQty(val) {
            const num = parseFloat(val);
            if (isNaN(num)) return '0.00';
            return num.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        notification: {
            show: false,
            type: 'success',
            message: '',
        },

        promptHide(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showHideModal = true;
        },

        cancelHide() {
            if (this.isProcessing) return;
            this.showHideModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeHide() {
            if (!this.targetProductId || this.isProcessing) return;
            this.isProcessing = true;

            const url = `{{ url('masters/products') }}/${this.targetProductId}/hide`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showHideModal = false;
                    this.showNotification('success', data.message || 'Product marked as hidden.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to hide product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while hiding product.');
            } finally {
                this.isProcessing = false;
            }
        },

        promptUnhide(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showUnhideModal = true;
        },

        cancelUnhide() {
            if (this.isProcessing) return;
            this.showUnhideModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeUnhide() {
            if (!this.targetProductId || this.isProcessing) return;
            this.isProcessing = true;

            const url = `{{ url('masters/products') }}/${this.targetProductId}/unhide`;
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            try {
                const response = await fetch(url, {
                    method: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': token,
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.showUnhideModal = false;
                    this.showNotification('success', data.message || 'Product restored to visible products.');
                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to unhide product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while unhiding product.');
            } finally {
                this.isProcessing = false;
            }
        },

        promptDelete(id, name, sku, serialNo) {
            this.targetProductId = id;
            this.targetProductName = name;
            this.targetProductSku = sku;
            this.targetProductSerial = serialNo || '—';
            this.showDeleteModal = true;
        },

        cancelDelete() {
            if (this.isProcessing || this.isDeleting) return;
            this.showDeleteModal = false;
            this.targetProductId = null;
            this.targetProductName = '';
            this.targetProductSku = '';
            this.targetProductSerial = '';
        },

        async executeDelete() {
            if (!this.targetProductId || this.isProcessing || this.isDeleting) return;
            this.isProcessing = true;
            this.isDeleting = true;

            const url = `{{ url('masters/products') }}/${this.targetProductId}`;
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
                    this.showNotification('success', data.message || 'Product deleted successfully.');

                    const rows = document.querySelectorAll('#listing-container tbody tr:not([data-empty])');
                    const currentUrl = new URL(window.location.href);
                    let currentPage = parseInt(currentUrl.searchParams.get('page') || '1', 10);

                    if (rows.length <= 1 && currentPage > 1) {
                        currentUrl.searchParams.set('page', currentPage - 1);
                        window.history.replaceState(null, '', currentUrl.toString());
                    }

                    await this.refreshList();
                } else {
                    this.showNotification('error', data.message || 'Failed to delete product.');
                }
            } catch (err) {
                console.error(err);
                this.showNotification('error', 'Network error occurred while deleting product.');
            } finally {
                this.isProcessing = false;
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

(function() {
    if (window.__hierarchicalFiltersBound) return;
    window.__hierarchicalFiltersBound = true;

    // Listen on capture phase so invalid dependent dropdown selections
    // are reset BEFORE dynamic-tables.js builds the URL and triggers fetchAndSwap.
    document.addEventListener('change', function(e) {
        const filter = e.target;
        if (!filter || !filter.matches || !filter.matches('[data-dynamic-filter]')) return;

        const group = filter.closest('[data-hierarchical-filter-group]');
        if (!group) return;

        const brandSelect = group.querySelector('select[name="brand_id"]');
        const categorySelect = group.querySelector('select[name="category_id"]');
        const subCategorySelect = group.querySelector('select[name="sub_category_id"]');

        if (!brandSelect || !categorySelect || !subCategorySelect) return;

        let allCats = [];
        let allSubCats = [];
        try {
            allCats = JSON.parse(group.getAttribute('data-all-categories') || '[]');
            allSubCats = JSON.parse(group.getAttribute('data-all-subcategories') || '[]');
        } catch (_) {}

        if (filter === brandSelect) {
            const newBrandId = brandSelect.value;
            const currentCatId = categorySelect.value;

            if (currentCatId && newBrandId) {
                // Check if current category is tied to another brand
                const currentCat = allCats.find(c => String(c.id) === String(currentCatId));
                if (currentCat && currentCat.brand_id && String(currentCat.brand_id) !== String(newBrandId)) {
                    categorySelect.value = '';
                    subCategorySelect.value = '';
                }
            }
        } else if (filter === categorySelect) {
            const newCatId = categorySelect.value;
            const currentSubId = subCategorySelect.value;

            if (currentSubId && newCatId) {
                // Check if current subcategory belongs to another category
                const currentSub = allSubCats.find(sc => String(sc.id) === String(currentSubId));
                if (currentSub && currentSub.category_id && String(currentSub.category_id) !== String(newCatId)) {
                    subCategorySelect.value = '';
                }
            }
        }
    }, true);

    // Keep return_url on all state-preserving actions updated with live query string
    document.addEventListener('click', function(e) {
        const link = e.target.closest('[data-preserve-state]');
        if (!link || !link.href) return;
        try {
            const u = new URL(link.href, window.location.origin);
            const appBasePath = @json(rtrim(request()->getBasePath(), '/'));
            let livePathAndQuery = window.location.pathname + window.location.search;
            if (appBasePath && livePathAndQuery === appBasePath) {
                livePathAndQuery = '/';
            } else if (appBasePath && livePathAndQuery.startsWith(appBasePath + '/')) {
                livePathAndQuery = livePathAndQuery.slice(appBasePath.length) || '/';
            } else if (appBasePath && livePathAndQuery.startsWith(appBasePath + '?')) {
                livePathAndQuery = '/' + livePathAndQuery.slice(appBasePath.length);
            }
            u.searchParams.set('return_url', livePathAndQuery);
            link.href = u.toString();
        } catch (_) {}
    }, true);
})();
</script>
@endpush
@endsection
