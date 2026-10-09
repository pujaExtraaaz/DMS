@props(['invoice' => null, 'live' => false])
@php
    $subtotal = old('subtotal', $invoice->subtotal ?? '0.00');
    $discount = old('discount_total', $invoice->discount_total ?? '0.00');
    $tax = old('tax_total', $invoice->tax_total ?? '0.00');
    $grand = old('grand_total', $invoice->grand_total ?? '0.00');
@endphp
<div class="balance-bar" id="invoice-totals">
    <span>Subtotal <strong @if($live) id="invoice-subtotal" @endif>{{ $live ? '0.00' : $subtotal }}</strong></span>
    <span>Discount <strong @if($live) id="invoice-discount" @endif>{{ $live ? '0.00' : $discount }}</strong></span>
    <span>Tax <strong @if($live) id="invoice-tax" @endif>{{ $live ? '0.00' : $tax }}</strong></span>
    <span>Grand total <strong @if($live) id="invoice-grand" @endif>{{ $live ? '0.00' : $grand }}</strong></span>
</div>
