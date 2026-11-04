@extends('layouts.dms')
@section('title', 'Edit Purchase Order - ' . $order->po_no)
@section('content')
<div x-data>
    <x-ui.page-header :title="'Edit Purchase Order: ' . $order->po_no">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('purchasing.orders.show', $order)">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>
    <x-ui.card>
    <form method="POST" action="{{ route('purchasing.orders.update', $order) }}" class="space-y-4">
        @csrf
        @method('PUT')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Voucher No.</label>
                <a href="{{ route('organization.document-number-series.index') }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                   title="Configure voucher numbering">
                    Configure
                </a>
            </div>
            <input type="text"
                   value="{{ $order->po_no }}"
                   readonly
                   class="block w-full rounded-lg border-gray-200 bg-slate-50 text-sm font-mono text-slate-700">
        </div>
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Supplier *</label>
                <a href="{{ route('masters.customers.create', [
                        'party_type' => \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                        'return_url' => route('purchasing.orders.edit', $order),
                    ]) }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    + Add Supplier
                </a>
            </div>
            @include('purchasing.orders.partials.supplier-search', [
                'suppliers' => $suppliers,
                'selected' => old('supplier_id', request('supplier_id', $order->supplier_id)),
            ])
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
            <select name="warehouse_id" id="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm" onchange="handleWarehouseChange(this)">
                <option value="" data-address="">Optional</option>
                @foreach($warehouses as $w)
                    @php
                        $wAddress = $w->address ?: ($w->branch?->address ?: ($w->company?->address ?: ''));
                    @endphp
                    <option value="{{ $w->id }}" data-address="{{ $wAddress }}" @selected(old('warehouse_id', $order->warehouse_id) == $w->id)>{{ $w->name }}</option>
                @endforeach
            </select>
        </div>
        <x-ui.input name="po_date" id="po_date" label="PO Date" type="date" :value="old('po_date', $order->po_date?->toDateString())" required onchange="recalcPoDueDate(); updateExpectedQuickHighlight()" oninput="recalcPoDueDate(); updateExpectedQuickHighlight()" />
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Expected Date</label>
            <input type="date" name="expected_date" id="po_expected_date"
                   value="{{ old('expected_date', $order->expected_date?->toDateString()) }}"
                   onchange="updateExpectedQuickHighlight()"
                   oninput="updateExpectedQuickHighlight()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <div class="flex flex-wrap items-center gap-1.5 mt-2">
                <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                <button type="button" onclick="setPoExpectedDate(0)" id="expected_quick_0"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    Today
                </button>
                <button type="button" onclick="setPoExpectedDate(7)" id="expected_quick_7"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +7 Days
                </button>
                <button type="button" onclick="setPoExpectedDate(15)" id="expected_quick_15"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +15 Days
                </button>
                <button type="button" onclick="setPoExpectedDate(30)" id="expected_quick_30"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    +30 Days
                </button>
            </div>
        </div>

        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-xs font-semibold text-slate-700 tracking-wider">Credit Days</label>
                <span class="text-xs text-slate-500" id="due_date_preview_badge" style="display: none;">
                    Due: <span class="font-semibold text-indigo-600" id="due_date_badge_text"></span>
                </span>
            </div>
            <input type="number" name="credit_days" id="po_credit_days" min="0" placeholder="0"
                   value="{{ old('credit_days', $order->credit_days ?? 0) }}"
                   oninput="recalcPoDueDate()"
                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <input type="hidden" name="due_date" id="po_due_date_hidden" value="{{ old('due_date', $order->due_date?->toDateString()) }}">

            <div class="flex items-center gap-1.5 mt-2">
                <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                <button type="button"
                        onclick="setPoCreditDays(30)"
                        id="quick_btn_30"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    30 Days
                </button>
                <button type="button"
                        onclick="setPoCreditDays(45)"
                        id="quick_btn_45"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    45 Days
                </button>
                <button type="button"
                        onclick="setPoCreditDays(50)"
                        id="quick_btn_50"
                        class="px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300">
                    50 Days
                </button>
            </div>
            <p class="mt-1 text-[11px] text-slate-500" id="due_date_preview_text_container" style="display: none;">
                Due Date Preview: <strong class="text-slate-700" id="due_date_preview_text"></strong>
            </p>
        </div>
    </div>

    @php
        $resolvedConsignee = $order->resolveConsigneeDetails();
        $currentShippingValue = old('shipping_address', $resolvedConsignee['delivery_address'] !== 'Address not provided' ? $resolvedConsignee['delivery_address'] : ($order->shipping_address ?: ''));
    @endphp

    @include('purchasing.orders.partials.party-details', [
        'initialBillingId' => old('billing_address_id', $order->billing_address_id),
        'initialBillingText' => old('billing_address', $order->billing_address),
        'initialDeliveryId' => old('company_delivery_address_id', $order->company_delivery_address_id),
        'initialShippingText' => $currentShippingValue,
    ])

    <!-- Items Table -->
    <div class="overflow-x-auto border rounded-lg">
    <table class="min-w-full text-sm">
    <thead class="bg-gray-50"><tr>
    <th class="px-3 py-2 text-left w-44">Brand</th>
    <th class="px-3 py-2 text-left min-w-[250px]">Product</th>
    <th class="px-3 py-2 text-left">Batch Name</th>
    <th class="px-3 py-2 text-left">UOM</th>
    <th class="px-3 py-2 text-left">Qty</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">MRP</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Purchase Price Incl. GST</th>
    <th class="px-3 py-2 text-left whitespace-nowrap">Purchase Disc. %</th>
    <th class="px-3 py-2 text-left">Unit Cost</th>
    <th class="px-3 py-2 text-left">Tax %</th>
    <th class="px-3 py-2 text-left">Cgst %</th>
    <th class="px-3 py-2 text-left">Sgst %</th>
    <th class="px-3 py-2"></th>
    </tr></thead>
    <tbody id="po-lines">
    @php
        $displayItems = old('items');
        if (empty($displayItems) || !is_array($displayItems)) {
            $displayItems = $order->items->map(function ($it) {
                return [
                    'brand_id' => $it->product?->brand_id,
                    'product_id' => $it->product_id,
                    'batch_no' => $it->batch_no,
                    'batch_name' => $it->batch_no,
                    'serial_no' => $it->serial_no,
                    'uom_id' => $it->uom_id,
                    'quantity' => (float)$it->quantity,
                    'unit_cost' => (float)$it->unit_cost,
                    'mrp' => (float) ($it->mrp ?? 0),
                    'purchase_price_inclusive' => $it->purchase_price_inclusive !== null ? (float) $it->purchase_price_inclusive : null,
                    'purchase_discount_percent' => $it->purchase_discount_percent !== null ? (float) $it->purchase_discount_percent : null,
                    'tax_percent' => (float)$it->tax_percent,
                    'cgst_percent' => (float)$it->cgst_percent,
                    'sgst_percent' => (float)$it->sgst_percent,
                ];
            })->values()->toArray();
        }
        if (empty($displayItems)) {
            $displayItems = [
                [
                    'brand_id' => null,
                    'product_id' => null,
                    'batch_no' => '',
                    'batch_name' => '',
                    'serial_no' => '',
                    'uom_id' => null,
                    'quantity' => 1,
                    'unit_cost' => 0,
                    'mrp' => 0,
                    'purchase_price_inclusive' => null,
                    'purchase_discount_percent' => null,
                    'tax_percent' => 0,
                    'cgst_percent' => 0,
                    'sgst_percent' => 0,
                ]
            ];
        }
    @endphp
    @foreach($displayItems as $idx => $rowItem)
        @php
            $selectedProduct = $products->firstWhere('id', $rowItem['product_id'] ?? null);
            $selectedBrandId = $rowItem['brand_id'] ?? ($selectedProduct ? $selectedProduct->brand_id : null);
            $selectedProductName = $selectedProduct ? $selectedProduct->name : '';
            $isBrandSelected = !empty($selectedBrandId);
        @endphp
        <tr>
        <td class="px-3 py-2">
            <select name="items[{{ $idx }}][brand_id]" class="po-brand block w-44 rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required onchange="handlePoBrandChange(this)">
                <option value="">Select brand</option>
                @foreach($brands as $b)
                    <option value="{{ $b->id }}" @selected($selectedBrandId == $b->id)>{{ $b->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-3 py-2">
            <div class="relative po-product-picker-container w-64">
                <select name="items[{{ $idx }}][product_id]" class="po-product hidden" onchange="handlePoProductChange(this)">
                    <option value="">Select product</option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" data-brand="{{ $p->brand_id }}" data-tax="{{ $p->tax_rate ?? 0 }}" data-cost="{{ $p->purchase_price ?? 0 }}" data-mrp="{{ $p->mrp ?? 0 }}" data-uom="{{ $p->base_uom_id }}" @selected(($rowItem['product_id'] ?? null) == $p->id)>{{ $p->name }}</option>
                    @endforeach
                </select>
                <div class="relative">
                    <input type="text"
                           class="po-product-search block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 pr-14 {{ $isBrandSelected ? 'bg-white' : 'bg-slate-100 text-slate-400 cursor-not-allowed' }}"
                           placeholder="{{ $isBrandSelected ? 'Select product...' : 'Select brand first' }}"
                           autocomplete="off"
                           value="{{ $selectedProductName }}"
                           {{ $isBrandSelected ? '' : 'disabled' }}
                           onfocus="openPoProductDropdown(this)"
                           onclick="openPoProductDropdown(this)"
                           oninput="handlePoProductSearchInput(this)"
                           onkeydown="handlePoProductSearchKeydown(event, this)">
                    <div class="absolute inset-y-0 right-0 flex items-center pr-1.5 gap-0.5">
                        <button type="button"
                                class="po-clear-product p-1 text-gray-400 hover:text-gray-600 transition"
                                title="Clear product"
                                style="{{ !empty($selectedProductName) ? '' : 'display: none;' }}"
                                onclick="clearPoProductSelection(this)">
                            <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <button type="button"
                                class="po-dropdown-toggle p-1 text-gray-400 hover:text-gray-600 transition {{ $isBrandSelected ? '' : 'opacity-40 pointer-events-none' }}"
                                tabindex="-1"
                                {{ $isBrandSelected ? '' : 'disabled' }}
                                onclick="togglePoProductDropdown(this)">
                            <svg class="h-4 w-4 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </td>
        <td class="px-3 py-2">
            <input type="text" name="items[{{ $idx }}][batch_no]" list="po-batch-list-{{ $idx }}" class="po-batch-input block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional" value="{{ $rowItem['batch_no'] ?? $rowItem['batch_name'] ?? '' }}">
            <datalist id="po-batch-list-{{ $idx }}"></datalist>
        </td>
        <td class="px-3 py-2">
            <select name="items[{{ $idx }}][uom_id]" class="po-uom block w-24 rounded-lg border-gray-300 text-sm" required>
                @foreach($uoms as $u)<option value="{{ $u->id }}" @selected(($rowItem['uom_id'] ?? null) == $u->id)>{{ $u->code }}</option>@endforeach
            </select>
        </td>
        <td class="px-3 py-2"><input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="block w-20 rounded-lg border-gray-300 text-sm" value="{{ $rowItem['quantity'] ?? 1 }}" required></td>
        @php
            $editUnitCost = (float) ($rowItem['unit_cost'] ?? 0);
            $editTaxPercent = (float) ($rowItem['tax_percent'] ?? 0);
            $editMrp = (float) ($rowItem['mrp'] ?? 0);
            $editPriceInclusive = $rowItem['price_inclusive_tax'] ?? $rowItem['purchase_price_inclusive'] ?? ($editTaxPercent > 0 ? round($editUnitCost * (1 + $editTaxPercent / 100), 2) : round($editUnitCost, 2));
            $editDiscount = $rowItem['purchase_discount_percent'] ?? ($editMrp > 0 ? round((($editMrp - (float) $editPriceInclusive) / $editMrp) * 100, 2) : '');
        @endphp
        <td class="px-3 py-2">
            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][mrp]" class="po-mrp block w-28 rounded-lg border-gray-300 text-sm" value="{{ $editMrp }}" title="MRP">
        </td>
        <td class="px-3 py-2">
            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][price_inclusive_tax]" class="po-price-inclusive block w-28 rounded-lg border-gray-300 text-sm" value="{{ $editPriceInclusive }}" title="Purchase Price Including GST">
        </td>
        <td class="px-3 py-2">
            <input type="number" step="0.01" name="items[{{ $idx }}][purchase_discount_percent]" class="po-purchase-discount block w-24 rounded-lg border-gray-300 text-sm bg-slate-50" value="{{ $editDiscount }}" title="Purchase Discount %" readonly tabindex="-1">
        </td>
        <td class="px-3 py-2"><input type="number" step="0.0001" name="items[{{ $idx }}][unit_cost]" class="po-cost block w-28 rounded-lg border-gray-300 text-sm" value="{{ $rowItem['unit_cost'] ?? 0 }}" required></td>
        <td class="px-3 py-2">
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="items[{{ $idx }}][tax_percent]"
                class="po-tax block w-20 rounded-lg border-gray-300 text-sm"
                value="{{ $rowItem['tax_percent'] ?? 0 }}"
            >
        </td>
        <td class="px-3 py-2">
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="items[{{ $idx }}][cgst_percent]"
                class="po-cgst block w-20 rounded-lg border-gray-300 text-sm"
                value="{{ $rowItem['cgst_percent'] ?? 0 }}"
            >
        </td>
        <td class="px-3 py-2">
            <input
                type="number"
                step="0.01"
                min="0"
                max="100"
                name="items[{{ $idx }}][sgst_percent]"
                class="po-sgst block w-20 rounded-lg border-gray-300 text-sm"
                value="{{ $rowItem['sgst_percent'] ?? 0 }}"
            >
        </td>
        <td class="px-3 py-2 text-right">
            <button type="button" onclick="removePoRow(this)" class="text-xs text-red-600 hover:text-red-800 po-remove-btn" @if(count($displayItems) === 1) style="display:none;" @endif>Remove</button>
        </td>
        </tr>
    @endforeach
    </tbody></table></div>

    <template id="po-line-template">
        @include('purchasing.orders.partials.line-row', [
            'idx' => '__INDEX__',
            'brands' => $brands,
            'products' => $products,
            'uoms' => $uoms,
            'showRemove' => true,
        ])
    </template>

    <!-- Section 3: Add Line Button directly below line items table -->
    <div class="mt-3 flex items-center justify-between">
        <x-ui.button type="button" variant="secondary" onclick="addPoRow()" class="inline-flex items-center gap-1.5 shadow-xs">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Add Line</span>
        </x-ui.button>
    </div>

    <!-- Freight Charges Section -->
    <div class="mt-6 border border-slate-200 rounded-xl bg-slate-50/70 p-4 space-y-4">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-3">
            <div>
                <h3 class="text-sm font-semibold text-slate-800">Freight Charges</h3>
                <p class="text-xs text-slate-500">Specify whether freight / shipping costs apply to this purchase order.</p>
            </div>
            <div class="flex items-center gap-3">
                <label class="text-xs font-semibold text-slate-700 tracking-wider">Freight Applicable?</label>
                <select name="is_freight_applicable" id="po_is_freight_applicable" onchange="toggleFreightFields()" class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="0" @selected(old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0) == 0)>No</option>
                    <option value="1" @selected(old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0) == 1)>Yes</option>
                </select>
            </div>
        </div>

        @include('purchasing.orders.partials.freight-fields', [
            'freightApplicable' => old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0),
            'freightMode' => old('freight_mode', $order->freight_mode ?? 'amount'),
            'freightPercent' => old('freight_percent', number_format((float) ($order->freight_percent ?? 0), 2, '.', '')),
            'freightAmount' => old('freight_amount', number_format((float) ($order->freight_amount ?? 0), 2, '.', '')),
            'freightTaxRate' => old('freight_tax_rate', number_format((float) ($order->freight_tax_rate ?? 0), 2, '.', '')),
            'freightTaxAmount' => old('freight_tax_amount', number_format((float) ($order->freight_tax_amount ?? 0), 2, '.', '')),
            'freightTotal' => old('freight_total', number_format((float) ($order->freight_total ?? 0), 2, '.', '')),
            'freightNotes' => old('freight_notes', $order->freight_notes),
        ])
    </div>

    <!-- Notes & Order Summary Section -->
    <div class="mt-4 grid grid-cols-1 lg:grid-cols-12 gap-4 items-start">
        <div class="lg:col-span-7 space-y-2">
            <label class="block text-xs font-semibold text-slate-700 tracking-wider">Notes / Instructions</label>
            <textarea name="notes" rows="3" placeholder="Optional Notes Or Instructions For This Purchase Order..." class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $order->notes) }}</textarea>
        </div>
        <div class="lg:col-span-5 bg-white border border-slate-200 rounded-xl p-4 shadow-xs">
            <h4 class="text-xs font-bold tracking-wider text-slate-600 border-b border-slate-100 pb-2 mb-3">Order Summary</h4>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between text-slate-600">
                    <span>Items Subtotal:</span>
                    <span class="font-mono font-medium" id="summary_subtotal">₹{{ number_format($order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Items Tax (GST):</span>
                    <span class="font-mono font-medium" id="summary_tax">₹{{ number_format($order->tax_amount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600" id="summary_freight_row" style="{{ old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0) ? 'display: flex;' : 'display: none;' }}">
                    <span>Freight Charges:</span>
                    <span class="font-mono font-medium" id="summary_freight">₹{{ number_format((float) ($order->freight_amount ?? 0), 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600" id="summary_freight_tax_row" style="{{ old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0) ? 'display: flex;' : 'display: none;' }}">
                    <span>Freight Tax:</span>
                    <span class="font-mono font-medium" id="summary_freight_tax">₹{{ number_format((float) ($order->freight_tax_amount ?? 0), 2) }}</span>
                </div>
                <div class="pt-2 border-t border-slate-200 flex justify-between text-base font-bold text-slate-900">
                    <span>Order Total:</span>
                    <span class="font-mono text-indigo-600" id="summary_grand_total">₹{{ number_format($order->grand_total, 2) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Section 7: Action bar with Submit for Approval and Update Purchase Order -->
    <div class="flex flex-wrap gap-3 items-center justify-end pt-4 border-t border-slate-200">
        @if($order->status === 'draft')
            <label class="inline-flex items-center gap-2 text-sm text-slate-700 cursor-pointer">
                <input type="checkbox" name="submit_for_approval" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                <span>Submit for approval</span>
            </label>
        @endif
        <x-ui.button type="submit" variant="primary">Update Purchase Order</x-ui.button>
    </div>
    </form></x-ui.card>

    <!-- Floating Product Search Dropdown Menu -->
    <div id="po-product-dropdown-menu"
         class="hidden fixed bg-white rounded-xl shadow-2xl border border-gray-200 z-[999999] overflow-hidden flex flex-col"
         style="width: 320px; max-height: 290px;">
        <!-- Sticky Search Box inside Dropdown -->
        <div class="p-2 border-b border-gray-100 bg-gray-50/90 sticky top-0 z-10">
            <div class="relative">
                <input type="text"
                       id="po-dropdown-search-box"
                       class="block w-full px-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white placeholder-gray-400"
                       placeholder="Search Product By Name Or Part Number..."
                       autocomplete="off"
                       oninput="handleDropdownFilter(this.value)"
                       onkeydown="handleDropdownSearchKeydown(event)">
            </div>
        </div>
        <!-- Scrollable options container -->
        <div id="po-dropdown-list" class="overflow-y-auto divide-y divide-gray-100 flex-1 overscroll-contain">
        </div>
    </div>
</div>
@push('scripts')
@php
    $poProductsData = $products->map(function($p) {
        return [
            'id' => (string) $p->id,
            'name' => $p->name,
            'sku' => $p->sku ?? '',
            'brand_id' => $p->brand_id ? (string)$p->brand_id : '',
            'tax_rate' => (float)($p->tax_rate ?? 0),
            'purchase_price' => (float)($p->purchase_price ?? 0),
            'mrp' => (float)($p->mrp ?? 0),
            'base_uom_id' => $p->base_uom_id ? (string)$p->base_uom_id : '',
        ];
    })->values();
@endphp
<script>
let poi = {{ count($displayItems) }};

const poProductsList = {!! json_encode($poProductsData) !!};

let activeRow = null;
let highlightedIndex = -1;
let currentFilteredProducts = [];

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function setPoProductPickerEnabled(row, enabled) {
    const searchInput = row.querySelector('.po-product-search');
    const toggleBtn = row.querySelector('.po-dropdown-toggle');

    if (searchInput) {
        searchInput.disabled = !enabled;
        if (enabled) {
            searchInput.removeAttribute('disabled');
            searchInput.placeholder = 'Select product...';
            searchInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.add('bg-white');
        } else {
            searchInput.setAttribute('disabled', 'disabled');
            searchInput.placeholder = 'Select brand first';
            searchInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.remove('bg-white');
        }
    }

    if (toggleBtn) {
        toggleBtn.disabled = !enabled;
        if (enabled) {
            toggleBtn.removeAttribute('disabled');
            toggleBtn.classList.remove('opacity-40', 'pointer-events-none');
        } else {
            toggleBtn.setAttribute('disabled', 'disabled');
            toggleBtn.classList.add('opacity-40', 'pointer-events-none');
        }
    }
}

function handlePoBrandChange(brandSelect) {
    const row = brandSelect.closest('tr');
    if (!row) return;

    const brandId = brandSelect.value;

    try {
        clearPoProductSelection(brandSelect);
    } catch (e) {
        console.error('clearPoProductSelection failed', e);
    }

    if (brandId) {
        setPoProductPickerEnabled(row, true);
        brandSelect.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
    } else {
        setPoProductPickerEnabled(row, false);
    }
}

function openPoProductDropdown(triggerEl, initialQuery = null) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    const brandSelect = row.querySelector('.po-brand');
    if (!brandSelect || !brandSelect.value) {
        if (brandSelect) {
            brandSelect.focus();
            brandSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
        }
        return;
    }

    if (activeRow && activeRow !== row) {
        activeRow.querySelector('.po-dropdown-toggle svg')?.classList.remove('rotate-180');
    }

    activeRow = row;
    row.querySelector('.po-dropdown-toggle svg')?.classList.add('rotate-180');

    const menu = document.getElementById('po-product-dropdown-menu');
    const searchBox = document.getElementById('po-dropdown-search-box');
    const select = row.querySelector('.po-product');
    const selectedId = select ? select.value : '';

    if (!menu) return;
    menu.classList.remove('hidden');
    positionPoDropdown();

    const query = initialQuery !== null ? initialQuery : '';
    if (searchBox) {
        searchBox.value = query;
        if (initialQuery === null) {
            setTimeout(() => searchBox.focus(), 20);
        }
    }

    handleDropdownFilter(query, selectedId);
}

function togglePoProductDropdown(buttonEl) {
    const row = buttonEl.closest('tr');
    if (!row) return;

    const brandSelect = row.querySelector('.po-brand');
    if (!brandSelect || !brandSelect.value) {
        if (brandSelect) {
            brandSelect.focus();
            brandSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
        }
        return;
    }

    const menu = document.getElementById('po-product-dropdown-menu');
    if (activeRow === row && menu && !menu.classList.contains('hidden')) {
        closePoProductDropdown();
    } else {
        openPoProductDropdown(buttonEl);
    }
}

function closePoProductDropdown() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu) {
        menu.classList.add('hidden');
    }
    if (activeRow) {
        activeRow.querySelector('.po-dropdown-toggle svg')?.classList.remove('rotate-180');
        activeRow = null;
    }
    highlightedIndex = -1;
    currentFilteredProducts = [];
}

function positionPoDropdown() {
    if (!activeRow) return;
    const container = activeRow.querySelector('.po-product-picker-container');
    const menu = document.getElementById('po-product-dropdown-menu');
    if (!container || !menu) return;

    const rect = container.getBoundingClientRect();
    const dropdownHeight = 290;
    const spaceBelow = window.innerHeight - rect.bottom;
    const spaceAbove = rect.top;

    menu.style.width = Math.max(rect.width, 320) + 'px';

    if (spaceBelow < dropdownHeight && spaceAbove > spaceBelow) {
        menu.style.top = Math.max(10, rect.top - dropdownHeight - 4) + 'px';
    } else {
        menu.style.top = (rect.bottom + 4) + 'px';
    }

    let left = rect.left;
    if (left + 320 > window.innerWidth) {
        left = window.innerWidth - 330;
    }
    menu.style.left = Math.max(10, left) + 'px';
}

function handleDropdownFilter(query, selectedIdOverride = null) {
    if (!activeRow) return;
    const list = document.getElementById('po-dropdown-list');
    if (!list) return;

    const brandSelect = activeRow.querySelector('.po-brand');
    const selectedBrandId = brandSelect ? String(brandSelect.value || '').trim() : '';

    const select = activeRow.querySelector('.po-product');
    const currentSelectedId = selectedIdOverride !== null ? selectedIdOverride : (select ? String(select.value) : '');

    const q = (query || '').toLowerCase().trim();

    currentFilteredProducts = poProductsList.filter(p => {
        if (selectedBrandId && String(p.brand_id) !== selectedBrandId) {
            return false;
        }
        if (!q) return true;
        const nameMatch = (p.name || '').toLowerCase().includes(q);
        const skuMatch = (p.sku || '').toLowerCase().includes(q);
        return nameMatch || skuMatch;
    });

    highlightedIndex = -1;

    if (currentFilteredProducts.length === 0) {
        list.innerHTML = `
            <div class="px-4 py-6 text-center text-xs text-gray-500">
                <p class="font-medium text-gray-600">No products found</p>
                <p class="mt-1 text-gray-400">Try adjusting your search terms</p>
            </div>
        `;
        return;
    }

    let html = '';
    currentFilteredProducts.forEach((p, idx) => {
        const isSelected = String(p.id) === currentSelectedId;
        html += `
            <div class="po-dropdown-item px-3 py-2 text-xs cursor-pointer hover:bg-indigo-50 transition flex items-center justify-between ${isSelected ? 'bg-indigo-50 font-semibold text-indigo-900' : 'text-gray-700'}"
                 data-id="${escapeHtml(p.id)}"
                 data-index="${idx}"
                 onmouseenter="highlightPoDropdownItem(${idx})">
                <div class="min-w-0 pr-2">
                    <div class="truncate ${isSelected ? 'text-indigo-900 font-medium' : 'text-gray-900'}">${escapeHtml(p.name)}</div>
                    ${p.sku ? `<div class="text-[10px] text-gray-400 truncate">Part No: ${escapeHtml(p.sku)}</div>` : ''}
                </div>
                <div class="text-right shrink-0">
                    <span class="text-[11px] font-mono text-gray-600">₹${p.purchase_price.toFixed(2)}</span>
                    <span class="text-[10px] text-gray-400 block">${p.tax_rate}% GST</span>
                </div>
            </div>
        `;
    });

    list.innerHTML = html;
}

function highlightPoDropdownItem(index) {
    highlightedIndex = index;
    const items = document.querySelectorAll('.po-dropdown-item');
    items.forEach((item, idx) => {
        if (idx === index) {
            item.classList.add('bg-indigo-100');
        } else {
            item.classList.remove('bg-indigo-100');
        }
    });
}

function handleDropdownSearchKeydown(event) {
    const items = document.querySelectorAll('.po-dropdown-item');
    if (!items.length) return;

    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex = (highlightedIndex + 1) % items.length;
        updateHighlightScroll(items);
    } else if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex = (highlightedIndex - 1 + items.length) % items.length;
        updateHighlightScroll(items);
    } else if (event.key === 'Enter') {
        event.preventDefault();
        if (highlightedIndex >= 0 && highlightedIndex < items.length) {
            const selectedItem = items[highlightedIndex];
            if (selectedItem && selectedItem.dataset.id) {
                selectPoProduct(selectedItem.dataset.id);
            }
        }
    } else if (event.key === 'Escape') {
        event.preventDefault();
        closePoProductDropdown();
        if (activeRow) {
            activeRow.querySelector('.po-product-search')?.focus();
        }
    }
}

function updateHighlightScroll(items) {
    items.forEach((item, idx) => {
        if (idx === highlightedIndex) {
            item.classList.add('bg-indigo-100');
            item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        } else {
            item.classList.remove('bg-indigo-100');
        }
    });
}

function handlePoProductSearchInput(input) {
    const row = input.closest('tr');
    if (!row) return;

    input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');

    if (activeRow !== row) {
        openPoProductDropdown(input, input.value);
    } else {
        const searchBox = document.getElementById('po-dropdown-search-box');
        if (searchBox) searchBox.value = input.value;
        handleDropdownFilter(input.value);
    }
}

function handlePoProductSearchKeydown(event, input) {
    if (event.key === 'ArrowDown' || event.key === 'Enter') {
        event.preventDefault();
        openPoProductDropdown(input, input.value);
        const searchBox = document.getElementById('po-dropdown-search-box');
        if (searchBox) searchBox.focus();
    } else if (event.key === 'Escape') {
        closePoProductDropdown();
    }
}

function selectPoProduct(productId) {
    if (!activeRow) return;

    const select = activeRow.querySelector('.po-product');
    const searchInput = activeRow.querySelector('.po-product-search');
    const clearBtn = activeRow.querySelector('.po-clear-product');

    if (select) {
        select.value = productId;
        select.dispatchEvent(new Event('change'));
    }

    const prod = poProductsList.find(p => String(p.id) === String(productId));
    if (searchInput) {
        searchInput.value = prod ? prod.name : '';
        searchInput.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
    }

    if (clearBtn) {
        clearBtn.style.display = prod ? 'inline-flex' : 'none';
    }

    closePoProductDropdown();
}

function clearPoProductSelection(triggerEl) {
    const row = triggerEl.closest('tr');
    if (!row) return;

    const select = row.querySelector('.po-product');
    const searchInput = row.querySelector('.po-product-search');
    const clearBtn = row.querySelector('.po-clear-product');

    if (select) {
        select.value = '';
    }
    if (searchInput) {
        searchInput.value = '';
    }
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }

    const cost = row.querySelector('.po-cost');
    const tax = row.querySelector('.po-tax');
    const cgst = row.querySelector('.po-cgst');
    const sgst = row.querySelector('.po-sgst');
    if (cost) cost.value = '0';
    if (tax) tax.value = '0';
    if (cgst) cgst.value = '0';
    if (sgst) sgst.value = '0';
    recalculatePoTotals();
}

function handlePoProductChange(select) {
    const row = select.closest('tr');
    if (!row) return;

    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const productMeta = (typeof poProductsList !== 'undefined')
        ? poProductsList.find(p => String(p.id) === String(opt.value))
        : null;
    const taxVal = parseFloat(opt.dataset.tax) || Number(productMeta?.tax_rate) || 0;
    const costVal = parseFloat(opt.dataset.cost) || Number(productMeta?.purchase_price) || 0;
    const mrpVal = parseFloat(opt.dataset.mrp) || Number(productMeta?.mrp) || 0;
    const uomVal = opt.dataset.uom || productMeta?.base_uom_id || '';

    const costInput = row.querySelector('.po-cost');
    const mrpInput = row.querySelector('.po-mrp');
    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');
    const uomSelect = row.querySelector('.po-uom');

    if (taxInput) taxInput.value = taxVal;
    if (cgstInput) cgstInput.value = (taxVal / 2).toFixed(2);
    if (sgstInput) sgstInput.value = (taxVal / 2).toFixed(2);
    if (mrpInput && (!mrpInput.value || parseFloat(mrpInput.value) === 0)) {
        mrpInput.value = mrpVal;
    }
    if (costInput && (!costInput.value || parseFloat(costInput.value) === 0)) {
        costInput.value = costVal;
        syncPoPriceTaxPair(row, 'cost');
    } else if (costInput) {
        syncPoPriceTaxPair(row, 'cost');
    } else {
        syncPoPurchaseDiscount(row);
    }
    if (uomSelect && uomVal) uomSelect.value = uomVal;

    loadBatchesForProduct(opt.value, row);
    recalculatePoTotals();
}

async function loadBatchesForProduct(productId, row) {
    const datalist = row.querySelector('datalist');
    if (!datalist || !productId) return;

    try {
        const res = await fetch(`{{ url('/inventory/products') }}/${productId}/batches`, {
            headers: { 'Accept': 'application/json' }
        });
        const batches = await res.json();
        datalist.innerHTML = '';
        if (Array.isArray(batches)) {
            batches.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b.batch_no || b.name || b;
                datalist.appendChild(opt);
            });
        }
    } catch (e) {
        console.error('Failed to load batches:', e);
    }
}

function syncPoTax(row, changed) {
    const tax = row.querySelector('.po-tax');
    const cgst = row.querySelector('.po-cgst');
    const sgst = row.querySelector('.po-sgst');

    if (!tax || !cgst || !sgst) return;

    const taxValue = parseFloat(tax.value) || 0;

    if (changed === 'tax') {
        const half = (taxValue / 2).toFixed(2);
        cgst.value = half;
        sgst.value = half;
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
        return;
    }

    const cgstValue = parseFloat(cgst.value) || 0;

    if (changed === 'cgst') {
        sgst.value = Math.max(0, taxValue - cgstValue).toFixed(2);
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
        return;
    }

    if (changed === 'sgst') {
        cgst.value = Math.max(0, taxValue - (parseFloat(sgst.value) || 0)).toFixed(2);
        syncPoPriceTaxPair(row, row.dataset.priceEditSource === 'inclusive' ? 'inclusive' : 'cost');
        recalculatePoTotals();
    }
}

function poRowTaxPercent(row) {
    const tax = row.querySelector('.po-tax');
    return Math.max(0, parseFloat(tax?.value) || 0);
}

function syncPoPurchaseDiscount(row) {
    if (!row) return;
    const mrpInput = row.querySelector('.po-mrp');
    const inclusiveInput = row.querySelector('.po-price-inclusive');
    const discountInput = row.querySelector('.po-purchase-discount');
    if (!discountInput) return;

    const mrp = parseFloat(mrpInput?.value) || 0;
    const inclusive = parseFloat(inclusiveInput?.value) || 0;
    discountInput.value = mrp > 0 ? (((mrp - inclusive) / mrp) * 100).toFixed(2) : '';
}

function syncPoPriceTaxPair(row, source) {
    if (!row) return;
    const costInput = row.querySelector('.po-cost');
    const inclusiveInput = row.querySelector('.po-price-inclusive');
    if (!costInput || !inclusiveInput) return;

    const tax = poRowTaxPercent(row);
    const factor = 1 + (tax / 100);

    if (source === 'inclusive') {
        const inclusive = parseFloat(inclusiveInput.value) || 0;
        costInput.value = factor > 0 ? (inclusive / factor).toFixed(2) : inclusive.toFixed(2);
        row.dataset.priceEditSource = 'inclusive';
    } else {
        const cost = parseFloat(costInput.value) || 0;
        inclusiveInput.value = (cost * factor).toFixed(2);
        row.dataset.priceEditSource = 'cost';
    }
    syncPoPurchaseDiscount(row);
}

function toggleFreightFields() {
    const isApplicable = document.getElementById('po_is_freight_applicable')?.value === '1';
    const container = document.getElementById('freight_fields_container');
    const freightRow = document.getElementById('summary_freight_row');
    const freightTaxRow = document.getElementById('summary_freight_tax_row');
    if (container) {
        container.style.display = isApplicable ? 'block' : 'none';
    }
    if (freightRow) {
        freightRow.style.display = isApplicable ? 'flex' : 'none';
    }
    if (freightTaxRow) {
        freightTaxRow.style.display = isApplicable ? 'flex' : 'none';
    }
    recalculatePoTotals();
}

function toggleFreightMode() {
    const mode = document.getElementById('po_freight_mode')?.value || 'amount';
    const percentWrap = document.getElementById('freight_percent_wrap');
    const amountWrap = document.getElementById('freight_amount_wrap');
    if (percentWrap) percentWrap.style.display = mode === 'percent' ? '' : 'none';
    if (amountWrap) amountWrap.style.display = mode === 'amount' ? '' : 'none';
}

function recalculatePoTotals() {
    let subtotal = 0;
    let taxTotal = 0;

    const rows = document.querySelectorAll('#po-lines tr');
    rows.forEach(row => {
        const qtyInput = row.querySelector('input[name*="[quantity]"]');
        const costInput = row.querySelector('input[name*="[unit_cost]"]');
        const taxInput = row.querySelector('.po-tax');

        const qty = parseFloat(qtyInput?.value) || 0;
        const cost = parseFloat(costInput?.value) || 0;
        const taxPercent = parseFloat(taxInput?.value) || 0;

        const lineSubtotal = qty * cost;
        const lineTax = (lineSubtotal * taxPercent) / 100;

        subtotal += lineSubtotal;
        taxTotal += lineTax;
    });

    const isFreightApplicable = document.getElementById('po_is_freight_applicable')?.value === '1';
    const freightMode = document.getElementById('po_freight_mode')?.value || 'amount';
    const freightAmountInput = document.getElementById('po_freight_amount');
    const freightPercentInput = document.getElementById('po_freight_percent');
    const freightTaxRateInput = document.getElementById('po_freight_tax_rate');
    const freightTaxAmountInput = document.getElementById('po_freight_tax_amount');
    const freightTotalInput = document.getElementById('po_freight_total');
    const freightCalculated = document.getElementById('freight_calculated_amount');

    let freightAmount = 0;
    let freightTaxRate = 0;
    let freightTaxAmount = 0;
    let freightTotal = 0;

    if (isFreightApplicable) {
        freightTaxRate = parseFloat(freightTaxRateInput?.value) || 0;
        if (freightMode === 'percent') {
            const freightPercent = Math.min(100, Math.max(0, parseFloat(freightPercentInput?.value) || 0));
            freightAmount = subtotal * freightPercent / 100;
            if (freightAmountInput) {
                freightAmountInput.value = freightAmount.toFixed(2);
            }
        } else {
            freightAmount = parseFloat(freightAmountInput?.value) || 0;
        }
        freightTaxAmount = (freightAmount * freightTaxRate) / 100;
        freightTotal = freightAmount + freightTaxAmount;

        if (freightTaxAmountInput) {
            freightTaxAmountInput.value = freightTaxAmount.toFixed(2);
        }
        if (freightTotalInput) {
            freightTotalInput.value = freightTotal.toFixed(2);
        }
    } else {
        if (freightTaxAmountInput) {
            freightTaxAmountInput.value = '0.00';
        }
        if (freightTotalInput) {
            freightTotalInput.value = '0.00';
        }
    }

    const grandTotal = subtotal + taxTotal + freightTotal;

    const summarySubtotal = document.getElementById('summary_subtotal');
    const summaryTax = document.getElementById('summary_tax');
    const summaryFreight = document.getElementById('summary_freight');
    const summaryFreightTax = document.getElementById('summary_freight_tax');
    const summaryGrandTotal = document.getElementById('summary_grand_total');

    if (summarySubtotal) summarySubtotal.textContent = '₹' + subtotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryTax) summaryTax.textContent = '₹' + taxTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (freightCalculated) freightCalculated.textContent = '₹' + freightAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryFreight) summaryFreight.textContent = '₹' + freightAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryFreightTax) summaryFreightTax.textContent = '₹' + freightTaxAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryGrandTotal) summaryGrandTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function resetPoSelect(sel) {
    if (!sel) return;
    Array.from(sel.options).forEach((opt, index) => {
        opt.selected = index === 0;
        if (index === 0) {
            opt.setAttribute('selected', 'selected');
        } else {
            opt.removeAttribute('selected');
        }
    });
    sel.selectedIndex = 0;
    sel.value = sel.options[0] ? sel.options[0].value : '';
}

function addPoRow() {
    closePoProductDropdown();

    const table = document.getElementById('po-lines');
    const tpl = document.getElementById('po-line-template');
    if (!table || !tpl) {
        alert('Could not add line. Please refresh the page and try again.');
        return;
    }

    const index = poi;
    const html = tpl.innerHTML.replaceAll('__INDEX__', String(index));
    const wrap = document.createElement('tbody');
    wrap.innerHTML = html.trim();
    const newRow = wrap.querySelector('tr');
    if (!newRow) return;

    setPoProductPickerEnabled(newRow, false);

    table.appendChild(newRow);
    poi++;
    updateRemoveButtons();
    recalculatePoTotals();

    const brandSelect = newRow.querySelector('.po-brand');
    if (brandSelect) {
        brandSelect.disabled = false;
        brandSelect.removeAttribute('disabled');
        brandSelect.focus();
    }
}

function removePoRow(btn) {
    const table = document.getElementById('po-lines');
    const row = btn.closest('tr');
    if (activeRow === row) {
        closePoProductDropdown();
    }
    if (table.rows.length > 1) {
        row.remove();
    }
    updateRemoveButtons();
    recalculatePoTotals();
}

function updateRemoveButtons() {
    const table = document.getElementById('po-lines');
    const rows = table.rows;
    for (let i = 0; i < rows.length; i++) {
        const btn = rows[i].querySelector('.po-remove-btn');
        if (btn) {
            btn.style.display = rows.length > 1 ? 'inline' : 'none';
        }
    }
}

document.addEventListener('input', function (event) {
    const input = event.target;

    if (input.name === 'po_date' || input.id === 'po_credit_days') {
        recalcPoDueDate();
    }

    if (input.classList.contains('po-price-inclusive')) {
        const row = input.closest('tr');
        if (row) {
            syncPoPriceTaxPair(row, 'inclusive');
            recalculatePoTotals();
        }
        return;
    }

    if (input.classList.contains('po-mrp')) {
        const row = input.closest('tr');
        if (row) syncPoPurchaseDiscount(row);
        return;
    }

    if (input.classList.contains('po-cost') || input.name?.includes('[unit_cost]')) {
        const row = input.closest('tr');
        if (row) {
            syncPoPriceTaxPair(row, 'cost');
        }
    }

    if (input.classList.contains('po-tax')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'tax');
    } else if (input.classList.contains('po-cgst')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'cgst');
    } else if (input.classList.contains('po-sgst')) {
        const row = input.closest('tr');
        if (row) syncPoTax(row, 'sgst');
    }

    if (
        input.name?.includes('[quantity]') ||
        input.name?.includes('[unit_cost]') ||
        input.classList.contains('po-tax') ||
        input.classList.contains('po-cost') ||
        input.classList.contains('po-price-inclusive') ||
        input.id === 'po_freight_amount' ||
        input.id === 'po_freight_tax_rate'
    ) {
        recalculatePoTotals();
    }
});

document.addEventListener('change', function (event) {
    if (event.target.name === 'po_date' || event.target.id === 'po_credit_days') {
        recalcPoDueDate();
    }
});

let supplierAddresses = [];
let companyDeliveryAddresses = [];
let companyDeliveryMeta = { name: '', legal_name: '' };
let initialLoad = true;

const initialBillingId = @json(old('billing_address_id', $order->billing_address_id));
const initialCompanyDeliveryId = @json(old('company_delivery_address_id', $order->company_delivery_address_id));
const deliveryAddressesUrl = @json(route('purchasing.delivery-addresses.index'));
const deliveryAddressesStoreUrl = @json(route('purchasing.delivery-addresses.store'));
const csrfToken = @json(csrf_token());

function setPoCreditDays(days) {
    const input = document.getElementById('po_credit_days');
    if (input) {
        input.value = parseInt(days, 10) || 0;
    }
    recalcPoDueDate();
}

function formatLocalDate(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
}

function setPoExpectedDate(daysFromPo) {
    const poDateInput = document.querySelector('input[name="po_date"]');
    const expectedInput = document.getElementById('po_expected_date');
    if (!poDateInput?.value || !expectedInput) return;

    const parts = String(poDateInput.value).split('-');
    if (parts.length !== 3) return;
    const base = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
    if (Number.isNaN(base.getTime())) return;

    base.setDate(base.getDate() + (parseInt(daysFromPo, 10) || 0));
    expectedInput.value = formatLocalDate(base);
    updateExpectedQuickHighlight();
}

function updateExpectedQuickHighlight() {
    const poDateInput = document.querySelector('input[name="po_date"]');
    const expectedInput = document.getElementById('po_expected_date');
    const options = [0, 7, 15, 30];
    let active = -1;

    if (poDateInput?.value && expectedInput?.value) {
        const poParts = String(poDateInput.value).split('-');
        const exParts = String(expectedInput.value).split('-');
        if (poParts.length === 3 && exParts.length === 3) {
            const po = new Date(parseInt(poParts[0], 10), parseInt(poParts[1], 10) - 1, parseInt(poParts[2], 10));
            const ex = new Date(parseInt(exParts[0], 10), parseInt(exParts[1], 10) - 1, parseInt(exParts[2], 10));
            const diff = Math.round((ex - po) / 86400000);
            if (options.includes(diff)) active = diff;
        }
    }

    options.forEach((days) => {
        const btn = document.getElementById(`expected_quick_${days}`);
        if (!btn) return;
        if (days === active) {
            btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-indigo-600 text-white border-indigo-600';
        } else {
            btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300';
        }
    });
}

function recalcPoDueDate() {
    const poDateInput = document.getElementById('po_date') || document.querySelector('input[name="po_date"]');
    const creditDaysInput = document.getElementById('po_credit_days');
    const hiddenDueDate = document.getElementById('po_due_date_hidden');
    const badgeContainer = document.getElementById('due_date_preview_badge');
    const badgeText = document.getElementById('due_date_badge_text');
    const textContainer = document.getElementById('due_date_preview_text_container');
    const previewText = document.getElementById('due_date_preview_text');

    const poDateVal = poDateInput?.value;
    if (!poDateVal) {
        if (badgeContainer) badgeContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (hiddenDueDate) hiddenDueDate.value = '';
        updateQuickButtonsHighlight(-1);
        return;
    }

    const days = parseInt(creditDaysInput?.value, 10);
    const validDays = (!isNaN(days) && days >= 0) ? days : 0;

    const parts = String(poDateVal).split('-');
    if (parts.length !== 3) {
        if (badgeContainer) badgeContainer.style.display = 'none';
        if (textContainer) textContainer.style.display = 'none';
        if (hiddenDueDate) hiddenDueDate.value = '';
        updateQuickButtonsHighlight(-1);
        return;
    }

    const year = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const day = parseInt(parts[2], 10);

    const d = new Date(year, month, day);
    d.setDate(d.getDate() + validDays);

    const yyyy = d.getFullYear();
    const mm = String(d.getMonth() + 1).padStart(2, '0');
    const dd = String(d.getDate()).padStart(2, '0');
    const formattedYmd = `${yyyy}-${mm}-${dd}`;

    const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const formattedPreview = `${dd} ${monthNames[d.getMonth()]} ${yyyy}`;

    if (hiddenDueDate) hiddenDueDate.value = formattedYmd;
    if (badgeText) badgeText.textContent = formattedPreview;
    if (previewText) previewText.textContent = formattedPreview;
    if (badgeContainer) badgeContainer.style.display = 'inline';
    if (textContainer) textContainer.style.display = 'block';

    updateQuickButtonsHighlight(validDays);
}

function updateQuickButtonsHighlight(currentDays) {
    [30, 45, 50].forEach(d => {
        const btn = document.getElementById('quick_btn_' + d);
        if (btn) {
            if (currentDays === d) {
                btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-indigo-600 text-white border-indigo-600 shadow-sm';
            } else {
                btn.className = 'px-2 py-0.5 text-xs font-medium rounded border transition-colors bg-white text-slate-700 hover:bg-slate-50 border-slate-300';
            }
        }
    });
}

function setText(id, value) {
    const el = document.getElementById(id);
    if (el) el.textContent = value || '—';
}

function renderBillingDetails(customer, addr) {
    const empty = document.getElementById('billing-empty-hint');
    const list = document.getElementById('billing-details-list');
    const snapshot = document.getElementById('billing_address');
    const hiddenId = document.getElementById('billing_address_id');

    if (!customer && !addr) {
        if (empty) empty.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        if (snapshot) snapshot.value = '';
        if (hiddenId) hiddenId.value = '';
        return;
    }

    if (empty) empty.classList.add('hidden');
    if (list) list.classList.remove('hidden');

    const partyName = customer?.name || '';
    const mailing = addr?.contact_person || partyName;
    const lines = [addr?.address_line_1, addr?.address_line_2].filter(Boolean).join('\n') || (addr?.full_address || '');
    setText('billing_party_name', partyName);
    setText('billing_address_type', addr?.label || (addr?.type ? String(addr.type) : 'Primary'));
    setText('billing_mailing_name', mailing);
    setText('billing_address_lines', lines);
    setText('billing_state', addr?.state || customer?.state || '');
    setText('billing_pincode', addr?.pincode || '');
    setText('billing_gstin', addr?.gstin || customer?.gstin || '');

    if (hiddenId) hiddenId.value = addr?.id || initialBillingId || '';
    if (snapshot) snapshot.value = addr ? (addr.snapshot || addr.full_address || '') : (snapshot.value || '');
}

function renderDeliveryDetails(addr) {
    const empty = document.getElementById('delivery-empty-hint');
    const list = document.getElementById('delivery-details-list');
    const snapshot = document.getElementById('shipping_address');

    if (!addr) {
        if (empty) empty.classList.remove('hidden');
        if (list) list.classList.add('hidden');
        return;
    }

    if (empty) empty.classList.add('hidden');
    if (list) list.classList.remove('hidden');

    setText('delivery_party_name', companyDeliveryMeta.legal_name || companyDeliveryMeta.name || addr.mailing_name || '');
    setText('delivery_address_type', addr.address_type || '');
    setText('delivery_mailing_name', addr.mailing_name || companyDeliveryMeta.legal_name || companyDeliveryMeta.name || '');
    setText('delivery_address_lines', addr.address || '');
    setText('delivery_state', addr.state || '');
    setText('delivery_country', addr.country || 'India');
    setText('delivery_pincode', addr.pincode || '');
    setText('delivery_gstin', addr.gstin || '');

    if (snapshot) snapshot.value = addr.snapshot || '';
}

function populateDeliverySelect(selectedId) {
    const select = document.getElementById('company_delivery_address_id');
    if (!select) return;
    select.innerHTML = '<option value="">Select Delivery Address</option>';
    companyDeliveryAddresses.forEach((addr) => {
        select.add(new Option(addr.display_text || addr.address_type, addr.id));
    });
    const prefer = selectedId || initialCompanyDeliveryId;
    if (prefer && companyDeliveryAddresses.some((a) => String(a.id) === String(prefer))) {
        select.value = prefer;
    } else {
        const def = companyDeliveryAddresses.find((a) => a.is_default) || companyDeliveryAddresses[0];
        if (def) select.value = def.id;
    }
    handleDeliveryChange();
}

async function loadCompanyDeliveryAddresses(preferId) {
    try {
        const res = await fetch(deliveryAddressesUrl, { headers: { 'Accept': 'application/json' } });
        const data = await res.json();
        if (!data.success) throw new Error('Failed');
        companyDeliveryMeta = data.company || companyDeliveryMeta;
        companyDeliveryAddresses = data.addresses || [];
        populateDeliverySelect(preferId || data.default_id);
        const mailing = document.getElementById('new_delivery_mailing_name');
        if (mailing && !mailing.value) {
            mailing.value = companyDeliveryMeta.legal_name || companyDeliveryMeta.name || '';
        }
        const gstin = document.getElementById('new_delivery_gstin');
        if (gstin && !gstin.value && data.company?.gstin) gstin.value = data.company.gstin;
    } catch (e) {
        console.error('Failed to load delivery addresses:', e);
    }
}

async function handleSupplierChange(supplierId) {
    const statusText = document.getElementById('address-status-text');
    const billingSnapshot = document.getElementById('billing_address');

    if (!supplierId) {
        supplierAddresses = [];
        if (billingSnapshot) billingSnapshot.value = '';
        renderBillingDetails(null, null);
        if (!initialLoad) {
            setPoCreditDays(0);
        }
        return;
    }

    if (!initialLoad) {
        const supSelect = document.getElementById('supplier_id');
        const supOpt = supSelect ? supSelect.querySelector(`option[value="${supplierId}"]`) : null;
        if (supOpt && supOpt.dataset.creditDays !== undefined) {
            const sDays = parseInt(supOpt.dataset.creditDays, 10);
            setPoCreditDays(!isNaN(sDays) && sDays >= 0 ? sDays : 0);
        }
    }

    if (statusText) statusText.innerText = 'Loading vendor billing details...';
    try {
        const res = await fetch(`{{ url('/masters/customers') }}/${supplierId}/addresses`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            supplierAddresses = data.addresses || [];
            let addr = null;
            if (initialLoad && initialBillingId) {
                addr = supplierAddresses.find((a) => String(a.id) === String(initialBillingId)) || null;
            }
            if (!addr) {
                const billingId = data.default_billing_id;
                addr = supplierAddresses.find((a) => String(a.id) === String(billingId)) || supplierAddresses[0] || null;
            }
            renderBillingDetails(data.customer || null, addr);
            if (statusText) statusText.innerText = 'Vendor billing loaded automatically';
            initialLoad = false;
        }
    } catch (e) {
        console.error('Failed to load supplier addresses:', e);
        if (statusText) statusText.innerText = 'Could not load vendor billing';
        initialLoad = false;
    }
}

function handleDeliveryChange() {
    const select = document.getElementById('company_delivery_address_id');
    const addr = companyDeliveryAddresses.find((a) => String(a.id) === String(select?.value));
    renderDeliveryDetails(addr || null);
}

function handleWarehouseChange(selectEl) {
    // Delivery address is chosen from company delivery addresses (Tally-style consignee).
}

function openDeliveryAddressModal() {
    const modal = document.getElementById('delivery-address-modal');
    if (!modal) return;
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modal.setAttribute('aria-hidden', 'false');
    const err = document.getElementById('new-delivery-error');
    if (err) { err.classList.add('hidden'); err.textContent = ''; }
}

function closeDeliveryAddressModal() {
    const modal = document.getElementById('delivery-address-modal');
    if (!modal) return;
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    modal.setAttribute('aria-hidden', 'true');
}

async function saveDeliveryAddress() {
    const err = document.getElementById('new-delivery-error');
    const addressType = document.getElementById('new_delivery_address_type')?.value?.trim() || '';
    const address = document.getElementById('new_delivery_address')?.value?.trim() || '';
    if (!addressType || !address) {
        if (err) { err.textContent = 'Address type and address are required.'; err.classList.remove('hidden'); }
        return;
    }

    const payload = {
        address_type: addressType,
        mailing_name: document.getElementById('new_delivery_mailing_name')?.value?.trim() || '',
        address,
        state: document.getElementById('new_delivery_state')?.value?.trim() || '',
        country: document.getElementById('new_delivery_country')?.value?.trim() || 'India',
        pincode: document.getElementById('new_delivery_pincode')?.value?.trim() || '',
        gstin: document.getElementById('new_delivery_gstin')?.value?.trim() || '',
        is_default: !!document.getElementById('new_delivery_is_default')?.checked,
    };

    try {
        const res = await fetch(deliveryAddressesStoreUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json();
        if (!res.ok || !data.success) {
            throw new Error(data.message || Object.values(data.errors || {}).flat().join(' ') || 'Could not save address');
        }
        closeDeliveryAddressModal();
        document.getElementById('new_delivery_address_type').value = '';
        document.getElementById('new_delivery_address').value = '';
        await loadCompanyDeliveryAddresses(data.address?.id);
    } catch (e) {
        if (err) { err.textContent = e.message || 'Could not save address'; err.classList.remove('hidden'); }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const poLines = document.getElementById('po-lines');
    if (poLines) {
        poLines.addEventListener('change', function (event) {
            if (!event.target) return;
            if (event.target.classList.contains('po-brand')) {
                handlePoBrandChange(event.target);
            } else if (event.target.classList.contains('po-product')) {
                handlePoProductChange(event.target);
            }
        });
    }

    const btnNew = document.getElementById('btn-new-delivery-address');
    if (btnNew) btnNew.addEventListener('click', openDeliveryAddressModal);

    loadCompanyDeliveryAddresses(initialCompanyDeliveryId);

    const supSelect = document.getElementById('supplier_id');
    if (supSelect && supSelect.value) {
        handleSupplierChange(supSelect.value);
    } else {
        initialLoad = false;
    }
    recalcPoDueDate();
    updateExpectedQuickHighlight();

    const dropdownList = document.getElementById('po-dropdown-list');
    if (dropdownList) {
        dropdownList.addEventListener('click', function (e) {
            const item = e.target.closest('.po-dropdown-item');
            if (item && item.dataset.id) {
                selectPoProduct(item.dataset.id);
            }
        });
    }

    const poForm = document.querySelector('form[action="{{ route('purchasing.orders.update', $order) }}"]');
    if (poForm) {
        poForm.addEventListener('submit', function (e) {
            let firstInvalid = null;
            let missingType = null;
            document.querySelectorAll('#po-lines tr').forEach(row => {
                const brandSelect = row.querySelector('.po-brand');
                const select = row.querySelector('.po-product');
                const searchInput = row.querySelector('.po-product-search');

                if (brandSelect && !brandSelect.value) {
                    if (!firstInvalid) {
                        firstInvalid = brandSelect;
                        missingType = 'brand';
                    }
                    brandSelect.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                } else if (select && !select.value) {
                    if (!firstInvalid) {
                        firstInvalid = searchInput || select;
                        missingType = 'product';
                    }
                    if (searchInput) {
                        searchInput.classList.add('border-rose-500', 'ring-1', 'ring-rose-500');
                    }
                }
            });
            if (firstInvalid) {
                e.preventDefault();
                firstInvalid.focus();
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (missingType === 'brand') {
                    alert('Please select a brand for all line items before saving.');
                } else {
                    alert('Please select a product for all line items before saving.');
                }
            }
        });
    }

    toggleFreightFields();
    recalculatePoTotals();
});

document.addEventListener('click', function(event) {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (!menu || menu.classList.contains('hidden')) return;

    if (menu.contains(event.target)) return;
    if (activeRow && activeRow.querySelector('.po-product-picker-container')?.contains(event.target)) return;

    closePoProductDropdown();
});

window.addEventListener('scroll', function() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu && !menu.classList.contains('hidden') && activeRow) {
        const container = activeRow.querySelector('.po-product-picker-container');
        if (container) {
            const rect = container.getBoundingClientRect();
            if (rect.bottom < 0 || rect.top > window.innerHeight) {
                closePoProductDropdown();
                return;
            }
        }
        positionPoDropdown();
    }
}, true);

window.addEventListener('resize', function() {
    const menu = document.getElementById('po-product-dropdown-menu');
    if (menu && !menu.classList.contains('hidden')) {
        positionPoDropdown();
    }
});
</script>
@endpush
@endsection

