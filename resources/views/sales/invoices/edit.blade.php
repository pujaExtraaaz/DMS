@extends('layouts.dms')
@section('title', 'Edit Tax Invoice - ' . $invoice->invoice_no)

@section('content')
@php
    $companyState = $company?->state ?? '';
    $initialAddresses = ($invoice->customer ? $invoice->customer->activeAddresses()->get() : collect())->map(fn($a) => [
        'id' => $a->id,
        'label' => $a->label ?? 'Address #' . $a->id,
        'name' => $a->name ?? $invoice->customer?->name,
        'type' => $a->type ?? 'both',
        'contact_person' => $a->contact_person ?: ($a->name ?: $invoice->customer?->name),
        'contact_phone' => $a->contact_phone ?: $invoice->customer?->phone,
        'address_line_1' => $a->address_line_1 ?? $a->address,
        'address_line_2' => $a->address_line_2,
        'city' => $a->city ?: $invoice->customer?->city,
        'state' => $a->state ?: $invoice->customer?->state,
        'pincode' => $a->pincode ?: $invoice->customer?->pincode,
        'gstin' => $a->gstin ?: $invoice->customer?->gstin,
        'display_text' => $a->display_text,
        'full_address' => $a->full_address,
        'snapshot' => $a->formatSnapshot(),
    ])->values()->toArray();
@endphp

<x-ui.page-header :title="'Edit Tax Invoice - ' . $invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">View Invoice</x-ui.button>
        <x-ui.button variant="secondary" :href="route('invoices.index')">Back to Invoices</x-ui.button>
    </x-slot>
</x-ui.page-header>

<form method="POST" action="{{ route('invoices.update', $invoice) }}"
      x-data="{
          customerId: '{{ $invoice->customer_id }}',
          salespersonId: @js(old('salesperson_id', (string)($invoice->salesperson_id ?? ''))),
          invoiceDate: '{{ $invoice->invoice_date?->format('Y-m-d') }}',
          deliveryState: '{{ $invoice->delivery_state }}',
          notes: @js($invoice->notes ?? ''),

          partyAddresses: @js($initialAddresses),
          selectedBillingId: @js(old('billing_address_id', (string)($invoice->billing_address_id ?? ''))),
          selectedShippingId: @js(old('shipping_address_id', (string)($invoice->shipping_address_id ?? ''))),
          billingSnapshot: @js(old('billing_address', $invoice->billing_address ?? '')),
          shippingSnapshot: @js(old('shipping_address', $invoice->shipping_address ?? '')),
          sameAsBilling: @js(empty($invoice->shipping_address_id) || (string)$invoice->shipping_address_id === (string)$invoice->billing_address_id),

          // Delivery Note Details
          deliveryNoteNo: @js($invoice->delivery_note_no ?? ''),
          deliveryNoteNa: {{ $invoice->delivery_note_na ? 'true' : 'false' }},

          // Dispatch Details
          dispatchDocNo: @js($invoice->dispatch_doc_no ?? ''),
          dispatchedThrough: @js($invoice->dispatched_through ?? $invoice->transport_mode ?? ''),
          destination: @js($invoice->destination ?? ''),
          carrierName: @js($invoice->carrier_name ?? ''),
          lrRrNo: @js($invoice->lr_rr_no ?? ''),
          lrRrDate: @js($invoice->lr_rr_date?->format('Y-m-d') ?? ''),
          motorVehicleNo: @js($invoice->vehicle_no ?? ''),
          transportMode: @js(old('transport_mode', $invoice->transport_mode ?? '1 - Road')),
          vehicleType: @js($invoice->vehicle_type ?? 'R'),
          distanceKm: @js($invoice->distance_km ?? 0),
          transporterId: @js(old('transporter_id', $invoice->transporter_id ?? '')),
          transporterName: @js(old('transporter_name', $invoice->transporter_name ?? '')),
          billToPlace: @js(old('bill_to_place', $invoice->bill_to_place ?? ($invoice->customer->city ?? ''))),
          shipToPlace: @js(old('ship_to_place', $invoice->ship_to_place ?? ($invoice->shippingAddress?->city ?? $invoice->customer->shipping_city ?? $invoice->customer->city ?? ''))),
          ewayBillRequired: @js($invoice->eway_bill_required ?? 'auto'),

          // e-Way Bill Details
          ewayBillNo: @js(old('eway_bill_no', $invoice->eway_bill_no ?? '')),
          ewayBillDate: @js(old('eway_bill_date', $invoice->eway_bill_date?->format('Y-m-d') ?? '')),
          ewaySubType: @js(old('eway_sub_type', $invoice->eway_sub_type ?? 'Supply')),
          ewayDocType: @js(old('eway_doc_type', $invoice->eway_doc_type ?? 'Tax Invoice')),
          ewaySupplyType: @js(old('eway_supply_type', $invoice->eway_supply_type ?? 'Outward')),
          ewayNa: false,
          partBNa: false,

          // Consignor Details (From)
          consignorName: @js(old('consignor_name', $invoice->consignor_name ?? $company?->name ?? '')),
          consignorGstin: @js(old('consignor_gstin', $invoice->consignor_gstin ?? $company?->gstin ?? '')),
          consignorState: @js(old('consignor_state', $invoice->consignor_state ?? $company?->state ?? '')),
          consignorAddressType: @js(old('consignor_address_type', $invoice->consignor_address_type ?? 'Principal Place of Business')),
          consignorAddressLine1: @js(old('consignor_address_line_1', $invoice->consignor_address_line_1 ?? $company?->address ?? '')),
          consignorAddressLine2: @js(old('consignor_address_line_2', $invoice->consignor_address_line_2 ?? '')),
          consignorPincode: @js(old('consignor_pincode', $invoice->consignor_pincode ?? $company?->pincode ?? '')),
          consignorCity: @js(old('consignor_city', $invoice->consignor_city ?? '')),
          consignorActualState: @js(old('consignor_actual_state', $invoice->consignor_actual_state ?? $company?->state ?? '')),

          // Consignee Details (To)
          consigneeName: @js(old('consignee_name', $invoice->consignee_name ?? $invoice->customer->name ?? '')),
          consigneeGstin: @js(old('consignee_gstin', $invoice->consignee_gstin ?? $invoice->customer->gstin ?? '')),
          consigneeState: @js(old('consignee_state', $invoice->consignee_state ?? $invoice->customer->shipping_state ?? $invoice->customer->state ?? '')),
          consigneeAddressType: @js(old('consignee_address_type', $invoice->consignee_address_type ?? 'Delivery')),
          consigneeAddressLine1: @js(old('consignee_address_line_1', $invoice->consignee_address_line_1 ?? $invoice->customer->shipping_address ?? $invoice->customer->address ?? '')),
          consigneeAddressLine2: @js(old('consignee_address_line_2', $invoice->consignee_address_line_2 ?? '')),
          consigneePincode: @js(old('consignee_pincode', $invoice->consignee_pincode ?? $invoice->customer->shipping_pincode ?? $invoice->customer->pincode ?? '')),
          consigneeCity: @js(old('consignee_city', $invoice->consignee_city ?? $invoice->customer->shipping_city ?? $invoice->customer->city ?? '')),
          consigneeActualState: @js(old('consignee_actual_state', $invoice->consignee_actual_state ?? $invoice->customer->shipping_state ?? $invoice->customer->state ?? '')),

          // Quick Add Address Modal state
          addressModal: {
              open: false,
              saving: false,
              error: '',
              label: 'Delivery Branch',
              type: 'delivery',
              contact_person: '',
              contact_phone: '',
              address_line_1: '',
              address_line_2: '',
              city: '',
              state: '',
              pincode: '',
              gstin: '',
              is_default_delivery: true,
          },

          // Order Details
          orderNo: @js($invoice->order_no ?? ($invoice->order?->order_no ?? '')),
          paymentTerms: @js($invoice->payment_terms ?? ''),
          otherReferences: @js($invoice->other_references ?? $invoice->reference_no ?? ''),
          termsOfDelivery: @js($invoice->terms_of_delivery ?? ''),

          get selectedShippingAddress() {
              const id = this.sameAsBilling ? this.selectedBillingId : this.selectedShippingId;
              return this.partyAddresses.find(a => String(a.id) === String(id)) || null;
          },

          get isEWayBillMandatory() {
              return {{ (float)$invoice->grand_total >= 50000 ? 'true' : 'false' }};
          },

          openAddressModal() {
              this.addressModal.open = true;
              this.addressModal.saving = false;
              this.addressModal.error = '';
              this.addressModal.label = 'Delivery Branch';
              this.addressModal.type = 'delivery';
              this.addressModal.contact_person = @js($invoice->customer->name ?? '');
              this.addressModal.contact_phone = @js($invoice->customer->phone ?? '');
              this.addressModal.address_line_1 = '';
              this.addressModal.address_line_2 = '';
              this.addressModal.city = @js($invoice->customer->city ?? '');
              this.addressModal.state = @js($invoice->customer->state ?? '');
              this.addressModal.pincode = @js($invoice->customer->pincode ?? '');
              this.addressModal.gstin = @js($invoice->customer->gstin ?? '');
              this.addressModal.is_default_delivery = true;
          },

          async saveNewAddress() {
              if (!this.addressModal.address_line_1 || !this.addressModal.city || !this.addressModal.state) {
                  this.addressModal.error = 'Address Line 1, City, and State are required.';
                  return;
              }
              this.addressModal.saving = true;
              this.addressModal.error = '';
              try {
                  const res = await fetch(`{{ url('/masters/customers') }}/${this.customerId}/addresses`, {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                          'X-CSRF-TOKEN': '{{ csrf_token() }}'
                      },
                      body: JSON.stringify({
                          label: this.addressModal.label,
                          type: this.addressModal.type,
                          contact_person: this.addressModal.contact_person,
                          contact_phone: this.addressModal.contact_phone,
                          address_line_1: this.addressModal.address_line_1,
                          address_line_2: this.addressModal.address_line_2,
                          city: this.addressModal.city,
                          state: this.addressModal.state,
                          pincode: this.addressModal.pincode,
                          gstin: this.addressModal.gstin,
                          is_default_delivery: this.addressModal.is_default_delivery,
                      })
                  });
                  const data = await res.json();
                  if (!res.ok || !data.success) {
                      this.addressModal.error = data.message || (data.errors ? Object.values(data.errors).flat().join(', ') : 'Failed to save address.');
                      return;
                  }
                  const newAddr = data.address;
                  this.partyAddresses.push(newAddr);
                  this.sameAsBilling = false;
                  this.selectedShippingId = newAddr.id;
                  this.onShippingAddressChange();
                  this.addressModal.open = false;
              } catch (err) {
                  console.error(err);
                  this.addressModal.error = 'Network or server error while saving address.';
              } finally {
                  this.addressModal.saving = false;
              }
          },

          syncConsigneeFromAddress(addr) {
              if (addr) {
                  this.consigneeName = addr.contact_person || addr.name || @js($invoice->customer->name ?? '');
                  this.consigneeGstin = addr.gstin || @js($invoice->customer->gstin ?? '');
                  this.consigneeState = addr.state || @js($invoice->customer->state ?? '');
                  this.consigneeAddressType = addr.label || 'Delivery';
                  this.consigneeAddressLine1 = addr.address_line_1 || addr.address || @js($invoice->customer->address ?? '');
                  this.consigneeAddressLine2 = addr.address_line_2 || '';
                  this.consigneePincode = addr.pincode || @js($invoice->customer->pincode ?? '');
                  this.consigneeCity = addr.city || @js($invoice->customer->city ?? '');
                  this.consigneeActualState = addr.state || @js($invoice->customer->state ?? '');
              }
          },

          get isRoadMode() {
              return !this.transportMode || this.transportMode === '1 - Road' || this.transportMode === '1' || this.transportMode === 'Road';
          },

          onTransporterChange(event) {
              const select = event.target;
              if (!this.transporterName) {
                  this.transporterId = '';
                  return;
              }
              const opt = select.options[select.selectedIndex];
              const tid = opt ? opt.getAttribute('data-transporter-id') : '';
              this.transporterId = tid ? tid.trim() : '';
          },

          onTransportModeChange() {
              this.partBNa = (this.transportMode === 'Not Applicable');
          },

          onBillingAddressChange() {
              const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
              this.billingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
              if (addr?.city) {
                  this.billToPlace = addr.city;
              }
              if (this.sameAsBilling) {
                  this.selectedShippingId = this.selectedBillingId;
                  this.shippingSnapshot = this.billingSnapshot;
                  if (addr?.city) {
                      this.shipToPlace = addr.city;
                  }
                  if (addr?.state) {
                      this.deliveryState = addr.state;
                  }
                  this.syncConsigneeFromAddress(addr);
              }
          },

          onShippingAddressChange() {
              const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedShippingId));
              this.shippingSnapshot = addr ? (addr.snapshot || addr.full_address) : '';
              if (addr?.city) {
                  this.shipToPlace = addr.city;
              }
              if (addr?.state) {
                  this.deliveryState = addr.state;
              }
              this.syncConsigneeFromAddress(addr);
          },

          onSameAsBillingToggle() {
              if (this.sameAsBilling) {
                  this.selectedShippingId = this.selectedBillingId;
                  this.shippingSnapshot = this.billingSnapshot;
                  this.shipToPlace = this.billToPlace;
                  const addr = this.partyAddresses.find(a => String(a.id) === String(this.selectedBillingId));
                  if (addr?.city) {
                      this.shipToPlace = addr.city;
                  }
                  if (addr?.state) {
                      this.deliveryState = addr.state;
                  }
                  this.syncConsigneeFromAddress(addr);
              } else {
                  this.onShippingAddressChange();
              }
          },

          onDeliveryNoteNaToggle() {
              if (this.deliveryNoteNa) {
                  this.deliveryNoteNo = '';
              }
          },

          onEwayNaToggle() {
              if (this.ewayNa) {
                  this.ewayBillRequired = 'no';
                  this.ewayBillNo = '';
                  this.ewayBillDate = '';
              } else {
                  this.ewayBillRequired = 'auto';
              }
          },

          onPartBNaToggle() {
              if (this.partBNa) {
                  this.transportMode = 'Not Applicable';
                  this.distanceKm = 0;
              } else if (this.transportMode === 'Not Applicable') {
                  this.transportMode = '1 - Road';
              }
          }
      }"
      class="space-y-6">
    @csrf
    @method('PUT')

    {{-- e-Invoice Summary Details Banner (If e-Invoice exists) --}}
    @if($invoice->eInvoice)
        <div class="rounded-xl border border-indigo-200 bg-gradient-to-r from-indigo-50/80 via-white to-blue-50/80 p-4 shadow-sm">
            <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-indigo-100">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center justify-center w-7 h-7 rounded-lg bg-indigo-600 text-white font-bold text-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    </span>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">e-Invoice Details (Government IRP)</h4>
                        <p class="text-[11px] text-slate-500">Live GST e-Invoice registered with Invoice Registration Portal</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if($invoice->eInvoice->status === 'generated' || $invoice->eInvoice->status === 'active')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                            ✓ Generated / Active
                        </span>
                    @elseif($invoice->eInvoice->status === 'cancelled')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-200">
                            ✕ Cancelled
                        </span>
                    @elseif($invoice->eInvoice->status === 'failed')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                            ⚠ Failed
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-200">
                            {{ ucfirst($invoice->eInvoice->status ?? 'Pending') }}
                        </span>
                    @endif
                    <a href="{{ route('invoices.e-invoice', $invoice) }}"
                       class="inline-flex items-center gap-1 px-3 py-1 text-xs font-semibold text-indigo-700 bg-white border border-indigo-200 rounded-lg hover:bg-indigo-50 shadow-sm transition">
                        <span>View e-Invoice</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 mt-3 text-xs">
                <div>
                    <span class="block text-[11px] font-semibold text-slate-500 uppercase">Acknowledgement No. (Ack No)</span>
                    <span class="font-mono font-bold text-slate-800 text-sm">{{ $invoice->eInvoice->ack_no ?: '—' }}</span>
                </div>
                <div>
                    <span class="block text-[11px] font-semibold text-slate-500 uppercase">Acknowledgement Date (Ack Date)</span>
                    <span class="font-mono font-medium text-slate-800 text-xs">
                        {{ $invoice->eInvoice->ack_date ? (is_string($invoice->eInvoice->ack_date) ? $invoice->eInvoice->ack_date : $invoice->eInvoice->ack_date->format('d/m/Y H:i')) : '—' }}
                    </span>
                </div>
                <div class="sm:col-span-2">
                    <span class="block text-[11px] font-semibold text-slate-500 uppercase">Invoice Reference Number (IRN)</span>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="font-mono text-xs font-bold text-slate-800 truncate select-all" title="{{ $invoice->eInvoice->irn }}">
                            {{ $invoice->eInvoice->irn ?: '—' }}
                        </span>
                        @if($invoice->eInvoice->irn)
                            <button type="button"
                                    @click="navigator.clipboard.writeText('{{ $invoice->eInvoice->irn }}'); alert('IRN copied to clipboard!')"
                                    class="text-indigo-600 hover:text-indigo-800 p-1 rounded hover:bg-indigo-50"
                                    title="Copy IRN">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Remarks Banner at Top --}}
    <div class="rounded-xl border-2 border-amber-400 bg-amber-50 p-4">
        <label class="block text-sm font-extrabold uppercase tracking-wide text-amber-950">Remarks & Additional Delivery</label>
        <p class="mt-1 text-xs font-medium text-amber-800">Put delivery instructions, transport notes, and invoice remarks here.</p>
        <textarea name="notes" rows="2" placeholder="Delivery instructions and invoice remarks..."
                  x-model="notes"
                  class="mt-2 block w-full rounded-lg border-amber-300 bg-white text-sm font-bold text-slate-900 focus:border-amber-500 focus:ring-amber-500"></textarea>
        @error('notes')
            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- Invoice Header Summary --}}
    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Customer / Debtor</label>
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-semibold text-slate-800">
                    {{ $invoice->customer->name }}
                    @if($invoice->customer->mobile_number || $invoice->customer->phone)
                        <span class="text-xs font-normal text-slate-500">({{ $invoice->customer->mobile_number ?: $invoice->customer->phone }})</span>
                    @endif
                </div>
            </div>

            <div>
                <label for="salesperson_id" class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Salesperson</label>
                <select name="salesperson_id"
                        id="salesperson_id"
                        x-model="salespersonId"
                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">Select Salesperson (Optional)</option>
                    @foreach($salespersons as $sp)
                        <option value="{{ $sp->id }}" @selected(old('salesperson_id', $invoice->salesperson_id) == $sp->id)>
                            {{ $sp->name }}
                        </option>
                    @endforeach
                </select>
                @error('salesperson_id')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Number</label>
                <div class="p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm font-mono font-bold text-indigo-700">
                    {{ $invoice->invoice_no }}
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Invoice Date</label>
                <input type="date" name="invoice_date" x-model="invoiceDate" value="{{ $invoice->invoice_date?->format('Y-m-d') }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Delivery State</label>
                <input type="text" name="delivery_state" x-model="deliveryState"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>
        </div>

        {{-- Billing & Shipping Address Section --}}
        <div class="mt-4 pt-4 border-t border-slate-200">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-bold uppercase tracking-wider text-slate-700">Billing & Delivery Addresses</span>
                <span class="text-xs text-slate-500" x-text="`${partyAddresses.length} address(es) available`"></span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                {{-- Billing Address Card --}}
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider">Billing Address</label>
                        <span class="text-[11px] text-indigo-600 font-medium">Billed To</span>
                    </div>
                    <select name="billing_address_id"
                            x-model="selectedBillingId"
                            @change="onBillingAddressChange()"
                            class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Default Address</option>
                        <template x-for="addr in partyAddresses" :key="addr.id">
                            <option :value="addr.id" x-text="`${addr.label || 'Address'} — ${addr.full_address || addr.address_line_1}`"></option>
                        </template>
                    </select>
                    <textarea name="billing_address"
                              x-model="billingSnapshot"
                              rows="3"
                              placeholder="Billing Address Snapshot"
                              class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    @error('billing_address_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Delivery Address Card --}}
                <div class="bg-white p-3.5 rounded-lg border border-slate-200 space-y-2.5">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider">Delivery / Consignee Address</label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-700 cursor-pointer font-medium select-none">
                            <input type="checkbox"
                                   x-model="sameAsBilling"
                                   @change="onSameAsBillingToggle()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Same as Billing Address</span>
                        </label>
                    </div>
                    
                    {{-- Dropdown and Quick Add button when not Same as Billing --}}
                    <div x-show="!sameAsBilling" class="space-y-2">
                        <div class="flex items-center gap-2">
                            <select name="shipping_address_id"
                                    x-model="selectedShippingId"
                                    @change="onShippingAddressChange()"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Saved Delivery Address --</option>
                                <template x-for="addr in partyAddresses" :key="addr.id">
                                    <option :value="addr.id" x-text="`${addr.label || 'Address'} — ${addr.full_address || addr.address_line_1}`"></option>
                                </template>
                            </select>
                            <button type="button"
                                    @click="openAddressModal()"
                                    class="inline-flex items-center gap-1 whitespace-nowrap px-3 py-2 text-xs font-semibold text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 rounded-lg shadow-sm transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <span>Add Address</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="sameAsBilling" class="text-xs text-slate-500 italic py-1 bg-slate-50 px-2.5 rounded border border-slate-100 flex items-center gap-1.5" x-cloak>
                        <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>Delivery address automatically matches the selected Billing Address.</span>
                    </div>

                    {{-- Selected Delivery Address Formatted Card --}}
                    <div x-show="selectedShippingAddress" class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs space-y-1.5" x-cloak>
                        <div class="flex items-center justify-between pb-1 border-b border-slate-200">
                            <div class="flex items-center gap-1.5">
                                <span class="font-semibold text-slate-800" x-text="selectedShippingAddress?.name || selectedShippingAddress?.contact_person || 'Consignee'"></span>
                                <span class="inline-block px-1.5 py-0.5 text-[10px] font-semibold bg-indigo-100 text-indigo-700 rounded" x-text="selectedShippingAddress?.label || selectedShippingAddress?.type || 'Delivery'"></span>
                            </div>
                            <template x-if="selectedShippingAddress?.gstin">
                                <span class="inline-block px-1.5 py-0.5 text-[10px] font-mono font-medium bg-emerald-50 text-emerald-700 border border-emerald-200 rounded">
                                    GST: <span x-text="selectedShippingAddress.gstin"></span>
                                </span>
                            </template>
                        </div>
                        <div class="text-slate-600">
                            <template x-if="selectedShippingAddress?.contact_phone">
                                <div class="text-[11px] text-slate-500">Contact: <span class="font-medium text-slate-700" x-text="selectedShippingAddress.contact_phone"></span></div>
                            </template>
                            <div x-text="selectedShippingAddress?.address_line_1 || ''"></div>
                            <template x-if="selectedShippingAddress?.address_line_2">
                                <div x-text="selectedShippingAddress.address_line_2"></div>
                            </template>
                            <div>
                                <span x-text="selectedShippingAddress?.city || ''"></span><span x-show="selectedShippingAddress?.city && selectedShippingAddress?.state">, </span>
                                <span class="font-medium text-slate-700" x-text="selectedShippingAddress?.state || ''"></span>
                                <template x-if="selectedShippingAddress?.pincode">
                                    <span> - <span x-text="selectedShippingAddress.pincode"></span></span>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Snapshot Textarea --}}
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Address Snapshot (Stored with Invoice)</label>
                        <textarea name="shipping_address"
                                  x-model="shippingSnapshot"
                                  rows="2"
                                  placeholder="Delivery Address Snapshot"
                                  class="block w-full rounded-lg border-gray-200 text-xs text-slate-700 bg-slate-50 focus:bg-white focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                    </div>
                    @error('shipping_address_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Place of Party (Bill to Place & Ship to Place) --}}
            <div class="mt-4 pt-3.5 border-t border-slate-200">
                <div class="mb-2">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                        Place of Party (GST Invoicing &amp; Dispatch Destination)
                    </label>
                    <p class="text-[11px] text-slate-500">
                        Auto-populated from the customer's billing and delivery city/location master records; saved with invoice.
                    </p>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Bill to Place <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="bill_to_place"
                               x-model="billToPlace"
                               placeholder="E.g. Mumbai, Bengaluru, Pune"
                               maxlength="100"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium">
                        <p class="mt-1 text-[11px] text-slate-400">Place/City of the buyer / billing party master.</p>
                        @error('bill_to_place')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Ship to Place <span class="text-rose-500">*</span>
                        </label>
                        <input type="text"
                               name="ship_to_place"
                               x-model="shipToPlace"
                               placeholder="E.g. Bhiwandi, Whitefield, Manesar"
                               maxlength="100"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-medium">
                        <p class="mt-1 text-[11px] text-slate-400">Place/City of the delivery / consignee destination.</p>
                        @error('ship_to_place')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Delivery Note, Dispatch, and Order Details --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Section 1 & 2: Delivery Note & Dispatch Details --}}
        <x-ui.card>
            <div class="space-y-4">
                {{-- 1. Delivery Note Details --}}
                <div class="pb-3 border-b border-slate-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-800">
                            Delivery Note Details
                        </label>
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer font-medium select-none">
                            <input type="checkbox"
                                   name="delivery_note_na"
                                   value="1"
                                   x-model="deliveryNoteNa"
                                   @change="onDeliveryNoteNaToggle()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Not Applicable</span>
                        </label>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Delivery Note No(s)
                        </label>
                        <input type="text"
                               name="delivery_note_no"
                               x-model="deliveryNoteNo"
                               :disabled="deliveryNoteNa"
                               :placeholder="deliveryNoteNa ? 'Not Applicable' : 'E.g. DN-2024-001, DN-2024-002'"
                               :class="deliveryNoteNa ? 'bg-slate-100 text-slate-400 cursor-not-allowed' : 'bg-white text-slate-900'"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 transition-colors">
                        @error('delivery_note_no')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- 2. Dispatch Details --}}
                <div>
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800 mb-3">Dispatch Details</h4>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                        {{-- 1. Dispatch Doc No. --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Dispatch Doc No.
                            </label>
                            <input type="text"
                                   name="dispatch_doc_no"
                                   x-model="dispatchDocNo"
                                   placeholder="E.g. DISP-1092"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('dispatch_doc_no')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 2. Dispatched Through --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Dispatched Through
                            </label>
                            <input type="text"
                                   name="dispatched_through"
                                   x-model="dispatchedThrough"
                                   placeholder="E.g. Road, Blue Dart, Express"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('dispatched_through')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 3. Destination --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Destination
                            </label>
                            <input type="text"
                                   name="destination"
                                   x-model="destination"
                                   placeholder="E.g. Mumbai, Pune Warehouse"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('destination')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 4. Carrier Name / Agent --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Carrier Name / Agent
                            </label>
                            <input type="text"
                                   name="carrier_name"
                                   x-model="carrierName"
                                   placeholder="E.g. VRL Logistics, SafeX"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('carrier_name')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 5. Bill of Lading / LR-RR No. --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Bill of Lading / LR-RR No.
                            </label>
                            <input type="text"
                                   name="lr_rr_no"
                                   x-model="lrRrNo"
                                   placeholder="E.g. LR-987654"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('lr_rr_no')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 6. Bill of Lading / LR-RR Date --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Bill of Lading / LR-RR Date
                            </label>
                            <input type="date"
                                   name="lr_rr_date"
                                   x-model="lrRrDate"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('lr_rr_date')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 7. Motor Vehicle No. & Transport Mode --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Motor Vehicle No.
                            </label>
                            <input type="text"
                                   name="motor_vehicle_no"
                                   x-model="motorVehicleNo"
                                   placeholder="E.g. MH-12-AB-1234"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <input type="hidden" name="vehicle_no" :value="motorVehicleNo">
                            @error('motor_vehicle_no')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
        </x-ui.card>

        {{-- Section 3: Order Details --}}
        <x-ui.card>
            <div class="space-y-3.5">
                <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">Order Details</h4>
                    @if($invoice->order)
                        <span class="text-xs font-semibold text-emerald-600">
                            Linked to {{ $invoice->order->order_no }}
                        </span>
                    @endif
                </div>

                {{-- 1. Order No(s) --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Order No(s)
                    </label>
                    <input type="text"
                           name="order_no"
                           x-model="orderNo"
                           placeholder="E.g. SO-2024-001 or PO-Client-449"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="mt-1 text-[11px] text-slate-400">
                        Populated automatically from sales order or entered manually.
                    </p>
                    @error('order_no')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 2. Mode/Terms of Payment --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Mode / Terms of Payment
                    </label>
                    <input type="text"
                           name="payment_terms"
                           x-model="paymentTerms"
                           placeholder="E.g. Net 30, Advance 50% / Balance on Delivery, Immediate"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @error('payment_terms')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 3. Other References --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Other References
                    </label>
                    <input type="text"
                           name="other_references"
                           x-model="otherReferences"
                           placeholder="E.g. Customer PO No, Quotation Ref, Tender No"
                           class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <input type="hidden" name="reference_no" :value="otherReferences">
                    @error('other_references')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- 4. Terms of Delivery --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        Terms of Delivery
                    </label>
                    <textarea name="terms_of_delivery"
                              x-model="termsOfDelivery"
                              rows="4"
                              placeholder="Enter delivery terms, instructions, or conditions (supports multiple lines)..."
                              class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 resize-y min-h-[105px] leading-relaxed">{{ old('terms_of_delivery', $invoice->terms_of_delivery) }}</textarea>
                    <p class="mt-1 text-[11px] text-slate-400">
                        Supports detailed delivery instructions, timing, and conditions across multiple lines.
                    </p>
                    @error('terms_of_delivery')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>
    </div>

    {{-- Card 3: e-Way Bill, Consignor, Consignee & Transport Details --}}
    <x-ui.card>
        <div class="space-y-6">
            {{-- Header & e-Way Bill Details --}}
            <div>
                <div class="flex flex-wrap items-center justify-between gap-3 pb-3 border-b border-slate-200">
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-800">e-Way Bill Details</h4>
                        <p class="text-[11px] text-slate-500">Applicable for inter-state or intra-state movement of goods exceeding ₹50,000 consignment value.</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer font-medium select-none">
                            <input type="checkbox"
                                   x-model="ewayNa"
                                   @change="onEwayNaToggle()"
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Not Applicable / Exempt</span>
                        </label>
                    </div>
                </div>

                {{-- Requirement Selector --}}
                <div class="mt-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-2">
                        e-Way Bill Requirement Mode
                    </label>
                    <div class="flex flex-wrap items-center gap-5 text-xs">
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="eway_bill_required" value="auto" x-model="ewayBillRequired" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-slate-800">Auto-Determined (Recommended — Mandatory if &gt; ₹50,000)</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="eway_bill_required" value="yes" x-model="ewayBillRequired" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-slate-800">Yes (Generate / Mandatory)</span>
                        </label>
                        <label class="inline-flex items-center gap-1.5 cursor-pointer" :class="isEWayBillMandatory ? 'opacity-50 cursor-not-allowed' : ''">
                            <input type="radio" name="eway_bill_required" value="no" x-model="ewayBillRequired" :disabled="isEWayBillMandatory" class="text-indigo-600 focus:ring-indigo-500">
                            <span class="font-medium text-slate-800">No (Exempt)</span>
                        </label>
                    </div>
                    <p x-show="isEWayBillMandatory" class="mt-1.5 text-[11px] text-amber-700 font-medium flex items-center gap-1" x-cloak>
                        <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <span>Consignment value exceeds ₹50,000 threshold; E-Way Bill is legally mandatory under GST Rule 138.</span>
                    </p>
                </div>

                {{-- e-Way Bill Document & Classification Fields --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3.5 mt-3.5" :class="ewayNa ? 'opacity-50 pointer-events-none' : ''">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            e-Way Bill No. <span class="text-slate-400 font-normal">(Optional / Manual)</span>
                        </label>
                        <input type="text"
                               name="eway_bill_no"
                               x-model="ewayBillNo"
                               placeholder="12-digit e-Way Bill No"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        @error('eway_bill_no')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            e-Way Bill Date
                        </label>
                        <input type="date"
                               name="eway_bill_date"
                               x-model="ewayBillDate"
                               class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('eway_bill_date')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Sub-Type
                        </label>
                        <select name="eway_sub_type" x-model="ewaySubType"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Supply">Supply</option>
                            <option value="Export">Export</option>
                            <option value="Job Work">Job Work</option>
                            <option value="SKD/CKD">SKD / CKD</option>
                            <option value="Recipient Not Known">Recipient Not Known</option>
                            <option value="For Own Use">For Own Use</option>
                            <option value="Exhibition/Fair">Exhibition or Fair</option>
                            <option value="Line Sales">Line Sales</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Document Type
                        </label>
                        <select name="eway_doc_type" x-model="ewayDocType"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Tax Invoice">Tax Invoice</option>
                            <option value="Bill of Supply">Bill of Supply</option>
                            <option value="Delivery Challan">Delivery Challan</option>
                            <option value="Others">Others</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                            Supply Type
                        </label>
                        <select name="eway_supply_type" x-model="ewaySupplyType"
                                class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="Outward">Outward</option>
                            <option value="Inward">Inward</option>
                        </select>
                    </div>
                </div>
            </div>

            {{-- Consignor & Consignee Details (2 Columns) --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 pt-4 border-t border-slate-200">
                {{-- Consignor Details (From) --}}
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-indigo-600"></span>
                            Consignor Details (From - Seller)
                        </h5>
                        <span class="text-[10px] font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded border border-indigo-100">
                            Company Master
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Party / Company Name</label>
                            <input type="text" name="consignor_name" x-model="consignorName" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">GSTIN / UIN</label>
                            <input type="text" name="consignor_gstin" x-model="consignorGstin" class="block w-full rounded-lg border-gray-300 text-xs font-mono uppercase focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">State</label>
                            <input type="text" name="consignor_state" x-model="consignorState" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Type</label>
                            <input type="text" name="consignor_address_type" x-model="consignorAddressType" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Actual State</label>
                            <input type="text" name="consignor_actual_state" x-model="consignorActualState" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Line 1</label>
                            <input type="text" name="consignor_address_line_1" x-model="consignorAddressLine1" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Line 2</label>
                            <input type="text" name="consignor_address_line_2" x-model="consignorAddressLine2" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">City / Place</label>
                            <input type="text" name="consignor_city" x-model="consignorCity" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">PIN Code</label>
                            <input type="text" name="consignor_pincode" x-model="consignorPincode" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>
                </div>

                {{-- Consignee Details (To) --}}
                <div class="bg-slate-50/70 p-4 rounded-xl border border-slate-200 space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-200">
                        <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                            Consignee Details (To - Delivery Party)
                        </h5>
                        <span class="text-[10px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded border border-emerald-100">
                            Party Delivery Master
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Consignee Name</label>
                            <input type="text" name="consignee_name" x-model="consigneeName" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">GSTIN / UIN</label>
                            <input type="text" name="consignee_gstin" x-model="consigneeGstin" class="block w-full rounded-lg border-gray-300 text-xs font-mono uppercase focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">State</label>
                            <input type="text" name="consignee_state" x-model="consigneeState" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Type</label>
                            <input type="text" name="consignee_address_type" x-model="consigneeAddressType" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Actual State</label>
                            <input type="text" name="consignee_actual_state" x-model="consigneeActualState" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Line 1</label>
                            <input type="text" name="consignee_address_line_1" x-model="consigneeAddressLine1" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">Address Line 2</label>
                            <input type="text" name="consignee_address_line_2" x-model="consigneeAddressLine2" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">City / Place</label>
                            <input type="text" name="consignee_city" x-model="consigneeCity" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[11px] font-semibold text-slate-600 mb-1">PIN Code</label>
                            <input type="text" name="consignee_pincode" x-model="consigneePincode" class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Transport Details & Part B (Vehicle / Transporter) --}}
            <div class="pt-4 border-t border-slate-200">
                <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-slate-800 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                        Transport Details &amp; Part B (Vehicle Details)
                    </h5>
                    <label class="inline-flex items-center gap-1.5 text-xs text-slate-600 cursor-pointer font-medium select-none">
                        <input type="checkbox"
                               x-model="partBNa"
                               @change="onPartBNaToggle()"
                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <span>Part B Not Applicable (Direct Handover / Local)</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5 text-xs" :class="partBNa ? 'opacity-50 pointer-events-none' : ''">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Mode of Transport
                        </label>
                        <select name="transport_mode"
                                x-model="transportMode"
                                @change="onTransportModeChange()"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500 font-medium">
                            <option value="Not Applicable">Not Applicable / NA</option>
                            <option value="1 - Road">1 - Road</option>
                            <option value="2 - Rail">2 - Rail</option>
                            <option value="3 - Air">3 - Air</option>
                            <option value="4 - Ship">4 - Ship</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Distance (in KM)
                        </label>
                        <input type="number"
                               min="0"
                               max="9999"
                               name="distance_km"
                               x-model.number="distanceKm"
                               placeholder="E.g. 150 (0 for portal auto-calc)"
                               class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                        <p class="mt-0.5 text-[10px] text-slate-400">Portal auto-calculates if left 0 based on PIN codes.</p>
                        @error('distance_km')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Transporter Name
                        </label>
                        <select name="transporter_name"
                                x-model="transporterName"
                                @change="onTransporterChange($event)"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- None / Direct --</option>
                            @foreach($transporters as $transporter)
                                @php
                                    $tName = is_array($transporter) ? ($transporter['name'] ?? '') : $transporter->name;
                                    $tId = is_array($transporter) ? ($transporter['transporter_id'] ?? '') : ($transporter->gstin ?: $transporter->code);
                                @endphp
                                <option value="{{ $tName }}"
                                        data-transporter-id="{{ $tId }}"
                                        @selected(old('transporter_name', $invoice->transporter_name) == $tName)>
                                    {{ $tName }}{{ $tId ? ' ('.$tId.')' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('transporter_name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Transporter ID / GSTIN
                        </label>
                        <input type="text"
                               name="transporter_id"
                               x-model="transporterId"
                               placeholder="15-digit GSTIN or TRANS ID"
                               maxlength="25"
                               class="block w-full rounded-lg border-gray-300 text-xs uppercase focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        <p class="mt-0.5 text-[10px] text-slate-400">Auto-fetched from party master; editable if missing.</p>
                        @error('transporter_id')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Doc / LR / RR / Airway No.
                        </label>
                        <input type="text"
                               name="lr_rr_no"
                               x-model="lrRrNo"
                               placeholder="Transport Doc / LR / RR No"
                               class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">
                            Doc / LR / RR Date
                        </label>
                        <input type="date"
                               name="lr_rr_date"
                               x-model="lrRrDate"
                               class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                    </div>

                    <div :class="!isRoadMode ? 'opacity-60' : ''">
                        <label class="block font-semibold text-slate-700 mb-1">
                            Motor Vehicle No. <span x-show="isRoadMode" class="text-slate-400 font-normal">(Road)</span><span x-show="!isRoadMode" class="text-slate-400 font-normal text-[10px]" x-cloak>(Optional for Rail/Air/Ship)</span>
                        </label>
                        <input type="text"
                               name="motor_vehicle_no"
                               x-model="motorVehicleNo"
                               placeholder="E.g. MH12AB1234"
                               class="block w-full rounded-lg border-gray-300 text-xs uppercase focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                        <input type="hidden" name="vehicle_no" :value="motorVehicleNo">
                    </div>

                    <div :class="!isRoadMode ? 'opacity-60' : ''">
                        <label class="block font-semibold text-slate-700 mb-1">
                            Vehicle Type
                        </label>
                        <select name="vehicle_type" x-model="vehicleType"
                                class="block w-full rounded-lg border-gray-300 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="R">Regular Cargo (R)</option>
                            <option value="O">Over Dimensional Cargo (ODC)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </x-ui.card>

    {{-- Line Items Summary Preview --}}
    <x-ui.card padding="false">
        <div class="p-4 border-b border-slate-200">
            <h3 class="text-sm font-semibold text-slate-700">Invoice Items ({{ $invoice->items->count() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left divide-y divide-slate-200">
                <thead class="bg-slate-50 text-slate-600 font-semibold uppercase">
                    <tr>
                        <th class="p-3">#</th>
                        <th class="p-3">Product</th>
                        <th class="p-3 text-right">Quantity</th>
                        <th class="p-3 text-right">Unit Price</th>
                        <th class="p-3 text-right">Discount</th>
                        <th class="p-3 text-right">Taxable</th>
                        <th class="p-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($invoice->items as $idx => $it)
                        <tr>
                            <td class="p-3 text-slate-400">{{ $idx + 1 }}</td>
                            <td class="p-3 font-medium text-slate-800">{{ $it->product?->name ?? $it->item_name }}</td>
                            <td class="p-3 text-right font-mono">{{ $it->quantity }} {{ $it->uom?->code }}</td>
                            <td class="p-3 text-right font-mono">₹{{ number_format($it->unit_price, 2) }}</td>
                            <td class="p-3 text-right font-mono text-amber-700">₹{{ number_format($it->discount_amount, 2) }}</td>
                            <td class="p-3 text-right font-mono">₹{{ number_format($it->taxable_amount, 2) }}</td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900">₹{{ number_format($it->line_total, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-200 flex justify-end">
            <div class="text-right space-y-1 text-xs">
                <div>Subtotal: <span class="font-mono font-semibold">₹{{ number_format($invoice->subtotal, 2) }}</span></div>
                @if($invoice->taxable_amount !== null && (float)$invoice->taxable_amount > 0)
                    <div>Taxable Value: <span class="font-mono font-semibold">₹{{ number_format($invoice->taxable_amount, 2) }}</span></div>
                @endif
                @if(($invoice->tax_type ?? '') === 'intra' || (!$invoice->tax_type && ($invoice->cgst_amount > 0 || $invoice->sgst_amount > 0)))
                    <div>CGST: <span class="font-mono font-semibold">₹{{ number_format($invoice->cgst_amount, 2) }}</span></div>
                    <div>SGST: <span class="font-mono font-semibold">₹{{ number_format($invoice->sgst_amount, 2) }}</span></div>
                @elseif(($invoice->tax_type ?? '') === 'inter' || $invoice->igst_amount > 0)
                    <div>IGST: <span class="font-mono font-semibold">₹{{ number_format($invoice->igst_amount, 2) }}</span></div>
                @else
                    <div>Tax: <span class="font-mono font-semibold">₹{{ number_format($invoice->tax_amount, 2) }}</span></div>
                @endif
                @if(!empty($invoice->round_off) && (float)$invoice->round_off != 0)
                    <div>Round Off: <span class="font-mono font-semibold">₹{{ number_format($invoice->round_off, 2) }}</span></div>
                @endif
                <div class="text-sm font-bold text-indigo-700">Grand Total: <span class="font-mono">₹{{ number_format($invoice->grand_total, 2) }}</span></div>
            </div>
        </div>
    </x-ui.card>

    {{-- Form Footer Actions --}}
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" class="px-6 py-2.5 text-sm font-semibold shadow-sm">
            Update Invoice
        </x-ui.button>
    </div>
</form>

{{-- Quick Add Delivery Address Modal --}}
<div x-show="addressModal.open"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div x-show="addressModal.open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="addressModal.open = false"
             class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity"></div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        <div x-show="addressModal.open"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-xl sm:w-full">
            
            <div class="bg-indigo-600 px-5 py-3.5 flex items-center justify-between text-white">
                <h3 class="text-sm font-bold flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <span>Add New Delivery Address</span>
                </h3>
                <button type="button" @click="addressModal.open = false" class="text-indigo-100 hover:text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="p-6 space-y-4 text-xs">
                <div x-show="addressModal.error" class="p-2.5 bg-rose-50 border border-rose-200 text-rose-700 rounded-lg" x-text="addressModal.error"></div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Address Label *</label>
                        <input type="text" x-model="addressModal.label" placeholder="E.g. Main Warehouse, Branch 2" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Address Type</label>
                        <select x-model="addressModal.type" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="delivery">Delivery</option>
                            <option value="warehouse">Warehouse</option>
                            <option value="both">Both (Billing & Delivery)</option>
                            <option value="shipping">Shipping</option>
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Contact Person</label>
                        <input type="text" x-model="addressModal.contact_person" placeholder="Name" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">Contact Phone</label>
                        <input type="text" x-model="addressModal.contact_phone" placeholder="Phone Number" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Address Line 1 *</label>
                        <input type="text" x-model="addressModal.address_line_1" placeholder="Plot / Flat / Building / Street" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="sm:col-span-2">
                        <label class="block font-semibold text-slate-700 mb-1">Address Line 2</label>
                        <input type="text" x-model="addressModal.address_line_2" placeholder="Area / Landmark" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">City *</label>
                        <input type="text" x-model="addressModal.city" placeholder="City / Town" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">State *</label>
                        <select x-model="addressModal.state" class="w-full rounded-lg border-gray-300 text-xs focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">-- Select State --</option>
                            @foreach(\App\Support\IndianStates::namesAlphabetical() as $st)
                                <option value="{{ $st }}">{{ $st }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">PIN Code</label>
                        <input type="text" x-model="addressModal.pincode" placeholder="6-digit PIN" class="w-full rounded-lg border-gray-300 text-xs font-mono focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block font-semibold text-slate-700 mb-1">GSTIN (Optional)</label>
                        <input type="text" x-model="addressModal.gstin" placeholder="15-digit GSTIN" class="w-full rounded-lg border-gray-300 text-xs font-mono uppercase focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="sm:col-span-2 pt-1">
                        <label class="inline-flex items-center gap-2 cursor-pointer font-medium text-slate-700">
                            <input type="checkbox" x-model="addressModal.is_default_delivery" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                            <span>Set as default delivery address for this customer</span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="bg-slate-50 px-5 py-3 flex items-center justify-end gap-2.5 border-t border-slate-200">
                <button type="button" @click="addressModal.open = false" class="px-4 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 rounded-lg transition">Cancel</button>
                <button type="button" @click="saveNewAddress()" :disabled="addressModal.saving" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg shadow-sm transition disabled:opacity-50">
                    <span x-show="!addressModal.saving">Save Address</span>
                    <span x-show="addressModal.saving">Saving...</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

