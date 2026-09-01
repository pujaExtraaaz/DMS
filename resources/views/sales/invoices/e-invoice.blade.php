@extends('layouts.dms')

@section('title', 'E-Invoice - '.$invoice->invoice_no)

@section('content')
<x-ui.page-header title="E-Invoice" :description="$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">Back to Invoice</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <div class="overflow-hidden rounded-lg border border-slate-200 bg-white">
        <iframe id="e-invoice-document" src="{{ route('invoices.e-invoice.preview', $invoice) }}" title="E-Invoice {{ $invoice->invoice_no }}" class="w-full border-0 bg-white" style="height: calc(100vh - 12rem); min-height: 820px;"></iframe>
    </div>
    <div class="mt-4 flex flex-wrap justify-end gap-3 border-t border-slate-100 pt-4">
        <x-ui.button variant="secondary" :href="route('invoices.pdf', ['invoice' => $invoice, 'download' => 1])">Download</x-ui.button>
        <x-ui.button type="button" variant="primary" onclick="document.getElementById('e-invoice-document').contentWindow.print()">Print</x-ui.button>
    </div>
</x-ui.card>
@endsection
