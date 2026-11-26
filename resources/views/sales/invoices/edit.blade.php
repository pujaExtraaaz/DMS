@extends('layouts.dms')
@section('title', 'Edit Tax Invoice - ' . $invoice->invoice_no)

@section('content')
@php
    $company = ($company ?? null)
        ?: ($invoice->selling_company ?? $invoice->company ?? \App\Domains\Organization\Models\Company::resolveCurrent() ?? \App\Domains\Organization\Models\Company::query()->first());
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

    $isStatutoryLocked = (bool) ($isStatutoryLocked ?? $invoice->isStatutoryLocked());
    $isEInvoiceGenerated = (bool) ($isEInvoiceGenerated ?? $invoice->isEInvoiceGenerated());
    $isEWayBillGenerated = (bool) ($isEWayBillGenerated ?? $invoice->isEWayBillGenerated());

    $canEditProduct = auth()->user() && (
        auth()->user()->hasAnyRole(['super-admin', 'owner', 'client-admin']) ||
        auth()->user()->can('products.manage') ||
        auth()->user()->can('products.edit') ||
        auth()->user()->can('masters.manage')
    );

    $productsJson = ($products ?? collect())->map(function ($p) {
        $isTracked = (bool) ($p->isSerialTracked() || $p->productType?->track_serial);
        $allowedUoms = $p->getAllowedUoms();
        return [
            'id' => $p->id,
            'name' => $p->name,
            'brand_id' => $p->brand_id,
            'base_uom_id' => $p->base_uom_id ? (string) $p->base_uom_id : '',
            'base_uom_code' => $p->baseUom?->code ?? $p->baseUom?->name ?? 'PCS',
            'unit' => $p->baseUom?->code ?? $p->baseUom?->name ?? 'PCS',
            'allowed_uoms' => $allowedUoms->values()->toArray(),
            'allow_unit_change' => $allowedUoms->count() > 1,
            'selling_price' => (float) $p->selling_price,
            'tax_rate' => (float) ($p->tax_rate ?? 0),
            'tracking_type' => $isTracked ? 'serial' : ($p->tracking_type ?? 'none'),
            'selling_discount_type' => $p->selling_discount_type ?? 'percent',
            'selling_discount_value' => (float) ($p->selling_discount_value ?? 0),
            'hsn_code' => $p->hsn_code ?? '',
        ];
    })->values()->toArray();

    $uomsJson = ($uoms ?? collect())->map(fn($u) => [
        'id' => (string) $u->id,
        'code' => $u->code,
        'name' => $u->name,
    ])->values()->toArray();

    $linesJson = $invoice->items->map(function ($it, $idx) use ($invoice) {
        $p = $it->product;
        $isTracked = (bool) ($p?->isSerialTracked() || $p?->productType?->track_serial);
        $allowedUoms = $p ? $p->getAllowedUoms() : collect();
        $serials = is_array($it->serial_numbers)
            ? implode(', ', $it->serial_numbers)
            : (filled($it->serial_numbers) ? (string) $it->serial_numbers : '');

        $isGeneral = (bool) ($it->is_general || ($it->product_id === null && filled($it->description)));

        return [
            '_key' => $idx + 1,
            'id' => $it->id,
            'is_general' => $isGeneral,
            'description' => $it->description ?? '',
            'product_id' => $it->product_id ? (string) $it->product_id : '',
            'uom_id' => $it->uom_id ? (string) $it->uom_id : '',
            'unit' => $it->uom?->code ?? $it->uom?->name ?? ($p?->baseUom?->code ?? 'PCS'),
            'allowed_uoms' => $isGeneral
                ? (($it->uom_id) ? [['id' => (string) $it->uom_id, 'code' => $it->uom?->code ?? 'NOS', 'name' => $it->uom?->name ?? 'Nos']] : [])
                : $allowedUoms->values()->toArray(),
            'allow_unit_change' => $isGeneral ? false : $allowedUoms->count() > 1,
            'tracking_type' => $isGeneral ? 'none' : ($isTracked ? 'serial' : ($p?->tracking_type ?? 'none')),
            'quantity' => (float) $it->quantity,
            'ordered_quantity' => null,
            'unit_price' => (float) $it->unit_price,
            'discount_type' => $it->discount_type ?? 'flat',
            'discount_value' => (float) ($it->discount_value ?? 0),
            'tax_rate' => (float) ($it->tax_rate ?? $p?->tax_rate ?? 0),
            'batch_no' => $isGeneral ? '' : ($it->batch_no ?? ''),
            'serial_numbers' => $isGeneral ? '' : $serials,
            'taxable_amount' => (float) $it->taxable_amount,
            'tax_amount' => (float) $it->tax_amount,
            'line_total' => (float) $it->line_total,
        ];
    })->values()->toArray();
@endphp

<x-ui.page-header :title="'Edit Tax Invoice - ' . $invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">View Invoice</x-ui.button>
        <x-ui.button variant="secondary" :href="route('invoices.index')">Back to Invoices</x-ui.button>
    </x-slot>
</x-ui.page-header>

@if($isStatutoryLocked)
    <div class="mb-6 p-4 rounded-xl border border-amber-300 bg-amber-50 text-amber-900 shadow-sm">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <div class="space-y-1 text-xs">
                <h4 class="font-bold text-sm text-amber-950 flex items-center gap-2">
                    <span>Statutory Lock Active — Product &amp; Pricing Modification Restricted</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold bg-amber-200 text-amber-900 uppercase">
                        Legal Restriction
                    </span>
                </h4>
                <p class="text-amber-800 leading-relaxed">
                    This tax invoice has statutory compliance documents generated:
                    @if($isEInvoiceGenerated)
                        <span class="font-bold text-amber-950">E-Invoice (IRN: {{ $invoice->eInvoice?->irn ?? 'Generated' }})</span>
                    @endif
                    @if($isEInvoiceGenerated && $isEWayBillGenerated) and @endif
                    @if($isEWayBillGenerated)
                        <span class="font-bold text-amber-950">E-Way Bill (No: {{ $invoice->eWayBill?->eway_bill_no ?: ($invoice->eway_bill_no ?: 'Generated') }})</span>
                    @endif.
                    Under statutory GST regulations, once statutory documents are registered with the government portal, invoice line items, pricing, discounts, and tax values cannot be modified directly.
                </p>
                <div class="pt-2 flex flex-wrap items-center gap-3 text-xs border-t border-amber-200/80 mt-2">
                    <span class="font-bold text-amber-950">Applicable Statutory Correction Options:</span>
                    @if($isEInvoiceGenerated)
                        <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-700 hover:text-indigo-900 underline">
                            Cancel E-Invoice IRN
                        </a>
                    @endif
                    @if($isEWayBillGenerated)
                        @if($isEInvoiceGenerated)<span class="text-amber-400">•</span>@endif
                        <a href="{{ route('invoices.show', $invoice) }}" class="inline-flex items-center gap-1 font-semibold text-indigo-700 hover:text-indigo-900 underline">
                            Cancel E-Way Bill
                        </a>
                    @endif
                    <span class="text-amber-400">•</span>
                    <a href="{{ route('books.tally.invoices.credit-note.create') }}" class="inline-flex items-center gap-1 font-semibold text-indigo-700 hover:text-indigo-900 underline">
                        Issue Credit Note / Debit Note
                    </a>
                </div>
            </div>
        </div>
    </div>
@endif

<script>
function editSalesInvoiceForm() {
    return {
          invoiceId: '{{ $invoice->id }}',
          canEditProduct: {{ $canEditProduct ? 'true' : 'false' }},
          updatedProduct: @js($updatedProduct ?? null),
          targetRow: @js($targetRow ?? request('target_row')),
          savedDraft: @js($savedDraft ?? null),

          customerId: '{{ $invoice->customer_id }}',
          salespersonId: @js(old('salesperson_id', (string)($invoice->salesperson_id ?? ''))),
          invoiceDate: '{{ $invoice->invoice_date?->format('Y-m-d') }}',
          deliveryState: '{{ $invoice->delivery_state }}',
          companyState: @js(\App\Domains\Master\Services\GstTaxCalculationService::resolveSellerState($company)),
          isIntraState() {
              if (!this.companyState || !this.deliveryState) return true;
              return this.companyState.trim().toLowerCase() === this.deliveryState.trim().toLowerCase();
          },
          notes: @js($invoice->notes ?? ''),

          isStatutoryLocked: {{ $isStatutoryLocked ? 'true' : 'false' }},
          isEInvoiceGenerated: {{ $isEInvoiceGenerated ? 'true' : 'false' }},
          isEWayBillGenerated: {{ $isEWayBillGenerated ? 'true' : 'false' }},
          products: @js($productsJson),
          uoms: @js($uomsJson),
          lines: @js($linesJson),
          _seq: {{ count($linesJson) }},
          priceType: '{{ old('price_type', $invoice->price_type ?? 'exclusive') }}',
          universalType: '{{ old('universal_discount_type', $invoice->universal_discount_type ?? 'flat') }}',
          universalValue: {{ old('universal_discount_value', (float)($invoice->universal_discount_value ?? 0)) }},
          roundOff: {{ old('round_off', (float)($invoice->round_off ?? 0)) }},
          isManualRoundOff: {{ old('round_off') !== null ? 'true' : 'false' }},

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
                      this.recalc();
                  }
                  this.syncConsigneeFromAddress(addr);
              } else {
                  this.onShippingAddressChange();
              }
          },

          // Line items management
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
                      id: String(p.base_uom_id || ''),
                      code: p.unit || p.base_uom_code || 'PCS',
                      name: p.unit || p.base_uom_code || 'PCS',
                      is_base: true
                  }];

              const allowUnitChange = Boolean(p.allow_unit_change ?? (allowedUoms.length > 1));

              let matched = requestedUomId ? allowedUoms.find(u => String(u.id) === String(requestedUomId)) : null;
              if (!matched) {
                  matched = allowedUoms.find(u => u.is_base) || allowedUoms[0];
              }

              return {
                  uom_id: matched ? String(matched.id) : (p.base_uom_id ? String(p.base_uom_id) : ''),
                  unit: matched ? (matched.code || matched.name) : (p.unit || 'PCS'),
                  allowed_uoms: allowedUoms,
                  allow_unit_change: allowUnitChange,
              };
          },

          defaultNosUom: @js(($defaultNosUom ?? null) ? ['id' => (string) $defaultNosUom->id, 'code' => $defaultNosUom->code, 'name' => $defaultNosUom->name] : null),

          createBlankItem(overrides = {}) {
              return Object.assign({
                  _key: ++this._seq,
                  is_general: false,
                  description: '',
                  product_id: '',
                  uom_id: '',
                  unit: '—',
                  allowed_uoms: [],
                  allow_unit_change: false,
                  tracking_type: 'none',
                  quantity: 1,
                  ordered_quantity: null,
                  unit_price: 0,
                  discount_type: 'percent',
                  discount_value: 0,
                  tax_rate: 0,
                  batch_no: '',
                  serial_numbers: '',
              }, overrides);
          },

          addItem() {
              if (this.isStatutoryLocked) return;
              this.lines.push(this.createBlankItem());
              this.recalc();
          },

          addGeneralEntry() {
              if (this.isStatutoryLocked) return;
              const nos = this.defaultNosUom;
              this.lines.push(this.createBlankItem({
                  is_general: true,
                  description: '',
                  product_id: '',
                  uom_id: nos ? String(nos.id) : '',
                  unit: nos ? (nos.code || nos.name || 'NOS') : 'NOS',
                  allowed_uoms: nos ? [{ id: String(nos.id), code: nos.code || 'NOS', name: nos.name || 'Nos' }] : [],
                  allow_unit_change: false,
              }));
              this.recalc();
          },

          removeItem(index) {
              if (this.isStatutoryLocked) return;
              if (this.lines.length > 1) {
                  this.lines.splice(index, 1);
                  this.recalc();
              }
          },

          isSerialTracked(row) {
              if (!row || !row.tracking_type) return false;
              const tt = String(row.tracking_type).toLowerCase();
              return tt === 'serial' || tt.includes('serial');
          },

          onProductChange(index) {
              if (this.isStatutoryLocked) return;
              const row = this.lines[index];
              if (!row) return;
              const prodId = row.product_id;
              if (!prodId) {
                  row.uom_id = '';
                  row.unit = '—';
                  row.allowed_uoms = [];
                  row.allow_unit_change = false;
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
                  row.unit_price = Number(p.selling_price) || 0;
                  row.discount_type = p.selling_discount_type || 'percent';
                  row.discount_value = Number(p.selling_discount_value) || 0;
                  row.tax_rate = Number(p.tax_rate) || 0;
              }
              this.recalc();
          },

          onUomChange(index) {
              if (this.isStatutoryLocked) return;
              const row = this.lines[index];
              if (!row) return;
              const selected = (row.allowed_uoms || []).find(u => String(u.id) === String(row.uom_id));
              if (selected) {
                  row.unit = selected.code || selected.name;
              }
              this.recalc();
          },

          init() {
              const urlParams = new URLSearchParams(window.location.search);
              const returnedUpdatedProductId = (this.updatedProduct && this.updatedProduct.id ? String(this.updatedProduct.id) : '') || urlParams.get('updated_product_id');
              const isReturnedFromProductEdit = Boolean(returnedUpdatedProductId) || urlParams.get('returned_from') === 'product_edit';
              const hasServerOldValues = @json(old('_token') !== null);

              let draft = this.savedDraft || null;
              if (!draft) {
                  try {
                      const raw = sessionStorage.getItem('dms_sales_invoice_edit_draft_' + this.invoiceId);
                      if (raw) draft = JSON.parse(raw);
                  } catch (e) {
                      draft = null;
                  }
              }

              if (draft && (isReturnedFromProductEdit || !hasServerOldValues)) {
                  if (draft.customerId) this.customerId = String(draft.customerId);
                  if (draft.salespersonId !== undefined) this.salespersonId = String(draft.salespersonId);
                  if (draft.invoiceDate) this.invoiceDate = draft.invoiceDate;
                  if (draft.deliveryState) this.deliveryState = draft.deliveryState;
                  if (draft.notes !== undefined) this.notes = draft.notes;
                  if (draft.priceType) this.priceType = draft.priceType;
                  if (draft.universalType) this.universalType = draft.universalType;
                  if (draft.universalValue !== undefined) this.universalValue = Number(draft.universalValue);
                  if (draft.roundOff !== undefined) this.roundOff = Number(draft.roundOff);
                  if (draft.isManualRoundOff !== undefined) this.isManualRoundOff = Boolean(draft.isManualRoundOff);

                  if (draft.selectedBillingId !== undefined) this.selectedBillingId = draft.selectedBillingId;
                  if (draft.selectedShippingId !== undefined) this.selectedShippingId = draft.selectedShippingId;
                  if (draft.billingSnapshot !== undefined) this.billingSnapshot = draft.billingSnapshot;
                  if (draft.shippingSnapshot !== undefined) this.shippingSnapshot = draft.shippingSnapshot;
                  if (draft.sameAsBilling !== undefined) this.sameAsBilling = Boolean(draft.sameAsBilling);

                  if (draft.deliveryNoteNo !== undefined) this.deliveryNoteNo = draft.deliveryNoteNo;
                  if (draft.deliveryNoteNa !== undefined) this.deliveryNoteNa = Boolean(draft.deliveryNoteNa);
                  if (draft.dispatchDocNo !== undefined) this.dispatchDocNo = draft.dispatchDocNo;
                  if (draft.dispatchedThrough !== undefined) this.dispatchedThrough = draft.dispatchedThrough;
                  if (draft.destination !== undefined) this.destination = draft.destination;
                  if (draft.carrierName !== undefined) this.carrierName = draft.carrierName;
                  if (draft.lrRrNo !== undefined) this.lrRrNo = draft.lrRrNo;
                  if (draft.lrRrDate !== undefined) this.lrRrDate = draft.lrRrDate;
                  if (draft.motorVehicleNo !== undefined) this.motorVehicleNo = draft.motorVehicleNo;
                  if (draft.transportMode !== undefined) this.transportMode = draft.transportMode;
                  if (draft.vehicleType !== undefined) this.vehicleType = draft.vehicleType;
                  if (draft.distanceKm !== undefined) this.distanceKm = draft.distanceKm;
                  if (draft.transporterId !== undefined) this.transporterId = draft.transporterId;
                  if (draft.transporterName !== undefined) this.transporterName = draft.transporterName;
                  if (draft.billToPlace !== undefined) this.billToPlace = draft.billToPlace;
                  if (draft.shipToPlace !== undefined) this.shipToPlace = draft.shipToPlace;
                  if (draft.ewayBillRequired !== undefined) this.ewayBillRequired = draft.ewayBillRequired;
                  if (draft.ewayBillNo !== undefined) this.ewayBillNo = draft.ewayBillNo;
                  if (draft.ewayBillDate !== undefined) this.ewayBillDate = draft.ewayBillDate;
                  if (draft.ewaySubType !== undefined) this.ewaySubType = draft.ewaySubType;
                  if (draft.ewayDocType !== undefined) this.ewayDocType = draft.ewayDocType;
                  if (draft.orderNo !== undefined) this.orderNo = draft.orderNo;
                  if (draft.paymentTerms !== undefined) this.paymentTerms = draft.paymentTerms;
                  if (draft.otherReferences !== undefined) this.otherReferences = draft.otherReferences;
                  if (draft.termsOfDelivery !== undefined) this.termsOfDelivery = draft.termsOfDelivery;

                  if (Array.isArray(draft.lines) && draft.lines.length && !this.isStatutoryLocked) {
                      this.lines = draft.lines.map((it, idx) => {
                          const isGeneral = Boolean(it.is_general);
                          const p = isGeneral ? null : this.products.find(prod => String(prod.id) === String(it.product_id));
                          const nos = this.defaultNosUom;
                          const uomInfo = isGeneral
                              ? {
                                  uom_id: it.uom_id ? String(it.uom_id) : (nos ? String(nos.id) : ''),
                                  unit: it.unit || (nos ? (nos.code || nos.name || 'NOS') : 'NOS'),
                                  allowed_uoms: nos ? [{ id: String(nos.id), code: nos.code || 'NOS', name: nos.name || 'Nos' }] : [],
                                  allow_unit_change: false,
                              }
                              : this.resolveLineUom(it.product_id, it.uom_id, it.unit);
                          return {
                              _key: ++this._seq,
                              id: it.id || null,
                              is_general: isGeneral,
                              description: it.description || '',
                              product_id: isGeneral ? '' : (it.product_id ? String(it.product_id) : ''),
                              uom_id: uomInfo.uom_id,
                              unit: uomInfo.unit,
                              allowed_uoms: uomInfo.allowed_uoms,
                              allow_unit_change: uomInfo.allow_unit_change,
                              tracking_type: isGeneral ? 'none' : (it.tracking_type || (p ? p.tracking_type : 'none')),
                              quantity: Number(it.quantity) || 1,
                              ordered_quantity: null,
                              unit_price: Number(it.unit_price) || 0,
                              discount_type: it.discount_type || 'flat',
                              discount_value: Number(it.discount_value) || 0,
                              tax_rate: (it.tax_rate !== undefined && it.tax_rate !== null) ? Number(it.tax_rate) : (p ? Number(p.tax_rate) : 0),
                              batch_no: isGeneral ? '' : (it.batch_no || ''),
                              serial_numbers: isGeneral ? '' : (it.serial_numbers || ''),
                          };
                      });
                  }
              }

              // Update product catalog list with updated product if returned from product edit
              if (this.updatedProduct && this.updatedProduct.id) {
                  const uId = String(this.updatedProduct.id);
                  const existingIdx = this.products.findIndex(p => String(p.id) === uId);
                  if (existingIdx !== -1) {
                      this.products[existingIdx] = { ...this.products[existingIdx], ...this.updatedProduct };
                  } else {
                      this.products.push(this.updatedProduct);
                  }

                  if (!this.isStatutoryLocked) {
                      this.lines.forEach(row => {
                          if (String(row.product_id) === uId) {
                              row.tracking_type = this.updatedProduct.tracking_type || 'none';
                              // If tax rate changed on product master, update line tax rate
                              if (this.updatedProduct.tax_rate !== undefined) {
                                  row.tax_rate = Number(this.updatedProduct.tax_rate) || 0;
                              }
                              row.allowed_uoms = this.updatedProduct.allowed_uoms || [];
                              row.allow_unit_change = Boolean(this.updatedProduct.allow_unit_change);
                              if (this.updatedProduct.unit && (!row.unit || row.unit === '—')) {
                                  row.unit = this.updatedProduct.unit;
                                  row.uom_id = this.updatedProduct.base_uom_id;
                              }
                              if (!this.isSerialTracked(row)) {
                                  row.serial_numbers = '';
                              }
                          }
                      });
                  }
              }

              this.recalc();

              // Clear draft after restoring
              try {
                  sessionStorage.removeItem('dms_sales_invoice_edit_draft_' + this.invoiceId);
              } catch (e) {}

              // Clean query parameters from URL so refreshes don't re-trigger
              if (isReturnedFromProductEdit && window.history && window.history.replaceState) {
                  window.history.replaceState({}, document.title, window.location.pathname);
              }
          },

          productEditUrl(productId, targetRowIndex = null) {
              if (!productId) return '#';
              let url = @json(route('masters.products.edit', ['product' => '__PID__'])).replace('__PID__', encodeURIComponent(productId));
              url += (url.includes('?') ? '&' : '?') + 'return_to=invoices.edit&return_url=' + encodeURIComponent(@json(route('invoices.edit', $invoice))) + '&invoice_id=' + encodeURIComponent(this.invoiceId);
              if (targetRowIndex !== null && targetRowIndex !== undefined) {
                  url += '&target_row=' + encodeURIComponent(targetRowIndex);
              }
              return url;
          },

          getInvoiceDraftState(targetRowIndex = null) {
              return {
                  customerId: this.customerId || '',
                  salespersonId: this.salespersonId || '',
                  invoiceDate: this.invoiceDate || '',
                  deliveryState: this.deliveryState || '',
                  notes: this.notes || '',
                  priceType: this.priceType || 'exclusive',
                  universalType: this.universalType || 'flat',
                  universalValue: this.universalValue || 0,
                  roundOff: this.roundOff || 0,
                  isManualRoundOff: this.isManualRoundOff,
                  selectedBillingId: this.selectedBillingId || '',
                  selectedShippingId: this.selectedShippingId || '',
                  billingSnapshot: this.billingSnapshot || '',
                  shippingSnapshot: this.shippingSnapshot || '',
                  sameAsBilling: Boolean(this.sameAsBilling),
                  deliveryNoteNo: this.deliveryNoteNo || '',
                  deliveryNoteNa: Boolean(this.deliveryNoteNa),
                  dispatchDocNo: this.dispatchDocNo || '',
                  dispatchedThrough: this.dispatchedThrough || '',
                  destination: this.destination || '',
                  carrierName: this.carrierName || '',
                  lrRrNo: this.lrRrNo || '',
                  lrRrDate: this.lrRrDate || '',
                  motorVehicleNo: this.motorVehicleNo || '',
                  transportMode: this.transportMode || '',
                  vehicleType: this.vehicleType || '',
                  distanceKm: this.distanceKm || 0,
                  transporterId: this.transporterId || '',
                  transporterName: this.transporterName || '',
                  billToPlace: this.billToPlace || '',
                  shipToPlace: this.shipToPlace || '',
                  ewayBillRequired: this.ewayBillRequired || 'auto',
                  ewayBillNo: this.ewayBillNo || '',
                  ewayBillDate: this.ewayBillDate || '',
                  ewaySubType: this.ewaySubType || 'Supply',
                  ewayDocType: this.ewayDocType || 'Tax Invoice',
                  orderNo: this.orderNo || '',
                  paymentTerms: this.paymentTerms || '',
                  otherReferences: this.otherReferences || '',
                  termsOfDelivery: this.termsOfDelivery || '',
                  targetRow: targetRowIndex,
                  lines: (this.lines || []).map(r => ({
                      id: r.id || null,
                      is_general: Boolean(r.is_general),
                      description: r.description || '',
                      product_id: r.product_id || '',
                      uom_id: r.uom_id || '',
                      unit: r.unit || '',
                      quantity: Number(r.quantity) || 1,
                      unit_price: Number(r.unit_price) || 0,
                      discount_type: r.discount_type || 'flat',
                      discount_value: Number(r.discount_value) || 0,
                      tax_rate: (r.tax_rate !== undefined && r.tax_rate !== null) ? Number(r.tax_rate) : 0,
                      batch_no: r.batch_no || '',
                      serial_numbers: r.serial_numbers || '',
                      tracking_type: r.tracking_type || 'none',
                      allowed_uoms: r.allowed_uoms || [],
                      allow_unit_change: Boolean(r.allow_unit_change),
                  })),
              };
          },

          saveDraftAndRedirectToEditProduct(event, productId, targetRowIndex = null) {
              if (event) {
                  event.preventDefault();
              }

              const draft = this.getInvoiceDraftState(targetRowIndex);

              try {
                  sessionStorage.setItem('dms_sales_invoice_edit_draft_' + this.invoiceId, JSON.stringify(draft));
              } catch (e) {
                  console.error('Failed to save invoice edit draft to sessionStorage', e);
              }

              const targetUrl = this.productEditUrl(productId, targetRowIndex);

              const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ||
                                document.querySelector('input[name="_token"]')?.value;

              if (csrfToken && window.fetch) {
                  const controller = new AbortController();
                  const timeoutId = setTimeout(() => controller.abort(), 1200);
                  fetch(@json(route('invoices.draft.save')), {
                      method: 'POST',
                      headers: {
                          'Content-Type': 'application/json',
                          'Accept': 'application/json',
                          'X-CSRF-TOKEN': csrfToken
                      },
                      body: JSON.stringify({ draft, invoice_id: this.invoiceId }),
                      signal: controller.signal
                  }).finally(() => {
                      clearTimeout(timeoutId);
                      window.location.href = targetUrl;
                  });
              } else {
                  window.location.href = targetUrl;
              }
          },

          clearDraftOnSubmit() {
              try {
                  sessionStorage.removeItem('dms_sales_invoice_edit_draft_' + this.invoiceId);
              } catch (e) {}
          },

          // Mathematical calculations
          lineGross(row) {
              const qty = Number(row.quantity) || 0;
              const entered = Number(row.unit_price) || 0;
              return Math.max(0, qty * entered);
          },

          lineDiscount(row) {
              const g = this.lineGross(row);
              const v = Number(row.discount_value) || 0;
              if (v <= 0) return 0;
              return row.discount_type === 'flat' ? Math.min(g, v * Math.max(Number(row.quantity) || 1, 1)) : (g * v / 100);
          },

          lineTaxable(row) {
              const g = this.lineGross(row);
              const d = this.lineDiscount(row);
              const net = Math.max(0, g - d);
              const rate = Number(row.tax_rate) || 0;

              if (this.priceType === 'inclusive') {
                  return rate > 0 ? (net * 100 / (100 + rate)) : net;
              }
              return net;
          },

          lineTax(row) {
              const taxable = this.lineTaxable(row);
              const rate = Number(row.tax_rate) || 0;
              return (taxable * rate) / 100;
          },

          lineTotal(row) {
              if (this.priceType === 'inclusive') {
                  return Math.max(0, this.lineGross(row) - this.lineDiscount(row));
              }
              return this.lineTaxable(row) + this.lineTax(row);
          },

          subTotal() {
              return this.lines.reduce((sum, r) => sum + this.lineGross(r), 0);
          },

          netTaxableSubtotal() {
              return this.lines.reduce((sum, r) => sum + this.lineTaxable(r), 0);
          },

          itemDiscountTotal() {
              return this.lines.reduce((sum, r) => sum + this.lineDiscount(r), 0);
          },

          universalDiscountAmount() {
              const sub = this.netTaxableSubtotal();
              const v = Number(this.universalValue) || 0;
              if (v <= 0) return 0;
              return this.universalType === 'flat' ? Math.min(sub, v) : (sub * v / 100);
          },

          totalTaxAmount() {
              const netSub = this.netTaxableSubtotal();
              const uDisc = this.universalDiscountAmount();
              if (uDisc <= 0 || netSub <= 0) {
                  return this.lines.reduce((sum, r) => sum + this.lineTax(r), 0);
              }
              const factor = Math.max(0, (netSub - uDisc) / netSub);
              return this.lines.reduce((sum, r) => sum + (this.lineTax(r) * factor), 0);
          },

          cgstTotal() {
              if (!this.isIntraState()) return 0;
              return this.totalTaxAmount() / 2;
          },

          sgstTotal() {
              if (!this.isIntraState()) return 0;
              return this.totalTaxAmount() / 2;
          },

          igstTotal() {
              if (this.isIntraState()) return 0;
              return this.totalTaxAmount();
          },

          totalBeforeRoundOff() {
              const netTaxable = Math.max(0, this.netTaxableSubtotal() - this.universalDiscountAmount());
              return netTaxable + this.totalTaxAmount();
          },

          autoRoundOff() {
              this.isManualRoundOff = false;
              const total = this.totalBeforeRoundOff();
              this.roundOff = Math.round((Math.round(total) - total) * 100) / 100;
          },

          grandTotal() {
              const total = this.totalBeforeRoundOff();
              const r = Number(this.roundOff) || 0;
              return Math.max(0, Math.round((total + r) * 100) / 100);
          },

          recalc() {
              if (!this.isManualRoundOff) {
                  const total = this.totalBeforeRoundOff();
                  this.roundOff = Math.round((Math.round(total) - total) * 100) / 100;
              }
          },

          setPriceType(type) {
              if (this.isStatutoryLocked) return;
              if (this.priceType === type) return;
              this.priceType = type;
              this.recalc();
          },

          formatCurrency(n) {
              return '₹' + Number(n || 0).toLocaleString('en-IN', {
                  minimumFractionDigits: 2,
                  maximumFractionDigits: 2
              });
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
    };
}
</script>

<form method="POST" action="{{ route('invoices.update', $invoice) }}"
      @submit="clearDraftOnSubmit()"
      x-data="editSalesInvoiceForm()"
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

            {{-- Delivery State Searchable Dropdown --}}
            <x-ui.state-dropdown name="delivery_state"
                                 id="edit_delivery_state"
                                 label="Delivery State"
                                 model="deliveryState"
                                 :value="$invoice->delivery_state"
                                 placeholder="Search or select delivery state..." />
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

    {{-- Line Items Section --}}
    @if($isStatutoryLocked)
        {{-- Read-Only View when Statutory Documents are Generated --}}
        <x-ui.card padding="false">
            <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-amber-50/50">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-bold text-slate-800">Invoice Items ({{ $invoice->items->count() }})</h3>
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                        <svg class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Legally Locked (Statutory Generated)
                    </span>
                </div>
                <p class="text-xs text-amber-800 italic">
                    Product and pricing fields are read-only because statutory document(s) have been generated.
                </p>
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
                                <td class="p-3 font-medium text-slate-800">
                                    <div class="flex items-center justify-between gap-2">
                                        <div>
                                            <div>
                                                {{ $it->product?->name ?? $it->description ?? $it->item_name }}
                                                @if($it->is_general ?? false)
                                                    <span class="ml-1 inline-flex items-center rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-semibold text-amber-800">General</span>
                                                @endif
                                            </div>
                                            @if($it->product?->code)
                                                <div class="text-[10px] text-slate-400">Code: {{ $it->product->code }}</div>
                                            @endif
                                        </div>
                                        @if($it->product_id && $canEditProduct)
                                            <a href="{{ route('masters.products.edit', ['product' => $it->product_id, 'return_to' => 'invoices.edit', 'return_url' => route('invoices.edit', $invoice), 'invoice_id' => $invoice->id, 'target_row' => $idx]) }}"
                                               class="font-semibold text-amber-600 hover:text-amber-800 inline-flex items-center gap-1 text-[11px] transition-colors shrink-0"
                                               title="Edit this product in Product Master">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                Edit Product
                                            </a>
                                        @endif
                                    </div>
                                    @php
                                        $serials = is_array($it->serial_numbers) ? $it->serial_numbers : (filled($it->serial_numbers) ? explode(',', $it->serial_numbers) : []);
                                        $serials = array_filter(array_map('trim', $serials));
                                    @endphp
                                    @if(count($serials) > 0)
                                        <div class="mt-1 text-[11px] font-mono text-amber-700">
                                            <span class="font-bold">S/N:</span> {{ implode(', ', $serials) }}
                                        </div>
                                    @endif
                                </td>
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
    @else
        {{-- Interactive Editable Line Items Table when neither E-Invoice nor E-Way Bill is generated --}}
        <x-ui.card padding="false">
            <div class="p-4 border-b border-slate-200 flex flex-wrap items-center justify-between gap-3 bg-slate-50">
                <div class="space-y-0.5">
                    <div class="flex items-center gap-3">
                        <h3 class="text-sm font-bold text-slate-800">Invoice Items &amp; Pricing</h3>
                        {{-- Price Type Toggle --}}
                        <div class="inline-flex rounded-lg p-0.5 bg-slate-200 text-xs">
                            <button type="button"
                                    @click="setPriceType('exclusive')"
                                    :class="priceType === 'exclusive' ? 'bg-white text-indigo-700 shadow-2xs font-bold border border-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'"
                                    class="cursor-pointer px-3 py-1 rounded-md transition flex items-center gap-1.5 focus:outline-none">
                                <span class="w-1.5 h-1.5 rounded-full transition-colors" :class="priceType === 'exclusive' ? 'bg-indigo-600' : 'bg-transparent'"></span>
                                Tax Exclusive
                            </button>
                            <button type="button"
                                    @click="setPriceType('inclusive')"
                                    :class="priceType === 'inclusive' ? 'bg-white text-indigo-700 shadow-2xs font-bold border border-slate-200' : 'text-slate-600 hover:text-slate-900 font-medium border border-transparent'"
                                    class="cursor-pointer px-3 py-1 rounded-md transition flex items-center gap-1.5 focus:outline-none">
                                <span class="w-1.5 h-1.5 rounded-full transition-colors" :class="priceType === 'inclusive' ? 'bg-indigo-600' : 'bg-transparent'"></span>
                                Tax Inclusive
                            </button>
                        </div>
                        <input type="hidden" name="price_type" :value="priceType">
                    </div>
                    <p class="text-[11px] text-slate-500">
                        <span x-show="priceType === 'exclusive'">Unit price is taxable base price. Applicable GST is added to calculate total.</span>
                        <span x-show="priceType === 'inclusive'" class="text-indigo-600 font-medium" x-cloak>Unit price already includes GST. Tax is extracted without double taxation.</span>
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="addItem()"
                            class="inline-flex items-center gap-1 rounded-lg bg-indigo-50 px-3.5 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition shadow-2xs">
                        + Add Item
                    </button>
                    <button type="button" @click="addGeneralEntry()"
                            class="inline-flex items-center gap-1 rounded-lg bg-amber-50 px-3.5 py-1.5 text-xs font-semibold text-amber-800 hover:bg-amber-100 transition shadow-2xs border border-amber-200/70">
                        + General Entry
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto border-t border-slate-200">
                <table class="w-full min-w-[1280px] text-left text-xs border-collapse">
                    <thead class="bg-slate-50 text-slate-600 tracking-wider border-b border-slate-200 font-semibold text-[11px]">
                        <tr>
                            <th class="p-3 min-w-[300px]">Product / Description</th>
                            <th class="p-3 min-w-[90px] w-24 text-center">Unit</th>
                            <th class="p-3 min-w-[100px] w-28 text-right">Qty *</th>
                            <th class="p-3 min-w-[130px] w-36 text-right" x-text="priceType === 'inclusive' ? 'Unit Price (Inc GST) *' : 'Unit Price (₹) *'">Unit Price (₹) *</th>
                            <th class="p-3 min-w-[170px] w-44 text-left">Discount</th>
                            <th class="p-3 min-w-[85px] w-24 text-right" x-show="isIntraState()">CGST %</th>
                            <th class="p-3 min-w-[85px] w-24 text-right" x-show="isIntraState()">SGST %</th>
                            <th class="p-3 min-w-[95px] w-28 text-center" x-text="isIntraState() ? 'Total Tax %' : 'IGST %'">Tax Rate / IGST</th>
                            <th class="p-3 min-w-[130px] w-36">Batch No</th>
                            <th class="p-3 min-w-[130px] w-36 text-right">Line Total (₹)</th>
                            <th class="p-3 min-w-[50px] w-14 text-center"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(row, i) in lines" :key="row._key">
                            <tr class="align-top hover:bg-slate-50/50 transition-colors">
                                {{-- Product / General Entry Column --}}
                                <td class="p-3 align-top min-w-[300px]">
                                    <input type="hidden" :name="`items[${i}][is_general]`" :value="row.is_general ? 1 : 0">

                                    <template x-if="row.is_general">
                                        <div>
                                            <div class="flex items-center justify-between gap-2 mb-1">
                                                <span class="text-[11px] font-semibold text-amber-700">General Entry</span>
                                                <span class="text-[10px] font-medium text-slate-400">Non-inventory charge</span>
                                            </div>
                                            <input type="text"
                                                   :name="`items[${i}][description]`"
                                                   x-model="row.description"
                                                   placeholder=""
                                                   required
                                                   class="block w-full rounded-md border-amber-200 text-xs sm:text-sm font-medium text-slate-800 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500 h-[38px] px-3 py-2 bg-amber-50/40">
                                        </div>
                                    </template>

                                    <template x-if="!row.is_general">
                                        <div>
                                    <select :name="`items[${i}][product_id]`"
                                            x-model="row.product_id"
                                            @change="onProductChange(i)"
                                            required
                                            class="block w-full rounded-md border-gray-300 text-xs sm:text-sm font-medium text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 h-[38px] px-3 py-2 bg-white">
                                        <option value="">Select product...</option>
                                        <template x-for="p in products" :key="p.id">
                                            <option :value="p.id" x-text="p.name" :selected="String(p.id) === String(row.product_id)"></option>
                                        </template>
                                    </select>
                                    @if($canEditProduct)
                                        <div class="mt-1 flex items-center justify-between text-[11px]" x-show="canEditProduct && row.product_id">
                                            <template x-if="canEditProduct && row.product_id">
                                                <a :href="productEditUrl(row.product_id, i)"
                                                   @click="saveDraftAndRedirectToEditProduct($event, row.product_id, i)"
                                                   class="font-semibold text-amber-600 hover:text-amber-800 inline-flex items-center gap-1 transition-colors"
                                                   title="Edit this product in Product Master">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                                    Edit Product
                                                </a>
                                            </template>
                                        </div>
                                    @endif

                                    {{-- Serial Number Tracking Block --}}
                                    <div class="mt-2" x-show="isSerialTracked(row)" x-cloak>
                                        <div class="flex items-center justify-between mb-1">
                                            <label class="flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider text-amber-800">
                                                Serial Numbers
                                                <span class="text-[10px] font-bold uppercase text-amber-700 bg-amber-100 px-1.5 py-0.2 rounded">Tracked</span>
                                            </label>
                                            <span class="text-[10px] font-semibold text-slate-500 font-mono"
                                                  x-text="`Expected: ${Math.round(Number(row.quantity) || 1)}`"></span>
                                        </div>
                                        <input type="text"
                                               :name="`items[${i}][serial_numbers]`"
                                               x-model="row.serial_numbers"
                                               :required="isSerialTracked(row)"
                                               placeholder="Enter serial number(s) comma separated"
                                               class="block w-full rounded-md border-gray-300 px-2.5 py-2 text-xs font-mono text-slate-800 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                        <p class="mt-1 text-[10px] text-amber-700 font-medium"
                                           x-text="`Requires ${Math.round(Number(row.quantity) || 1)} serial number(s), comma separated.`"></p>
                                    </div>
                                        </div>
                                    </template>
                                </td>

                                {{-- UOM Column --}}
                                <td class="p-3 align-top min-w-[90px] w-24 text-center">
                                    <template x-if="!row.allow_unit_change">
                                        <div class="relative">
                                            <input type="hidden" :name="`items[${i}][uom_id]`" :value="row.uom_id">
                                            <span class="inline-flex items-center justify-center w-full h-[38px] px-2 text-xs font-semibold text-slate-700 bg-slate-100 border border-slate-200 rounded-md cursor-not-allowed select-none"
                                                  x-text="row.unit || '—'"
                                                  :title="row.product_id ? ('Unit locked to ' + (row.unit || 'configured unit') + ' (single unit configured)') : 'Select a product'">
                                            </span>
                                        </div>
                                    </template>
                                    <template x-if="row.allow_unit_change">
                                        <div>
                                            <select :name="`items[${i}][uom_id]`"
                                                    x-model="row.uom_id"
                                                    @change="onUomChange(i)"
                                                    required
                                                    class="block w-full rounded-md border-gray-300 text-xs font-semibold text-slate-700 h-[38px] px-2 bg-white shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                <template x-for="u in (row.allowed_uoms || [])" :key="u.id">
                                                    <option :value="String(u.id)" x-text="u.code || u.name" :selected="String(u.id) === String(row.uom_id)"></option>
                                                </template>
                                            </select>
                                        </div>
                                    </template>
                                </td>

                                {{-- Quantity Column --}}
                                <td class="p-3 align-top min-w-[100px] w-28">
                                    <input type="number"
                                           step="0.0001"
                                           min="0.0001"
                                           :name="`items[${i}][quantity]`"
                                           x-model.number="row.quantity"
                                           @input="recalc()"
                                           required
                                           class="block w-full h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                </td>

                                {{-- Unit Price Column --}}
                                <td class="p-3 align-top min-w-[130px] w-36">
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           :name="`items[${i}][unit_price]`"
                                           x-model.number="row.unit_price"
                                           @input="recalc()"
                                           required
                                           class="block w-full h-[38px] px-2.5 py-2 text-xs font-mono text-right rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 font-semibold text-slate-900">
                                </td>

                                {{-- Line Discount Column --}}
                                <td class="p-3 align-top min-w-[170px] w-44">
                                    <div class="flex items-center rounded-md shadow-sm border border-gray-300 focus-within:border-indigo-500 focus-within:ring-1 focus-within:ring-indigo-500 bg-white">
                                        <select :name="`items[${i}][discount_type]`"
                                                x-model="row.discount_type"
                                                @change="recalc()"
                                                aria-label="Discount Type"
                                                class="h-[38px] rounded-l-md border-0 bg-slate-50 px-2 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 focus:ring-0 border-r border-gray-200 w-14 shrink-0">
                                            <option value="percent">%</option>
                                            <option value="flat">₹</option>
                                        </select>
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               :name="`items[${i}][discount_value]`"
                                               x-model.number="row.discount_value"
                                               @input="recalc()"
                                               placeholder="0"
                                               aria-label="Discount Value"
                                               class="block w-full min-w-0 h-[38px] border-0 px-2.5 py-2 text-xs font-mono text-right text-slate-900 rounded-r-md focus:ring-0">
                                    </div>
                                    <template x-if="lineDiscount(row) > 0">
                                        <p class="mt-1 text-[10px] text-slate-500 text-right font-mono" x-text="`- ${formatCurrency(lineDiscount(row))}`"></p>
                                    </template>
                                </td>

                                {{-- CGST % Column (Intra-State only) --}}
                                <td class="p-3 align-top min-w-[85px] w-24 text-right" x-show="isIntraState()">
                                    <div class="h-[38px] flex items-center justify-end font-mono text-xs text-slate-700">
                                        <span x-text="((row.tax_rate / 2).toFixed(2) + '%')"></span>
                                    </div>
                                </td>

                                {{-- SGST % Column (Intra-State only) --}}
                                <td class="p-3 align-top min-w-[85px] w-24 text-right" x-show="isIntraState()">
                                    <div class="h-[38px] flex items-center justify-end font-mono text-xs text-slate-700">
                                        <span x-text="((row.tax_rate / 2).toFixed(2) + '%')"></span>
                                    </div>
                                </td>

                                {{-- Tax Rate / IGST % Column --}}
                                <td class="p-3 align-top min-w-[95px] w-28 text-center">
                                    <template x-if="row.is_general">
                                        <input type="number"
                                               step="0.01"
                                               min="0"
                                               max="100"
                                               :name="`items[${i}][tax_rate]`"
                                               x-model.number="row.tax_rate"
                                               @input="recalc()"
                                               class="block w-full h-[38px] px-2 py-2 text-xs font-mono text-center rounded-md border-amber-200 bg-amber-50/40 shadow-sm focus:border-amber-500 focus:ring-1 focus:ring-amber-500">
                                    </template>
                                    <template x-if="!row.is_general">
                                        <div class="h-[38px] flex items-center justify-center font-semibold text-xs text-slate-700">
                                            <input type="hidden" :name="`items[${i}][tax_rate]`" :value="row.tax_rate || 0">
                                            <span x-text="isIntraState() ? ((row.tax_rate || 0).toFixed(2) + '%') : ((row.tax_rate || 0).toFixed(2) + '% (IGST)')"></span>
                                        </div>
                                    </template>
                                </td>

                                {{-- Batch No Column --}}
                                <td class="p-3 align-top min-w-[130px] w-36">
                                    <input type="text"
                                           :name="`items[${i}][batch_no]`"
                                           x-model="row.batch_no"
                                           x-show="!row.is_general"
                                           placeholder="Optional"
                                           class="block w-full h-[38px] px-2.5 py-2 text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                    <div x-show="row.is_general" class="h-[38px] flex items-center justify-center text-[10px] text-slate-400">—</div>
                                </td>

                                {{-- Line Total Column --}}
                                <td class="p-3 align-top min-w-[130px] w-36 text-right">
                                    <div class="h-[38px] flex items-center justify-end font-bold font-mono text-slate-900 text-sm whitespace-nowrap"
                                         x-text="formatCurrency(lineTotal(row))">
                                    </div>
                                </td>

                                {{-- Remove Row Action Column --}}
                                <td class="p-3 align-top min-w-[50px] w-14 text-center">
                                    <div class="h-[38px] flex items-center justify-center">
                                        <button type="button"
                                                @click="removeItem(i)"
                                                x-show="lines.length > 1"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-md text-rose-500 hover:text-rose-700 hover:bg-rose-50 transition cursor-pointer"
                                                title="Remove row">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- Bottom Summary & Totals Block --}}
            <div class="p-4 bg-slate-50/50 border-t border-slate-200 grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
                {{-- Left: Universal Discount --}}
                <div class="space-y-4">
                    <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-3">
                        <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Universal Invoice Discount</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Type</label>
                                <select name="universal_discount_type" x-model="universalType" @change="recalc()"
                                        class="block w-full rounded-md border-gray-300 text-xs">
                                    <option value="flat">Flat ₹</option>
                                    <option value="percent">Percent %</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Discount Value</label>
                                <input type="number" step="0.01" min="0" name="universal_discount_value" x-model.number="universalValue" @input="recalc()"
                                       class="block w-full rounded-md border-gray-300 text-xs font-mono">
                            </div>
                        </div>
                        <p class="text-[11px] text-slate-400">Applied on the invoice taxable subtotal in addition to per-line discounts.</p>
                    </div>
                </div>

                {{-- Right: Live Financial Summary Card --}}
                <div class="bg-white p-4 rounded-lg border border-slate-200 shadow-2xs space-y-3 text-xs lg:max-w-md lg:justify-self-end w-full">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700 border-b pb-2">Invoice Summary</h4>

                    <div class="flex justify-between items-center text-slate-600">
                        <span class="font-medium" x-text="priceType === 'inclusive' ? 'Total Before Tax & Discounts:' : 'Taxable Subtotal:'"></span>
                        <span class="font-mono font-semibold text-slate-800 text-sm" x-text="formatCurrency(subTotal())"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600" x-show="priceType === 'inclusive'" x-cloak>
                        <span class="font-medium">Net Taxable Value:</span>
                        <span class="font-mono font-semibold text-indigo-700 text-sm" x-text="formatCurrency(netTaxableSubtotal())"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600" x-show="itemDiscountTotal() > 0" x-cloak>
                        <span class="font-medium">Item Discounts Total:</span>
                        <span class="font-mono font-semibold text-amber-700 text-sm" x-text="`- ${formatCurrency(itemDiscountTotal())}`"></span>
                    </div>

                    <div class="flex justify-between items-center text-slate-600" x-show="universalDiscountAmount() > 0" x-cloak>
                        <span class="font-medium">Universal Discount:</span>
                        <span class="font-mono font-semibold text-amber-700 text-sm" x-text="`- ${formatCurrency(universalDiscountAmount())}`"></span>
                    </div>

                    {{-- Tax Breakdown --}}
                    <template x-if="isIntraState()">
                        <div class="space-y-1.5 pt-1 border-t border-dashed border-slate-200">
                            <div class="flex justify-between items-center text-slate-500 text-[11px]">
                                <span x-text="priceType === 'inclusive' ? 'Extracted CGST Total:' : 'CGST Total:'">CGST Total:</span>
                                <span class="font-mono" x-text="formatCurrency(cgstTotal())"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-500 text-[11px]">
                                <span x-text="priceType === 'inclusive' ? 'Extracted SGST Total:' : 'SGST Total:'">SGST Total:</span>
                                <span class="font-mono" x-text="formatCurrency(sgstTotal())"></span>
                            </div>
                            <div class="flex justify-between items-center text-slate-700 font-medium">
                                <span x-text="priceType === 'inclusive' ? 'Total Extracted Tax (CGST + SGST):' : 'Total Tax (CGST + SGST):'">Total Tax (CGST + SGST):</span>
                                <span class="font-mono font-semibold text-sm" x-text="formatCurrency(totalTaxAmount())"></span>
                            </div>
                        </div>
                    </template>

                    <template x-if="!isIntraState()">
                        <div class="space-y-1.5 pt-1 border-t border-dashed border-slate-200">
                            <div class="flex justify-between items-center text-slate-700 font-medium">
                                <span x-text="priceType === 'inclusive' ? 'Total Extracted Tax (IGST):' : 'Total Tax (IGST - Inter-State):'">Total Tax (IGST - Inter-State):</span>
                                <span class="font-mono font-semibold text-sm" x-text="formatCurrency(totalTaxAmount())"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Round Off (Editable / Automatic) --}}
                    <div class="flex items-center justify-between text-slate-600 pt-2 border-t border-slate-200">
                        <div class="flex items-center gap-1.5">
                            <span class="font-medium">Round Off (₹):</span>
                            <button type="button" @click="autoRoundOff()" class="text-[10px] text-indigo-600 hover:text-indigo-800 underline" title="Re-calculate automatic round off">Auto</button>
                        </div>
                        <input type="number" step="0.01" min="-500" max="500" name="round_off" x-model.number="roundOff" @input="isManualRoundOff = true; recalc()"
                               placeholder="0.00"
                               class="w-28 min-h-[32px] px-2 py-1 rounded-md border-gray-300 text-xs font-mono text-right shadow-2xs focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>

                    {{-- Grand Total --}}
                    <div class="flex justify-between items-center font-bold text-base text-slate-900 border-t border-slate-200 pt-3 mt-1">
                        <span>Grand Total:</span>
                        <span class="font-mono text-indigo-700 text-xl font-extrabold" x-text="formatCurrency(grandTotal())"></span>
                    </div>
                </div>
            </div>
        </x-ui.card>
    @endif

    {{-- Form Footer Actions --}}
    <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary" class="px-6 py-2.5 text-sm font-semibold shadow-sm">
            Update Invoice
        </x-ui.button>
    </div>
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
</form>
@endsection

