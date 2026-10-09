@props(['lines', 'products' => [], 'godowns' => [], 'taxRates' => [], 'hsnSacs' => [], 'purchase' => false])
@php
    $rows = old('lines', $lines);
    if ($rows === [] || $rows === null) {
        $rows = [['item_name' => '', 'product_id' => '', 'godown_id' => '', 'tax_rate_id' => '', 'quantity' => '', 'rate' => '', 'discount' => '', 'tax_amount' => '']];
    }
    $blank = ['item_name' => '', 'product_id' => '', 'godown_id' => '', 'tax_rate_id' => '', 'quantity' => '', 'rate' => '', 'discount' => '', 'tax_amount' => ''];
@endphp
<div id="invoice-lines">
    <div class="table-wrap">
        <table class="data invoice-lines">
            <thead>
                <tr>
                    <th>Item</th>
                    <th class="money">Quantity</th>
                    <th class="money">Rate</th>
                    <th class="money">Discount</th>
                    <th class="money">Tax</th>
                    <th class="money">Amount</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $index => $line)
                    <x-tally::invoice.line :index="$index" :line="$line" :products="$products" :godowns="$godowns" :tax-rates="$taxRates" :hsn-sacs="$hsnSacs" :purchase="$purchase" />
                @endforeach
            </tbody>
        </table>
    </div>
    <button class="btn" type="button" data-add-line>Add row</button>
    <p class="form-note">Choose a tax rate to calculate CGST and SGST for the same state, or IGST for a different state. Without a tax rate, the tax amount stays inside the sales or purchase total. A product line also needs a godown and updates stock when the invoice is posted.</p>
</div>
<template id="invoice-line">
    <x-tally::invoice.line index="__INDEX__" :line="$blank" :products="$products" :godowns="$godowns" :tax-rates="$taxRates" :hsn-sacs="$hsnSacs" :purchase="$purchase" />
</template>
