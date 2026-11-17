@extends('layouts.dms')
@section('title', 'Edit Purchase Invoice: ' . ($invoice->invoice_number ?? $invoice->invoice_no))

@section('content')
<x-ui.page-header :title="'Edit Purchase Invoice: ' . ($invoice->invoice_number ?? $invoice->invoice_no)">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.show', $invoice)">View Invoice</x-ui.button>
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back to List</x-ui.button>
    </x-slot>
</x-ui.page-header>

<form method="POST" action="{{ route('purchasing.invoices.update', $invoice) }}"
      x-data="purchaseInvoiceEditForm(@js($products), @js($invoice), @js($initialOrders ?? []), @js($invoiceItems ?? []), @js($odAccounts ?? []))"
      class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Supplier *</label>
                    <a href="{{ route('masters.customers.create', [
                            'party_type' => \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                            'return_url' => route('purchasing.invoices.edit', $invoice),
                        ]) }}"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        + Add Supplier
                    </a>
                </div>
                <select name="supplier_id" id="supplier_id" x-model="supplierId" @change="onSupplierChange(); syncSupplierLabel()" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}"
                                data-credit-days="{{ $s->credit_days ?? 0 }}"
                                @selected(old('supplier_id', request('supplier_id', $invoice->supplier_id))==$s->id)>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Purchase Order</label>
                    <span x-show="poLoading" class="text-[11px] text-indigo-600 animate-pulse font-medium inline-flex items-center gap-1" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </div>
                <div class="relative" @click.outside="closePoDropdown()">
                    <!-- Hidden input submitting selected PO ID -->
                    <input type="hidden" name="purchase_order_id" :value="purchaseOrderId">

                    <!-- Trigger Button -->
                    <button type="button"
                            @click="togglePoDropdown()"
                            :disabled="!supplierId || poLoading"
                            class="flex items-center justify-between w-full min-h-[38px] px-3 py-2 text-sm text-left bg-white border border-gray-300 rounded-lg shadow-2xs transition-all focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed"
                            :class="{ 'border-indigo-500 ring-2 ring-indigo-500/20': poDropdownOpen }">
                        <span class="truncate flex-1"
                              :class="{ 'text-slate-400': !purchaseOrderId, 'text-slate-900 font-medium': purchaseOrderId }"
                              x-text="!supplierId ? 'Select a supplier first' : (poLoading ? 'Loading purchase orders...' : (selectedOrderDisplay || 'Select Purchase Order (Optional)'))">
                        </span>
                        <div class="flex items-center gap-1.5 ml-2 shrink-0">
                            <span x-show="purchaseOrderId && !poLoading"
                                  @click.stop="clearPurchaseOrder()"
                                  class="p-0.5 text-slate-400 hover:text-rose-600 rounded cursor-pointer transition-colors"
                                  title="Clear purchase order selection">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                            </span>
                            <svg class="w-4 h-4 text-slate-400 transition-transform duration-200"
                                 :class="{ 'rotate-180 text-indigo-600': poDropdownOpen }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>

                    <!-- Floating Dropdown Popover -->
                    <div x-show="poDropdownOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute z-50 left-0 right-0 mt-1 bg-white rounded-lg shadow-xl border border-slate-200 overflow-hidden"
                         style="display: none;"
                         x-cloak>

                        <!-- Search Input Header -->
                        <div class="p-2 bg-slate-50 border-b border-slate-100">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input type="text"
                                       x-ref="poSearchInput"
                                       x-model="poSearchQuery"
                                       @keydown.escape.prevent="closePoDropdown()"
                                       @keydown.arrow-down.prevent="navigatePoOptions(1)"
                                       @keydown.arrow-up.prevent="navigatePoOptions(-1)"
                                       @keydown.enter.prevent="selectHighlightedPo()"
                                       placeholder="Search by PO #, date, product, status..."
                                       autocomplete="off"
                                       class="block w-full pl-8 pr-3 py-1.5 text-xs bg-white border border-slate-300 rounded-md shadow-2xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 placeholder:text-slate-400">
                            </div>
                        </div>

                        <!-- Orders List -->
                        <div class="max-h-64 overflow-y-auto divide-y divide-slate-100">
                            <template x-for="(po, idx) in filteredSupplierOrders" :key="po.id">
                                <div @click="selectPurchaseOrder(po.id)"
                                     @mouseenter="highlightedPoIndex = idx"
                                     :class="{
                                         'bg-indigo-50 border-l-4 border-indigo-600 font-semibold': String(purchaseOrderId) === String(po.id),
                                         'bg-slate-50': highlightedPoIndex === idx && String(purchaseOrderId) !== String(po.id)
                                     }"
                                     class="p-2.5 hover:bg-indigo-50/60 cursor-pointer text-xs flex flex-col gap-1 transition-colors">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-slate-900" x-text="po.po_no"></span>
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold"
                                                  :class="{
                                                      'bg-emerald-100 text-emerald-800': (po.status || '').toLowerCase().includes('approved'),
                                                      'bg-blue-100 text-blue-800': (po.status || '').toLowerCase().includes('partially'),
                                                      'bg-purple-100 text-purple-800': (po.status || '').toLowerCase().includes('closed'),
                                                      'bg-slate-100 text-slate-700': !(po.status || '').toLowerCase().includes('approved') && !(po.status || '').toLowerCase().includes('partially') && !(po.status || '').toLowerCase().includes('closed')
                                                  }"
                                                  x-text="po.status"></span>
                                        </div>
                                        <div class="font-mono font-semibold text-slate-900 text-xs">
                                            ₹<span x-text="Number(po.grand_total || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})"></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center justify-between text-[11px] text-slate-500">
                                        <span x-text="'Date: ' + (po.po_date || '—')"></span>
                                        <span class="text-amber-700 font-medium" x-show="po.remaining_qty !== undefined" x-text="'Remaining: ' + po.remaining_qty + '/' + po.total_qty"></span>
                                    </div>
                                    <div x-show="po.products_summary || (po.products && po.products.length)" class="text-[10px] text-slate-500 truncate">
                                        <span class="font-medium text-slate-600">Items:</span>
                                        <span x-text="po.products_summary || (po.products ? po.products.join(', ') : '')"></span>
                                    </div>
                                </div>
                            </template>

                            <!-- Empty / filter feedback inside dropdown -->
                            <div x-show="supplierOrders.length > 0 && filteredSupplierOrders.length === 0" class="p-4 text-center text-xs text-slate-500 italic">
                                No purchase orders match "<span class="font-semibold text-slate-700" x-text="poSearchQuery"></span>"
                            </div>
                            <div x-show="supplierOrders.length === 0" class="p-4 text-center text-xs text-amber-700 font-medium">
                                No eligible Purchase Orders found for this supplier
                            </div>
                        </div>
                    </div>
                </div>
                <p x-show="supplierId && !poLoading && supplierOrders.length === 0" class="mt-1 text-[11px] text-amber-600 font-medium" x-cloak>
                    No eligible purchase orders found for this supplier
                </p>
                <p x-show="!supplierId" class="mt-1 text-[11px] text-slate-400">
                    Select a supplier first to load purchase orders
                </p>
                <p x-show="selectedOrderLabel && purchaseOrderId" class="mt-1 text-[11px] text-emerald-600 font-medium" x-cloak>
                    Linked to <span x-text="selectedOrderLabel"></span>
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Warehouse *</label>
                <select name="warehouse_id" x-model="warehouseId" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(old('warehouse_id', $invoice->warehouse_id)==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" x-model="invoiceDate" @change="recalcDueDate()" @input="recalcDueDate()"
                       value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Supplier Inv No.</label>
                <input type="text" name="supplier_invoice_number"
                       value="{{ old('supplier_invoice_number', $invoice->supplier_invoice_number ?? $invoice->supplier_invoice_no) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="E.G. Inv-10023">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Material Dispatch Date</label>
                <input type="date" name="material_dispatch_date"
                       value="{{ old('material_dispatch_date', $invoice->material_dispatch_date?->format('Y-m-d')) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Expected Delivery Date</label>
                <input type="date" name="expected_delivery_date" x-model="expectedDeliveryDate"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                    <button type="button"
                            @click="setExpectedDeliveryDays(30)"
                            :class="expectedDeliveryQuickDays === 30 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        30 Days
                    </button>
                    <button type="button"
                            @click="setExpectedDeliveryDays(45)"
                            :class="expectedDeliveryQuickDays === 45 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        45 Days
                    </button>
                    <button type="button"
                            @click="setExpectedDeliveryDays(50)"
                            :class="expectedDeliveryQuickDays === 50 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        50 Days
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Payment Order Date</label>
                <input type="date" name="payment_order_date" x-model="paymentOrderDate"
                       value="{{ old('payment_order_date', $invoice->payment_order_date?->format('Y-m-d')) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Credit Days</label>
                    <span class="text-xs text-slate-500" x-show="dueDatePreview">
                        Due: <span class="font-semibold text-indigo-600" x-text="dueDatePreview"></span>
                    </span>
                </div>
                <input type="number" name="credit_days" min="0" placeholder="0"
                       x-model.number="creditDays"
                       @input="recalcDueDate()"
                       value="{{ old('credit_days', $invoice->credit_days ?? 0) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <input type="hidden" name="due_date" :value="dueDateFormattedYmd">

                <div class="flex items-center gap-1.5 mt-2">
                    <span class="text-[11px] font-medium text-slate-400">Quick:</span>
                    <button type="button"
                            @click="setCreditDays(30)"
                            :class="creditDays === 30 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        30 Days
                    </button>
                    <button type="button"
                            @click="setCreditDays(45)"
                            :class="creditDays === 45 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        45 Days
                    </button>
                    <button type="button"
                            @click="setCreditDays(50)"
                            :class="creditDays === 50 ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm' : 'bg-white text-slate-700 hover:bg-slate-50 border-slate-300'"
                            class="px-2 py-0.5 text-xs font-medium rounded border transition-colors">
                        50 Days
                    </button>
                </div>
            </div>

        </div>
    </x-ui.card>

    @php
        $editFreightOn = old('is_freight_applicable');
        if ($editFreightOn === null) {
            $editFreightOn = ($invoice->is_freight_applicable || (float) $invoice->freight_charge > 0) ? '1' : '0';
        }
    @endphp
    @include('purchasing.invoices.partials.dispatch-and-freight', [
        'receiptDocNo' => old('receipt_doc_no', $invoice->receipt_doc_no),
        'dispatchedThrough' => old('dispatched_through', $invoice->dispatched_through),
        'vehicleNumber' => old('vehicle_number', $invoice->vehicle_number),
        'freightNotes' => old('freight_notes', $invoice->freight_notes),
    ])

    <!-- Supplier Billing & Delivery Addresses -->
    <div x-show="supplierId" class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 space-y-4">
        <div class="flex items-center justify-between pb-2 border-b border-slate-200">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing &amp; Delivery Addresses</span>
            <span class="text-xs text-slate-500" x-text="addressesLoading ? 'Loading addresses...' : `${partyAddresses.length} address(es) available`"></span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Billing Address Card -->
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Billing Address</label>
                    <span class="text-[11px] text-indigo-600 font-medium">Billed from</span>
                </div>
                <select name="billing_address_id" x-model="selectedBillingId" @change="onBillingAddressChange()"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Default Address</option>
                    <template x-for="addr in partyAddresses" :key="addr.id">
                        <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                    </template>
                </select>
                <textarea name="billing_address" x-model="billingSnapshot" rows="3"
                          placeholder="Billing Address Snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Delivery Address Card -->
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery / Dispatch Address</label>
                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer font-medium">
                        <input type="checkbox" x-model="sameAsBilling" @change="onSameAsBillingToggle()"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Same as Billing Address</span>
                    </label>
                </div>
                <div x-show="!sameAsBilling">
                    <select name="shipping_address_id" x-model="selectedShippingId" @change="onShippingAddressChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Default Delivery Address</option>
                        <template x-for="addr in partyAddresses" :key="addr.id">
                            <option :value="addr.id" x-text="`${addr.label} — ${addr.full_address}`"></option>
                        </template>
                    </select>
                </div>
                <div x-show="sameAsBilling" class="text-xs text-slate-500 italic py-1">
                    Delivery address matches the selected Billing Address.
                </div>
                <textarea name="shipping_address" x-model="shippingSnapshot" rows="3"
                          placeholder="Delivery Address Snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <x-ui.card padding="false">
        <div class="p-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <div>
                <div class="flex items-center gap-3">
                    <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
                    <div class="inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 p-0.5 text-xs font-semibold">
                        <label class="cursor-pointer px-2.5 py-1 rounded-md transition"
                               :class="priceType === 'exclusive' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <input type="radio" name="price_type" value="exclusive" :checked="priceType === 'exclusive'" @change="switchPriceType('exclusive')" class="sr-only">
                            Tax Exclusive
                        </label>
                        <label class="cursor-pointer px-2.5 py-1 rounded-md transition"
                               :class="priceType === 'inclusive' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-600 hover:text-slate-900'">
                            <input type="radio" name="price_type" value="inclusive" :checked="priceType === 'inclusive'" @change="switchPriceType('inclusive')" class="sr-only">
                            Tax Inclusive
                        </label>
                    </div>
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    <span x-show="priceType === 'exclusive'">Unit cost is before GST. Tax is added on top, and Purchase Price Incl. GST is calculated from it.</span>
                    <span x-show="priceType === 'inclusive'" x-cloak class="text-indigo-700 font-medium">Unit cost already includes GST. Tax is taken out of that price and is not added again. Purchase Price Incl. GST matches the unit cost.</span>
                </p>
            </div>
            <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                + Add Item
            </button>
        </div>

        <div class="overflow-x-auto border-t border-slate-200">
            <table class="w-full min-w-[1580px] text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 tracking-wider border-b border-slate-200 font-semibold text-[11px]">
                    <tr>
                        <th class="p-3 min-w-[340px] lg:min-w-[380px]">Product / Description</th>
                        <th class="p-3 min-w-[70px] w-20 text-center">Unit</th>
                        <th class="p-3 min-w-[110px] w-28 text-right">Qty *</th>
                        <th class="p-3 min-w-[120px] w-28 text-right">MRP (₹)</th>
                        <th class="p-3 min-w-[145px] w-36 text-right" title="Actual purchase price paid including GST">Purchase Price Incl. GST (₹)</th>
                        <th class="p-3 min-w-[110px] w-28 text-right" title="Auto: [(MRP − Purchase Price) ÷ MRP] × 100">Disc. %</th>
                        <th class="p-3 min-w-[150px] w-40 text-right" x-text="priceType === 'inclusive' ? 'Unit Cost Incl. GST (₹) *' : 'Unit Cost Ex. GST (₹) *'"></th>
                        <th class="p-3 min-w-[95px] w-24 text-right">Cgst %</th>
                        <th class="p-3 min-w-[95px] w-24 text-right">Sgst %</th>
                        <th class="p-3 min-w-[100px] w-28 text-center">Tax Rate</th>
                        <th class="p-3 min-w-[130px] w-36">Batch No</th>
                        <th class="p-3 min-w-[140px] w-36 text-right">Line Total (₹)</th>
                        <th class="p-3 min-w-[50px] w-14 text-center"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <template x-for="(row, i) in items" :key="i">
                        <tr class="align-top hover:bg-slate-50/50 transition-colors">
                            <td class="p-3 min-w-[340px] lg:min-w-[380px]">
                                <select :name="`items[${i}][product_id]`"
                                        x-model="row.product_id"
                                        @change="onProductChange(i)"
                                        required
                                        class="block w-full rounded-md border-gray-300 text-xs sm:text-sm font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px] px-3 py-2 bg-white">
                                    <option value="">Select product...</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id" x-text="p.name" :selected="String(p.id) === String(row.product_id)"></option>
                                    </template>
                                </select>

                                    <template x-if="isSerialTracked(row)">
                                        <div class="mt-2">
                                            <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                                Serial number
                                                <span class="text-[10px] font-bold uppercase text-amber-700">Required</span>
                                            </label>
                                            <template x-for="(sNo, sIdx) in (row.serial_numbers || [])" :key="'serial-' + i + '-' + sIdx">
                                                <input type="text"
                                                       :name="`items[${i}][serial_numbers][${sIdx}]`"
                                                       x-model="row.serial_numbers[sIdx]"
                                                       :placeholder="(row.serial_numbers.length > 1) ? ('Serial ' + (sIdx + 1)) : 'Serial number'"
                                                       required
                                                       class="mb-1 block w-full rounded-md border-gray-300 px-2.5 py-2 text-xs font-mono text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            </template>
                                        </div>
                                    </template>
                            </td>

                            <td class="p-3 align-top min-w-[70px] w-20 text-center text-xs font-semibold text-slate-600 pt-5"
                                x-text="row.unit || '—'"></td>

                            <td class="p-3 align-top min-w-[110px] w-28">
                                <input type="number"
                                       step="0.0001"
                                       min="0.0001"
                                       :name="`items[${i}][quantity]`"
                                       x-model.number="row.quantity"
                                       @input="syncSerials(i); recalc()"
                                       required
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[120px] w-28">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][batch_mrp]`"
                                       x-model.number="row.batch_mrp"
                                       @input="syncPurchaseDiscount(i)"
                                       placeholder="MRP"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[145px] w-36">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][purchase_price_inclusive]`"
                                       x-model.number="row.purchase_price_inclusive"
                                       @input="onPurchasePriceInclusiveChange(i)"
                                       placeholder="Incl. GST"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[110px] w-28">
                                <input type="number"
                                       step="0.01"
                                       :name="`items[${i}][purchase_discount_percent]`"
                                       x-model.number="row.purchase_discount_percent"
                                       readonly
                                       tabindex="-1"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 bg-slate-50 shadow-sm">
                            </td>

                            <td class="p-3 align-top min-w-[130px] w-36">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][unit_cost]`"
                                       x-model.number="row.unit_cost"
                                       @input="onUnitCostChange(i)"
                                       required
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[95px] w-24">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       :name="`items[${i}][cgst_percent]`"
                                       x-model.number="row.cgst_percent"
                                       @input="onTaxChange(i)"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[95px] w-24">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       :name="`items[${i}][sgst_percent]`"
                                       x-model.number="row.sgst_percent"
                                       @input="onTaxChange(i)"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[100px] w-28 text-center pt-5 font-semibold text-xs text-slate-700"
                                x-text="((row.cgst_percent || 0) + (row.sgst_percent || 0)).toFixed(2) + '%'"></td>

                            <td class="p-3 align-top min-w-[130px] w-36">
                                <input type="text"
                                       :name="`items[${i}][batch_number]`"
                                       x-model="row.batch_number"
                                       placeholder="Batch No"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[140px] w-36 text-right pt-5 font-bold font-mono text-slate-900 text-sm whitespace-nowrap"
                                x-text="formatCurrency(row.line_total)"></td>

                            <td class="p-3 align-top min-w-[50px] w-14 text-center pt-4">
                                <button type="button"
                                        @click="removeItem(i)"
                                        x-show="items.length > 1"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                                        title="Remove row">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <div class="p-4 bg-slate-50/50 border-t border-slate-200 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-2.5 text-xs md:col-start-2">
                <div class="flex justify-between items-center text-slate-600">
                    <span class="font-medium">Taxable Subtotal:</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(subtotal)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span class="font-medium">Total Tax (CGST + SGST):</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(taxTotal)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-600" x-show="freightApplicable === '1' && freightBase > 0">
                    <span class="font-medium">Freight:</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(freightBase)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-600" x-show="freightApplicable === '1' && freightTaxAmount > 0">
                    <span class="font-medium">Freight Tax:</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(freightTaxAmount)"></span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span class="font-medium">Other Charges (₹):</span>
                    <input type="number" step="0.01" min="0" name="other_charges" x-model.number="otherCharges" @input="recalc()"
                           class="w-32 min-h-[34px] px-2.5 py-1.5 rounded-md border-gray-300 text-xs font-mono text-right shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="flex items-center justify-between text-slate-600 pt-1 border-t border-dashed border-slate-200">
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium">Round Off (₹):</span>
                        <button type="button" @click="autoRoundOff()" class="text-[10px] text-indigo-600 hover:text-indigo-800 underline" title="Re-calculate automatic round off">Auto</button>
                    </div>
                    <input type="number" step="0.01" min="-500" max="500" name="round_off" x-model.number="roundOff" @input="isManualRoundOff = true; recalc()"
                           placeholder="0.00"
                           class="w-32 min-h-[34px] px-2.5 py-1.5 rounded-md border-gray-300 text-xs font-mono text-right shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="flex justify-between items-center font-bold text-base text-slate-900 border-t border-slate-200 pt-3 mt-1">
                    <span>Grand Total:</span>
                    <span class="font-mono text-indigo-700 text-lg" x-text="formatCurrency(grandTotal)"></span>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center gap-3 pt-2 border-t border-slate-200">
        @if($invoice->canReceiveMaterial())
            <x-ui.button type="submit" name="save_mode" value="draft" variant="secondary"
                         title="Mark remaining material as received and post stock">
                Save as Draft
            </x-ui.button>
            <x-ui.button type="submit" name="save_mode" value="open" variant="primary"
                         title="Keep material in transit">
                Save as Open
            </x-ui.button>
        @else
            <x-ui.button type="submit" name="save_mode" value="open" variant="primary">
                Update Purchase Invoice
            </x-ui.button>
        @endif
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.show', $invoice)">Cancel</x-ui.button>
    </div>
    @if($invoice->canReceiveMaterial())
        <p class="text-[11px] text-slate-500 -mt-2">
            <span class="font-semibold text-slate-600">Draft</span> = material received, ready to pay (stock posted).
            <span class="font-semibold text-slate-600">Open</span> = material still in transit.
        </p>
    @endif
</form>

@push('scripts')
<script>
function purchaseInvoiceEditForm(products, invoice, initialOrders, existingItems, odAccounts) {
    return {
        products: products || [],
        odAccounts: odAccounts || [],
        odAccountId: '',
        onOdAccountChange() {},
        supplierId: '{{ old('supplier_id', request('supplier_id', $invoice->supplier_id)) }}',
        supplierQuery: '',
        supplierOpen: false,
        suppliers: [],
        warehouseId: '{{ old('warehouse_id', $invoice->warehouse_id) }}',
        supplierOrders: initialOrders || [],
        poLoading: false,
        poDropdownOpen: false,
        poSearchQuery: '',
        highlightedPoIndex: -1,
        purchaseOrderId: '{{ old('purchase_order_id', $invoice->purchase_order_id ?? '') }}',
        selectedOrderLabel: invoice && invoice.purchase_order ? (invoice.purchase_order.po_no || invoice.purchase_order.order_number || '') : '',
        linkedPoId: invoice ? invoice.purchase_order_id : null,
        partyAddresses: [],
        addressesLoading: false,
        selectedBillingId: '{{ old('billing_address_id', $invoice->billing_address_id ?? '') }}',
        selectedShippingId: '{{ old('shipping_address_id', $invoice->shipping_address_id ?? '') }}',
        billingSnapshot: @js(old('billing_address', $invoice->billing_address ?? '')),
        shippingSnapshot: @js(old('shipping_address', $invoice->shipping_address ?? '')),
        sameAsBilling: !('{{ $invoice->shipping_address_id }}') || ('{{ $invoice->shipping_address_id }}' === '{{ $invoice->billing_address_id }}'),
        invoiceDate: '{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d') ?? date('Y-m-d')) }}',
        purchaseReceiveDate: '{{ old('purchase_receive_date', $invoice->purchase_receive_date?->format('Y-m-d') ?? '') }}',
        paymentOrderDate: '{{ old('payment_order_date', $invoice->payment_order_date?->format('Y-m-d') ?? '') }}',
        creditDays: {{ old('credit_days', $invoice->credit_days ?? 0) }},
        expectedDeliveryDate: '{{ old('expected_delivery_date', $invoice->expected_delivery_date?->format('Y-m-d') ?? '') }}',
        expectedDeliveryQuickDays: null,
        dueDatePreview: '',
        dueDateFormattedYmd: '{{ old('due_date', $invoice->due_date?->format('Y-m-d') ?? '') }}',
        freightApplicable: '{{ $editFreightOn }}',
        freightMode: '{{ old('freight_mode', $invoice->freight_mode ?: 'amount') }}',
        freightPercent: {{ (float) old('freight_percent', $invoice->freight_percent ?? 0) }},
        freightAmount: {{ (float) old('freight_amount', $invoice->freight_amount ?: $invoice->freight_charge) }},
        freightTaxRate: {{ (float) old('freight_tax_rate', $invoice->freight_tax_rate ?? 0) }},
        freightBase: 0,
        freightTaxAmount: 0,
        freightTotal: 0,
        orderNos: @js(old('order_nos', $invoice->order_nos ?? $invoice->purchaseOrder?->po_no ?? '')),
        otherCharges: {{ old('other_charges', (float)($invoice->other_charges ?? 0)) }},
        priceType: '{{ old('price_type', $invoice->price_type ?? 'exclusive') }}',
        roundOff: {{ old('round_off', (float)($invoice->round_off ?? 0)) }},
        isManualRoundOff: {{ old('round_off') !== null || $invoice->round_off !== null ? 'true' : 'false' }},
        subtotal: 0,
        taxTotal: 0,
        grandTotal: 0,
        items: [],

        async init() {
            this.recalcDueDate();
            if (this.supplierId) {
                await this.loadSupplierAddresses(this.supplierId);
                if (!this.supplierOrders.length) {
                    await this.loadSupplierOrders(this.supplierId, this.purchaseOrderId);
                }
            }

            if (existingItems && existingItems.length) {
                this.items = existingItems.map(it => {
                    const p = this.products.find(prod => prod.id === it.product_id);
                    const storedBase = Number(it.unit_cost) || 0;
                    const cgst = Number(it.cgst_percent) || 0;
                    const sgst = Number(it.sgst_percent) || 0;
                    const tax = cgst + sgst;
                    const mrp = Number(it.batch_mrp) || (p ? (Number(p.mrp) || 0) : 0);
                    const inclusive = Number(it.purchase_price_inclusive) || Number((storedBase * (1 + tax / 100)).toFixed(2));
                    const entered = Number(it.entered_unit_cost) || 0;
                    const unitCost = this.priceType === 'inclusive'
                        ? (entered || inclusive || storedBase)
                        : storedBase;
                    const isTracked = p ? (p.is_serial_tracked !== undefined ? Boolean(p.is_serial_tracked) : String(p.tracking_type || '').includes('serial')) : false;
                    return {
                        product_id: it.product_id,
                        unit: it.unit || (p ? (p.unit || 'PCS') : 'PCS'),
                        tracking_type: p ? (p.tracking_type || 'none') : 'none',
                        is_serial_tracked: isTracked,
                        quantity: Number(it.quantity) || 1,
                        unit_cost: unitCost,
                        purchase_price_inclusive: this.priceType === 'inclusive' ? unitCost : inclusive,
                        purchase_discount_percent: Number(it.purchase_discount_percent) || (mrp > 0 ? Number((((mrp - inclusive) / mrp) * 100).toFixed(2)) : 0),
                        cgst_percent: cgst,
                        sgst_percent: sgst,
                        batch_number: it.batch_number || it.batch_no || '',
                        batch_mrp: mrp,
                        serial_numbers: isTracked && Array.isArray(it.serial_numbers) && it.serial_numbers.length ? it.serial_numbers : (isTracked ? [''] : []),
                        line_total: Number(it.line_total) || 0
                    };
                });
            } else {
                this.addItem();
            }
            this.items.forEach((_, idx) => this.syncSerials(idx));
            this.recalc();
        },

        addItem() {
            this.items.push({
                product_id: '',
                unit: '-',
                tracking_type: 'none',
                is_serial_tracked: false,
                quantity: 1,
                unit_cost: 0,
                purchase_price_inclusive: 0,
                purchase_discount_percent: 0,
                cgst_percent: 0,
                sgst_percent: 0,
                batch_number: '',
                batch_mrp: 0,
                serial_numbers: [],
                line_total: 0
            });
            this.recalc();
        },

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.recalc();
            }
        },

        isSerialTracked(row) {
            if (!row) return false;
            if (row.is_serial_tracked !== undefined && row.is_serial_tracked !== null) {
                return Boolean(row.is_serial_tracked);
            }
            if (row.product_id) {
                const p = this.products.find(item => String(item.id) === String(row.product_id));
                if (p) {
                    if (p.is_serial_tracked !== undefined) return Boolean(p.is_serial_tracked);
                    if (p.track_serial !== undefined) return Boolean(p.track_serial);
                    if (p.tracking_type) {
                        return String(p.tracking_type).toLowerCase().includes('serial');
                    }
                }
            }
            let tt = row.tracking_type;
            if (!tt || tt === 'none') return false;
            if (Array.isArray(tt)) return tt.includes('serial');
            const str = String(tt).toLowerCase();
            return str === 'serial' || str.split(',').map(s => s.trim()).includes('serial');
        },

        rowTaxPercent(row) {
            return (Number(row.cgst_percent) || 0) + (Number(row.sgst_percent) || 0);
        },

        syncPurchaseDiscount(index) {
            const row = this.items[index];
            if (!row) return;
            const mrp = Number(row.batch_mrp) || 0;
            const inclusive = Number(row.purchase_price_inclusive) || 0;
            row.purchase_discount_percent = mrp > 0
                ? Number((((mrp - inclusive) / mrp) * 100).toFixed(2))
                : 0;
        },

        grossFromBase(base, taxPercent) {
            return Number(((Number(base) || 0) * (1 + (Number(taxPercent) || 0) / 100)).toFixed(2));
        },

        baseFromGross(gross, taxPercent) {
            const tax = Number(taxPercent) || 0;
            const amount = Number(gross) || 0;
            return tax > 0 ? Number((amount * 100 / (100 + tax)).toFixed(2)) : amount;
        },

        onPurchasePriceInclusiveChange(index) {
            const row = this.items[index];
            if (!row) return;
            const tax = this.rowTaxPercent(row);
            const inclusive = Number(row.purchase_price_inclusive) || 0;
            if (this.priceType === 'inclusive') {
                row.unit_cost = inclusive;
            } else {
                row.unit_cost = this.baseFromGross(inclusive, tax);
            }
            this.syncPurchaseDiscount(index);
            this.recalc();
        },

        onUnitCostChange(index) {
            const row = this.items[index];
            if (!row) return;
            const tax = this.rowTaxPercent(row);
            const cost = Number(row.unit_cost) || 0;
            row.purchase_price_inclusive = this.priceType === 'inclusive'
                ? cost
                : this.grossFromBase(cost, tax);
            this.syncPurchaseDiscount(index);
            this.recalc();
        },

        onTaxChange(index) {
            const row = this.items[index];
            if (!row) return;
            if (this.priceType === 'inclusive') {
                row.purchase_price_inclusive = Number(row.unit_cost) || 0;
                this.syncPurchaseDiscount(index);
                this.recalc();
                return;
            }
            this.onUnitCostChange(index);
        },

        switchPriceType(next) {
            const target = next === 'inclusive' ? 'inclusive' : 'exclusive';
            if (this.priceType === target) {
                this.recalc();
                return;
            }
            this.items.forEach(row => {
                const tax = this.rowTaxPercent(row);
                if (target === 'inclusive') {
                    const gross = Number(row.purchase_price_inclusive) || this.grossFromBase(row.unit_cost, tax);
                    row.unit_cost = gross;
                    row.purchase_price_inclusive = gross;
                } else {
                    const gross = Number(row.unit_cost) || Number(row.purchase_price_inclusive) || 0;
                    row.purchase_price_inclusive = gross;
                    row.unit_cost = this.baseFromGross(gross, tax);
                }
            });
            this.priceType = target;
            this.items.forEach((_, idx) => this.syncPurchaseDiscount(idx));
            this.recalc();
        },

        async onProductChange(index) {
            const row = this.items[index];
            if (!row) return;

            const prodId = row.product_id;
            if (!prodId) {
                row.tracking_type = 'none';
                row.is_serial_tracked = false;
                row.serial_numbers = [];
                this.syncSerials(index);
                this.recalc();
                return;
            }

            const p = this.products.find(item => String(item.id) === String(prodId));
            if (p) {
                row.unit = p.unit || p.base_uom?.name || p.base_uom?.code || 'PCS';
                row.tracking_type = p.tracking_type || 'none';
                row.is_serial_tracked = p.is_serial_tracked !== undefined
                    ? Boolean(p.is_serial_tracked)
                    : (p.track_serial !== undefined ? Boolean(p.track_serial) : String(p.tracking_type || '').includes('serial'));
                row.cgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.sgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.batch_mrp = Number(p.mrp) || 0;
                const catalogBase = Number(p.purchase_price) || 0;
                if (this.priceType === 'inclusive') {
                    row.unit_cost = this.grossFromBase(catalogBase, this.rowTaxPercent(row));
                } else {
                    row.unit_cost = catalogBase;
                }
                if (!row.is_serial_tracked) {
                    row.serial_numbers = [];
                }
                this.syncSerials(index);
                this.onUnitCostChange(index);
            }

            try {
                const res = await fetch(`{{ url('/purchasing/invoices/products') }}/${prodId}/details`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const json = await res.json();
                    if (json && json.data) {
                        const dbProd = json.data;
                        if (String(row.product_id) === String(dbProd.id)) {
                            row.tracking_type = dbProd.tracking_type || 'none';
                            row.is_serial_tracked = Boolean(dbProd.is_serial_tracked);
                            if (!row.is_serial_tracked) {
                                row.serial_numbers = [];
                            } else {
                                this.syncSerials(index);
                            }
                            const cached = this.products.find(item => String(item.id) === String(dbProd.id));
                            if (cached) {
                                cached.tracking_type = dbProd.tracking_type;
                                cached.is_serial_tracked = dbProd.is_serial_tracked;
                                cached.track_serial = dbProd.track_serial;
                            }
                        }
                    }
                }
            } catch (err) {}

            this.syncSerials(index);
            this.recalc();
        },

        syncSerials(index) {
            const row = this.items[index];
            if (!row) return;
            if (!this.isSerialTracked(row)) {
                row.serial_numbers = [];
                return;
            }
            const qty = Math.min(30, Math.max(1, Math.round(Number(row.quantity) || 1)));
            if (!Array.isArray(row.serial_numbers)) row.serial_numbers = [];
            while (row.serial_numbers.length < qty) {
                row.serial_numbers.push('');
            }
            while (row.serial_numbers.length > qty) {
                row.serial_numbers.pop();
            }
        },

        recalc() {
            let sub = 0;
            let tax = 0;

            this.items.forEach(row => {
                const qty = Number(row.quantity) || 0;
                const enteredCost = Number(row.unit_cost) || 0;
                const taxPercent = (Number(row.cgst_percent) || 0) + (Number(row.sgst_percent) || 0);

                let lineBase, lineTax;
                if (this.priceType === 'inclusive') {
                    const baseCost = taxPercent > 0 ? (enteredCost * 100 / (100 + taxPercent)) : enteredCost;
                    lineBase = qty * baseCost;
                    lineTax = (lineBase * taxPercent) / 100;
                    row.line_total = qty * enteredCost;
                } else {
                    lineBase = qty * enteredCost;
                    lineTax = (lineBase * taxPercent) / 100;
                    row.line_total = lineBase + lineTax;
                }

                sub += lineBase;
                tax += lineTax;
            });

            this.subtotal = sub;
            this.taxTotal = tax;
            this.applyFreight(sub);
            const beforeRound = sub + tax + (Number(this.freightTotal) || 0) + (Number(this.otherCharges) || 0);
            if (!this.isManualRoundOff) {
                this.roundOff = Math.round((Math.round(beforeRound) - beforeRound) * 100) / 100;
            }
            const r = Number(this.roundOff) || 0;
            this.grandTotal = Math.max(0, Math.round((beforeRound + r) * 100) / 100);
        },

        autoRoundOff() {
            const beforeRound = this.subtotal + this.taxTotal + (Number(this.freightTotal) || 0) + (Number(this.otherCharges) || 0);
            this.roundOff = Math.round((Math.round(beforeRound) - beforeRound) * 100) / 100;
            this.isManualRoundOff = false;
            this.recalc();
        },

        applyFreight(subtotal) {
            if (String(this.freightApplicable) !== '1') {
                this.freightBase = 0;
                this.freightTaxAmount = 0;
                this.freightTotal = 0;
                return;
            }
            const base = this.freightMode === 'percent'
                ? (Number(subtotal) || 0) * (Number(this.freightPercent) || 0) / 100
                : (Number(this.freightAmount) || 0);
            const tax = base * (Number(this.freightTaxRate) || 0) / 100;
            this.freightBase = Math.round(base * 100) / 100;
            this.freightTaxAmount = Math.round(tax * 100) / 100;
            this.freightTotal = Math.round((this.freightBase + this.freightTaxAmount) * 100) / 100;
        },

        clearFreight() {
            this.freightApplicable = '0';
            this.freightMode = 'amount';
            this.freightPercent = 0;
            this.freightAmount = 0;
            this.freightTaxRate = 0;
            this.freightBase = 0;
            this.freightTaxAmount = 0;
            this.freightTotal = 0;
        },

        applyOrderFreight(ord) {
            const on = !!ord.is_freight_applicable || Number(ord.freight_total) > 0;
            this.freightApplicable = on ? '1' : '0';
            this.freightMode = ord.freight_mode === 'percent' ? 'percent' : 'amount';
            this.freightPercent = Number(ord.freight_percent) || 0;
            this.freightAmount = Number(ord.freight_amount) || 0;
            this.freightTaxRate = Number(ord.freight_tax_rate) || 0;
        },

        formatCurrency(val) {
            return '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async onSupplierChange() {
            const sel = document.querySelector('select[name="supplier_id"]');
            if (sel && sel.value !== undefined) {
                this.supplierId = sel.value;
            }
            const hadLinkedPo = !!this.linkedPoId;
            this.purchaseOrderId = '';
            this.selectedOrderLabel = '';
            this.supplierOrders = [];
            this.selectedBillingId = '';
            this.selectedShippingId = '';
            this.billingSnapshot = '';
            this.shippingSnapshot = '';
            this.sameAsBilling = true;

            if (hadLinkedPo) {
                this.items = [];
                this.addItem();
                this.clearFreight();
                this.linkedPoId = null;
                this.recalc();
            }

            if (!this.supplierId) {
                this.partyAddresses = [];
                return;
            }

            const supOpt = document.querySelector(`select[name="supplier_id"] option[value="${this.supplierId}"]`);
            if (supOpt && supOpt.dataset.creditDays && (!this.creditDays || this.creditDays === 0)) {
                const sDays = parseInt(supOpt.dataset.creditDays, 10);
                if (sDays > 0) {
                    this.setCreditDays(sDays);
                }
            }

            await Promise.all([
                this.loadSupplierAddresses(this.supplierId, true),
                this.loadSupplierOrders(this.supplierId)
            ]);
        },

        async loadSupplierOrders(supId, preselectId = null) {
            if (!supId) {
                this.supplierOrders = [];
                this.purchaseOrderId = '';
                this.selectedOrderLabel = '';
                return;
            }

            this.poLoading = true;
            try {
                const res = await fetch(`{{ url('/purchasing/invoices/suppliers') }}/${supId}/purchase-orders`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.supplierOrders = data.orders || [];

                if (preselectId) {
                    const found = this.supplierOrders.find(o => String(o.id) === String(preselectId));
                    if (found) {
                        this.purchaseOrderId = found.id;
                        this.selectedOrderLabel = found.po_no;
                    }
                } else if (this.purchaseOrderId) {
                    const found = this.supplierOrders.find(o => String(o.id) === String(this.purchaseOrderId));
                    if (!found) {
                        this.purchaseOrderId = '';
                        this.selectedOrderLabel = '';
                    }
                }
            } catch (e) {
                console.error('Failed to load supplier purchase orders:', e);
                this.supplierOrders = [];
            } finally {
                this.poLoading = false;
            }
        },

        get filteredSupplierOrders() {
            if (!this.poSearchQuery || !this.poSearchQuery.trim()) {
                return this.supplierOrders || [];
            }
            const q = this.poSearchQuery.toLowerCase().trim();
            return (this.supplierOrders || []).filter(po => {
                const poNo = (po.po_no || '').toLowerCase();
                const poDate = (po.po_date || '').toLowerCase();
                const status = (po.status || '').toLowerCase();
                const supName = (po.supplier_name || '').toLowerCase();
                const label = (po.label || '').toLowerCase();
                const productsSummary = (po.products_summary || '').toLowerCase();
                const productsMatch = Array.isArray(po.products) && po.products.some(p => p && p.toLowerCase().includes(q));
                return poNo.includes(q) || poDate.includes(q) || status.includes(q) || supName.includes(q) || label.includes(q) || productsSummary.includes(q) || productsMatch;
            });
        },

        get selectedOrderDisplay() {
            if (!this.purchaseOrderId) return '';
            const found = (this.supplierOrders || []).find(o => String(o.id) === String(this.purchaseOrderId));
            if (found) {
                const dateStr = found.po_date ? ` (${found.po_date})` : '';
                const amtStr = found.grand_total !== undefined ? ` - ₹${Number(found.grand_total || 0).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}` : '';
                return `${found.po_no}${dateStr}${amtStr}`;
            }
            return this.selectedOrderLabel ? this.selectedOrderLabel : `Selected PO #${this.purchaseOrderId}`;
        },

        togglePoDropdown() {
            if (!this.supplierId || this.poLoading) return;
            this.poDropdownOpen = !this.poDropdownOpen;
            if (this.poDropdownOpen) {
                this.poSearchQuery = '';
                this.highlightedPoIndex = -1;
                this.$nextTick(() => {
                    if (this.$refs.poSearchInput) {
                        this.$refs.poSearchInput.focus();
                    }
                });
            }
        },

        closePoDropdown() {
            this.poDropdownOpen = false;
            this.highlightedPoIndex = -1;
            this.poSearchQuery = '';
        },

        async selectPurchaseOrder(poId) {
            if (String(this.purchaseOrderId) === String(poId)) {
                this.closePoDropdown();
                return;
            }
            this.purchaseOrderId = poId ? String(poId) : '';
            const found = (this.supplierOrders || []).find(o => String(o.id) === String(poId));
            this.selectedOrderLabel = found ? found.po_no : '';
            this.closePoDropdown();
            await this.onPurchaseOrderChange();
        },

        async clearPurchaseOrder() {
            this.purchaseOrderId = '';
            this.selectedOrderLabel = '';
            this.closePoDropdown();
            await this.onPurchaseOrderChange();
        },

        navigatePoOptions(step) {
            const list = this.filteredSupplierOrders;
            if (!list || list.length === 0) return;
            let next = this.highlightedPoIndex + step;
            if (next < 0) next = list.length - 1;
            if (next >= list.length) next = 0;
            this.highlightedPoIndex = next;
        },

        selectHighlightedPo() {
            const list = this.filteredSupplierOrders;
            if (!list || list.length === 0) return;
            if (this.highlightedPoIndex >= 0 && this.highlightedPoIndex < list.length) {
                this.selectPurchaseOrder(list[this.highlightedPoIndex].id);
            } else if (list.length > 0) {
                this.selectPurchaseOrder(list[0].id);
            }
        },

        async onPurchaseOrderChange() {
            if (!this.purchaseOrderId) {
                if (this.linkedPoId) {
                    this.items = [];
                    this.addItem();
                    this.clearFreight();
                    this.linkedPoId = null;
                    this.selectedOrderLabel = '';
                    this.recalc();
                }
                return;
            }

            this.poLoading = true;
            try {
                const res = await fetch(`{{ url('/purchasing/invoices/purchase-order') }}/${this.purchaseOrderId}/data`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.order) {
                    const ord = data.order;
                    this.linkedPoId = ord.id;
                    this.selectedOrderLabel = ord.po_no || ord.order_number;

                    if (ord.warehouse_id) {
                        this.warehouseId = ord.warehouse_id;
                    }
                    if (ord.credit_days !== null && ord.credit_days !== undefined) {
                        this.setCreditDays(ord.credit_days);
                    }
                    if (ord.receive_date && !this.purchaseReceiveDate) {
                        this.purchaseReceiveDate = ord.receive_date;
                    }
                    this.applyOrderFreight(ord);
                    if (ord.po_no && !this.orderNos) {
                        this.orderNos = ord.po_no;
                    }

                    if (ord.billing_address_id && this.partyAddresses.some(a => String(a.id) === String(ord.billing_address_id))) {
                        this.selectedBillingId = ord.billing_address_id;
                        this.onBillingAddressChange();
                    }
                    if (ord.shipping_address_id && this.partyAddresses.some(a => String(a.id) === String(ord.shipping_address_id))) {
                        this.selectedShippingId = ord.shipping_address_id;
                        this.onShippingAddressChange();
                    }

                    if (ord.items && ord.items.length) {
                        this.items = ord.items.map(it => {
                            const p = this.products.find(prod => prod.id === it.product_id);
                            const unitCost = Number(it.unit_cost) || 0;
                            const cgst = Number(it.cgst_percent) || 0;
                            const sgst = Number(it.sgst_percent) || 0;
                            const tax = cgst + sgst;
                            const mrp = Number(it.batch_mrp) || (p ? (Number(p.mrp) || 0) : 0);
                            const inclusive = Number(it.purchase_price_inclusive) || Number((unitCost * (1 + tax / 100)).toFixed(2));
                            const isTracked = (it.is_serial_tracked !== undefined && it.is_serial_tracked !== null)
                                ? Boolean(it.is_serial_tracked)
                                : (p ? Boolean(p.is_serial_tracked || (p.tracking_type && String(p.tracking_type).toLowerCase().includes('serial'))) : false);
                            return {
                                product_id: it.product_id,
                                unit: p ? (p.unit || 'PCS') : 'PCS',
                                tracking_type: p ? (p.tracking_type || 'none') : (it.tracking_type || 'none'),
                                is_serial_tracked: isTracked,
                                quantity: Number(it.quantity) || 1,
                                unit_cost: unitCost,
                                purchase_price_inclusive: inclusive,
                                purchase_discount_percent: Number(it.purchase_discount_percent) || (mrp > 0 ? Number((((mrp - inclusive) / mrp) * 100).toFixed(2)) : 0),
                                cgst_percent: cgst,
                                sgst_percent: sgst,
                                batch_number: it.batch_number || '',
                                batch_mrp: mrp,
                                serial_numbers: isTracked ? [''] : [],
                                line_total: Number(it.line_total) || 0
                            };
                        });
                    }
                    this.items.forEach((_, idx) => this.syncSerials(idx));
                    this.recalc();
                }
            } catch (e) {
                console.error('Failed to load purchase order details:', e);
            } finally {
                this.poLoading = false;
            }
        },

        async loadSupplierAddresses(supId, forceDefault = false) {
            this.addressesLoading = true;
            try {
                const res = await fetch(`{{ url('/masters/customers') }}/${supId}/addresses`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.partyAddresses = data.addresses || [];
                    const billingValid = this.partyAddresses.some(a => String(a.id) === String(this.selectedBillingId));
                    const shippingValid = this.partyAddresses.some(a => String(a.id) === String(this.selectedShippingId));

                    if (forceDefault || !this.selectedBillingId || !billingValid) {
                        this.selectedBillingId = data.default_billing_id || (this.partyAddresses[0]?.id ?? '');
                    }
                    if (forceDefault || !this.selectedShippingId || !shippingValid) {
                        this.selectedShippingId = data.default_delivery_id || this.selectedBillingId;
                    }

                    this.onBillingAddressChange();
                    if (!this.sameAsBilling) {
                        this.onShippingAddressChange();
                    } else {
                        this.selectedShippingId = this.selectedBillingId;
                        this.shippingSnapshot = this.billingSnapshot;
                    }
                }
            } catch (e) {
                console.error('Failed to load supplier addresses:', e);
            } finally {
                this.addressesLoading = false;
            }
        },

        onBillingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
            this.billingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
            }
        },

        onShippingAddressChange() {
            const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId));
            this.shippingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
        },

        onSameAsBillingToggle() {
            if (this.sameAsBilling) {
                this.selectedShippingId = this.selectedBillingId;
                this.shippingSnapshot = this.billingSnapshot;
            } else {
                this.onShippingAddressChange();
            }
        },

        setCreditDays(days) {
            this.creditDays = parseInt(days, 10) || 0;
            this.recalcDueDate();
        },

        addDaysToYmd(baseYmd, days) {
            if (!baseYmd) return '';
            const parts = String(baseYmd).split('-');
            if (parts.length !== 3) return '';
            const d = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
            d.setDate(d.getDate() + (parseInt(days, 10) || 0));
            const yyyy = d.getFullYear();
            const mm = String(d.getMonth() + 1).padStart(2, '0');
            const dd = String(d.getDate()).padStart(2, '0');
            return `${yyyy}-${mm}-${dd}`;
        },

        setExpectedDeliveryDays(days) {
            const n = parseInt(days, 10) || 0;
            this.expectedDeliveryQuickDays = n;
            const base = this.invoiceDate || new Date().toISOString().slice(0, 10);
            this.expectedDeliveryDate = this.addDaysToYmd(base, n);
        },

        recalcDueDate() {
            if (!this.invoiceDate) {
                this.dueDatePreview = '—';
                this.dueDateFormattedYmd = '';
                return;
            }
            const days = parseInt(this.creditDays, 10);
            const validDays = (!isNaN(days) && days >= 0) ? days : 0;

            const parts = String(this.invoiceDate).split('-');
            if (parts.length !== 3) {
                this.dueDatePreview = '—';
                this.dueDateFormattedYmd = '';
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
            this.dueDateFormattedYmd = `${yyyy}-${mm}-${dd}`;

            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            this.dueDatePreview = `${dd} ${monthNames[d.getMonth()]} ${yyyy}`;
        }
    };
}
</script>
@endpush
@endsection

