@extends('layouts.dms')

@section('title', 'Book Order')

@section('content')

<div
    x-data="orderBuilder()"
    x-init="init()"
    @keydown.window="handleShortcut($event)"
    class="space-y-6"
>

    {{-- Page Header --}}
    <x-ui.page-header
        title="Book Order"
        description="Fast order entry with live customer pricing."
    >
        <x-slot name="actions">
            <x-ui.button
                variant="secondary"
                :href="route('orders.index')"
            >
                Back
            </x-ui.button>
        </x-slot>
    </x-ui.page-header>


    {{-- Validation Errors --}}
    @if ($errors->any())
        <x-ui.alert type="error">
            <ul class="list-disc list-inside space-y-1 text-sm">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </x-ui.alert>
    @endif


    {{-- Order Form --}}
    <form
        method="POST"
        action="{{ route('orders.store') }}"
        @submit="beforeSubmit"
        class="space-y-6"
    >

        @csrf


        {{-- =========================================================
             ORDER DETAILS
        ========================================================== --}}
        <x-ui.card title="Order Details">

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">

                {{-- Customer --}}
                <x-ui.select
                    name="customer_id"
                    id="customer_id"
                    label="Customer"
                    required
                    placeholder="Select customer"
                    x-model="customerId"
                    @change="customerChanged()"
                >
                    @foreach($customers as $customer)

                        <option
                            value="{{ $customer->id }}"
                            @selected(
                                old('customer_id') == $customer->id ||
                                (!old('customer_id') && request('customer_id') == $customer->id)
                            )
                        >
                            {{ $customer->name }}
                            @if($customer->code)
                                ({{ $customer->code }})
                            @endif
                        </option>

                    @endforeach
                </x-ui.select>


                {{-- Order Date --}}
                <x-ui.input
                    name="order_date"
                    label="Order Date"
                    type="date"
                    :value="old('order_date', now()->toDateString())"
                    :max="now()->toDateString()"
                    required
                />


                {{-- Created By --}}
                <x-ui.input
                    name="created_by_name"
                    label="Created By"
                    :value="old('created_by_name', auth()->user()->name ?? '')"
                    maxlength="100"
                    required
                    placeholder="Person who created this order"
                />

            </div>


            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">

                {{-- Discount --}}
                <x-ui.input
                    name="discount_amount"
                    label="Discount"
                    type="number"
                    step="0.01"
                    min="0"
                    :value="old('discount_amount', 0)"
                />

            </div>

        </x-ui.card>



        {{-- =========================================================
             PRODUCTS
        ========================================================== --}}
        <x-ui.card title="Products">

            <div class="mb-4 rounded-lg bg-indigo-50 px-4 py-3 text-sm text-indigo-800">
                Prices are always recalculated on the server when the order is saved.
                The displayed price is only a convenience preview.
            </div>


            {{-- Product Table --}}
            <div class="overflow-x-auto">

                <table class="min-w-full text-sm">

                    <thead>
                        <tr class="border-b bg-gray-50">

                            <th class="px-3 py-3 text-left">
                                Product
                            </th>

                            <th class="px-3 py-3 text-left">
                                UOM
                            </th>

                            <th class="px-3 py-3 text-right">
                                Qty
                            </th>

                            <th class="px-3 py-3 text-right">
                                Price
                            </th>

                            <th class="px-3 py-3 text-right">
                                Total
                            </th>

                            <th class="px-3 py-3"></th>

                        </tr>
                    </thead>


                    <tbody>

                        <template
                            x-for="(row, index) in rows"
                            :key="row.key"
                        >

                            <tr class="border-b">

                                {{-- PRODUCT --}}
                                <td class="px-3 py-3 min-w-[300px]">

                                    <div class="relative">

                                        <input
                                            type="text"
                                            autocomplete="off"
                                            class="product-search block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            x-model="row.productLabel"
                                            @input.debounce.250ms="searchProducts(row, $event.target.value)"
                                            @focus="row.productResultsOpen = row.productResults.length > 0"
                                            placeholder="Search product by name or SKU..."
                                        >


                                        <input
                                            type="hidden"
                                            :name="`items[${index}][product_id]`"
                                            x-model="row.productId"
                                        >


                                        {{-- Product Search Results --}}
                                        <div
                                            x-cloak
                                            x-show="row.productResultsOpen"
                                            @click.outside="row.productResultsOpen = false"
                                            class="absolute z-50 mt-1 w-full overflow-hidden rounded-lg border border-gray-200 bg-white shadow-xl"
                                        >

                                            <template
                                                x-for="product in row.productResults"
                                                :key="product.id"
                                            >

                                                <button
                                                    type="button"
                                                    @click="selectProduct(row, product)"
                                                    class="block w-full px-4 py-3 text-left text-sm hover:bg-indigo-50"
                                                >

                                                    <div
                                                        class="font-medium text-gray-900"
                                                        x-text="product.name"
                                                    ></div>

                                                    <div
                                                        class="text-xs text-gray-500"
                                                        x-text="product.sku ? 'SKU: ' + product.sku : 'No SKU'"
                                                    ></div>

                                                </button>

                                            </template>

                                        </div>

                                    </div>

                                </td>


                                {{-- UOM --}}
                                <td class="px-3 py-3 min-w-[180px]">

                                    <select
                                        :name="`items[${index}][uom_id]`"
                                        x-model="row.uomId"
                                        @change="resolvePrice(row)"
                                        :disabled="!row.productId || row.uoms.length === 0"
                                        class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 disabled:bg-gray-100"
                                    >

                                        <option value="">
                                            Select UOM
                                        </option>

                                        <template
                                            x-for="uom in row.uoms"
                                            :key="uom.id"
                                        >

                                            <option
                                                :value="uom.id"
                                                x-text="uom.label"
                                            ></option>

                                        </template>

                                    </select>

                                </td>


                                {{-- QUANTITY --}}
                                <td class="px-3 py-3 min-w-[120px]">

                                    <input
                                        type="number"
                                        min="0.0001"
                                        step="0.0001"
                                        :name="`items[${index}][quantity]`"
                                        x-model="row.quantity"
                                        @input="quantityChanged(row)"
                                        class="block w-full rounded-lg border-gray-300 text-sm text-right focus:border-indigo-500 focus:ring-indigo-500"
                                    >

                                </td>


                                {{-- PRICE --}}
                                <td class="px-3 py-3 min-w-[130px]">

                                    <div class="relative">

                                        <input
                                            type="number"
                                            step="0.01"
                                            readonly
                                            :name="`items[${index}][unit_price]`"
                                            x-model="row.unitPrice"
                                            class="block w-full rounded-lg border-gray-300 bg-gray-50 text-sm text-right"
                                        >

                                        <span
                                            x-show="row.priceLoading"
                                            class="absolute right-2 top-2 text-xs text-gray-400"
                                        >
                                            Loading...
                                        </span>

                                    </div>

                                </td>


                                {{-- LINE TOTAL --}}
                                <td class="px-3 py-3 text-right whitespace-nowrap">

                                    ₹<span
                                        x-text="formatMoney(row.lineTotal)"
                                    ></span>

                                </td>


                                {{-- REMOVE --}}
                                <td class="px-3 py-3 text-right">

                                    <button
                                        type="button"
                                        @click="removeRow(index)"
                                        :disabled="rows.length === 1"
                                        class="text-sm font-medium text-red-600 hover:text-red-800 disabled:opacity-30 disabled:cursor-not-allowed"
                                    >
                                        Remove
                                    </button>

                                </td>

                            </tr>

                        </template>

                    </tbody>

                </table>

            </div>


            {{-- Add Product / Total --}}
            <div class="mt-5 flex flex-wrap items-center justify-between gap-4">

                <button
                    type="button"
                    @click="addRow()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                >
                    + Add Product
                </button>


                <div class="text-right">

                    <p class="text-sm text-gray-500">
                        Preview Total
                    </p>

                    <p class="text-xl font-semibold text-gray-900">
                        ₹<span x-text="formatMoney(grandTotal)"></span>
                    </p>

                </div>

            </div>

        </x-ui.card>



        {{-- =========================================================
             NOTES + SUBMIT
        ========================================================== --}}
        <x-ui.card>

            <x-ui.input
                name="notes"
                label="Notes"
                :value="old('notes')"
                placeholder="Optional order notes..."
            />


            <div class="mt-5 flex flex-wrap gap-3">

                <button
                    type="submit"
                    :disabled="submitting"
                    class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:cursor-not-allowed disabled:opacity-50"
                >

                    <span x-show="!submitting">
                        Book Order
                    </span>

                    <span x-show="submitting">
                        Saving...
                    </span>

                </button>


                <button
                    type="button"
                    @click="addRow()"
                    class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50"
                >
                    Add Line
                </button>

            </div>


            <p class="mt-3 text-xs text-gray-500">
                Keyboard: <strong>Ctrl + Enter</strong> submits the order.
                Press <strong>A</strong> to add another product line.
            </p>

        </x-ui.card>

    </form>

</div>

@endsection


@push('scripts')

<script>

document.addEventListener('alpine:init', () => {

    Alpine.data('orderBuilder', () => ({

        customerId: @json(old('customer_id', request('customer_id'))),

        rows: [],

        submitting: false,

        nextKey: 1,


        init() {

            const oldItems = @json($initialItems);

            if (oldItems.length > 0) {

                oldItems.forEach(item => {

                    const row = this.emptyRow();

                    row.productId = item.product_id || '';

                    row.productLabel = item.product_name
                        ? `${item.product_name}${item.product_sku ? ' (' + item.product_sku + ')' : ''}`
                        : '';

                    row.uomId = item.uom_id || '';

                    row.quantity = item.quantity || 1;

                    row.unitPrice = item.unit_price || '';

                    this.rows.push(row);

                    if (row.productId) {
                        this.loadUoms(row);
                    }

                });

            } else {

                this.rows.push(this.emptyRow());

            }

        },


        emptyRow() {

            return {

                key: this.nextKey++,

                productId: '',

                productLabel: '',

                productResults: [],

                productResultsOpen: false,

                uomId: '',

                uoms: [],

                quantity: 1,

                unitPrice: '',

                lineTotal: 0,

                priceLoading: false,

                priceTimer: null,

            };

        },


        customerChanged() {

            this.rows.forEach(row => {

                if (
                    row.productId &&
                    row.uomId &&
                    Number(row.quantity) > 0
                ) {

                    this.resolvePrice(row);

                }

            });

        },


        addRow() {

            this.rows.push(this.emptyRow());

            this.$nextTick(() => {

                const inputs =
                    document.querySelectorAll('.product-search');

                const lastInput =
                    inputs[inputs.length - 1];

                lastInput?.focus();

            });

        },


        removeRow(index) {

            if (this.rows.length <= 1) {
                return;
            }

            this.rows.splice(index, 1);

        },


        async searchProducts(row, term) {

            row.productId = '';

            row.uomId = '';

            row.uoms = [];

            row.unitPrice = '';

            row.lineTotal = 0;


            term = String(term || '').trim();


            if (term.length < 2) {

                row.productResults = [];

                row.productResultsOpen = false;

                return;

            }


            try {

                const url =
                    @json(route('orders.search.products')) +
                    '?q=' +
                    encodeURIComponent(term);


                const response = await fetch(url, {

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }

                });


                if (!response.ok) {
                    throw new Error('Product search failed.');
                }


                row.productResults =
                    await response.json();

                row.productResultsOpen =
                    row.productResults.length > 0;

            } catch (error) {

                console.error(error);

                row.productResults = [];

                row.productResultsOpen = false;

            }

        },


        async selectProduct(row, product) {

            row.productId = product.id;

            row.productLabel = product.label ||
                `${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`;

            row.productResultsOpen = false;

            row.productResults = [];

            row.uomId = '';

            row.unitPrice = '';

            row.lineTotal = 0;

            row.uoms = [];


            await this.loadUoms(row);

        },


        async loadUoms(row) {

            if (!row.productId) {

                row.uoms = [];

                row.uomId = '';

                return;

            }


            try {

                const url =
                    @json(route('orders.search.uoms')) +
                    '?product_id=' +
                    encodeURIComponent(row.productId);


                const response = await fetch(url, {

                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    }

                });


                if (!response.ok) {
                    throw new Error('UOM lookup failed.');
                }


                row.uoms = await response.json();


                if (row.uoms.length === 1) {

                    row.uomId =
                        String(row.uoms[0].id);

                    await this.resolvePrice(row);

                }

            } catch (error) {

                console.error(error);

                row.uoms = [];

                row.uomId = '';

            }

        },


        quantityChanged(row) {

            this.updateTotal(row);

            clearTimeout(row.priceTimer);


            row.priceTimer = setTimeout(() => {

                this.resolvePrice(row);

            }, 250);

        },


        async resolvePrice(row) {

            this.updateTotal(row);


            if (
                !this.customerId ||
                !row.productId ||
                !row.uomId ||
                Number(row.quantity) <= 0
            ) {

                row.unitPrice = '';

                row.lineTotal = 0;

                return;

            }


            row.priceLoading = true;


            try {

                const params = new URLSearchParams({

                    customer_id: this.customerId,

                    product_id: row.productId,

                    uom_id: row.uomId,

                    quantity: row.quantity,

                });


                const response = await fetch(

                    @json(route('orders.resolve-price')) +
                    '?' +
                    params.toString(),

                    {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    }

                );


                const data = await response.json();


                if (!response.ok) {
                    throw new Error(
                        data.message ||
                        'Price lookup failed.'
                    );
                }


                row.unitPrice = data.found
                    ? Number(data.unit_price).toFixed(2)
                    : '';


                this.updateTotal(row);


            } catch (error) {

                console.error(error);

                row.unitPrice = '';

                row.lineTotal = 0;

            } finally {

                row.priceLoading = false;

            }

        },


        updateTotal(row) {

            const quantity =
                Number(row.quantity || 0);

            const unitPrice =
                Number(row.unitPrice || 0);


            row.lineTotal =
                Number.isFinite(quantity) &&
                Number.isFinite(unitPrice)
                    ? quantity * unitPrice
                    : 0;

        },


        get grandTotal() {

            return this.rows.reduce(

                (total, row) =>
                    total +
                    Number(row.lineTotal || 0),

                0

            );

        },


        formatMoney(value) {

            return Number(value || 0).toLocaleString(
                'en-IN',
                {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                }
            );

        },


        handleShortcut(event) {

            const tag =
                document.activeElement?.tagName;


            const typing =
                ['INPUT', 'TEXTAREA', 'SELECT']
                    .includes(tag);


            if (
                event.ctrlKey &&
                event.key === 'Enter'
            ) {

                event.preventDefault();

                document
                    .querySelector('form')
                    ?.requestSubmit();

                return;

            }


            if (
                event.key.toLowerCase() === 'a' &&
                !typing
            ) {

                event.preventDefault();

                this.addRow();

            }

        },


        beforeSubmit(event) {

            if (this.submitting) {

                event.preventDefault();

                return;

            }


            if (!this.customerId) {

                event.preventDefault();

                alert('Please select a customer.');

                return;

            }


            if (this.rows.length === 0) {

                event.preventDefault();

                alert('Please add at least one product.');

                return;

            }


            const hasEmptyLine =
                this.rows.some(row =>
                    !row.productId ||
                    !row.uomId ||
                    Number(row.quantity) <= 0
                );


            if (hasEmptyLine) {

                event.preventDefault();

                alert(
                    'Please complete every product line.'
                );

                return;

            }


            this.submitting = true;

        }

    }));

});

</script>

@endpush