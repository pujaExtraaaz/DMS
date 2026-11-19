@extends('layouts.dms')
@section('title', $item->exists ? 'Edit Quotation' : 'Create Quotation')
@section('content')
@php
    $initialRows = old('items');
    if (! is_array($initialRows)) {
        if ($item->exists) {
            $initialRows = $item->items->map(fn ($i) => [
                'product_id' => (string) $i->product_id,
                'uom_id' => (string) $i->uom_id,
                'quantity' => (float) $i->quantity,
                'unit_price' => (string) $i->unit_price,
                'description' => $i->description ?: '',
                'hsn_code' => $i->hsn_code ?: '',
                'mrp' => $i->mrp !== null ? (string) $i->mrp : '',
                'tax_rate' => $i->tax_rate !== null ? (string) $i->tax_rate : '',
                'existing_image_path' => $i->image_path ?: '',
                'image_preview' => $i->imageUrl() ?: '',
            ])->values()->all();
        } else {
            $initialRows = [[
                'product_id' => '',
                'uom_id' => '',
                'quantity' => 1,
                'unit_price' => '',
                'description' => '',
                'hsn_code' => '',
                'mrp' => '',
                'tax_rate' => '',
                'existing_image_path' => '',
                'image_preview' => '',
            ]];
        }
    } else {
        $initialRows = collect($initialRows)->map(function ($row) {
            $row = is_array($row) ? $row : [];
            $row['existing_image_path'] = $row['existing_image_path'] ?? '';
            $row['image_preview'] = $row['image_preview'] ?? (
                ! empty($row['existing_image_path'])
                    ? asset('storage/'.$row['existing_image_path'])
                    : ''
            );
            $row['uom_id'] = isset($row['uom_id']) ? (string) $row['uom_id'] : '';
            $row['product_id'] = isset($row['product_id']) ? (string) $row['product_id'] : '';

            return $row;
        })->values()->all();
    }
    $initialCustomerId = (string) old('customer_id', $item->customer_id ?? '');
@endphp
<x-ui.page-header :title="$item->exists ? 'Edit Quotation' : 'Create Quotation'">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('quotations.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST"
      action="{{ $item->exists ? route('quotations.update', $item) : route('quotations.store') }}"
      enctype="multipart/form-data"
      class="space-y-4"
      x-data="quotationForm(@js($productPayload), @js($initialRows), @js($initialCustomerId), @js(route('orders.resolve-price')))">
    @csrf
    @if($item->exists) @method('PUT') @endif
    @if($item->company_id)
        <input type="hidden" name="company_id" value="{{ old('company_id', $item->company_id) }}">
    @endif

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        @if($item->exists)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No.</label>
                <input type="text" value="{{ $item->quotation_no }}" class="block w-full rounded-lg border-gray-300 bg-slate-100 text-slate-600 text-sm cursor-not-allowed font-mono" readonly disabled>
                <p class="mt-1 text-xs text-slate-500">Voucher number is locked.</p>
            </div>
        @elseif(isset($qtnSeries) && $qtnSeries && $qtnSeries->isManual())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No. *</label>
                <input type="text" name="quotation_no" value="{{ old('quotation_no') }}" placeholder="Enter quotation number" class="block w-full rounded-lg border-gray-300 text-sm uppercase" required>
                <p class="mt-1 text-xs text-amber-600">Manual voucher numbering is active.</p>
            </div>
        @elseif(isset($qtnSeries) && $qtnSeries && $qtnSeries->isManualOverride())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No. (Manual Override)</label>
                <input type="text" name="quotation_no" value="{{ old('quotation_no', $nextNumber) }}" placeholder="{{ $nextNumber ?? 'Leave blank to auto-generate' }}" class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono">
                <p class="mt-1 text-xs text-indigo-600">Auto-suggested. You may edit or keep default.</p>
            </div>
        @elseif(isset($qtnSeries) && $qtnSeries && $qtnSeries->isMultiUserAuto())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No.</label>
                <input type="text" value="[Auto-generated on Save]" class="block w-full rounded-lg border-gray-300 bg-slate-50 text-slate-500 text-sm cursor-not-allowed font-mono" readonly disabled>
                <p class="mt-1 text-xs text-emerald-600">Multi-user Auto sequence: {{ $qtnSeries->preview() }}</p>
            </div>
        @elseif(isset($qtnSeries) && $qtnSeries && $qtnSeries->isAutomatic())
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No.</label>
                <input type="text" value="{{ $nextNumber ?? $qtnSeries->preview() }}" class="block w-full rounded-lg border-gray-300 bg-slate-50 text-slate-500 text-sm cursor-not-allowed font-mono" readonly disabled>
                <p class="mt-1 text-xs text-sky-600">Automatic numbering: {{ $qtnSeries->preview() }}</p>
            </div>
        @else
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Quotation No.</label>
                <input type="text" name="quotation_no" value="{{ old('quotation_no') }}" placeholder="Auto-generated if blank" class="block w-full rounded-lg border-gray-300 text-sm uppercase font-mono">
                <p class="mt-1 text-xs text-slate-500">Leave blank to auto-generate.</p>
            </div>
        @endif

        <div class="space-y-1">
            <label for="customer_id" class="block text-sm font-medium text-gray-700">Customer <span class="text-red-500">*</span></label>
            <select id="customer_id" name="customer_id" x-model="customerId" @change="onCustomerChange()" required
                    class="block w-full rounded-lg border-gray-300 shadow-sm text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <option value="">Select customer</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" @selected($initialCustomerId == (string) $customer->id)>{{ $customer->name }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.input name="quotation_date" label="Quotation Date" type="date" :value="old('quotation_date', optional($item->quotation_date)->toDateString() ?? now()->toDateString())" required />
        <x-ui.input name="valid_until" label="Valid Until" type="date" :value="old('valid_until', optional($item->valid_until)->toDateString())" />
    </div>

    @if(!empty($isManualNumbering) && ! $item->exists)
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input name="quotation_no" label="Quotation No (Manual)" :value="old('quotation_no')" required
                        placeholder="Enter voucher number" />
            <div class="md:col-span-2 text-xs text-slate-500 self-end pb-2">
                Sales Quotation numbering is set to <strong>Manual</strong> in Setup → Voucher Numbering.
                @if(!empty($numberSeries))
                    Series: {{ $numberSeries->name }} ({{ $numberSeries->prefix }})
                @endif
            </div>
        </div>
    @elseif(!empty($numberSeries) && ! $item->exists)
        <div class="text-xs text-slate-500 -mt-2">
            Numbering: <strong>{{ $numberSeries->name }}</strong>
            — preview style <code class="bg-slate-100 px-1 rounded">{{ $numberSeries->preview() }}</code>
            (configure in Setup → Voucher Numbering)
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <x-ui.input name="title" label="Quotation Title / Section" :value="old('title', $item->title)"/>
        <x-ui.input name="discount_amount" label="Discount Amount" type="number" step="0.01" :value="old('discount_amount', $item->discount_amount ?? 0)" />
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Special Instructions</label>
            <textarea name="special_instructions" rows="4" class="block w-full rounded-lg border-gray-300 text-sm"
                      placeholder="Shown beside customer details on preview">{{ old('special_instructions', $item->special_instructions ?? $defaultSpecialInstructions) }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label>
            <textarea name="terms_conditions" rows="4" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('terms_conditions', $item->terms_conditions ?? $defaultTerms) }}</textarea>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Internal Notes</label>
        <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes', $item->notes) }}</textarea>
    </div>

    <div class="border rounded-xl overflow-hidden">
        <div class="bg-slate-50 px-3 py-2 text-sm font-semibold flex items-center justify-between">
            <span>Line Items</span>
            <button type="button" class="text-sm font-medium text-indigo-600" @click="addRow()">+ Add line</button>
        </div>
        <div class="p-3 space-y-4">
            <template x-for="(row, index) in rows" :key="row._key">
                <div class="rounded-lg border border-slate-200 p-3 space-y-3 bg-white">
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                        <div class="md:col-span-2 flex items-end gap-2">
                            <div class="shrink-0">
                                <label class="text-xs text-slate-500 block mb-1">Image</label>
                                <div class="relative h-14 w-14 rounded-lg border border-slate-200 bg-slate-50 overflow-hidden flex items-center justify-center">
                                    <template x-if="row.image_preview">
                                        <img :src="row.image_preview" alt="" class="h-full w-full object-contain">
                                    </template>
                                    <template x-if="!row.image_preview">
                                        <span class="text-[10px] text-slate-400 text-center px-1">No image</span>
                                    </template>
                                </div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <label class="text-xs text-slate-500">Upload</label>
                                <input type="file"
                                       :name="`items[${index}][image]`"
                                       accept="image/jpeg,image/png,image/webp,image/gif"
                                       @change="onImageChange(index, $event)"
                                       class="block w-full text-[11px] text-slate-600 file:mr-2 file:rounded file:border-0 file:bg-indigo-50 file:px-2 file:py-1 file:text-xs file:font-medium file:text-indigo-700">
                                <input type="hidden" :name="`items[${index}][existing_image_path]`" :value="row.existing_image_path || ''">
                            </div>
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs text-slate-500">Product *</label>
                            <select :name="`items[${index}][product_id]`"
                                    :value="row.product_id"
                                    @change="row.product_id = $event.target.value; onProductChange(index)"
                                    class="w-full rounded-lg border-gray-300 text-sm" required>
                                <option value="">Select</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500">UOM *</label>
                            <select :name="`items[${index}][uom_id]`"
                                    :key="'uom-'+row._key+'-'+row.product_id+'-'+row.uom_id"
                                    :value="row.uom_id"
                                    @change="row.uom_id = $event.target.value; resolvePrice(index)"
                                    class="w-full rounded-lg border-gray-300 text-sm" required>
                                <option value="">Select</option>
                                @foreach($uoms as $uom)
                                    <option value="{{ $uom->id }}">{{ $uom->code }}{{ $uom->name && $uom->name !== $uom->code ? ' - '.$uom->name : '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="md:col-span-1">
                            <label class="text-xs text-slate-500">Qty *</label>
                            <input type="number" step="0.0001" min="0.0001" :name="`items[${index}][quantity]`" x-model="row.quantity" @change="resolvePrice(index)" class="w-full rounded-lg border-gray-300 text-sm" required>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs text-slate-500">Unit Price</label>
                            <input type="number" step="0.01" min="0" :name="`items[${index}][unit_price]`" x-model="row.unit_price" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Auto from price list">
                        </div>
                        <div class="md:col-span-2 flex items-end">
                            <button type="button" class="text-sm text-red-600 pb-2" @click="removeRow(index)" x-show="rows.length > 1">Remove</button>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                        <div>
                            <label class="text-xs text-slate-500">HSN</label>
                            <input type="text" :name="`items[${index}][hsn_code]`" x-model="row.hsn_code" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">MRP</label>
                            <input type="number" step="0.01" min="0" :name="`items[${index}][mrp]`" x-model="row.mrp" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                        <div>
                            <label class="text-xs text-slate-500">Tax %</label>
                            <input type="number" step="0.01" min="0" max="100" :name="`items[${index}][tax_rate]`" x-model="row.tax_rate" class="w-full rounded-lg border-gray-300 text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="text-xs text-slate-500">Description (shown on preview)</label>
                        <textarea :name="`items[${index}][description]`" x-model="row.description" rows="2" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    </div>
                </div>
            </template>
        </div>
    </div>

    <x-ui.button type="submit" variant="primary">Save Quotation</x-ui.button>
</form>
</x-ui.card>
@endsection

@push('scripts')
<script>
function quotationForm(products, initialRows, initialCustomerId, resolvePriceUrl) {
    const catalog = {};
    (products || []).forEach((p) => { catalog[String(p.id)] = p; });

    let rowKeySeq = 1;
    const blankRow = () => ({
        _key: 'r' + (rowKeySeq++),
        product_id: '',
        uom_id: '',
        quantity: 1,
        unit_price: '',
        description: '',
        hsn_code: '',
        mrp: '',
        tax_rate: '',
        existing_image_path: '',
        image_preview: '',
    });

    const seedRows = (initialRows && initialRows.length)
        ? initialRows.map((row) => Object.assign(blankRow(), row, {
            product_id: row.product_id != null ? String(row.product_id) : '',
            uom_id: row.uom_id != null ? String(row.uom_id) : '',
            existing_image_path: row.existing_image_path || '',
            image_preview: row.image_preview || '',
        }))
        : [blankRow()];

    return {
        products: catalog,
        customerId: initialCustomerId ? String(initialCustomerId) : '',
        resolvePriceUrl: resolvePriceUrl || '',
        rows: seedRows,
        addRow() {
            this.rows.push(blankRow());
        },
        removeRow(index) {
            if (this.rows.length <= 1) return;
            this.rows.splice(index, 1);
        },
        catalogFallbackPrice(prod) {
            if (!prod) return '';
            if (prod.selling_price !== null && prod.selling_price !== undefined && Number(prod.selling_price) > 0) {
                return String(prod.selling_price);
            }
            if (prod.trade_price !== null && prod.trade_price !== undefined && Number(prod.trade_price) > 0) {
                return String(prod.trade_price);
            }
            if (prod.mrp !== null && prod.mrp !== undefined && Number(prod.mrp) > 0) {
                return String(prod.mrp);
            }
            return '';
        },
        applyProductDefaults(index) {
            const row = this.rows[index];
            if (!row) return null;
            const prod = this.products[String(row.product_id)];
            if (!prod) return null;

            const nextUom = prod.base_uom_id ? String(prod.base_uom_id) : '';
            row.hsn_code = prod.hsn_code || '';
            row.mrp = prod.mrp > 0 ? String(prod.mrp) : '';
            row.tax_rate = (prod.tax_rate !== null && prod.tax_rate !== undefined)
                ? String(prod.tax_rate)
                : '';
            row.description = prod.description || prod.name || '';
            row.unit_price = this.catalogFallbackPrice(prod);

            if (!row.existing_image_path || !row.image_preview) {
                row.image_preview = prod.image_url || '';
                if (!row.existing_image_path && prod.image_path) {
                    row.existing_image_path = prod.image_path;
                }
            }

            // Force select remount so the UOM option paints after product change.
            row.uom_id = '';
            this.$nextTick(() => {
                row.uom_id = nextUom;
                this.resolvePrice(index);
            });

            return prod;
        },
        onProductChange(index) {
            this.applyProductDefaults(index);
        },
        onImageChange(index, event) {
            const row = this.rows[index];
            if (!row) return;
            const file = event.target.files && event.target.files[0];
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (e) => {
                row.image_preview = e.target.result;
            };
            reader.readAsDataURL(file);
        },
        onCustomerChange() {
            this.rows.forEach((_, index) => this.resolvePrice(index));
        },
        async resolvePrice(index) {
            const row = this.rows[index];
            if (!row || !row.product_id || !row.uom_id || Number(row.quantity) <= 0) {
                return;
            }

            if (!this.customerId || !this.resolvePriceUrl) {
                return;
            }

            try {
                const params = new URLSearchParams({
                    customer_id: this.customerId,
                    product_id: row.product_id,
                    uom_id: row.uom_id,
                    quantity: row.quantity,
                });
                const response = await fetch(this.resolvePriceUrl + '?' + params.toString(), {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                if (response.ok && data.found && data.unit_price !== null && data.unit_price !== undefined) {
                    row.unit_price = String(data.unit_price);
                }
            } catch (e) {
                // Keep catalog fallback already applied.
            }
        },
    };
}
</script>
@endpush
