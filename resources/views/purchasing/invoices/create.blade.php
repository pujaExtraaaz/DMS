@extends('layouts.dms')
@section('title', 'New Purchase Invoice')

@section('content')
<x-ui.page-header title="New Purchase Invoice">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<form method="POST" action="{{ route('purchasing.invoices.store') }}"
      id="purchase-invoice-form"
      novalidate
      x-data="purchaseInvoiceForm(@js($products), @js($order ?? null), @js($initialOrders ?? []), @js($odAccounts ?? []), @js($savedDraft ?? null))"
      @submit.prevent="submitForm(submitSaveMode)"
      class="space-y-6">
    @csrf

    @if ($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-rose-800 shadow-2xs">
            <div class="flex items-start gap-3">
                <svg class="h-5 w-5 text-rose-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <div class="text-sm">
                    <h4 class="font-semibold text-rose-900">Please correct the following errors:</h4>
                    <ul class="list-disc list-inside mt-1 space-y-0.5 text-xs text-rose-700">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @endif

    <div x-show="draftRestored" x-cloak class="rounded-xl border border-indigo-200 bg-indigo-50/80 px-4 py-3 text-xs text-indigo-900 flex items-center justify-between shadow-2xs">
        <div class="flex items-center gap-2">
            <svg class="h-4 w-4 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>Your previously entered invoice details and items have been restored.</span>
        </div>
        <button type="button" @click="discardDraft()" class="font-semibold text-rose-600 hover:text-rose-800 underline">
            Discard Draft
        </button>
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Supplier *</label>
                    <a href="{{ route('masters.customers.create', [
                            'party_type' => \App\Domains\Master\Models\Customer::PARTY_TYPE_SUNDRY_CREDITORS,
                            'return_url' => route('purchasing.invoices.create'),
                        ]) }}"
                       class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        + Add Supplier
                    </a>
                </div>
                <select name="supplier_id" id="supplier_id" x-model="supplierId" @change="onSupplierChange(); syncSupplierLabel(); delete formErrors.supplier_id" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}"
                                data-credit-days="{{ $s->credit_days ?? 0 }}"
                                @selected(old('supplier_id', request('supplier_id', $order?->supplier_id))==$s->id)>
                            {{ $s->name }}
                        </option>
                    @endforeach
                </select>
                <p x-show="formErrors.supplier_id" x-text="formErrors.supplier_id" class="mt-1 text-xs text-rose-600" x-cloak></p>
                @error('supplier_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
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
                              x-text="!supplierId ? 'Select a supplier first' : (poLoading ? 'Loading purchase orders...' : (selectedOrderDisplay || 'Select Purchase Order'))">
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
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Receipt Note (Optional)</label>
                    <span x-show="rnLoading" class="text-[11px] text-indigo-600 animate-pulse font-medium inline-flex items-center gap-1" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </div>
                <select name="receipt_note_id"
                        x-model="receiptNoteId"
                        @change="onReceiptNoteChange()"
                        :disabled="!supplierId || rnLoading"
                        data-no-search
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                    <option value="">Select Receipt Note (if goods already received)</option>
                    <template x-for="rn in supplierReceiptNotes" :key="rn.id">
                        <option :value="rn.id" x-text="rn.label" :selected="String(rn.id) === String(receiptNoteId)"></option>
                    </template>
                </select>
                <p x-show="supplierId && !rnLoading && supplierReceiptNotes.length === 0" class="mt-1 text-[11px] text-slate-400" x-cloak>
                    No pending receipt notes for this supplier
                </p>
                <p x-show="selectedRnLabel && receiptNoteId" class="mt-1 text-[11px] text-emerald-600 font-medium" x-cloak>
                    Material linked to <span x-text="selectedRnLabel"></span> (Inventory already in warehouse)
                </p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Warehouse *</label>
                <select name="warehouse_id" id="warehouse_id" x-model="warehouseId" @change="delete formErrors.warehouse_id" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(old('warehouse_id', $order?->warehouse_id)==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
                <p x-show="formErrors.warehouse_id" x-text="formErrors.warehouse_id" class="mt-1 text-xs text-rose-600" x-cloak></p>
                @error('warehouse_id')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" x-model="invoiceDate" @change="recalcDueDate(); delete formErrors.invoice_date" @input="recalcDueDate(); delete formErrors.invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p x-show="formErrors.invoice_date" x-text="formErrors.invoice_date" class="mt-1 text-xs text-rose-600" x-cloak></p>
                @error('invoice_date')
                    <p class="mt-1 text-xs text-rose-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Supplier Inv No.</label>
                <input type="text" name="supplier_invoice_number" x-model="supplierInvoiceNumber" value="{{ old('supplier_invoice_number') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="E.G. Inv-10023">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Material Dispatch Date</label>
                <input type="date" name="material_dispatch_date" x-model="materialDispatchDate" value="{{ old('material_dispatch_date') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                <p class="mt-1 text-[10px] text-slate-400">Optional. Material starts as In Transit — stock is not added yet.</p>
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
                       value="{{ old('credit_days', $order?->credit_days ?? 0) }}"
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

    @include('purchasing.invoices.partials.dispatch-and-freight', [
        'receiptDocNo' => old('receipt_doc_no'),
        'dispatchedThrough' => old('dispatched_through'),
        'vehicleNumber' => old('vehicle_number'),
        'freightNotes' => old('freight_notes', $order?->freight_notes ?? ''),
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

    <x-ui.card padding="false" id="line-items-section">
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

        <div x-show="itemsError" x-cloak class="mx-4 mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg flex items-center justify-between text-xs text-rose-700 shadow-2xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span class="font-medium" x-text="itemsError"></span>
            </div>
            <button type="button" @click="itemsError = ''" class="text-rose-400 hover:text-rose-600">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        @if($errors->has('items') || $errors->has('items.*'))
            <div class="mx-4 mt-3 p-3 bg-rose-50 border border-rose-200 rounded-lg text-xs text-rose-700 font-medium flex items-center gap-2 shadow-2xs">
                <svg class="w-4 h-4 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span>{{ $errors->first('items') ?: $errors->first('items.*') }}</span>
            </div>
        @endif

        <div class="overflow-x-auto border-t border-slate-200 mt-2">
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
                                <div class="flex items-center justify-between gap-2 mb-1">
                                    <span class="text-[11px] font-semibold text-slate-500">Product</span>
                                    <a :href="createProductUrl(i)"
                                       @click.prevent="saveDraftAndRedirectToProduct(i)"
                                       class="text-[11px] font-semibold text-indigo-600 hover:text-indigo-800 whitespace-nowrap">
                                        + Create product
                                    </a>
                                </div>
                                <select :name="`items[${i}][product_id]`"
                                        x-model="row.product_id"
                                        @change="onProductChange(i); itemsError = ''"
                                        required
                                        class="block w-full rounded-md border-gray-300 text-xs sm:text-sm font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 min-h-[38px] px-3 py-2 bg-white">
                                    <option value="">Select product...</option>
                                    <template x-for="p in products" :key="p.id">
                                        <option :value="p.id"
                                                :data-code="p.sku || ''"
                                                :data-subtext="p.sku ? ('Part No: ' + p.sku) : ''"
                                                x-text="p.sku ? (p.name + ' (' + p.sku + ')') : p.name"></option>
                                    </template>
                                </select>
                                <template x-if="getSelectedProductSku(row.product_id)">
                                    <div class="mt-1 text-[11px] text-slate-500 flex items-center gap-1.5 font-mono">
                                        <span class="text-slate-400">Part No / SKU:</span>
                                        <span class="font-semibold text-slate-700 bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200" x-text="getSelectedProductSku(row.product_id)"></span>
                                    </div>
                                </template>
                                <input type="hidden" :name="`items[${i}][receipt_note_item_id]`" :value="row.receipt_note_item_id || ''">

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

                            <td class="p-3 align-top min-w-[85px] w-24 text-center">
                                <template x-if="!row.allow_unit_change">
                                    <div class="relative">
                                        <input type="hidden" :name="`items[${i}][uom_id]`" :value="row.uom_id">
                                        <span class="inline-flex items-center justify-center w-full h-[38px] px-2 text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 rounded-md cursor-not-allowed select-none"
                                              x-text="row.unit || '—'"
                                              :title="row.product_id ? ('Unit locked to ' + (row.unit || 'configured unit')) : 'Select product'"></span>
                                    </div>
                                </template>
                                <template x-if="row.allow_unit_change">
                                    <div>
                                        <select :name="`items[${i}][uom_id]`"
                                                x-model="row.uom_id"
                                                @change="onUomChange(i)"
                                                class="block w-full rounded-md border-gray-300 text-xs font-semibold text-slate-700 h-[38px] px-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                            <template x-for="u in (row.allowed_uoms || [])" :key="u.id">
                                                <option :value="String(u.id)" x-text="u.code || u.name" :selected="String(u.id) === String(row.uom_id)"></option>
                                            </template>
                                        </select>
                                    </div>
                                </template>
                            </td>

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
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-2.5 text-xs md:col-span-2 lg:max-w-md lg:justify-self-end">
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
        <button type="button"
                @click="submitForm('draft')"
                :disabled="isSubmitting"
                class="inline-flex items-center justify-center px-4 py-2 border border-slate-300 shadow-sm text-sm font-medium rounded-lg text-slate-700 bg-white hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
                title="Material already received — invoice is ready for payment; stock will be posted now">
            <svg x-show="isSubmitting && submitSaveMode === 'draft'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-slate-700" fill="none" viewBox="0 0 24 24" x-cloak>
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isSubmitting && submitSaveMode === 'draft' ? 'Saving...' : 'Save as Draft'"></span>
        </button>
        <button type="button"
                @click="submitForm('open')"
                :disabled="isSubmitting"
                class="inline-flex items-center justify-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-lg text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed transition"
                title="Material still in transit — stock posts only after you confirm receipt">
            <svg x-show="isSubmitting && submitSaveMode === 'open'" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" x-cloak>
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span x-text="isSubmitting && submitSaveMode === 'open' ? 'Saving...' : 'Save as Open'"></span>
        </button>
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')" @click="clearDraft()">Cancel</x-ui.button>
    </div>
    <p class="text-[11px] text-slate-500 -mt-2">
        <span class="font-semibold text-slate-600">Draft</span> = material received, ready to pay (stock posted).
        <span class="font-semibold text-slate-600">Open</span> = material still in transit.
    </p>
</form>

@push('scripts')
<script>
function purchaseInvoiceForm(products, initialOrder, initialOrders, odAccounts, serverDraft) {
    return {
        products: products || [],
        odAccounts: odAccounts || [],
        odAccountId: '',
        onOdAccountChange() {},
        draftRestored: false,
        supplierId: initialOrder ? initialOrder.supplier_id : '{{ old('supplier_id', request('supplier_id', '')) }}',
        supplierQuery: '',
        supplierOpen: false,
        suppliers: [],
        warehouseId: initialOrder ? initialOrder.warehouse_id : '{{ old('warehouse_id', '') }}',
        supplierOrders: initialOrders || [],
        poLoading: false,
        poDropdownOpen: false,
        poSearchQuery: '',
        highlightedPoIndex: -1,
        purchaseOrderId: '{{ old('purchase_order_id', $order?->id ?? '') }}',
        selectedOrderLabel: initialOrder ? (initialOrder.po_no || initialOrder.order_number || '') : '',
        linkedPoId: initialOrder ? initialOrder.id : ({{ old('purchase_order_id') ? (int)old('purchase_order_id') : 'null' }}),
        supplierReceiptNotes: [],
        rnLoading: false,
        receiptNoteId: '{{ old('receipt_note_id', request('receipt_note_id', '')) }}',
        selectedRnLabel: '',
        partyAddresses: [],
        addressesLoading: false,
        selectedBillingId: initialOrder ? (initialOrder.billing_address_id || '') : '',
        selectedShippingId: initialOrder ? (initialOrder.shipping_address_id || '') : '',
        billingSnapshot: initialOrder ? (initialOrder.billing_address || '') : '',
        shippingSnapshot: initialOrder ? (initialOrder.shipping_address || '') : '',
        sameAsBilling: !initialOrder || !initialOrder.shipping_address_id || (initialOrder.shipping_address_id === initialOrder.billing_address_id),
        invoiceDate: '{{ old('invoice_date', date('Y-m-d')) }}',
        supplierInvoiceNumber: @js(old('supplier_invoice_number', '')),
        materialDispatchDate: @js(old('material_dispatch_date', '')),
        purchaseReceiveDate: '{{ old('purchase_receive_date', $defaultReceiveDate ?? '') }}',
        paymentOrderDate: '{{ old('payment_order_date', $defaultPaymentOrderDate ?? '') }}',
        creditDays: {{ old('credit_days', $order?->credit_days ?? 0) }},
        expectedDeliveryDate: '{{ old('expected_delivery_date', '') }}',
        expectedDeliveryQuickDays: null,
        dueDatePreview: '',
        dueDateFormattedYmd: '',
        freightApplicable: '{{ old('is_freight_applicable', ($order?->is_freight_applicable ?? false) || (float) ($order?->freight_total ?? 0) > 0 ? '1' : '0') }}',
        freightMode: '{{ old('freight_mode', $order?->freight_mode ?: 'amount') }}',
        freightPercent: {{ (float) old('freight_percent', $order?->freight_percent ?? 0) }},
        freightAmount: {{ (float) old('freight_amount', $order?->freight_amount ?? 0) }},
        freightTaxRate: {{ (float) old('freight_tax_rate', $order?->freight_tax_rate ?? 0) }},
        freightBase: 0,
        freightTaxAmount: 0,
        freightTotal: 0,
        orderNos: @js(old('order_nos', $order?->po_no ?? '')),
        otherCharges: 0,
        priceType: '{{ old('price_type', 'exclusive') }}',
        roundOff: {{ old('round_off', 0) }},
        isManualRoundOff: {{ old('round_off') !== null ? 'true' : 'false' }},
        subtotal: 0,
        taxTotal: 0,
        grandTotal: 0,
        items: [],
        formErrors: {},
        itemsError: '',
        isSubmitting: false,
        submitSaveMode: 'draft',

        syncSupplierLabel() {
            const sel = document.querySelector('select[name="supplier_id"]');
            if (sel && sel.selectedOptions && sel.selectedOptions[0]) {
                this.supplierQuery = sel.selectedOptions[0].textContent.trim();
            }
        },

        getSelectedProductSku(id) {
            if (!id) return '';
            const p = this.products.find(item => String(item.id) === String(id));
            return p ? (p.sku || p.part_number || '') : '';
        },

        validateForm() {
            this.formErrors = {};
            this.itemsError = '';

            if (!this.supplierId) {
                this.formErrors.supplier_id = 'Please select a supplier.';
            }
            if (!this.warehouseId) {
                this.formErrors.warehouse_id = 'Please select a warehouse.';
            }
            if (!this.invoiceDate) {
                this.formErrors.invoice_date = 'Please select an invoice date.';
            }

            if (!this.items || this.items.length === 0) {
                this.itemsError = 'The items field is required. Please add at least one product.';
            } else {
                let hasValidItem = false;
                for (let i = 0; i < this.items.length; i++) {
                    const item = this.items[i];
                    if (!item.product_id) {
                        this.itemsError = `Please select a product for line #${i + 1}.`;
                        return false;
                    }
                    if (!item.quantity || Number(item.quantity) <= 0) {
                        this.itemsError = `Please enter a valid quantity greater than 0 for line #${i + 1}.`;
                        return false;
                    }
                    if (this.isSerialTracked(item)) {
                        const serials = (item.serial_numbers || []).filter(s => String(s || '').trim() !== '');
                        const needed = Math.round(Number(item.quantity) || 1);
                        if (serials.length < needed) {
                            this.itemsError = `Please enter all ${needed} required serial numbers for line #${i + 1}.`;
                            return false;
                        }
                    }
                    hasValidItem = true;
                }

                if (!hasValidItem) {
                    this.itemsError = 'The items field is required. Please select at least one product.';
                    return false;
                }
            }

            if (Object.keys(this.formErrors).length > 0 || this.itemsError) {
                return false;
            }
            return true;
        },

        submitForm(mode) {
            if (this.isSubmitting) return;

            this.submitSaveMode = mode || 'draft';
            if (!this.validateForm()) {
                if (this.itemsError) {
                    const el = document.getElementById('line-items-section');
                    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (this.formErrors.supplier_id) {
                    const supEl = document.getElementById('supplier_id_searchable_trigger') || document.getElementById('supplier_id');
                    if (supEl) supEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                } else if (this.formErrors.warehouse_id) {
                    const whEl = document.getElementById('warehouse_id_searchable_trigger') || document.getElementById('warehouse_id');
                    if (whEl) whEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
                return;
            }

            this.isSubmitting = true;
            this.clearDraft();

            const form = document.getElementById('purchase-invoice-form');
            if (form) {
                let saveModeInput = form.querySelector('input[name="save_mode"]');
                if (!saveModeInput) {
                    saveModeInput = document.createElement('input');
                    saveModeInput.type = 'hidden';
                    saveModeInput.name = 'save_mode';
                    form.appendChild(saveModeInput);
                }
                saveModeInput.value = this.submitSaveMode;
                form.submit();
            }
        },

        async init() {
            this.recalcDueDate();
            if (initialOrder) {
                this.purchaseOrderId = initialOrder.id;
                this.selectedOrderLabel = initialOrder.po_no || initialOrder.order_number || '';
                this.linkedPoId = initialOrder.id;
                if (initialOrder.receive_date && !this.purchaseReceiveDate) {
                    this.purchaseReceiveDate = initialOrder.receive_date;
                }
            }
            const newProductId = @json(request('new_product_id'));
            const returnedFrom = @json(request('returned_from'));
            const draft = this.restoreCreateDraft(serverDraft);
            const targetRow = draft?.targetRow != null
                ? parseInt(draft.targetRow, 10) || 0
                : (parseInt(@json(request('target_row', '0')), 10) || 0);

            if (this.supplierId) {
                const forceAddressDefault = !(initialOrder && initialOrder.billing_address_id) && !draft?.selectedBillingId;
                await this.loadSupplierAddresses(this.supplierId, forceAddressDefault);
                if (!this.supplierOrders.length) {
                    await this.loadSupplierOrders(this.supplierId, this.purchaseOrderId);
                }
                await this.loadSupplierReceiptNotes(this.supplierId, this.receiptNoteId);
                if (this.receiptNoteId && !draft && (!initialOrder || !initialOrder.items || !initialOrder.items.length)) {
                    await this.onReceiptNoteChange();
                }
            }

            if (!draft && initialOrder && initialOrder.items && initialOrder.items.length) {
                this.items = initialOrder.items.map(it => {
                    const p = this.products.find(prod => prod.id === it.product_id);
                    const unitCost = Number(it.unit_cost) || 0;
                    const cgst = Number(it.cgst_percent) || 0;
                    const sgst = Number(it.sgst_percent) || 0;
                    const tax = cgst + sgst;
                    const mrp = Number(it.batch_mrp) || Number(it.mrp) || (p ? (Number(p.mrp) || 0) : 0);
                    const inclusive = Number(it.purchase_price_inclusive) || Number((unitCost * (1 + tax / 100)).toFixed(2));
                    const isTracked = p ? (p.is_serial_tracked !== undefined ? Boolean(p.is_serial_tracked) : String(p.tracking_type || '').includes('serial')) : false;
                    const uomInfo = this.resolveLineUom(it.product_id, it.uom_id, it.unit);
                    return {
                        product_id: it.product_id,
                        uom_id: uomInfo.uom_id,
                        unit: uomInfo.unit,
                        allowed_uoms: uomInfo.allowed_uoms,
                        allow_unit_change: uomInfo.allow_unit_change,
                        tracking_type: p ? (p.tracking_type || 'none') : 'none',
                        is_serial_tracked: isTracked,
                        quantity: Number(it.quantity) || 1,
                        unit_cost: unitCost,
                        purchase_price_inclusive: inclusive,
                        purchase_discount_percent: Number(it.purchase_discount_percent) || (mrp > 0 ? Number((((mrp - inclusive) / mrp) * 100).toFixed(2)) : 0),
                        cgst_percent: cgst,
                        sgst_percent: sgst,
                        batch_number: it.batch_number || it.batch_no || '',
                        batch_mrp: mrp,
                        serial_numbers: isTracked && Array.isArray(it.serial_numbers) && it.serial_numbers.length ? it.serial_numbers : (isTracked ? [''] : []),
                        line_total: 0
                    };
                });
                if (this.priceType === 'inclusive') {
                    this.items.forEach(row => {
                        const gross = Number(row.purchase_price_inclusive) || this.grossFromBase(row.unit_cost, this.rowTaxPercent(row));
                        row.unit_cost = gross;
                        row.purchase_price_inclusive = gross;
                    });
                }
            } else if (!this.items.length) {
                this.addItem();
            }

            if (newProductId) {
                const productObj = this.products.find(p => String(p.id) === String(newProductId));
                if (productObj && productObj.is_active !== false) {
                    const alreadyAdded = this.items.some(it => String(it.product_id) === String(newProductId));
                    if (!alreadyAdded) {
                        let rowToUse = -1;
                        if (targetRow >= 0 && targetRow < this.items.length && !this.items[targetRow].product_id) {
                            rowToUse = targetRow;
                        } else {
                            this.addItem();
                            rowToUse = this.items.length - 1;
                        }

                        this.items[rowToUse].product_id = String(newProductId);
                        this.onProductChange(rowToUse);
                    }
                }
            }

            if ((newProductId || returnedFrom) && window.history && window.history.replaceState) {
                const cleanUrl = window.location.pathname;
                window.history.replaceState({}, document.title, cleanUrl);
            }

            this.syncSupplierLabel();
            this.recalcDueDate();
            this.items.forEach((_, idx) => this.syncSerials(idx));
            this.recalc();

            try {
                sessionStorage.setItem('pi_create_draft', JSON.stringify(this.getDraftState(targetRow)));
            } catch (e) {}
        },

        resolveLineUom(productId, requestedUomId = null, fallbackUnit = null) {
            const p = this.products.find(prod => String(prod.id) === String(productId));
            if (!p) {
                return {
                    uom_id: requestedUomId ? String(requestedUomId) : '',
                    unit: fallbackUnit || '—',
                    allowed_uoms: [],
                    allow_unit_change: false,
                };
            }

            const allowedUoms = (p.allowed_uoms && p.allowed_uoms.length)
                ? p.allowed_uoms
                : [{
                    id: String(p.base_uom_id || p.base_uom?.id || ''),
                    code: p.unit || p.base_uom?.code || p.base_uom?.name || 'PCS',
                    name: p.unit || p.base_uom?.name || p.base_uom?.code || 'PCS',
                    is_base: true
                }];

            const allowUnitChange = Boolean(p.allow_unit_change ?? (allowedUoms.length > 1));
            let matched = requestedUomId ? allowedUoms.find(u => String(u.id) === String(requestedUomId)) : null;
            if (!matched) {
                matched = allowedUoms.find(u => u.is_base) || allowedUoms[0];
            }

            return {
                uom_id: matched ? String(matched.id) : (p.base_uom_id ? String(p.base_uom_id) : ''),
                unit: matched ? (matched.code || matched.name) : (p.unit || p.base_uom?.name || p.base_uom?.code || 'PCS'),
                allowed_uoms: allowedUoms,
                allow_unit_change: allowUnitChange,
            };
        },

        onUomChange(index) {
            const row = this.items[index];
            if (!row) return;
            const selected = (row.allowed_uoms || []).find(u => String(u.id) === String(row.uom_id));
            if (selected) {
                row.unit = selected.code || selected.name;
            }
            this.recalc();
        },

        addItem() {
            this.items.push({
                product_id: '',
                uom_id: '',
                unit: '—',
                allowed_uoms: [],
                allow_unit_change: false,
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
            this.itemsError = '';
            this.recalc();
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

        removeItem(index) {
            if (this.items.length > 1) {
                this.items.splice(index, 1);
                this.itemsError = '';
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

        async onProductChange(index) {
            const row = this.items[index];
            if (!row) return;

            const prodId = row.product_id;
            if (!prodId) {
                row.uom_id = '';
                row.unit = '—';
                row.allowed_uoms = [];
                row.allow_unit_change = false;
                row.tracking_type = 'none';
                row.is_serial_tracked = false;
                row.serial_numbers = [];
                this.syncSerials(index);
                this.recalc();
                return;
            }

            const p = this.products.find(item => String(item.id) === String(prodId));
            if (p) {
                const uomInfo = this.resolveLineUom(p.id, null, p.unit);
                row.uom_id = uomInfo.uom_id;
                row.unit = uomInfo.unit;
                row.allowed_uoms = uomInfo.allowed_uoms;
                row.allow_unit_change = uomInfo.allow_unit_change;
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
                            if (dbProd.allowed_uoms && dbProd.allowed_uoms.length) {
                                row.allowed_uoms = dbProd.allowed_uoms;
                                row.allow_unit_change = Boolean(dbProd.allow_unit_change);
                                if (!row.uom_id || !row.allowed_uoms.some(u => String(u.id) === String(row.uom_id))) {
                                    const defU = row.allowed_uoms.find(u => u.is_base) || row.allowed_uoms[0];
                                    row.uom_id = defU ? String(defU.id) : '';
                                    row.unit = defU ? (defU.code || defU.name) : (dbProd.unit || 'PCS');
                                }
                            }
                            const cached = this.products.find(item => String(item.id) === String(dbProd.id));
                            if (cached) {
                                cached.tracking_type = dbProd.tracking_type;
                                cached.is_serial_tracked = dbProd.is_serial_tracked;
                                cached.track_serial = dbProd.track_serial;
                                if (dbProd.allowed_uoms) cached.allowed_uoms = dbProd.allowed_uoms;
                                if (dbProd.allow_unit_change !== undefined) cached.allow_unit_change = dbProd.allow_unit_change;
                                if (dbProd.base_uom_id) cached.base_uom_id = dbProd.base_uom_id;
                                if (dbProd.unit) cached.unit = dbProd.unit;
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

        getDraftState(targetRow = 0) {
            const getVal = (name) => {
                const el = document.querySelector(`[name="${name}"]`);
                return el ? el.value : '';
            };

            return {
                supplierId: this.supplierId,
                supplierQuery: this.supplierQuery,
                warehouseId: this.warehouseId,
                purchaseOrderId: this.purchaseOrderId,
                selectedOrderLabel: this.selectedOrderLabel,
                linkedPoId: this.linkedPoId,
                invoiceDate: this.invoiceDate,
                supplierInvoiceNumber: this.supplierInvoiceNumber || getVal('supplier_invoice_number'),
                materialDispatchDate: this.materialDispatchDate || getVal('material_dispatch_date'),
                expectedDeliveryDate: this.expectedDeliveryDate,
                expectedDeliveryQuickDays: this.expectedDeliveryQuickDays,
                purchaseReceiveDate: this.purchaseReceiveDate,
                paymentOrderDate: this.paymentOrderDate,
                creditDays: this.creditDays,
                dueDateFormattedYmd: this.dueDateFormattedYmd,
                freightApplicable: this.freightApplicable,
                freightMode: this.freightMode,
                freightPercent: this.freightPercent,
                freightAmount: this.freightAmount,
                freightTaxRate: this.freightTaxRate,
                orderNos: this.orderNos,
                otherCharges: this.otherCharges,
                priceType: this.priceType,
                roundOff: this.roundOff,
                isManualRoundOff: this.isManualRoundOff,
                selectedBillingId: this.selectedBillingId,
                selectedShippingId: this.selectedShippingId,
                billingSnapshot: this.billingSnapshot,
                shippingSnapshot: this.shippingSnapshot,
                sameAsBilling: this.sameAsBilling,
                receiptDocNo: getVal('receipt_doc_no'),
                dispatchedThrough: getVal('dispatched_through'),
                vehicleNumber: getVal('vehicle_number'),
                freightNotes: getVal('freight_notes'),
                items: this.items.map(it => ({ ...it })),
                targetRow: targetRow ?? 0,
            };
        },

        createProductUrl(rowIndex) {
            const params = new URLSearchParams({
                return_to: 'purchasing.invoices.create',
                target_row: String(rowIndex ?? 0),
            });
            return @json(route('masters.products.create')) + '?' + params.toString();
        },

        saveDraftAndRedirectToProduct(rowIndex) {
            const draft = this.getDraftState(rowIndex);
            try {
                sessionStorage.setItem('pi_create_draft', JSON.stringify(draft));
            } catch (e) {}

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;
                fetch(@json(route('purchasing.invoices.draft.save')), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    body: JSON.stringify({ draft }),
                    keepalive: true,
                }).catch(() => {});
            } catch (e) {}

            window.location.href = this.createProductUrl(rowIndex);
        },

        restoreCreateDraft(serverDraft = null) {
            let draft = null;
            try {
                const raw = sessionStorage.getItem('pi_create_draft');
                if (raw) {
                    draft = JSON.parse(raw);
                }
            } catch (e) {
                draft = null;
            }
            if (!draft && serverDraft && typeof serverDraft === 'object') {
                draft = serverDraft;
            }
            if (!draft) return null;

            const setField = (name, val) => {
                if (val !== undefined && val !== null && val !== '') {
                    const el = document.querySelector(`[name="${name}"]`);
                    if (el) el.value = val;
                }
            };

            this.supplierId = draft.supplierId || this.supplierId;
            this.supplierQuery = draft.supplierQuery || this.supplierQuery;
            this.warehouseId = draft.warehouseId || this.warehouseId;
            this.purchaseOrderId = draft.purchaseOrderId || this.purchaseOrderId;
            this.selectedOrderLabel = draft.selectedOrderLabel || this.selectedOrderLabel;
            this.linkedPoId = draft.linkedPoId || this.linkedPoId;
            this.invoiceDate = draft.invoiceDate || this.invoiceDate;
            if (draft.supplierInvoiceNumber !== undefined) {
                this.supplierInvoiceNumber = draft.supplierInvoiceNumber;
                setField('supplier_invoice_number', draft.supplierInvoiceNumber);
            }
            if (draft.materialDispatchDate !== undefined) {
                this.materialDispatchDate = draft.materialDispatchDate;
                setField('material_dispatch_date', draft.materialDispatchDate);
            }
            this.expectedDeliveryDate = draft.expectedDeliveryDate || this.expectedDeliveryDate;
            this.expectedDeliveryQuickDays = draft.expectedDeliveryQuickDays ?? this.expectedDeliveryQuickDays;
            this.purchaseReceiveDate = draft.purchaseReceiveDate || this.purchaseReceiveDate;
            this.paymentOrderDate = draft.paymentOrderDate || this.paymentOrderDate;
            this.creditDays = draft.creditDays ?? this.creditDays;
            this.freightApplicable = draft.freightApplicable ?? this.freightApplicable;
            this.freightMode = draft.freightMode || this.freightMode;
            this.freightPercent = draft.freightPercent ?? this.freightPercent;
            this.freightAmount = draft.freightAmount ?? this.freightAmount;
            this.freightTaxRate = draft.freightTaxRate ?? this.freightTaxRate;
            this.orderNos = draft.orderNos ?? this.orderNos;
            this.otherCharges = draft.otherCharges ?? this.otherCharges;
            if (draft.priceType) this.priceType = draft.priceType;
            this.roundOff = draft.roundOff ?? this.roundOff;
            this.isManualRoundOff = draft.isManualRoundOff ?? this.isManualRoundOff;
            this.selectedBillingId = draft.selectedBillingId || '';
            this.selectedShippingId = draft.selectedShippingId || '';
            this.billingSnapshot = draft.billingSnapshot || '';
            this.shippingSnapshot = draft.shippingSnapshot || '';
            this.sameAsBilling = draft.sameAsBilling !== false;

            setField('receipt_doc_no', draft.receiptDocNo || draft.courierDocketNo);
            setField('dispatched_through', draft.dispatchedThrough || draft.courierName);
            setField('vehicle_number', draft.vehicleNumber);
            setField('freight_notes', draft.freightNotes);

            if (Array.isArray(draft.items) && draft.items.length) {
                this.items = draft.items.map(it => ({ ...it }));
            }
            this.draftRestored = true;
            return draft;
        },

        clearDraft() {
            try {
                sessionStorage.removeItem('pi_create_draft');
            } catch (e) {}
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || document.querySelector('input[name="_token"]')?.value;
                fetch(@json(route('purchasing.invoices.draft.clear')), {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken || '',
                    },
                    keepalive: true,
                }).catch(() => {});
            } catch (e) {}
            this.draftRestored = false;
        },

        discardDraft() {
            this.clearDraft();
            window.location.href = @json(route('purchasing.invoices.create'));
        },

        clearDraftOnSubmit() {
            this.clearDraft();
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
                this.loadSupplierOrders(this.supplierId),
                this.loadSupplierReceiptNotes(this.supplierId)
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
                    if (ord.po_no) {
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
                            const uomInfo = this.resolveLineUom(it.product_id, it.uom_id, it.unit);
                            return {
                                product_id: it.product_id,
                                uom_id: uomInfo.uom_id,
                                unit: uomInfo.unit,
                                allowed_uoms: uomInfo.allowed_uoms,
                                allow_unit_change: uomInfo.allow_unit_change,
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
                        if (this.priceType === 'inclusive') {
                            this.items.forEach(row => {
                                const gross = Number(row.purchase_price_inclusive) || this.grossFromBase(row.unit_cost, this.rowTaxPercent(row));
                                row.unit_cost = gross;
                                row.purchase_price_inclusive = gross;
                            });
                        }
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

        async loadSupplierReceiptNotes(supId, preselectId = null) {
            if (!supId) {
                this.supplierReceiptNotes = [];
                this.receiptNoteId = '';
                this.selectedRnLabel = '';
                return;
            }

            this.rnLoading = true;
            try {
                const res = await fetch(`{{ url('/purchasing/invoices/suppliers') }}/${supId}/receipt-notes`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                this.supplierReceiptNotes = data.receipt_notes || [];

                if (preselectId) {
                    const found = this.supplierReceiptNotes.find(rn => String(rn.id) === String(preselectId));
                    if (found) {
                        this.receiptNoteId = found.id;
                        this.selectedRnLabel = found.receipt_note_no;
                    }
                }
            } catch (e) {
                console.error('Failed to load supplier receipt notes:', e);
                this.supplierReceiptNotes = [];
            } finally {
                this.rnLoading = false;
            }
        },

        async onReceiptNoteChange() {
            if (!this.receiptNoteId) {
                this.selectedRnLabel = '';
                return;
            }

            this.rnLoading = true;
            try {
                const res = await fetch(`{{ url('/purchasing/invoices/receipt-note') }}/${this.receiptNoteId}/data`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.receipt_note) {
                    const rn = data.receipt_note;
                    this.selectedRnLabel = rn.receipt_note_no;

                    if (rn.warehouse_id) {
                        this.warehouseId = rn.warehouse_id;
                    }

                    if (rn.items && rn.items.length) {
                        this.items = rn.items.map(it => {
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

                            const serialsList = Array.isArray(it.serials) && it.serials.length
                                ? it.serials
                                : (isTracked ? [''] : []);

                            const uomInfo = this.resolveLineUom(it.product_id, it.uom_id, it.unit);
                            return {
                                receipt_note_item_id: it.receipt_note_item_id,
                                product_id: it.product_id,
                                uom_id: uomInfo.uom_id,
                                unit: uomInfo.unit,
                                allowed_uoms: uomInfo.allowed_uoms,
                                allow_unit_change: uomInfo.allow_unit_change,
                                tracking_type: p ? (p.tracking_type || 'none') : (it.tracking_type || 'none'),
                                is_serial_tracked: isTracked,
                                quantity: Number(it.quantity) || 1,
                                unit_cost: unitCost,
                                price_inclusive_tax: inclusive,
                                purchase_price_inclusive: inclusive,
                                batch_mrp: mrp,
                                discount_percent: Number(it.purchase_discount_percent) || 0,
                                purchase_discount_percent: Number(it.purchase_discount_percent) || 0,
                                cgst_percent: cgst,
                                sgst_percent: sgst,
                                batch_number: it.batch_number || '',
                                batch_mrp: mrp,
                                serial_numbers: serialsList,
                                line_total: Number(it.line_total) || 0
                            };
                        });
                        this.items.forEach((_, idx) => this.syncSerials(idx));
                        this.recalc();
                    }
                }
            } catch (e) {
                console.error('Failed to load receipt note details:', e);
            } finally {
                this.rnLoading = false;
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