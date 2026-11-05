@extends('layouts.dms')
@section('title', 'Edit Tax Invoice - ' . $invoice->invoice_no)

@section('content')
@php
    $companyState = $company?->state ?? '';

    $customersList = $customers->map(function ($c) {
        $phone = $c->mobile_number ?: ($c->phone ?: '');
        return [
            'id' => $c->id,
            'name' => $c->name,
            'phone' => $phone,
            'label' => $c->name . ($phone !== '' ? ' - ' . $phone : ''),
            'credit_days' => (int) ($c->credit_days ?? 0),
            'state' => $c->shipping_state ?? $c->state ?? '',
        ];
    })->values()->toArray();
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
          transportMode: @js($invoice->transport_mode ?? '1'),
          vehicleType: @js($invoice->vehicle_type ?? 'R'),
          distanceKm: @js($invoice->distance_km ?? 0),
          transporterId: @js($invoice->transporter_id ?? ''),
          transporterName: @js($invoice->transporter_name ?? ''),
          ewayBillRequired: @js($invoice->eway_bill_required ?? 'auto'),

          // Order Details
          orderNo: @js($invoice->order_no ?? ($invoice->order?->order_no ?? '')),
          paymentTerms: @js($invoice->payment_terms ?? ''),
          otherReferences: @js($invoice->other_references ?? $invoice->reference_no ?? ''),
          termsOfDelivery: @js($invoice->terms_of_delivery ?? ''),

          onDeliveryNoteNaToggle() {
              if (this.deliveryNoteNa) {
                  this.deliveryNoteNo = '';
              }
          }
      }"
      class="space-y-6">
    @csrf
    @method('PUT')

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
                            <input type="hidden" name="transport_mode" :value="dispatchedThrough">
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

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Transport Mode
                            </label>
                            <select name="transport_mode" x-model="transportMode"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="1">1 - Road</option>
                                <option value="2">2 - Rail</option>
                                <option value="3">3 - Air</option>
                                <option value="4">4 - Ship</option>
                            </select>
                        </div>

                        {{-- 8. Distance & Vehicle Type --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Distance (in KM)
                            </label>
                            <input type="number"
                                   min="1"
                                   max="9999"
                                   name="distance_km"
                                   x-model.number="distanceKm"
                                   placeholder="E.g. 120"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('distance_km')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Vehicle Type
                            </label>
                            <select name="vehicle_type" x-model="vehicleType"
                                    class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="R">Regular (R)</option>
                                <option value="O">Over Dimensional Cargo (ODC)</option>
                            </select>
                        </div>

                        {{-- 9. Transporter ID & Name --}}
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Transporter ID / GSTIN
                            </label>
                            <input type="text"
                                   name="transporter_id"
                                   x-model="transporterId"
                                   placeholder="15-digit GSTIN or TRANS ID"
                                   maxlength="25"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono">
                            @error('transporter_id')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                Transporter Name
                            </label>
                            <input type="text"
                                   name="transporter_name"
                                   x-model="transporterName"
                                   placeholder="E.g. SafeX Logistics"
                                   class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('transporter_name')
                                <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- 10. E-Way Bill Requirement --}}
                        <div class="sm:col-span-2 pt-2 border-t border-slate-200">
                            <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                                E-Way Bill Requirement
                            </label>
                            <div class="flex flex-wrap items-center gap-4 text-xs">
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="eway_bill_required" value="auto" x-model="ewayBillRequired" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium text-slate-800">Auto (Mandatory if &gt; ₹50,000)</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="eway_bill_required" value="yes" x-model="ewayBillRequired" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium text-slate-800">Yes (Generate)</span>
                                </label>
                                <label class="inline-flex items-center gap-1.5 cursor-pointer">
                                    <input type="radio" name="eway_bill_required" value="no" x-model="ewayBillRequired" class="text-indigo-600 focus:ring-indigo-500">
                                    <span class="font-medium text-slate-800">No (Exempt)</span>
                                </label>
                            </div>
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
@endsection

