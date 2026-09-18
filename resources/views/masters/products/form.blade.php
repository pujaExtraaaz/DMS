@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Product' : 'Create Product')
@section('content')
<x-ui.page-header :title="$item->exists ? 'Edit Product' : 'Create Product'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('masters.products.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST"
          action="{{ $item->exists ? route('masters.products.update', $item) : route('masters.products.store') }}"
          class="space-y-6 max-w-5xl"
          enctype="multipart/form-data">
        @csrf @if($item->exists) @method('PUT') @endif

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Identity</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="name" label="Product Name" :value="old('name', $item->name)" required />
                <x-ui.input name="sku" label="SKU" :value="old('sku', $item->sku)" required />
                <x-ui.input name="hsn_code" label="HSN Code" :value="old('hsn_code', $item->hsn_code)" />
                <x-ui.select name="base_uom_id" label="Base UOM" required placeholder="Select UOM">
                    @foreach($uoms as $u)
                        <option value="{{ $u->id }}" @selected(old('base_uom_id', $item->base_uom_id)==$u->id)>{{ $u->name }} ({{ $u->code }})</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="brand_id" label="Brand" placeholder="Select">
                    <option value=""></option>
                    @foreach($brands as $b)
                        <option value="{{ $b->id }}" @selected(old('brand_id', $item->brand_id)==$b->id)>{{ $b->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="category_id" label="Category" placeholder="Select">
                    <option value=""></option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $item->category_id)==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.select name="sub_category_id" label="Sub Category" placeholder="Select">
                    <option value=""></option>
                    @foreach($subCategories as $s)
                        <option value="{{ $s->id }}" @selected(old('sub_category_id', $item->sub_category_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </x-ui.select>
                <x-ui.input name="color_variant" label="Color / Variant" :value="old('color_variant', $item->color_variant)" placeholder="e.g. Red, 128GB, Silver" />
                <x-ui.select name="tracking_type" label="Tracking Type">
                    @foreach(['none'=>'Non-tracked','serial'=>'Serial-wise','batch'=>'Batch-wise'] as $val=>$label)
                        <option value="{{ $val }}" @selected(old('tracking_type', $item->tracking_type ?? 'none')==$val)>{{ $label }}</option>
                    @endforeach
                </x-ui.select>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Pricing</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="tax_rate" label="Tax Rate (%)" type="number" step="0.01" :value="old('tax_rate', $item->tax_rate ?? 0)" />
                <x-ui.input name="calculation_mrp" label="MRP" type="number" step="0.01" :value="old('calculation_mrp', $item->calculation_mrp ?? 0)" />
                <x-ui.input name="purchase_price" label="Purchase Price" type="number" step="0.01" :value="old('purchase_price', $item->purchase_price ?? 0)" />
                <x-ui.input name="trade_price" label="Trade Price" type="number" step="0.01" :value="old('trade_price', $item->trade_price ?? 0)" />
                <x-ui.input name="selling_price" label="Selling Price" type="number" step="0.01" :value="old('selling_price', $item->selling_price ?? 0)" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Discount rules</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.select name="discount_type" label="Product Discount Type">
                    <option value="percent" @selected(old('discount_type', $item->discount_type ?? 'percent')==='percent')>Percentage (%)</option>
                    <option value="flat" @selected(old('discount_type', $item->discount_type ?? 'percent')==='flat')>Flat Amount (₹)</option>
                </x-ui.select>
                <x-ui.input name="discount_value" label="Product Discount Value" type="number" step="0.01" :value="old('discount_value', $item->discount_value ?? 0)" />
                <div class="flex items-end">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="apply_discount_on_payable" value="1"
                               @checked(old('apply_discount_on_payable', $item->apply_discount_on_payable ?? false))
                               class="rounded border-gray-300 text-indigo-600">
                        Apply discount at billing (auto-applied on payable)
                    </label>
                </div>

                <x-ui.select name="selling_discount_type" label="Selling-time Discount Type">
                    <option value="percent" @selected(old('selling_discount_type', $item->selling_discount_type ?? 'percent')==='percent')>Percentage (%)</option>
                    <option value="flat" @selected(old('selling_discount_type', $item->selling_discount_type ?? 'percent')==='flat')>Flat Amount (₹)</option>
                </x-ui.select>
                <x-ui.input name="selling_discount_value" label="Selling-time Discount Value" type="number" step="0.01" :value="old('selling_discount_value', $item->selling_discount_value ?? 0)" />
                <div></div>
            </div>
            <p class="mt-2 text-xs text-slate-500">Product Discount is stored on master; Selling-time Discount is the default offered at billing but can still be overridden per invoice line.</p>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Warranty (split by party)</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="sender_warranty_months" label="Sender / Supplier Warranty (months)" type="number" min="0"
                            :value="old('sender_warranty_months', $item->sender_warranty_months)" />
                <x-ui.input name="customer_warranty_months" label="Customer Warranty (months)" type="number" min="0"
                            :value="old('customer_warranty_months', $item->customer_warranty_months)" />
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sender Warranty Terms</label>
                    <textarea name="sender_warranty_terms" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('sender_warranty_terms', $item->sender_warranty_terms) }}</textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Customer Warranty Terms</label>
                    <textarea name="customer_warranty_terms" rows="3" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('customer_warranty_terms', $item->customer_warranty_terms) }}</textarea>
                </div>
            </div>
        </div>

        @php
            $altUoms = old('product_uoms', $item->exists ? $item->productUoms->where('is_base', false)->map(fn($u) => [
                'uom_id' => $u->uom_id,
                'label' => $u->label,
                'conversion_factor' => (float) $u->conversion_factor,
                'selling_price' => $u->selling_price,
                'trade_price' => $u->trade_price,
                'purchase_price' => $u->purchase_price,
                'mrp' => $u->mrp,
                'is_default_sales' => (bool) $u->is_default_sales,
            ])->values()->toArray() : []);
            $uomOptions = $uoms->map(fn($u) => ['id' => $u->id, 'name' => $u->name.' ('.$u->code.')'])->values()->toArray();
        @endphp
        <div x-data='@json(["rows" => $altUoms, "uoms" => $uomOptions])'>
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-sm font-semibold text-slate-700">Alternate units / sub-units (with per-UOM pricing)</h3>
                <button type="button"
                        @click='rows.push({ uom_id:"", label:"", conversion_factor:1, selling_price:null, trade_price:null, purchase_price:null, mrp:null, is_default_sales:false })'
                        class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                    + Add unit
                </button>
            </div>
            <p class="text-xs text-slate-500 mb-3">The base UOM prices above are used automatically. Add alternate packaging (box, case, dozen…) with their own conversion factor and price. Leave a price blank to derive from base × factor at billing time.</p>
            <div class="space-y-3">
                <template x-for="(row, idx) in rows" :key="idx">
                    <div class="grid grid-cols-1 md:grid-cols-8 gap-3 items-end rounded-lg border border-slate-200 p-3">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Unit</label>
                            <select :name="`product_uoms[${idx}][uom_id]`" x-model="row.uom_id" class="block w-full rounded-lg border-gray-300 text-sm">
                                <option value="">Select…</option>
                                <template x-for="u in uoms" :key="u.id"><option :value="u.id" x-text="u.name"></option></template>
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="block text-xs font-medium text-gray-700 mb-1">Label</label>
                            <input type="text" :name="`product_uoms[${idx}][label]`" x-model="row.label" placeholder="e.g. Box of 12" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Factor</label>
                            <input type="number" step="0.0001" :name="`product_uoms[${idx}][conversion_factor]`" x-model="row.conversion_factor" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Selling</label>
                            <input type="number" step="0.01" :name="`product_uoms[${idx}][selling_price]`" x-model="row.selling_price" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Trade</label>
                            <input type="number" step="0.01" :name="`product_uoms[${idx}][trade_price]`" x-model="row.trade_price" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">Purchase</label>
                            <input type="number" step="0.01" :name="`product_uoms[${idx}][purchase_price]`" x-model="row.purchase_price" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1">MRP</label>
                            <input type="number" step="0.01" :name="`product_uoms[${idx}][mrp]`" x-model="row.mrp" class="block w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div class="md:col-span-8 flex items-center justify-between">
                            <label class="flex items-center gap-2 text-xs">
                                <input type="hidden" :name="`product_uoms[${idx}][is_default_sales]`" value="0">
                                <input type="checkbox" :name="`product_uoms[${idx}][is_default_sales]`" value="1" x-model="row.is_default_sales" class="rounded border-gray-300 text-indigo-600">
                                Default sales unit
                            </label>
                            <button type="button" @click="rows.splice(idx, 1)" class="text-xs font-semibold text-red-600 hover:text-red-800">Remove</button>
                        </div>
                    </div>
                </template>
                <template x-if="rows.length === 0">
                    <div class="text-xs text-slate-500 italic">No alternate units yet — click "Add unit" to introduce packaging like Box, Case, Dozen…</div>
                </template>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Inventory</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-ui.input name="min_stock" label="Min Stock" type="number" step="0.0001" :value="old('min_stock', $item->min_stock ?? 0)" />
                <x-ui.input name="reorder_level" label="Reorder Level" type="number" step="0.0001" :value="old('reorder_level', $item->reorder_level ?? 0)" />
                <x-ui.input name="lifespan_days" label="Lifespan (days)" type="number" :value="old('lifespan_days', $item->lifespan_days)" />
                <x-ui.input name="catalog_link" label="Catalog Link" :value="old('catalog_link', $item->catalog_link)" />
            </div>
        </div>

        <div>
            <h3 class="text-sm font-semibold text-slate-700 mb-3">Media &amp; description</h3>
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
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                <textarea name="description" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('description', $item->description) }}</textarea>
            </div>
            <div class="mt-3">
                <label class="block text-sm font-medium text-gray-700 mb-1">Specification</label>
                <textarea name="specification" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('specification', $item->specification) }}</textarea>
            </div>
        </div>

        {{-- Legacy hidden fields — kept for backward compatibility, no longer surfaced in the form --}}
        <input type="hidden" name="warranty_months" value="{{ old('warranty_months', $item->warranty_months ?? '') }}">
        <input type="hidden" name="warranty_terms" value="{{ old('warranty_terms', $item->warranty_terms ?? '') }}">
        <input type="hidden" name="discount_percent" value="{{ old('discount_percent', $item->discount_percent ?? 0) }}">
        <input type="hidden" name="aging_threshold_days" value="{{ old('aging_threshold_days', $item->aging_threshold_days ?? '') }}">
        <input type="hidden" name="credit_period_days" value="{{ old('credit_period_days', $item->credit_period_days ?? '') }}">
        <input type="hidden" name="payment_period_days" value="{{ old('payment_period_days', $item->payment_period_days ?? '') }}">

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-gray-300 text-indigo-600"> Active
        </label>

        <x-ui.button type="submit" variant="primary">Save</x-ui.button>
    </form>
</x-ui.card>

@if(isset($priceHistory) && $priceHistory->count())
    <x-ui.card class="mt-6" title="Price change history">
        <x-ui.table>
            <x-slot name="head">
                <tr>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Effective From</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Effective To</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">UOM</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">MRP</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Purchase</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Trade</th>
                    <th class="px-4 py-2 text-right text-xs font-semibold uppercase text-gray-500">Selling</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">Source</th>
                    <th class="px-4 py-2 text-left text-xs font-semibold uppercase text-gray-500">By</th>
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
@endif
@endsection
