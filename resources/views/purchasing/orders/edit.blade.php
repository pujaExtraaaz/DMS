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
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Supplier *</label>
                <button type="button" @click="$dispatch('open-quick-add-supplier')"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    + Add Supplier
                </button>
            </div>
            <select name="supplier_id" id="supplier_id" class="block w-full rounded-lg border-gray-300 text-sm" required onchange="handleSupplierChange(this.value)">
                <option value="">Select supplier</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->id }}" data-credit-days="{{ $s->credit_days ?? 0 }}" @selected(old('supplier_id', $order->supplier_id) == $s->id)>{{ $s->name }}</option>
                @endforeach
            </select>
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
        <x-ui.input name="po_date" id="po_date" label="PO Date" type="date" :value="old('po_date', $order->po_date?->toDateString())" required onchange="recalcPoDueDate()" oninput="recalcPoDueDate()" />
        <x-ui.input name="expected_date" label="Expected Date" type="date" :value="old('expected_date', $order->expected_date?->toDateString())" />

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

    <!-- Billing & Delivery Addresses -->
    <div id="supplier-addresses-card" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing &amp; Delivery Addresses</span>
            <span id="address-status-text" class="text-xs text-slate-500"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Billing Address Card (Supplier) -->
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Billing Address (Supplier / Vendor)</label>
                    <span class="text-[11px] text-indigo-600 font-medium">Billed from</span>
                </div>
                <select name="billing_address_id" id="billing_address_id" onchange="handleBillingChange()"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Default Address</option>
                </select>
                <textarea name="billing_address" id="billing_address" rows="3"
                          placeholder="Supplier Billing Address Snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">{{ old('billing_address', $order->billing_address) }}</textarea>
            </div>

            <!-- Delivery Address Card (Consignee) -->
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery / Dispatch Address (Consignee)</label>
                    <span class="text-[11px] text-emerald-600 font-medium">Deliver to</span>
                </div>
                <textarea name="shipping_address" id="shipping_address" rows="3"
                          oninput="this.dataset.autoFilled='0'"
                          placeholder="Consignee Delivery Address (Defaults To Warehouse/Branch Address)"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500">{{ $currentShippingValue }}</textarea>
                <p class="text-[11px] text-slate-400">Auto-fills from selected warehouse/branch. You may customize if needed.</p>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="overflow-x-auto border rounded-lg">
    <table class="min-w-full text-sm">
    <thead class="bg-gray-50"><tr>
    <th class="px-3 py-2 text-left w-44">Brand</th>
    <th class="px-3 py-2 text-left min-w-[250px]">Product</th>
    <th class="px-3 py-2 text-left">Batch Name</th>
    <th class="px-3 py-2 text-left">UOM</th>
    <th class="px-3 py-2 text-left">Qty</th>
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
                    'uom_id' => $it->uom_id,
                    'quantity' => (float)$it->quantity,
                    'unit_cost' => (float)$it->unit_cost,
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
                    'uom_id' => null,
                    'quantity' => 1,
                    'unit_cost' => 0,
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
                        <option value="{{ $p->id }}" data-brand="{{ $p->brand_id }}" data-tax="{{ $p->tax_rate ?? 0 }}" data-cost="{{ $p->purchase_price ?? 0 }}" data-uom="{{ $p->base_uom_id }}" @selected(($rowItem['product_id'] ?? null) == $p->id)>{{ $p->name }}</option>
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

        <div id="freight_fields_container" style="{{ old('is_freight_applicable', $order->is_freight_applicable ? 1 : 0) ? 'display: block;' : 'display: none;' }}" class="space-y-4 pt-1">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Freight Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="freight_amount" id="po_freight_amount"
                           value="{{ old('freight_amount', number_format((float) ($order->freight_amount ?? 0), 2, '.', '')) }}"
                           oninput="recalculatePoTotals()"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Freight Tax Rate (%)</label>
                    <input type="number" step="0.01" min="0" max="100" name="freight_tax_rate" id="po_freight_tax_rate"
                           value="{{ old('freight_tax_rate', number_format((float) ($order->freight_tax_rate ?? 0), 2, '.', '')) }}"
                           oninput="recalculatePoTotals()"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Freight Tax Amount (₹)</label>
                    <input type="number" step="0.01" min="0" name="freight_tax_amount" id="po_freight_tax_amount"
                           value="{{ old('freight_tax_amount', number_format((float) ($order->freight_tax_amount ?? 0), 2, '.', '')) }}" readonly
                           class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-600 text-sm cursor-not-allowed">
                </div>
                <div>
                    <label class="block text-xs font-medium text-slate-700 mb-1">Freight Total (₹)</label>
                    <input type="number" step="0.01" min="0" name="freight_total" id="po_freight_total"
                           value="{{ old('freight_total', number_format((float) ($order->freight_total ?? 0), 2, '.', '')) }}" readonly
                           class="block w-full rounded-lg border-gray-200 bg-slate-100 text-slate-700 font-semibold text-sm cursor-not-allowed">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-slate-700 mb-1">Freight Notes / Transporter Details (Optional)</label>
                <input type="text" name="freight_notes" id="po_freight_notes"
                       value="{{ old('freight_notes', $order->freight_notes) }}"
                       maxlength="1000"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="E.G. Transporter Name, Terms (Paid/To-Pay), Dispatch Terms...">
            </div>
        </div>
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
    <x-quick-add-supplier targetSelect="supplier_id" />

    <!-- Floating Product Search Dropdown Menu -->
    <div id="po-product-dropdown-menu"
         class="hidden fixed bg-white rounded-xl shadow-2xl border border-gray-200 z-[999999] overflow-hidden flex flex-col"
         style="width: 320px; max-height: 290px;">
        <!-- Sticky Search Box inside Dropdown -->
        <div class="p-2 border-b border-gray-100 bg-gray-50/90 sticky top-0 z-10">
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-gray-400">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
                <input type="text"
                       id="po-dropdown-search-box"
                       class="block w-full pl-8 pr-3 py-1.5 text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 bg-white placeholder-gray-400"
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

function handlePoBrandChange(brandSelect) {
    const row = brandSelect.closest('tr');
    if (!row) return;

    const brandId = brandSelect.value;
    const searchInput = row.querySelector('.po-product-search');
    const toggleBtn = row.querySelector('.po-dropdown-toggle');

    clearPoProductSelection(brandSelect);

    if (brandId) {
        if (searchInput) {
            searchInput.disabled = false;
            searchInput.placeholder = 'Select product...';
            searchInput.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.add('bg-white');
        }
        if (toggleBtn) {
            toggleBtn.disabled = false;
            toggleBtn.classList.remove('opacity-40', 'pointer-events-none');
        }
        brandSelect.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
    } else {
        if (searchInput) {
            searchInput.disabled = true;
            searchInput.placeholder = 'Select brand first';
            searchInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
            searchInput.classList.remove('bg-white');
        }
        if (toggleBtn) {
            toggleBtn.disabled = true;
            toggleBtn.classList.add('opacity-40', 'pointer-events-none');
        }
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

    const taxVal = parseFloat(opt.dataset.tax) || 0;
    const costVal = parseFloat(opt.dataset.cost) || 0;
    const uomVal = opt.dataset.uom;

    const costInput = row.querySelector('.po-cost');
    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');
    const uomSelect = row.querySelector('.po-uom');

    if (costInput && (!costInput.value || parseFloat(costInput.value) === 0)) {
        costInput.value = costVal;
    }
    if (taxInput) taxInput.value = taxVal;
    if (cgstInput) cgstInput.value = (taxVal / 2).toFixed(2);
    if (sgstInput) sgstInput.value = (taxVal / 2).toFixed(2);
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
        recalculatePoTotals();
        return;
    }

    const cgstValue = parseFloat(cgst.value) || 0;

    if (changed === 'cgst') {
        sgst.value = Math.max(0, taxValue - cgstValue).toFixed(2);
        recalculatePoTotals();
        return;
    }

    if (changed === 'sgst') {
        cgst.value = Math.max(0, taxValue - (parseFloat(sgst.value) || 0)).toFixed(2);
        recalculatePoTotals();
    }
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
    const freightAmountInput = document.getElementById('po_freight_amount');
    const freightTaxRateInput = document.getElementById('po_freight_tax_rate');
    const freightTaxAmountInput = document.getElementById('po_freight_tax_amount');
    const freightTotalInput = document.getElementById('po_freight_total');

    let freightAmount = 0;
    let freightTaxRate = 0;
    let freightTaxAmount = 0;
    let freightTotal = 0;

    if (isFreightApplicable) {
        freightAmount = parseFloat(freightAmountInput?.value) || 0;
        freightTaxRate = parseFloat(freightTaxRateInput?.value) || 0;
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
    if (summaryFreight) summaryFreight.textContent = '₹' + freightAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryFreightTax) summaryFreightTax.textContent = '₹' + freightTaxAmount.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    if (summaryGrandTotal) summaryGrandTotal.textContent = '₹' + grandTotal.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function addPoRow() {
    const table = document.getElementById('po-lines');
    const firstRow = table.rows[0];
    const newRow = firstRow.cloneNode(true);

    newRow.innerHTML = newRow.innerHTML.replace(/items\[\d+\]/g, 'items[' + poi + ']');

    newRow.querySelectorAll('input').forEach(input => {
        if (input.classList.contains('po-product-search')) {
            input.value = '';
            input.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
        } else if (input.name.includes('[quantity]')) {
            input.value = '1';
        } else if (input.name.includes('[batch_no]') || input.name.includes('[batch_name]')) {
            input.value = '';
        } else {
            input.value = '0';
        }
    });

    const batchInput = newRow.querySelector('.po-batch-input');
    if (batchInput) {
        batchInput.setAttribute('list', 'po-batch-list-' + poi);
        batchInput.value = '';
    }
    const datalist = newRow.querySelector('datalist');
    if (datalist) {
        datalist.id = 'po-batch-list-' + poi;
        datalist.innerHTML = '';
    }

    newRow.querySelectorAll('select').forEach(sel => {
        if (sel.classList.contains('po-product')) {
            sel.value = '';
            sel.selectedIndex = 0;
        } else if (sel.classList.contains('po-brand')) {
            sel.value = '';
            sel.selectedIndex = 0;
            sel.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500');
        }
    });

    const searchInput = newRow.querySelector('.po-product-search');
    if (searchInput) {
        searchInput.value = '';
        searchInput.disabled = true;
        searchInput.placeholder = 'Select brand first';
        searchInput.classList.remove('border-rose-500', 'ring-1', 'ring-rose-500', 'bg-white');
        searchInput.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
    }

    const clearBtn = newRow.querySelector('.po-clear-product');
    if (clearBtn) {
        clearBtn.style.display = 'none';
    }

    const toggleBtn = newRow.querySelector('.po-dropdown-toggle');
    if (toggleBtn) {
        toggleBtn.disabled = true;
        toggleBtn.classList.add('opacity-40', 'pointer-events-none');
    }

    const toggleSvg = newRow.querySelector('.po-dropdown-toggle svg');
    if (toggleSvg) {
        toggleSvg.classList.remove('rotate-180');
    }

    const removeBtn = newRow.querySelector('.po-remove-btn');
    if (removeBtn) removeBtn.style.display = 'inline';

    table.appendChild(newRow);
    poi++;
    updateRemoveButtons();
    recalculatePoTotals();
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
let initialLoad = true;

const initialBillingId = "{{ old('billing_address_id', $order->billing_address_id) }}";

function setPoCreditDays(days) {
    const input = document.getElementById('po_credit_days');
    if (input) {
        input.value = parseInt(days, 10) || 0;
    }
    recalcPoDueDate();
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

async function handleSupplierChange(supplierId) {
    const card = document.getElementById('supplier-addresses-card');
    const statusText = document.getElementById('address-status-text');
    const billingSelect = document.getElementById('billing_address_id');
    const billingSnapshot = document.getElementById('billing_address');

    if (!supplierId) {
        supplierAddresses = [];
        billingSnapshot.value = '';
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

    statusText.innerText = 'Loading supplier addresses...';
    try {
        const res = await fetch(`{{ url('/masters/customers') }}/${supplierId}/addresses`, {
            headers: { 'Accept': 'application/json' }
        });
        const data = await res.json();
        if (data.success) {
            supplierAddresses = data.addresses || [];
            statusText.innerText = `${supplierAddresses.length} supplier billing address(es) available`;

            billingSelect.innerHTML = '<option value="">Default Address</option>';

            supplierAddresses.forEach(addr => {
                const opt1 = new Option(`${addr.label} — ${addr.full_address}`, addr.id);
                billingSelect.add(opt1);
            });

            if (initialLoad) {
                if (initialBillingId) billingSelect.value = initialBillingId;
                initialLoad = false;
            } else {
                if (data.default_billing_id) billingSelect.value = data.default_billing_id;
                handleBillingChange();
            }
        }
    } catch (e) {
        console.error('Failed to load supplier addresses:', e);
        statusText.innerText = 'Could not load addresses';
    }
}

function handleBillingChange() {
    const billingSelect = document.getElementById('billing_address_id');
    const billingSnapshot = document.getElementById('billing_address');
    const addr = supplierAddresses.find(a => String(a.id) === String(billingSelect.value));
    if (addr) {
        billingSnapshot.value = (addr.snapshot || addr.full_address);
    }
}

function handleWarehouseChange(selectEl) {
    const selectedOption = selectEl.options[selectEl.selectedIndex];
    const warehouseAddress = selectedOption ? (selectedOption.dataset.address || '') : '';
    const shippingSnapshot = document.getElementById('shipping_address');
    if (shippingSnapshot && warehouseAddress) {
        if (!shippingSnapshot.value || shippingSnapshot.dataset.autoFilled === '1' || shippingSnapshot.value.trim() === '') {
            shippingSnapshot.value = warehouseAddress;
            shippingSnapshot.dataset.autoFilled = '1';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const supSelect = document.getElementById('supplier_id');
    if (supSelect && supSelect.value) {
        handleSupplierChange(supSelect.value);
    }
    const whSelect = document.getElementById('warehouse_id');
    if (whSelect && whSelect.value) {
        const opt = whSelect.options[whSelect.selectedIndex];
        const addr = opt ? (opt.dataset.address || '') : '';
        const shippingSnapshot = document.getElementById('shipping_address');
        if (shippingSnapshot && !shippingSnapshot.value.trim() && addr) {
            shippingSnapshot.value = addr;
            shippingSnapshot.dataset.autoFilled = '1';
        }
    }
    initialLoad = false;
    recalcPoDueDate();

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

