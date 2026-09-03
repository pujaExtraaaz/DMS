@extends('layouts.dms')

@section('title', 'Book Order')

@section('content')

<div
    x-data="orderBuilder()"
    x-init="init()"
    @keydown.window="handleShortcut($event)"
    @click.window="closeProductDropdowns($event)"
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
                <div class="w-full md:max-w-lg">

                    <div class="mb-2 flex items-center justify-between">
                        <label class="block text-sm font-semibold text-gray-800">
                            Discount
                        </label>

                        <span
                            class="text-xs font-medium text-gray-400"
                            x-text="discountType === 'flat'
                                ? 'Fixed amount'
                                : 'Percentage'"
                        ></span>
                    </div>

                    <div class="rounded-xl border border-gray-200 bg-white p-1.5 shadow-sm">

                        {{-- Discount Type --}}
                        <div class="grid grid-cols-2 gap-1 rounded-lg bg-gray-100 p-1">

                            {{-- Flat --}}
                            <button
                                type="button"
                                @click="
                                    discountType = 'flat';
                                    discountValue = 0;
                                    discountChanged();
                                "
                                :class="
                                    discountType === 'flat'
                                        ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                            >
                                <span
                                    class="text-sm font-bold"
                                >
                                    ₹
                                </span>

                                <span>Flat</span>
                            </button>

                            {{-- Percent --}}
                            <button
                                type="button"
                                @click="
                                    discountType = 'percent';
                                    discountValue = 0;
                                    discountChanged();
                                "
                                :class="
                                    discountType === 'percent'
                                        ? 'bg-white text-indigo-600 shadow-sm ring-1 ring-gray-200'
                                        : 'text-gray-500 hover:text-gray-700'
                                "
                                class="inline-flex h-9 items-center justify-center gap-1.5 rounded-md px-3 text-xs font-semibold transition"
                            >
                                <span
                                    class="text-sm font-bold"
                                >
                                    %
                                </span>

                                <span>Percent</span>
                            </button>

                        </div>

                        {{-- Discount Value --}}
                        <div class="mt-2">

                            <div
                                class="relative flex items-center overflow-hidden rounded-lg border border-gray-300 bg-white transition focus-within:border-indigo-500 focus-within:ring-2 focus-within:ring-indigo-100"
                            >

                                {{-- Rupee Prefix --}}
                                <span
                                    x-show="discountType === 'flat'"
                                    x-cloak
                                    class="flex h-12 w-12 shrink-0 items-center justify-center border-r border-gray-200 bg-gray-50 text-base font-semibold text-gray-600"
                                >
                                    ₹
                                </span>

                                {{-- Input --}}
                                <input
                                    type="number"
                                    name="discount_value"
                                    x-model="discountValue"
                                    min="0"
                                    :max="discountType === 'percent' ? 100 : null"
                                    step="0.01"
                                    class="discount-value-input h-12 w-full border-0 bg-transparent px-4 text-base font-semibold text-gray-900 outline-none focus:ring-0"
                                    :class="discountType === 'percent' ? 'pl-12 pr-12' : ''"
                                    :placeholder="discountType === 'flat' ? 'Enter discount amount' : 'Enter discount percentage'"
                                    @input="discountChanged()"
                                >

                                {{-- Percent Suffix --}}
                                <span
                                    x-show="discountType === 'percent'"
                                    x-cloak
                                    class="flex h-12 w-12 shrink-0 items-center justify-center border-l border-gray-200 bg-gray-50 text-base font-semibold text-gray-600"
                                >
                                    %
                                </span>

                            </div>

</div>

                        {{-- Help Text --}}
                        <div class="mt-2 flex items-center gap-2 px-1">

                            <svg
                                class="h-3.5 w-3.5 shrink-0 text-gray-400"
                                fill="none"
                                stroke="currentColor"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M12 20a8 8 0 100-16 8 8 0 000 16z"
                                />
                            </svg>

                            <p class="text-xs text-gray-500">
                                <template x-if="discountType === 'flat'">
                                    <span>Enter a fixed rupee amount.</span>
                                </template>

                                <template x-if="discountType === 'percent'">
                                    <span>Enter a percentage between 0% and 100%.</span>
                                </template>
                            </p>

                        </div>

                        {{-- Applied Discount --}}
                        <div
                            x-show="discountAmount > 0"
                            x-cloak
                            class="mt-3 flex items-center justify-between rounded-lg bg-indigo-50 px-3 py-2.5"
                        >

                            <span class="text-xs font-medium text-indigo-700">
                                Applied discount
                            </span>

                            <span class="text-sm font-bold text-indigo-700">
                                ₹<span x-text="formatMoney(discountAmount)"></span>
                            </span>

                        </div>

                    </div>

                    {{-- Actual rupee amount sent to Laravel --}}
                    <input
                        type="hidden"
                        name="discount_amount"
                        x-model="discountAmount"
                    >

                </div>

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
                                Unit
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

                            <tr
                                class="border-b"
                                :class="row.productResultsOpen ? 'relative z-[100]' : 'relative z-0'"
                            >

                                {{-- PRODUCT --}}
                                <td class="px-3 py-3 min-w-[300px]">

                                    <div class="relative">

                                        {{-- Search / Selected Product --}}
                                        <div class="relative">

                                            <input
                                                type="text"
                                                autocomplete="off"
                                                class="product-search block w-full rounded-lg border-gray-300 pr-10 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                x-model="row.productLabel"
                                                @focus="openProductDropdown(row, $event.target)"
                                                @input="
                                                    searchProducts(row, $event.target.value);
                                                    positionProductDropdown(row, $event.target)
                                                "
                                                placeholder="Select or search product..."
                                            >

                                            {{-- Dropdown arrow --}}
                                            <button
                                                type="button"
                                                @mousedown.prevent="toggleProductDropdown(row)"
                                                class="product-dropdown-arrow absolute inset-y-0 right-0 flex w-10 items-center justify-center text-gray-400 hover:text-gray-600"
                                                tabindex="-1"
                                            >
                                                <svg
                                                    class="h-4 w-4 transition-transform"
                                                    :class="row.productResultsOpen ? 'rotate-180' : ''"
                                                    fill="none"
                                                    stroke="currentColor"
                                                    viewBox="0 0 24 24"
                                                >
                                                    <path
                                                        stroke-linecap="round"
                                                        stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 9l-7 7-7-7"
                                                    />
                                                </svg>
                                            </button>

                                        </div>

                                        {{-- Actual product ID --}}
                                        <input
                                            type="hidden"
                                            :name="`items[${index}][product_id]`"
                                            x-model="row.productId"
                                        >

                                        {{-- Product Dropdown --}}
                                        <div 
                                            x-cloak 
                                            x-show="row.productResultsOpen"
                                            :style="`
                                                position: fixed;
                                                top: ${row.productDropdownTop}px;
                                                left: ${row.productDropdownLeft}px;
                                                width: ${row.productDropdownWidth}px;
                                                z-index: 999999;
                                            `"
                                            class="product-dropdown max-h-72 overflow-y-auto rounded-xl border border-gray-200 bg-white shadow-2xl ring-1 ring-black/5"
                                        >

                                            {{-- No results --}}
                                            <div
                                                x-show="row.productResults.length === 0"
                                                class="px-4 py-3 text-sm text-gray-500"
                                            >
                                                No products found.
                                            </div>

                                            {{-- Products --}}
                                            <template
                                                x-for="product in row.productResults"
                                                :key="product.id"
                                            >

                                                <button
                                                    type="button"
                                                    @mousedown.prevent="selectProduct(row, product)"
                                                    class="block w-full border-b border-gray-100 px-4 py-2.5 text-left transition last:border-b-0 hover:bg-indigo-50"
                                                >

                                                    <div class="flex items-center justify-between gap-3">

                                                        <div class="min-w-0">

                                                            <div
                                                                class="truncate font-medium text-gray-900"
                                                                x-text="product.name"
                                                            ></div>

                                                            <div
                                                                class="mt-0.5 text-xs text-gray-500"
                                                                x-text="product.sku
                                                                    ? 'SKU: ' + product.sku
                                                                    : 'No SKU'"
                                                            ></div>

                                                        </div>

                                                        <span
                                                            class="shrink-0 rounded bg-gray-100 px-2 py-1 text-xs text-gray-500"
                                                            x-text="product.base_uom_name || ''"
                                                        ></span>

                                                    </div>

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
                                            Select Unit
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

            products: {{ Illuminate\Support\Js::from(
                $products->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'base_uom_name' => $product->baseUom?->name,
                ])->values()
            ) }},

            discountType: @json(old('discount_type', 'flat')),

            discountValue: @json(
                old(
                    'discount_value',
                    old('discount_amount', 0)
                )
            ),

            discountAmount: @json(
                old('discount_amount', 0)
            ),

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

                window.addEventListener('scroll', () => {

                this.rows.forEach(row => {

                    if (!row.productResultsOpen) {
                        return;
                    }

                    const input =
                        document.querySelector(
                            `.product-search`
                        );

                    if (input) {
                        this.positionProductDropdown(
                            row,
                            input
                        );
                    }

                });

            }, true);

            window.addEventListener('resize', () => {

                this.rows.forEach(row => {

                    if (!row.productResultsOpen) {
                        return;
                    }

                    const input =
                        document.querySelector(
                            `.product-search`
                        );

                    if (input) {
                        this.positionProductDropdown(
                            row,
                            input
                        );
                    }

                });

            });

        },


        emptyRow() {

            return {

                key: this.nextKey++,

                productId: '',

                productLabel: '',

                selectedProductLabel: '',

                productResults: [],
                productResultsOpen: false,
                productDropdownTop: 0,
                productDropdownLeft: 0,
                productDropdownWidth: 0,
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

        openProductDropdown(row, input = null) {

            row.productResults = [
                ...this.products
            ];

            row.productResultsOpen = true;

            this.$nextTick(() => {
                this.positionProductDropdown(row, input);
            });
        },

        positionProductDropdown(row, input = null) {

            if (!input) {
                input = this.$root.querySelector(
                    '.product-search:focus'
                );
            }

            if (!input) {
                return;
            }

            const rect = input.getBoundingClientRect();

            row.productDropdownTop =
                rect.bottom + 4;

            row.productDropdownLeft =
                rect.left;

            row.productDropdownWidth =
                rect.width;
        },

        toggleProductDropdown(row, event) {

            if (row.productResultsOpen) {

                row.productResultsOpen = false;

                return;
            }

            this.openProductDropdown(
                row,
                event?.currentTarget?.closest('td')
                    ?.querySelector('.product-search')
            );
        },

        closeProductDropdowns(event) {
            const clickedProductInput = event.target.closest('.product-search');
            const clickedProductDropdown = event.target.closest('.product-dropdown');
            const clickedProductArrow = event.target.closest('.product-dropdown-arrow');

            if (
                clickedProductInput ||
                clickedProductDropdown ||
                clickedProductArrow
            ) {
                return;
            }

            this.rows.forEach(row => {
                row.productResultsOpen = false;
            });
        },


        async searchProducts(row, term) {
            term = String(term || '')
                .trim()
                .toLowerCase();

            /*
            * If the user changes the selected product text,
            * clear the previous product selection.
            */
            if (
                row.productId &&
                row.productLabel !== row.selectedProductLabel
            ) {
                row.productId = '';
                row.uomId = '';
                row.uoms = [];
                row.unitPrice = '';
                row.lineTotal = 0;
            }

            if (term === '') {
                row.productResults = [
                    ...this.products
                ];
            } else {
                row.productResults = this.products.filter(product => {
                    const name = String(product.name || '')
                        .toLowerCase();

                    const sku = String(product.sku || '')
                        .toLowerCase();

                    return (
                        name.includes(term) ||
                        sku.includes(term)
                    );
                });
            }

            row.productResultsOpen = true;
        },

        async selectProduct(row, product) {

            row.productId = product.id;

            row.productLabel = product.label ||
                `${product.name}${product.sku ? ' (' + product.sku + ')' : ''}`;

            row.selectedProductLabel = row.productLabel;

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

        discountChanged() {

            let value = Number(this.discountValue || 0);

            if (!Number.isFinite(value) || value < 0) {
                value = 0;
            }

            if (this.discountType === 'percent') {

                if (value > 100) {
                    value = 100;
                    this.discountValue = 100;
                }

                const subtotal = this.rows.reduce(
                    (total, row) =>
                        total + Number(row.lineTotal || 0),
                    0
                );

                this.discountAmount = Math.round(
                    subtotal * value / 100 * 100
                ) / 100;

                return;
            }

            this.discountAmount =
                Math.round(value * 100) / 100;
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

            this.discountChanged();

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

            this.discountChanged();


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