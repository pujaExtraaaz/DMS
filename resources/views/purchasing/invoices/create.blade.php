@extends('layouts.dms')
@section('title', 'New Purchase Invoice')

@section('content')
<x-ui.page-header title="New Purchase Invoice">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Back</x-ui.button>
    </x-slot>
</x-ui.page-header>

<form method="POST" action="{{ route('purchasing.invoices.store') }}"
      x-data="purchaseInvoiceForm(@js($products), @js($order ?? null), @js($initialOrders ?? []), @js($odAccounts ?? []))"
      class="space-y-6">
    @csrf

    <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4">
        <label class="block text-sm font-extrabold uppercase tracking-wide text-amber-950">Remarks & Additional Delivery</label>
        <p class="mt-1 text-xs font-medium text-amber-800">Put delivery instructions here. This stays at the top so it is not missed.</p>
        <textarea name="notes" rows="3" placeholder="Delivery instructions and invoice remarks..."
                  class="mt-2 block w-full rounded-lg border-amber-300 bg-white text-sm font-bold text-slate-900 focus:border-amber-500 focus:ring-amber-500">{{ old('notes') }}</textarea>
    </div>

    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">Supplier *</label>
                    <button type="button" @click="$dispatch('open-quick-add-supplier')"
                            class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold inline-flex items-center gap-1">
                        + Add New
                    </button>
                </div>
                <div class="relative" @click.outside="supplierOpen = false">
                    <select name="supplier_id" x-model="supplierId" @change="onSupplierChange(); syncSupplierLabel()" required class="sr-only">
                        <option value="">Select supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" data-credit-days="{{ $s->credit_days ?? 0 }}" @selected(old('supplier_id', $order?->supplier_id)==$s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                    <input type="text" x-model="supplierQuery" @focus="supplierOpen = true" @input="supplierOpen = true" autocomplete="off" placeholder="Type to search supplier..."
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <div x-show="supplierOpen" x-cloak class="absolute z-40 left-0 right-0 mt-1 max-h-60 overflow-y-auto rounded-lg border border-slate-200 bg-white shadow-xl">
                        <template x-for="supplier in filteredSuppliers()" :key="supplier.id">
                            <button type="button" @mousedown.prevent="pickSupplier(supplier.id)" class="block w-full px-3 py-2 text-left text-sm text-slate-800 hover:bg-indigo-50" x-text="supplier.name"></button>
                        </template>
                        <div x-show="filteredSuppliers().length === 0" class="px-3 py-2 text-xs text-slate-500">No matching supplier</div>
                    </div>
                </div>
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
                <select name="purchase_order_id"
                        x-model="purchaseOrderId"
                        @change="onPurchaseOrderChange()"
                        :disabled="!supplierId || poLoading"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-slate-100 disabled:text-slate-400 disabled:cursor-not-allowed">
                    <option value="">Select Purchase Order</option>
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
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Warehouse *</label>
                <select name="warehouse_id" x-model="warehouseId" required
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select warehouse</option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected(old('warehouse_id', $order?->warehouse_id)==$w->id)>{{ $w->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Date *</label>
                <input type="date" name="invoice_date" x-model="invoiceDate" @change="recalcDueDate()" @input="recalcDueDate()" value="{{ old('invoice_date', date('Y-m-d')) }}" required
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Supplier Inv No.</label>
                <input type="text" name="supplier_invoice_number" value="{{ old('supplier_invoice_number') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                       placeholder="E.G. Inv-10023">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Purchase Receive Date</label>
                <input type="date" name="purchase_receive_date" x-model="purchaseReceiveDate"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
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
                <p class="mt-1 text-[11px] text-slate-500" x-show="dueDatePreview">
                    Due Date Preview: <strong class="text-slate-700" x-text="dueDatePreview"></strong>
                </p>
            </div>

            <div>
                <div class="flex items-center justify-between mb-1">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider">OD Bank Account</label>
                    <span class="text-[11px] text-slate-400 font-normal">Optional</span>
                </div>
                <select name="od_account_id" x-model="odAccountId" @change="onOdAccountChange()"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">No OD Financing (Standard)</option>
                    @foreach($odAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('od_account_id') == $acc->id)>
                            {{ $acc->bank_name }} ({{ $acc->masked_account_number }}) - Avail: ₹{{ number_format($acc->available_limit, 2) }}
                        </option>
                    @endforeach
                </select>
                <div x-show="odAccountId && selectedOdAccount" class="mt-1 text-[11px]" x-cloak>
                    <span class="text-slate-500">Available: </span>
                    <span class="font-semibold text-emerald-600" x-text="formatCurrency(selectedOdAccount ? selectedOdAccount.available_limit : 0)"></span>
                </div>
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
                <thead class="bg-slate-50 text-slate-600 tracking-wider border-b border-slate-200 font-semibold text-[11px]">
                    <tr>
                        <th class="p-3 min-w-[340px] lg:min-w-[380px]">Product / Description</th>
                        <th class="p-3 min-w-[70px] w-20 text-center">Unit</th>
                        <th class="p-3 min-w-[110px] w-28 text-right">Qty *</th>
                        <th class="p-3 min-w-[130px] w-36 text-right">Unit Cost (₹) *</th>
                        <th class="p-3 min-w-[95px] w-24 text-right">Cgst %</th>
                        <th class="p-3 min-w-[95px] w-24 text-right">Sgst %</th>
                        <th class="p-3 min-w-[100px] w-28 text-center">Tax Rate</th>
                        <th class="p-3 min-w-[130px] w-36">Batch No</th>
                        <th class="p-3 min-w-[125px] w-32 text-right" title="Optional shelf price printed on the batch. It is not added to this invoice.">Printed MRP (₹)<div class="font-normal normal-case tracking-normal text-[10px] text-slate-400">Optional, not in the bill</div></th>
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
                                        <option :value="p.id" x-text="p.name"></option>
                                    </template>
                                </select>

                                    <div class="mt-2">
                                        <label class="mb-1 flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-slate-700">
                                            Serial number
                                            <span x-show="isSerialTracked(row)" x-cloak class="text-[10px] font-bold uppercase text-amber-700">Required</span>
                                        </label>
                                        <template x-for="(sNo, sIdx) in (row.serial_numbers || [])" :key="'serial-' + i + '-' + sIdx">
                                            <input type="text"
                                                   :name="`items[${i}][serial_numbers][${sIdx}]`"
                                                   x-model="row.serial_numbers[sIdx]"
                                                   :placeholder="(row.serial_numbers.length > 1) ? ('Serial ' + (sIdx + 1)) : 'Serial number'"
                                                   :required="isSerialTracked(row)"
                                                   class="mb-1 block w-full rounded-md border-gray-300 px-2.5 py-2 text-xs font-mono text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        </template>
                                    </div>
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
            <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-2.5 text-xs md:col-span-2 lg:max-w-md lg:justify-self-end">
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

                <!-- OD Financing Summary Card -->
                <div x-show="odAccountId && selectedOdAccount" class="mt-4 pt-3 border-t border-slate-200 space-y-2 bg-indigo-50/60 p-3 rounded-lg border border-indigo-100" x-cloak>
                    <div class="flex items-center justify-between text-indigo-900 font-semibold text-xs">
                        <span class="inline-flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                            OD Financing Summary
                        </span>
                        <span class="text-[11px] font-mono font-medium text-indigo-700" x-text="selectedOdAccount ? selectedOdAccount.bank_name : ''"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600 text-xs">
                        <span>Available OD Limit:</span>
                        <span class="font-mono font-semibold text-slate-800" x-text="formatCurrency(selectedOdAccount ? selectedOdAccount.available_limit : 0)"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600 text-xs">
                        <span>Invoice OD Utilization:</span>
                        <span class="font-mono font-bold text-indigo-700" x-text="formatCurrency(grandTotal)"></span>
                    </div>

                    <div class="flex justify-between items-center text-xs border-t border-indigo-200/60 pt-1.5 font-medium">
                        <span class="text-slate-700">Projected Remaining Limit:</span>
                        <span class="font-mono font-bold text-sm"
                              :class="remainingOdLimit < 0 ? 'text-rose-600' : 'text-emerald-700'"
                              x-text="formatCurrency(remainingOdLimit)"></span>
                    </div>

                    <div x-show="remainingOdLimit < 0" class="mt-2 p-2 rounded bg-rose-50 border border-rose-200 text-rose-700 text-xs flex items-start gap-1.5 font-medium">
                        <svg class="w-4 h-4 text-rose-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span>Invoice amount exceeds available OD limit. Submission will fail.</span>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Create Invoice (Draft)</x-ui.button>
    </div>
</form>

<x-quick-add-supplier target-select="supplier_id" />

@push('scripts')
<script>
function purchaseInvoiceForm(products, initialOrder, initialOrders, odAccounts) {
    return {
        products: products || [],
        odAccounts: odAccounts || [],
        odAccountId: '{{ old('od_account_id', '') }}',
        get selectedOdAccount() {
            return this.odAccounts.find(a => String(a.id) === String(this.odAccountId)) || null;
        },
        get remainingOdLimit() {
            if (!this.selectedOdAccount) return 0;
            return (Number(this.selectedOdAccount.available_limit) || 0) - (Number(this.grandTotal) || 0);
        },
        onOdAccountChange() {},
        supplierId: initialOrder ? initialOrder.supplier_id : '{{ old('supplier_id', '') }}',
        supplierQuery: '',
        supplierOpen: false,
        suppliers: [],
        warehouseId: initialOrder ? initialOrder.warehouse_id : '{{ old('warehouse_id', '') }}',
        supplierOrders: initialOrders || [],
        poLoading: false,
        purchaseOrderId: '{{ old('purchase_order_id', $order?->id ?? '') }}',
        selectedOrderLabel: initialOrder ? (initialOrder.po_no || initialOrder.order_number || '') : '',
        linkedPoId: initialOrder ? initialOrder.id : ({{ old('purchase_order_id') ? (int)old('purchase_order_id') : 'null' }}),
        partyAddresses: [],
        addressesLoading: false,
        selectedBillingId: initialOrder ? (initialOrder.billing_address_id || '') : '',
        selectedShippingId: initialOrder ? (initialOrder.shipping_address_id || '') : '',
        billingSnapshot: initialOrder ? (initialOrder.billing_address || '') : '',
        shippingSnapshot: initialOrder ? (initialOrder.shipping_address || '') : '',
        sameAsBilling: !initialOrder || !initialOrder.shipping_address_id || (initialOrder.shipping_address_id === initialOrder.billing_address_id),
        invoiceDate: '{{ old('invoice_date', date('Y-m-d')) }}',
        purchaseReceiveDate: '{{ old('purchase_receive_date', $defaultReceiveDate ?? '') }}',
        paymentOrderDate: '{{ old('payment_order_date', $defaultPaymentOrderDate ?? '') }}',
        creditDays: {{ old('credit_days', $order?->credit_days ?? 0) }},
        dueDatePreview: '',
        dueDateFormattedYmd: '',
        freightCharge: {{ old('freight_charge', (float)($order?->freight_total ?? 0)) }},
        otherCharges: 0,
        subtotal: 0,
        taxTotal: 0,
        grandTotal: 0,
        items: [],

        filteredSuppliers() {
            const q = (this.supplierQuery || '').trim().toLowerCase();
            if (!q) return this.suppliers;
            return this.suppliers.filter(s => s.name.toLowerCase().includes(q));
        },
        syncSupplierLabel() {
            const found = this.suppliers.find(s => String(s.id) === String(this.supplierId));
            this.supplierQuery = found ? found.name : '';
        },
        pickSupplier(id) {
            this.supplierId = String(id);
            this.supplierOpen = false;
            this.syncSupplierLabel();
            this.onSupplierChange();
        },
        async init() {
            this.suppliers = Array.from(document.querySelectorAll('select[name="supplier_id"] option'))
                .filter(option => option.value)
                .map(option => ({ id: option.value, name: option.textContent.trim() }));
            this.syncSupplierLabel();
            this.recalcDueDate();
            if (initialOrder) {
                this.purchaseOrderId = initialOrder.id;
                this.selectedOrderLabel = initialOrder.po_no || initialOrder.order_number || '';
                this.linkedPoId = initialOrder.id;
                if (initialOrder.receive_date && !this.purchaseReceiveDate) {
                    this.purchaseReceiveDate = initialOrder.receive_date;
                }
            }
            if (this.supplierId) {
                await this.loadSupplierAddresses(this.supplierId);
                if (!this.supplierOrders.length) {
                    await this.loadSupplierOrders(this.supplierId, this.purchaseOrderId);
                }
            }
            if (initialOrder && initialOrder.items && initialOrder.items.length) {
                this.items = initialOrder.items.map(it => {
                    const p = this.products.find(prod => prod.id === it.product_id);
                    return {
                        product_id: it.product_id,
                        unit: p ? (p.unit || 'PCS') : 'PCS',
                        tracking_type: p ? (p.tracking_type || 'none') : 'none',
                        quantity: Number(it.quantity) || 1,
                        unit_cost: Number(it.unit_cost) || 0,
                        cgst_percent: Number(it.cgst_percent) || 0,
                        sgst_percent: Number(it.sgst_percent) || 0,
                        batch_number: it.batch_number || it.batch_no || '',
                        batch_mrp: p ? (Number(p.mrp) || 0) : 0,
                        serial_numbers: [''],
                        line_total: 0
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
                quantity: 1,
                unit_cost: 0,
                cgst_percent: 0,
                sgst_percent: 0,
                batch_number: '',
                batch_mrp: 0,
                serial_numbers: [''],
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
            }
            this.syncSerials(index);
            this.recalc();
        },

        syncSerials(index) {
            const row = this.items[index];
            if (!row) return;
            const qty = Math.min(30, Math.max(1, Math.round(Number(row.quantity) || 1)));
            if (!Array.isArray(row.serial_numbers)) row.serial_numbers = [];
            while (row.serial_numbers.length < qty) row.serial_numbers.push('');
            while (row.serial_numbers.length > qty && String(row.serial_numbers[row.serial_numbers.length - 1] || '').trim() === '') {
                row.serial_numbers.pop();
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
                                serial_numbers: [''],
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