@extends('layouts.dms')
@section('title', 'Price List Details · ' . ($priceMaster->isBrandWise() ? ($priceMaster->brand?->name ?? 'Brand') : ($priceMaster->customerType?->name ?? 'Customer Type')))

@php
    $can = fn (...$perms) => auth()->user()?->hasAnyRole(['super-admin', 'client-admin']) || auth()->user()?->hasAnyPermission($perms);
    $targetName = $priceMaster->isBrandWise() ? ($priceMaster->brand?->name ?? '—') : ($priceMaster->customerType?->name ?? '—');
    $typeLabel = $priceMaster->isBrandWise() ? 'Brand Wise' : 'Customer Type Wise';
@endphp

@section('content')
<div class="space-y-6 max-w-7xl mx-auto pb-12">
    {{-- Header --}}
    <x-ui.page-header :title="'Price List · ' . $targetName">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.price-masters.index')">
                <svg class="w-4 h-4 mr-1 text-slate-500 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Back to Price List
            </x-ui.button>
            @if($can('price-master.edit', 'price-master.manage', 'masters.edit', 'masters.manage'))
                <x-ui.button variant="primary" :href="route('masters.price-masters.edit', $priceMaster)">
                    <svg class="w-4 h-4 mr-1 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                    Edit Price
                </x-ui.button>
            @endif
        </x-slot>
    </x-ui.page-header>

    {{-- Summary Card --}}
    <x-ui.card>
        <div class="flex flex-col sm:flex-row items-start sm:items-center gap-5">
            <div class="w-16 h-16 rounded-2xl flex items-center justify-center font-bold text-2xl shadow-md shrink-0 text-white {{ $priceMaster->isBrandWise() ? 'bg-gradient-to-br from-purple-600 to-indigo-700' : 'bg-gradient-to-br from-blue-600 to-indigo-700' }}">
                {{ strtoupper(substr($targetName, 0, 2)) }}
            </div>
            <div class="space-y-1.5 flex-1 min-w-0">
                <div class="flex flex-wrap items-center gap-2.5">
                    <h2 class="text-xl font-bold text-slate-900 truncate">{{ $targetName }}</h2>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $priceMaster->isBrandWise() ? 'bg-purple-100 text-purple-800 border border-purple-200' : 'bg-blue-100 text-blue-800 border border-blue-200' }}">
                        {{ $typeLabel }}
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active
                    </span>
                </div>
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-slate-500">
                    <span class="font-mono bg-slate-100 px-2 py-0.5 rounded text-slate-700">Price List ID: #{{ $priceMaster->id }}</span>
                    @if($priceMaster->name)
                        <span>Price List Name: <strong class="text-slate-800 font-medium">{{ $priceMaster->name }}</strong></span>
                    @endif
                    <span>Company: <strong class="text-slate-800 font-medium">{{ $company?->name ?: 'Extraaaz DMS' }}</strong></span>
                    <span>Created: <span class="text-slate-700">{{ $priceMaster->created_at?->format('d M Y, h:i A') ?: '—' }}</span></span>
                    <span>Last Updated: <span class="text-slate-700">{{ $priceMaster->updated_at?->format('d M Y, h:i A') ?: '—' }}</span></span>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- 1. Product Master Information (Read-Only) --}}
        <x-ui.card title="Product Master Information">
            <div class="mb-3 pb-2 border-b border-slate-100 flex items-center justify-between">
                <span class="text-xs text-slate-500">Auto-fetched from Product Master (Read-Only)</span>
                @if($product)
                    <a href="{{ route('masters.products.edit', $product) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:underline inline-flex items-center gap-0.5">
                        <span>View Product Master</span>
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                @endif
            </div>
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Product Name</dt>
                    <dd class="mt-0.5 font-bold text-slate-900 text-base">{{ $product?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">SKU / Part Number</dt>
                    <dd class="mt-0.5 font-mono font-medium text-slate-800">{{ $product?->sku ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Brand</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $product?->brand?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Category</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $product?->category?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Subcategory</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $product?->subCategory?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Product Type</dt>
                    <dd class="mt-0.5 text-slate-800">{{ $product?->productType?->name ?: ($product?->product_type ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Model / Series</dt>
                    <dd class="mt-0.5 text-slate-800">{{ collect([$product?->model, $product?->series])->filter()->join(' / ') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">HSN Code</dt>
                    <dd class="mt-0.5 font-mono text-slate-800">{{ $product?->hsn_code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">GST Tax Rate</dt>
                    <dd class="mt-0.5 font-semibold text-indigo-700">{{ $product?->tax_rate !== null ? $product->tax_rate . '%' : '0%' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">MRP (Product Master)</dt>
                    <dd class="mt-0.5 font-bold text-slate-900">{{ $mrp > 0 ? '₹' . number_format($mrp, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500 tracking-wider">Base UOM</dt>
                    <dd class="mt-0.5 font-medium text-slate-800">{{ $product?->baseUom?->name ?: ($product?->baseUom?->code ?: '—') }}</dd>
                </div>
            </dl>
        </x-ui.card>

        {{-- 2. Pricing Information & Tax Breakdown --}}
        <x-ui.card title="Pricing Details">
            <div class="mb-3 pb-2 border-b border-slate-100 flex items-center justify-between text-xs text-slate-500">
                <span>Unit: <strong class="text-slate-800 font-semibold">{{ $priceMaster->uom?->code ?: ($priceMaster->uom?->name ?: '—') }}</strong></span>
                <span>Minimum Order Qty: <strong class="text-slate-800 font-semibold">{{ $priceMaster->min_qty !== null ? $priceMaster->min_qty : 'None (1)' }}</strong></span>
            </div>

            <div class="space-y-4">
                {{-- Dealer Price Box --}}
                <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-indigo-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-indigo-900">Dealer Price</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $priceMaster->dealer_price_gst_mode === 'inclusive' ? 'bg-indigo-100 text-indigo-800' : 'bg-slate-200 text-slate-800' }}">
                            {{ $priceMaster->dealer_price_gst_mode === 'inclusive' ? 'GST Inclusive' : 'GST Exclusive' }}
                        </span>
                    </div>
                    @if($dealerPricing['entered'] !== null)
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">Entered Dealer Price:</span>
                                <span class="font-bold text-base text-slate-900">₹{{ number_format($dealerPricing['entered'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span>Base Price (Excl. GST):</span>
                                <span class="font-mono text-slate-800">₹{{ number_format($dealerPricing['exclusive'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span>GST Amount ({{ $dealerPricing['tax_percent'] }}%):</span>
                                <span class="font-mono text-indigo-700 font-semibold">₹{{ number_format($dealerPricing['gst'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-indigo-100 text-slate-900 font-semibold">
                                <span>Total Payable (Incl. GST):</span>
                                <span class="font-bold text-indigo-950 text-sm">₹{{ number_format($dealerPricing['final'], 2) }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic py-2 text-center">No dealer price entered</p>
                    @endif
                </div>

                {{-- Retail Price Box --}}
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-4">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-emerald-100">
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-900">Retail Price</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $priceMaster->retail_price_gst_mode === 'inclusive' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-800' }}">
                            {{ $priceMaster->retail_price_gst_mode === 'inclusive' ? 'GST Inclusive' : 'GST Exclusive' }}
                        </span>
                    </div>
                    @if($retailPricing['entered'] !== null)
                        <div class="space-y-2 text-xs">
                            <div class="flex justify-between items-center">
                                <span class="text-slate-600">Entered Retail Price:</span>
                                <span class="font-bold text-base text-slate-900">₹{{ number_format($retailPricing['entered'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span>Base Price (Excl. GST):</span>
                                <span class="font-mono text-slate-800">₹{{ number_format($retailPricing['exclusive'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-600">
                                <span>GST Amount ({{ $retailPricing['tax_percent'] }}%):</span>
                                <span class="font-mono text-emerald-700 font-semibold">₹{{ number_format($retailPricing['gst'], 2) }}</span>
                            </div>
                            <div class="flex justify-between items-center pt-2 border-t border-emerald-100 text-slate-900 font-semibold">
                                <span>Total Payable (Incl. GST):</span>
                                <span class="font-bold text-emerald-950 text-sm">₹{{ number_format($retailPricing['final'], 2) }}</span>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-400 italic py-2 text-center">No retail price entered</p>
                    @endif
                </div>

                {{-- General Billing Rate / Fallback --}}
                <div class="rounded-xl border border-slate-200 bg-slate-50/60 p-3 text-xs flex justify-between items-center">
                    <div>
                        <span class="text-slate-500 block text-[11px]">General Billing Rate (Base Excl. GST)</span>
                        <span class="font-bold text-slate-900 text-sm">₹{{ number_format((float) $priceMaster->rate, 2) }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-slate-500 block text-[11px]">Legacy Mode</span>
                        <span class="font-medium text-slate-700">{{ $priceMaster->rate_includes_gst ? 'Inclusive of GST' : 'Exclusive of GST' }}</span>
                    </div>
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- 3. Associated Products in this Price List --}}
    <x-ui.card :title="'All Products in this ' . $typeLabel . ' Price List (' . $associatedProducts->count() . ')'">
        <p class="text-xs text-slate-500 mb-4">
            Listing all products and their configured pricing under <strong>{{ $targetName }}</strong>.
        </p>

        <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-semibold text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Product</th>
                        <th class="px-4 py-3">SKU / Code</th>
                        <th class="px-4 py-3">Category</th>
                        <th class="px-4 py-3">Unit</th>
                        <th class="px-4 py-3 text-right">Dealer Price</th>
                        <th class="px-4 py-3 text-right">Retail Price</th>
                        <th class="px-4 py-3 text-right">Min Qty</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse($associatedProducts as $row)
                        @php
                            $isCurrent = (int) $row->id === (int) $priceMaster->id;
                            $rowDealer = $row->dealerPricingSummary();
                            $rowRetail = $row->retailPricingSummary();
                        @endphp
                        <tr class="{{ $isCurrent ? 'bg-indigo-50/50 font-medium' : 'hover:bg-slate-50/80 transition-colors' }}">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1.5">
                                    @if($isCurrent)
                                        <span class="inline-block w-2 h-2 rounded-full bg-indigo-600 shrink-0" title="Currently viewing this product"></span>
                                    @endif
                                    <span class="text-slate-900 font-semibold">{{ $row->product?->name ?: '—' }}</span>
                                    @if($isCurrent)
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-bold bg-indigo-100 text-indigo-700">Viewing</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 font-mono text-slate-600">{{ $row->product?->sku ?: '—' }}</td>
                            <td class="px-4 py-3 text-slate-600">{{ $row->product?->category?->name ?: '—' }}</td>
                            <td class="px-4 py-3 font-mono text-slate-700">{{ $row->uom?->code ?: ($row->uom?->name ?: '—') }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($rowDealer['entered'] !== null)
                                    <div>
                                        <span class="font-bold text-slate-900">₹{{ number_format($rowDealer['entered'], 2) }}</span>
                                        <span class="text-[10px] text-slate-500">({{ $row->dealer_price_gst_mode === 'inclusive' ? 'Inc.' : 'Exc.' }})</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if($rowRetail['entered'] !== null)
                                    <div>
                                        <span class="font-bold text-slate-900">₹{{ number_format($rowRetail['entered'], 2) }}</span>
                                        <span class="text-[10px] text-slate-500">({{ $row->retail_price_gst_mode === 'inclusive' ? 'Inc.' : 'Exc.' }})</span>
                                    </div>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-slate-600">{{ $row->min_qty !== null ? $row->min_qty : '—' }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if(! $isCurrent)
                                        <a href="{{ route('masters.price-masters.show', $row) }}"
                                           class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-slate-700 hover:text-indigo-600 hover:bg-slate-100 border border-slate-200">
                                            View
                                        </a>
                                    @endif
                                    <a href="{{ route('masters.price-masters.edit', $row) }}"
                                       class="inline-flex items-center gap-1 rounded px-2 py-1 text-xs font-semibold text-indigo-700 hover:text-indigo-800 hover:bg-indigo-50 border border-indigo-200">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400">No associated products found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
