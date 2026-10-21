@extends('layouts.dms')
@section('title', $sheet ? 'Edit '.$sheet->number : 'New comparison')
@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="$sheet ? 'Edit '.$sheet->number : 'New comparison sheet'">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="$sheet ? route('purchasing.comparisons.show', $sheet) : route('purchasing.comparisons.index')">Back</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($errors->any())
        <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-800">
            <ul class="list-disc pl-4">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data"
          action="{{ $sheet ? route('purchasing.comparisons.update', $sheet) : route('purchasing.comparisons.store') }}"
          x-data="comparisonForm(@js($state), @js($products))" class="space-y-4">
        @csrf
        @if($sheet) @method('PUT') @endif

        <x-ui.card>
            <div class="grid gap-3 md:grid-cols-3">
                <label class="text-sm">Comparison date
                    <input type="date" name="comparison_date" x-model="comparison_date" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="text-sm">Indent number
                    <input type="text" name="indent_no" x-model="indent_no" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="text-sm">Customer / project
                    <input type="text" name="customer_project" x-model="customer_project" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="text-sm">Required delivery
                    <input type="date" name="required_delivery_date" x-model="required_delivery_date" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
                <label class="text-sm md:col-span-2">Remarks
                    <input type="text" name="remarks" x-model="remarks" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                </label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="mb-3 flex items-center justify-between">
                <h2 class="text-sm font-semibold text-slate-800">Vendors <span class="font-normal text-slate-500">2 to 10</span></h2>
                <button type="button" @click="addVendor()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">+ Vendor</button>
            </div>
            <div class="space-y-4">
                <template x-for="(vendor, vIndex) in vendors" :key="vIndex">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold" x-text="'Vendor ' + (vIndex + 1)"></h3>
                            <button type="button" x-show="vendors.length > 2" @click="removeVendor(vIndex)" class="text-xs font-semibold text-rose-600">Remove</button>
                        </div>
                        <input type="hidden" :name="`vendors[${vIndex}][id]`" :value="vendor.id || ''">
                        <div class="grid gap-3 md:grid-cols-3">
                            <label class="text-sm">Supplier
                                <select :name="`vendors[${vIndex}][supplier_id]`" x-model="vendor.supplier_id" @change="loadSupplier(vIndex)" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Select supplier</option>
                                    @foreach($suppliers as $supplier)
                                        <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm">Contact
                                <input type="text" :name="`vendors[${vIndex}][contact_person]`" x-model="vendor.contact_person" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Mobile
                                <input type="text" :name="`vendors[${vIndex}][mobile]`" x-model="vendor.mobile" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Email
                                <input type="email" :name="`vendors[${vIndex}][email]`" x-model="vendor.email" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">GSTIN
                                <input type="text" :name="`vendors[${vIndex}][gstin]`" x-model="vendor.gstin" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Quotation number
                                <input type="text" :name="`vendors[${vIndex}][quotation_number]`" x-model="vendor.quotation_number" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Quotation date
                                <input type="date" :name="`vendors[${vIndex}][quotation_date]`" x-model="vendor.quotation_date" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Valid until
                                <input type="date" :name="`vendors[${vIndex}][valid_until]`" x-model="vendor.valid_until" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Credit period
                                <input type="text" :name="`vendors[${vIndex}][credit_period]`" x-model="vendor.credit_period" placeholder="Advance, 30 days" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm md:col-span-3">Billing address
                                <textarea :name="`vendors[${vIndex}][billing_address]`" x-model="vendor.billing_address" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                            </label>
                            <label class="text-sm md:col-span-3">Dispatch address
                                <textarea :name="`vendors[${vIndex}][dispatch_address]`" x-model="vendor.dispatch_address" rows="2" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></textarea>
                            </label>
                            <label class="text-sm">Advance
                                <input type="text" :name="`vendors[${vIndex}][advance_payment]`" x-model="vendor.advance_payment" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Replacement
                                <input type="text" :name="`vendors[${vIndex}][replacement_policy]`" x-model="vendor.replacement_policy" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Returns
                                <input type="text" :name="`vendors[${vIndex}][return_policy]`" x-model="vendor.return_policy" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Support
                                <input type="text" :name="`vendors[${vIndex}][installation_support]`" x-model="vendor.installation_support" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Transporter
                                <input type="text" :name="`vendors[${vIndex}][transporter]`" x-model="vendor.transporter" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Who pays freight
                                <select :name="`vendors[${vIndex}][freight_paid_by]`" x-model="vendor.freight_paid_by" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="vendor">Vendor</option>
                                    <option value="company">Company</option>
                                </select>
                            </label>
                            <label class="text-sm">Delivery location
                                <input type="text" :name="`vendors[${vIndex}][delivery_location]`" x-model="vendor.delivery_location" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Credit note
                                <input type="text" :name="`vendors[${vIndex}][credit_limit_note]`" x-model="vendor.credit_limit_note" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" x-model="vendor.partial_delivery"> Partial delivery allowed
                                <input type="hidden" :name="`vendors[${vIndex}][partial_delivery]`" :value="vendor.partial_delivery ? 1 : 0">
                            </label>
                            <label class="text-sm md:col-span-2">Quotation file
                                <input type="file" :name="`vendors[${vIndex}][files][]`" multiple accept=".pdf,.xls,.xlsx,.jpg,.jpeg,.png,.webp" class="mt-1 block w-full text-sm">
                            </label>
                        </div>
                        <template x-if="vendor.files && vendor.files.length">
                            <div class="mt-2 space-y-1 text-xs text-slate-600">
                                <template x-for="file in vendor.files" :key="file.id">
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="remove_files[]" :value="file.id">
                                        <span x-text="'Remove ' + file.name"></span>
                                    </label>
                                </template>
                            </div>
                        </template>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <x-ui.card>
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <h2 class="text-sm font-semibold text-slate-800">Items and quotes</h2>
                <div class="flex items-center gap-2">
                    <select @change="awardAll($event.target.value); $event.target.value = ''" class="rounded-lg border-slate-300 text-xs">
                        <option value="">Award every item to…</option>
                        <template x-for="(vendor, vIndex) in vendors" :key="'award-' + vIndex">
                            <option :value="vIndex" x-text="vendorLabel(vIndex)"></option>
                        </template>
                    </select>
                    <button type="button" @click="addItem()" class="rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700">+ Item</button>
                </div>
            </div>
            <div class="space-y-4">
                <template x-for="(item, iIndex) in items" :key="iIndex">
                    <div class="rounded-lg border border-slate-200 p-3">
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold" x-text="'Item ' + (iIndex + 1)"></h3>
                            <button type="button" x-show="items.length > 1" @click="items.splice(iIndex, 1)" class="text-xs font-semibold text-rose-600">Remove</button>
                        </div>
                        <div class="grid gap-3 md:grid-cols-4">
                            <label class="text-sm md:col-span-2">Product
                                <select x-model="item.product_id" @change="applyProduct(item)" :name="`items[${iIndex}][product_id]`" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Not linked yet</option>
                                    <template x-for="product in products" :key="product.id">
                                        <option :value="String(product.id)" x-text="product.name"></option>
                                    </template>
                                </select>
                            </label>
                            <label class="text-sm md:col-span-2">Item name
                                <input type="text" :name="`items[${iIndex}][item_name]`" x-model="item.item_name" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Category
                                <input type="text" :name="`items[${iIndex}][category_name]`" x-model="item.category_name" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Sub category
                                <input type="text" :name="`items[${iIndex}][subcategory_name]`" x-model="item.subcategory_name" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Brand
                                <input type="text" :name="`items[${iIndex}][brand_name]`" x-model="item.brand_name" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Model / SKU
                                <input type="text" :name="`items[${iIndex}][model_sku]`" x-model="item.model_sku" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Required quantity
                                <input type="number" step="0.01" min="0.01" :name="`items[${iIndex}][required_quantity]`" x-model="item.required_quantity" required class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Unit
                                <select :name="`items[${iIndex}][uom_id]`" x-model="item.uom_id" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Select</option>
                                    @foreach($uoms as $uom)
                                        <option value="{{ $uom->id }}">{{ $uom->name }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-sm md:col-span-2">Description
                                <input type="text" :name="`items[${iIndex}][description]`" x-model="item.description" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                        </div>

                        <div class="mt-3 space-y-3">
                            <template x-for="(quote, qIndex) in item.quotes" :key="qIndex">
                                <div class="rounded-lg bg-slate-50 p-3">
                                    <label class="flex items-center gap-2 text-sm font-semibold">
                                        <input type="checkbox" x-model="quote.is_quoted" @change="if (quote.is_quoted && !quote.quoted_quantity) quote.quoted_quantity = item.required_quantity">
                                        <span x-text="vendorLabel(qIndex) + ' quoted this item'"></span>
                                        <input type="hidden" :name="`items[${iIndex}][quotes][${qIndex}][is_quoted]`" :value="quote.is_quoted ? 1 : 0">
                                    </label>
                                    <div class="mt-2 grid gap-2 md:grid-cols-4" x-show="quote.is_quoted">
                                        <label class="text-xs">Quoted qty
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][quoted_quantity]`" x-model="quote.quoted_quantity" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Basic rate
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][basic_rate]`" x-model="quote.basic_rate" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Discount
                                            <span class="mt-1 flex gap-1">
                                                <select :name="`items[${iIndex}][quotes][${qIndex}][discount_type]`" x-model="quote.discount_type" class="rounded-lg border-slate-300 text-sm">
                                                    <option value="percent">%</option>
                                                    <option value="amount">Amount / unit</option>
                                                </select>
                                                <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][discount_value]`" x-model="quote.discount_value" class="w-full rounded-lg border-slate-300 text-sm">
                                            </span>
                                        </label>
                                        <label class="text-xs">GST %
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][gst_rate]`" x-model="quote.gst_rate" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Freight
                                            <select :name="`items[${iIndex}][quotes][${qIndex}][freight_type]`" x-model="quote.freight_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                                <option value="free">Free</option>
                                                <option value="extra">Extra</option>
                                            </select>
                                        </label>
                                        <label class="text-xs">Freight value
                                            <span class="mt-1 flex gap-1">
                                                <select :name="`items[${iIndex}][quotes][${qIndex}][freight_mode]`" x-model="quote.freight_mode" class="rounded-lg border-slate-300 text-sm">
                                                    <option value="amount">Amount</option>
                                                    <option value="percent">%</option>
                                                </select>
                                                <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][freight_value]`" x-model="quote.freight_value" class="w-full rounded-lg border-slate-300 text-sm">
                                            </span>
                                        </label>
                                        <label class="text-xs">Packing
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][packing_charges]`" x-model="quote.packing_charges" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Insurance
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][insurance_charges]`" x-model="quote.insurance_charges" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Other charges
                                            <input type="number" step="0.01" :name="`items[${iIndex}][quotes][${qIndex}][other_charges]`" x-model="quote.other_charges" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Warranty
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][warranty_period]`" x-model="quote.warranty_period" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Warranty type
                                            <select :name="`items[${iIndex}][quotes][${qIndex}][warranty_type]`" x-model="quote.warranty_type" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                                <option value="">—</option>
                                                <option value="manufacturer">Manufacturer</option>
                                                <option value="seller">Seller</option>
                                                <option value="on_site">On site</option>
                                                <option value="carry_in">Carry in</option>
                                            </select>
                                        </label>
                                        <label class="text-xs">Stock
                                            <select :name="`items[${iIndex}][quotes][${qIndex}][stock_availability]`" x-model="quote.stock_availability" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                                <option value="">—</option>
                                                <option value="ready">Ready</option>
                                                <option value="limited">Limited</option>
                                                <option value="against_order">Against order</option>
                                            </select>
                                        </label>
                                        <label class="text-xs">Delivery time
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][delivery_time]`" x-model="quote.delivery_time" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Delivery date
                                            <input type="date" :name="`items[${iIndex}][quotes][${qIndex}][delivery_date]`" x-model="quote.delivery_date" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Payment terms
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][payment_terms]`" x-model="quote.payment_terms" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Price validity
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][price_validity]`" x-model="quote.price_validity" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs">Origin
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][country_of_origin]`" x-model="quote.country_of_origin" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                        <label class="text-xs md:col-span-2">Remarks
                                            <input type="text" :name="`items[${iIndex}][quotes][${qIndex}][remarks]`" x-model="quote.remarks" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        </label>
                                    </div>
                                </div>
                            </template>
                        </div>

                        <div class="mt-3 grid gap-3 md:grid-cols-4">
                            <label class="text-sm">Award to
                                <select :name="`items[${iIndex}][selected_vendor_index]`" x-model="item.selected_vendor_index" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    <option value="">Not chosen</option>
                                    <template x-for="(vendor, vIndex) in vendors" :key="'pick-' + iIndex + '-' + vIndex">
                                        <option :value="String(vIndex)" x-text="vendorLabel(vIndex)"></option>
                                    </template>
                                </select>
                            </label>
                            <label class="text-sm">Reason if not the lowest
                                <input type="text" :name="`items[${iIndex}][selection_reason]`" x-model="item.selection_reason" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Final negotiated rate
                                <input type="number" step="0.01" :name="`items[${iIndex}][final_rate]`" x-model="item.final_rate" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Final freight
                                <input type="number" step="0.01" :name="`items[${iIndex}][final_freight]`" x-model="item.final_freight" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                            <label class="text-sm">Final amount
                                <input type="number" step="0.01" :name="`items[${iIndex}][final_amount]`" x-model="item.final_amount" class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                            </label>
                        </div>
                    </div>
                </template>
            </div>
        </x-ui.card>

        <div class="flex flex-wrap gap-2">
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Save draft</button>
            <button name="submit_for_approval" value="1" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Save and send for approval</button>
        </div>
    </form>
</div>

<script>
function comparisonForm(state, products) {
    const blankQuote = () => ({
        is_quoted: false, quoted_quantity: '', basic_rate: '', discount_type: 'percent', discount_value: 0,
        gst_rate: 0, freight_type: 'free', freight_mode: 'amount', freight_value: 0, packing_charges: 0,
        insurance_charges: 0, other_charges: 0, warranty_period: '', warranty_type: '', stock_availability: '',
        delivery_time: '', delivery_date: '', payment_terms: '', price_validity: '', country_of_origin: '', remarks: '',
    });
    const blankVendor = () => ({
        id: null, supplier_id: '', billing_address: '', dispatch_address: '', contact_person: '', mobile: '', email: '',
        gstin: '', quotation_number: '', quotation_date: '', valid_until: '', credit_period: '', advance_payment: '',
        credit_limit_note: '', replacement_policy: '', return_policy: '', installation_support: '', transporter: '',
        freight_paid_by: 'vendor', delivery_location: '', partial_delivery: false, files: [],
    });
    state.vendors.forEach((vendor) => {
        vendor.supplier_id = vendor.supplier_id ? String(vendor.supplier_id) : '';
        vendor.partial_delivery = !!vendor.partial_delivery;
        vendor.files = vendor.files || [];
    });
    state.items.forEach((item) => {
        item.product_id = item.product_id ? String(item.product_id) : '';
        item.uom_id = item.uom_id ? String(item.uom_id) : '';
        item.selected_vendor_index = (item.selected_vendor_index === 0 || item.selected_vendor_index) ? String(item.selected_vendor_index) : '';
        item.quotes.forEach((quote) => { quote.is_quoted = !!quote.is_quoted || quote.is_quoted === 1 || quote.is_quoted === '1'; });
    });

    return {
        ...state,
        products,
        supplierUrl: @js(url('/purchasing/comparisons/suppliers')),
        vendorLabel(index) {
            const vendor = this.vendors[index];
            const select = document.querySelector(`[name="vendors[${index}][supplier_id]"]`);
            const name = select?.selectedOptions?.[0]?.text;
            return name && vendor?.supplier_id ? name : ('Vendor ' + (index + 1));
        },
        addVendor() {
            if (this.vendors.length >= 10) return;
            this.vendors.push(blankVendor());
            this.items.forEach((item) => item.quotes.push(blankQuote()));
        },
        removeVendor(index) {
            if (this.vendors.length <= 2) return;
            this.vendors.splice(index, 1);
            this.items.forEach((item) => {
                item.quotes.splice(index, 1);
                if (item.selected_vendor_index === '') return;
                const selected = Number(item.selected_vendor_index);
                if (selected === index) item.selected_vendor_index = '';
                else if (selected > index) item.selected_vendor_index = String(selected - 1);
            });
        },
        addItem() {
            this.items.push({
                item_name: '', category_name: '', subcategory_name: '', brand_name: '', product_id: '', uom_id: '',
                model_sku: '', description: '', required_quantity: 1, selected_vendor_index: '', selection_reason: '',
                final_rate: '', final_freight: '', final_amount: '',
                quotes: this.vendors.map(() => blankQuote()),
            });
        },
        awardAll(index) {
            if (index === '') return;
            this.items.forEach((item) => { item.selected_vendor_index = String(index); });
        },
        applyProduct(item) {
            const product = this.products.find((row) => String(row.id) === String(item.product_id));
            if (!product) return;
            item.item_name = product.name || item.item_name;
            item.brand_name = product.brand || '';
            item.category_name = product.category || '';
            item.subcategory_name = product.subcategory || '';
            item.model_sku = product.model || product.sku || '';
            item.uom_id = product.uom_id ? String(product.uom_id) : item.uom_id;
            item.quotes.forEach((quote) => {
                if (!quote.gst_rate) quote.gst_rate = product.tax_rate || 0;
                if (!quote.basic_rate && product.purchase_price) quote.basic_rate = product.purchase_price;
            });
        },
        async loadSupplier(index) {
            const id = this.vendors[index].supplier_id;
            if (!id) return;
            const response = await fetch(this.supplierUrl + '/' + id, { headers: { 'Accept': 'application/json' } });
            if (!response.ok) return;
            const data = await response.json();
            const vendor = this.vendors[index];
            vendor.billing_address = data.billing_address || '';
            vendor.dispatch_address = data.dispatch_address || '';
            vendor.contact_person = data.contact_person || '';
            vendor.mobile = data.mobile || '';
            vendor.email = data.email || '';
            vendor.gstin = data.gstin || '';
        },
    };
}
</script>
@endsection
