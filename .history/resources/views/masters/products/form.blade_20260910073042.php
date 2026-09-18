@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Product' : 'Create Product')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Product' : 'Create Product'"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.products.index')">Back</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card><form method="POST" action="{{ $item->exists ? route('masters.products.update', $item) : route('masters.products.store') }}" class="space-y-4 max-w-4xl" enctype="multipart/form-data">@csrf @if($item->exists) @method('PUT') @endif
<div class="grid grid-cols-1 md:grid-cols-2 gap-4">
<x-ui.input name="name" label="Name" :value="old('name', $item->name)" required />
<x-ui.input name="sku" label="SKU" :value="old('sku', $item->sku)" required />
<x-ui.input name="hsn_code" label="HSN Code" :value="old('hsn_code', $item->hsn_code)" />
<x-ui.select name="base_uom_id" label="Base UOM" required placeholder="Select UOM">@foreach($uoms as $u)<option value="{{ $u->id }}" @selected(old('base_uom_id', $item->base_uom_id)==$u->id)>{{ $u->name }} ({{ $u->code }})</option>@endforeach</x-ui.select>
<x-ui.select name="brand_id" label="Brand" placeholder="Select"><option value=""></option>@foreach($brands as $b)<option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id)==$b->id)>{{ $b->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="category_id" label="Category" placeholder="Select"><option value=""></option>@foreach($categories as $c)<option value="{{ $c->id }}" @selected(old('category_id', $item->category_id)==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="sub_category_id" label="Sub Category" placeholder="Select"><option value=""></option>@foreach($subCategories as $s)<option value="{{ $s->id }}" @selected(old('sub_category_id', $item->sub_category_id)==$s->id)>{{ $s->name }}</option>@endforeach</x-ui.select>
<x-ui.select name="tracking_type" label="Tracking Type">
@foreach(['none'=>'Non-tracked','serial'=>'Serial-wise','batch'=>'Batch-wise'] as $val=>$label)
<option value="{{ $val }}" @selected(old('tracking_type', $item->tracking_type ?? 'none')==$val)>{{ $label }}</option>
@endforeach
</x-ui.select>
<x-ui.input name="tax_rate" label="Tax Rate (%)" type="number" step="0.01" :value="old('tax_rate', $item->tax_rate ?? 0)" />
<x-ui.input name="selling_price" label="Selling Price" type="number" step="0.01" :value="old('selling_price', $item->selling_price ?? 0)" />
<x-ui.input name="trade_price" label="Trade Price" type="number" step="0.01" :value="old('trade_price', $item->trade_price ?? 0)" />
<x-ui.input name="purchase_price" label="Purchase Price" type="number" step="0.01" :value="old('purchase_price', $item->purchase_price ?? 0)" />
<x-ui.input name="calculation_mrp" label="Calculation MRP" type="number" step="0.01" :value="old('calculation_mrp', $item->calculation_mrp ?? 0)" />
<x-ui.input name="discount_percent" label="Discount %" type="number" step="0.01" :value="old('discount_percent', $item->discount_percent ?? 0)" />
<x-ui.input name="warranty_months" label="Warranty (months)" type="number" :value="old('warranty_months', $item->warranty_months)" />
<x-ui.input name="min_stock" label="Min Stock" type="number" step="0.0001" :value="old('min_stock', $item->min_stock ?? 0)" />
<x-ui.input name="reorder_level" label="Reorder Level" type="number" step="0.0001" :value="old('reorder_level', $item->reorder_level ?? 0)" />
<x-ui.input name="aging_threshold_days" label="Aging Threshold (days)" type="number" :value="old('aging_threshold_days', $item->aging_threshold_days)" />
<x-ui.input name="credit_period_days" label="Credit Period (days)" type="number" :value="old('credit_period_days', $item->credit_period_days)" />
<x-ui.input name="payment_period_days" label="Payment Period (days)" type="number" :value="old('payment_period_days', $item->payment_period_days)" />
<x-ui.input name="lifespan_days" label="Lifespan (days)" type="number" :value="old('lifespan_days', $item->lifespan_days)" />
<x-ui.input name="catalog_link" label="Catalog Link" :value="old('catalog_link', $item->catalog_link)" />
</div>
<div>
    <label class="block text-sm font-medium text-gray-700 mb-1">Product Image</label>
    @if($item->image_path)
        <div class="mb-2 flex items-center gap-3">
            <img src="{{ asset('storage/'.$item->image_path) }}" alt="{{ $item->name }}" class="h-20 w-20 rounded-lg object-cover border">
            <label class="flex items-center gap-2 text-sm text-red-600">
                <input type="checkbox" name="remove_image" value="1" class="rounded border-gray-300 text-red-600"> Remove image
            </label>
        </div>
    @endif
    <input type="file" name="image" accept="image/*" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:px-3 file:py-2 file:text-indigo-700">
    <p class="mt-1 text-xs text-slate-500">JPG/PNG up to 4MB. Used on quotations and product master.</p>
</div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('description', $item->description) }}</textarea></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Specification</label><textarea name="specification" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('specification', $item->specification) }}</textarea></div>
<div><label class="block text-sm font-medium text-gray-700 mb-1">Warranty Terms</label><textarea name="warranty_terms" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('warranty_terms', $item->warranty_terms) }}</textarea></div>
<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active</label>
<x-ui.button type="submit" variant="primary">Save</x-ui.button></form></x-ui.card>
@endsection
