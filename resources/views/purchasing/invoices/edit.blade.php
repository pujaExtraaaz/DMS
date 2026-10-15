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
      x-data="purchaseInvoiceEditForm(@js($products), @js($invoice), @js($initialOrders ?? []), @js($invoiceItems ?? []))"
      class="space-y-6">
    @csrf
    @method('PUT')

    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Supplier *</label>
                    <button type="button" @click="$dispatch('open-quick-add-supplier')"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                        + Add New
                    </button>
                </div>
                <select name="supplier_id" x-model="supplierId" @change="onSupplierChange()" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select supplier</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" data-credit-days="{{ $s->credit_days ?? 0 }}" @selected(old('supplier_id', $invoice->supplier_id)==$s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Purchase Order</label>
                    <span x-show="poLoading" class="text-[11px] text-indigo-600 animate-pulse font-medium inline-flex items-center gap-1" x-cloak>
                        <svg class="animate-spin h-3 w-3 text-indigo-600" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Loading...
                    </span>
                </div>
                <select name="purchase_order_id"
                        x-model="purchaseOrderId"
                        @change="onPurchaseOrderChange()"
                        :disabled="!supplierId || poLoading"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                    <option value="">Select Purchase Order (Optional)</option>
                    <template x-for="po in supplierOrders" :key="po.id">
                        <option :value="po.id" x-text="po.label" :selected="String(po.id) === String(purchaseOrderId)"></option>
                    </template>
                </select>
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
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Warehouse *</label>
                <select name="warehouse_id" x-model="warehouseId" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(old('warehouse_id', $invoice->warehouse_id)==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" x-model="invoiceDate" @change="recalcDueDate()" @input="recalcDueDate()"
                       value="{{ old('invoice_date', $invoice->invoice_date?->format('Y-m-d')) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Supplier Inv No.</label>
                <input type="text" name="supplier_invoice_number"
                       value="{{ old('supplier_invoice_number', $invoice->supplier_invoice_number ?? $invoice->supplier_invoice_no) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="e.g. INV-10023">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Purchase Receive Date</label>
                <input type="date" name="purchase_receive_date" x-model="purchaseReceiveDate"
                       value="{{ old('purchase_receive_date', $invoice->purchase_receive_date?->format('Y-m-d')) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Payment Order Date</label>
                <input type="date" name="payment_order_date" x-model="paymentOrderDate"
                       value="{{ old('payment_order_date', $invoice->payment_order_date?->format('Y-m-d')) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div class="sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Credit Days</label>
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
                <p class="mt-1 text-[11px] text-slate-500" x-show="dueDatePreview">
                    Due Date Preview: <strong class="text-slate-700" x-text="dueDatePreview"></strong>
                </p>
            </div>
        </div>
    </x-ui.card>

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
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Billing Address</label>
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
                          placeholder="Billing address snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>

            <!-- Delivery Address Card -->
            <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                <div class="flex items-center justify-between">
                    <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider">Delivery / Dispatch Address</label>
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
                          placeholder="Delivery address snapshot"
                          class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <x-ui.card padding="false">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="text-sm font-semibold text-slate-700">Line Items</h3>
            <button type="button" @click="addItem()"
                    class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition">
                + Add Item
            </button>
        </div>

        <div class="overflow-x-auto border-t border-slate-200">
            <table class="w-full min-w-[1360px] text-left text-xs border-collapse">
                <thead class="bg-slate-50 text-slate-600 uppercase tracking-wider border-b border-slate-200 font-semibold text-[11px]">
                    <tr>
                        <th class="p-3 min-w-[340px] lg:min-w-[380px]">Product / Description</th>
                        <th class="p-3 min-w-[70px] w-20 text-center">Unit</th>
                        <th class="p-3 min-w-[110px] w-28 text-right">Qty *</th>
                        <th class="p-3 min-w-[130px] w-36 text-right">Unit Cost (₹) *</th>
                        <th class="p-3 min-w-[95px] w-24 text-right">CGST %</th>
                        <th class="p-3 min-w-[95px] w-24 text-right">SGST %</th>
                        <th class="p-3 min-w-[100px] w-28 text-center">Tax Rate</th>
                        <th class="p-3 min-w-[130px] w-36">Batch No</th>
                        <th class="p-3 min-w-[125px] w-32 text-right">Batch MRP (₹)</th>
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
                                    <div class="mt-2.5 p-3 bg-slate-50 rounded-lg border border-slate-200/90 shadow-2xs space-y-2">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-1.5">
                                                <svg class="w-3.5 h-3.5 text-indigo-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                                                </svg>
                                                <span class="text-[11px] font-bold text-slate-700 uppercase tracking-wider">Serial Numbers</span>
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-700"
                                                      x-text="(row.serial_numbers ? row.serial_numbers.length : 0) + ' entered'"></span>
                                            </div>
                                            <span class="text-[11px] text-slate-500 font-medium"
                                                  x-text="'Target Qty: ' + (row.quantity || 1)"></span>
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2 pt-0.5">
                                            <template x-for="(sNo, sIdx) in (row.serial_numbers || [])" :key="sIdx">
                                                <div class="inline-flex items-center rounded-md border border-slate-300 bg-white shadow-2xs focus-within:ring-2 focus-within:ring-indigo-500 focus-within:border-indigo-500 overflow-hidden min-w-[130px] sm:min-w-[140px] max-w-[220px] h-9 transition">
                                                    <input type="text"
                                                           :name="`items[${i}][serial_numbers][${sIdx}]`"
                                                           x-model="row.serial_numbers[sIdx]"
                                                           :placeholder="`Serial #${sIdx + 1}`"
                                                           class="block w-full h-full px-2.5 py-1.5 text-xs font-mono font-medium text-slate-800 placeholder-slate-400 border-0 focus:ring-0 focus:outline-none bg-transparent">
                                                    <button type="button"
                                                            @click="removeSerial(i, sIdx)"
                                                            class="h-full px-2 bg-slate-50 hover:bg-rose-50 text-slate-400 hover:text-rose-600 border-l border-slate-200 transition-colors flex items-center justify-center shrink-0 cursor-pointer"
                                                            title="Remove Serial">
                                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                        </svg>
                                                    </button>
                                                </div>
                                            </template>

                                            <button type="button"
                                                    @click="addSerial(i)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold border border-indigo-200 transition shadow-2xs h-9 shrink-0 cursor-pointer"
                                                    title="Add Serial Number">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <span>+ Add Serial</span>
                                            </button>
                                        </div>
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
                                       @input="recalc()"
                                       required
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[130px] w-36">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][unit_cost]`"
                                       x-model.number="row.unit_cost"
                                       @input="recalc()"
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
                                       @input="recalc()"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            </td>

                            <td class="p-3 align-top min-w-[95px] w-24">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       max="100"
                                       :name="`items[${i}][sgst_percent]`"
                                       x-model.number="row.sgst_percent"
                                       @input="recalc()"
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

                            <td class="p-3 align-top min-w-[125px] w-32">
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       :name="`items[${i}][batch_mrp]`"
                                       x-model.number="row.batch_mrp"
                                       placeholder="MRP"
                                       class="block w-full min-h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
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
            <div>
                <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Notes / Remarks</label>
                <textarea name="notes" rows="4" placeholder="Additional delivery or invoice remarks..."
                          class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">{{ old('notes', $invoice->notes) }}</textarea>
            </div>

            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-2.5 text-xs">
                <div class="flex justify-between items-center text-slate-600">
                    <span class="font-medium">Taxable Subtotal:</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(subtotal)"></span>
                </div>
                <div class="flex justify-between items-center text-slate-600">
                    <span class="font-medium">Total Tax (CGST + SGST):</span>
                    <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(taxTotal)"></span>
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span class="font-medium">Freight / Landed Charge (₹):</span>
                    <input type="number" step="0.01" min="0" name="freight_charge" x-model.number="freightCharge" @input="recalc()"
                           class="w-32 min-h-[34px] px-2.5 py-1.5 rounded-md border-gray-300 text-xs font-mono text-right shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="flex items-center justify-between text-slate-600">
                    <span class="font-medium">Other Charges (₹):</span>
                    <input type="number" step="0.01" min="0" name="other_charges" x-model.number="otherCharges" @input="recalc()"
                           class="w-32 min-h-[34px] px-2.5 py-1.5 rounded-md border-gray-300 text-xs font-mono text-right shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                </div>
                <div class="flex justify-between items-center font-bold text-base text-slate-900 border-t border-slate-200 pt-3 mt-1">
                    <span>Grand Total:</span>
                    <span class="font-mono text-indigo-700 text-lg" x-text="formatCurrency(grandTotal)"></span>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.show', $invoice)">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Update Purchase Invoice</x-ui.button>
    </div>
</form>

<x-quick-add-supplier target-select="supplier_id" />

@push('scripts')
<script>
function purchaseInvoiceEditForm(products, invoice, initialOrders, existingItems) {
    return {
        products: products || [],
        supplierId: '{{ old('supplier_id', $invoice->supplier_id) }}',
        warehouseId: '{{ old('warehouse_id', $invoice->warehouse_id) }}',
        supplierOrders: initialOrders || [],
        poLoading: false,
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
        dueDatePreview: '',
        dueDateFormattedYmd: '{{ old('due_date', $invoice->due_date?->format('Y-m-d') ?? '') }}',
        freightCharge: {{ old('freight_charge', (float)($invoice->freight_charge ?? 0)) }},
        otherCharges: {{ old('other_charges', (float)($invoice->other_charges ?? 0)) }},
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
                    return {
                        product_id: it.product_id,
                        unit: it.unit || (p ? (p.unit || 'PCS') : 'PCS'),
                        tracking_type: p ? (p.tracking_type || 'none') : 'none',
                        quantity: Number(it.quantity) || 1,
                        unit_cost: Number(it.unit_cost) || 0,
                        cgst_percent: Number(it.cgst_percent) || 0,
                        sgst_percent: Number(it.sgst_percent) || 0,
                        batch_number: it.batch_number || it.batch_no || '',
                        batch_mrp: Number(it.batch_mrp) || (p ? (Number(p.mrp) || 0) : 0),
                        serial_numbers: it.serial_numbers || [],
                        line_total: Number(it.line_total) || 0
                    };
                });
            } else {
                this.addItem();
            }
            this.recalc();
        },

        addItem() {
            this.items.push({
                product_id: '',
                unit: '-',
                tracking_type: 'none',
                quantity: 1,
                unit_cost: 0,
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
            if (!row || !row.tracking_type) return false;
            const tt = row.tracking_type;
            if (Array.isArray(tt)) {
                return tt.includes('serial');
            }
            const str = String(tt).toLowerCase();
            return str === 'serial' || str === 'all' || str.includes('serial');
        },

        onProductChange(index) {
            const row = this.items[index];
            const p = this.products.find(item => item.id == row.product_id);
            if (p) {
                row.unit = p.unit || 'PCS';
                row.tracking_type = p.tracking_type || 'none';
                row.unit_cost = Number(p.purchase_price) || 0;
                row.cgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.sgst_percent = Number(p.tax_rate ? p.tax_rate / 2 : 0);
                row.batch_mrp = Number(p.mrp) || 0;
                if (this.isSerialTracked(row) && (!row.serial_numbers || row.serial_numbers.length === 0)) {
                    row.serial_numbers = [''];
                }
            }
            this.recalc();
        },

        addSerial(itemIndex) {
            if (!this.items[itemIndex].serial_numbers) {
                this.items[itemIndex].serial_numbers = [];
            }
            this.items[itemIndex].serial_numbers.push('');
        },

        removeSerial(itemIndex, serialIndex) {
            if (this.items[itemIndex].serial_numbers) {
                this.items[itemIndex].serial_numbers.splice(serialIndex, 1);
            }
        },

        recalc() {
            let sub = 0;
            let tax = 0;

            this.items.forEach(row => {
                const qty = Number(row.quantity) || 0;
                const cost = Number(row.unit_cost) || 0;
                const lineBase = qty * cost;
                const taxPercent = (Number(row.cgst_percent) || 0) + (Number(row.sgst_percent) || 0);
                const lineTax = (lineBase * taxPercent) / 100;
                row.line_total = lineBase + lineTax;

                sub += lineBase;
                tax += lineTax;
            });

            this.subtotal = sub;
            this.taxTotal = tax;
            this.grandTotal = sub + tax + (Number(this.freightCharge) || 0) + (Number(this.otherCharges) || 0);
        },

        formatCurrency(val) {
            return '₹' + Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },

        async onSupplierChange() {
            const hadLinkedPo = !!this.linkedPoId;
            this.purchaseOrderId = '';
            this.selectedOrderLabel = '';
            this.supplierOrders = [];

            if (hadLinkedPo) {
                this.items = [];
                this.addItem();
                this.freightCharge = 0;
                this.linkedPoId = null;
                this.recalc();
            }

            if (!this.supplierId) {
                this.partyAddresses = [];
                this.selectedBillingId = '';
                this.selectedShippingId = '';
                this.billingSnapshot = '';
                this.shippingSnapshot = '';
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
                this.loadSupplierAddresses(this.supplierId),
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

        async onPurchaseOrderChange() {
            if (!this.purchaseOrderId) {
                if (this.linkedPoId) {
                    this.items = [];
                    this.addItem();
                    this.freightCharge = 0;
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
                    if (ord.freight_total !== undefined) {
                        this.freightCharge = Number(ord.freight_total) || 0;
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
                            return {
                                product_id: it.product_id,
                                unit: p ? (p.unit || 'PCS') : 'PCS',
                                tracking_type: p ? (p.tracking_type || 'none') : 'none',
                                quantity: Number(it.quantity) || 1,
                                unit_cost: Number(it.unit_cost) || 0,
                                cgst_percent: Number(it.cgst_percent) || 0,
                                sgst_percent: Number(it.sgst_percent) || 0,
                                batch_number: it.batch_number || '',
                                batch_mrp: Number(it.batch_mrp) || (p ? (Number(p.mrp) || 0) : 0),
                                serial_numbers: [],
                                line_total: Number(it.line_total) || 0
                            };
                        });
                    }
                    this.recalc();
                }
            } catch (e) {
                console.error('Failed to load purchase order details:', e);
            } finally {
                this.poLoading = false;
            }
        },

        async loadSupplierAddresses(supId) {
            this.addressesLoading = true;
            try {
                const res = await fetch(`{{ url('/masters/customers') }}/${supId}/addresses`, {
                    headers: { 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    this.partyAddresses = data.addresses || [];
                    if (!this.selectedBillingId) {
                        this.selectedBillingId = data.default_billing_id || (this.partyAddresses[0]?.id ?? '');
                    }
                    if (!this.selectedShippingId) {
                        this.selectedShippingId = data.default_delivery_id || this.selectedBillingId;
                    }

                    this.onBillingAddressChange();
                    if (!this.sameAsBilling) {
                        this.onShippingAddressChange();
                    } else {
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

