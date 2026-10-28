@extends('layouts.dms')
@section('title', $product->name)

@section('content')
<div class="space-y-6 max-w-6xl mx-auto">
    <x-ui.page-header :title="$product->name" description="View only. This page does not change the product.">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('masters.products.index')">Back</x-ui.button>
            <x-ui.button variant="primary" :href="route('masters.products.edit', $product)">Edit</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <div class="flex flex-wrap items-center gap-2">
            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $product->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $product->is_active ? 'Active' : 'Inactive' }}
            </span>
            <span class="inline-flex items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">
                {{ $product->warranty ?: 'No warranty' }}
            </span>
            @if($product->warranty_months)
                <span class="text-xs text-slate-500">{{ $product->warranty_months }} months</span>
            @endif
        </div>
    </x-ui.card>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <x-ui.card title="Product">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Name</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $product->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Part Number</dt>
                    <dd class="mt-0.5 font-mono text-slate-900">{{ $product->sku ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Serial No.</dt>
                    <dd class="mt-0.5 font-mono text-slate-900">{{ $product->serial_no ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Model</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->model ?: ($product->models ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Brand</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->brand?->name ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Category</dt>
                    <dd class="mt-0.5 text-slate-900">{{ collect([$product->category?->name, $product->subCategory?->name])->filter()->join(' · ') ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Product Type</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->productType?->name ?: ($product->product_type ?: '—') }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Type of Supply</dt>
                    <dd class="mt-0.5 text-slate-900">{{ \App\Domains\Master\Models\Product::SUPPLY_TYPES[$product->supply_type] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">HSN Code</dt>
                    <dd class="mt-0.5 font-mono text-slate-900">{{ $product->hsn_code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">GST Rate</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->tax_rate }}%</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Warranty">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Warranty Period</dt>
                    <dd class="mt-0.5 font-medium text-slate-900">{{ $product->warranty ?: 'No warranty' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Length</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->warranty_months ? $product->warranty_months.' months' : '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-xs font-semibold text-slate-500">How dates are applied</dt>
                    <dd class="mt-0.5 text-slate-700">Each serial starts from its purchase or inward date. After a sale, the warranty starts again from the sales invoice date. Under Warranty or Out of Warranty is shown on the serial, not on this product.</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Units and Price">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Base Unit</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->baseUom?->code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Alternative Unit</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->alternateUom?->code ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Conversion</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->conversion_factor ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Purchase Price</dt>
                    <dd class="mt-0.5 text-slate-900">
                        {{ $product->purchase_price !== null ? number_format((float) $product->purchase_price, 2) : '—' }}
                        <span class="text-slate-500">{{ \App\Domains\Master\Models\Product::PURCHASE_TAX_BASES[$product->purchase_price_tax_basis] ?? '' }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Selling Price</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->selling_price !== null ? number_format((float) $product->selling_price, 2) : '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Trade Price</dt>
                    <dd class="mt-0.5 text-slate-900">{{ $product->trade_price !== null ? number_format((float) $product->trade_price, 2) : '—' }}</dd>
                </div>
            </dl>
        </x-ui.card>

        <x-ui.card title="Costing">
            <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Costing Method</dt>
                    <dd class="mt-0.5 text-slate-900">{{ \App\Domains\Master\Models\Product::COSTING_METHODS[$product->costing_method] ?? 'Company default' }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-semibold text-slate-500">Market Valuation</dt>
                    <dd class="mt-0.5 text-slate-900">{{ \App\Domains\Master\Models\Product::MARKET_VALUATION_METHODS[$product->market_valuation_method] ?? '—' }}</dd>
                </div>
                @unless($product->dimensions_not_applicable)
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">Dimensions (CM)</dt>
                        <dd class="mt-0.5 text-slate-900">{{ collect([$product->height_cm, $product->length_cm, $product->width_cm])->filter()->join(' × ') ?: '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-500">Net Weight</dt>
                        <dd class="mt-0.5 text-slate-900">{{ $product->net_weight ? $product->net_weight.' '.($product->weight_unit ?: 'KG') : '—' }}</dd>
                    </div>
                @endunless
            </dl>
        </x-ui.card>
    </div>
</div>
@endsection
