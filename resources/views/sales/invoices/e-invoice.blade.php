@extends('layouts.dms')

@section('title', 'E-Invoice - '.$invoice->invoice_no)

@section('content')
<x-ui.page-header title="E-Invoice" :description="$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('invoices.show', $invoice)">Back to Invoice</x-ui.button>
    </x-slot>
</x-ui.page-header>

{{-- e-Invoice Summary / Meta Card --}}
<div class="mb-6 rounded-xl border border-indigo-200 bg-white p-5 shadow-sm">
    <div class="flex flex-wrap items-center justify-between gap-4 pb-4 border-b border-slate-100">
        <div>
            <div class="flex items-center gap-2.5">
                <span class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-indigo-600 text-white font-bold text-sm shadow">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </span>
                <div>
                    <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider">GST e-Invoice Details</h3>
                    <p class="text-xs text-slate-500">Government Invoice Registration Portal (IRP) Verification Data</p>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-3">
            @php
                $eInv = $invoice->eInvoice;
                $status = strtolower($eInv?->status ?? 'pending');
            @endphp
            @if($status === 'generated' || $status === 'active')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-300">
                    <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                    Generated / Active
                </span>
            @elseif($status === 'cancelled')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-800 border border-rose-300">
                    <span class="w-2 h-2 rounded-full bg-rose-600"></span>
                    Cancelled
                </span>
            @elseif($status === 'failed')
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-300">
                    <span class="w-2 h-2 rounded-full bg-amber-600"></span>
                    Failed
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-800 border border-slate-300">
                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                    {{ ucfirst($status) }}
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4 text-xs">
        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
            <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Acknowledgement No. (Ack No.)</span>
            <span class="font-mono font-bold text-slate-800 text-sm mt-0.5 block">
                {{ $invoice->eInvoice?->ack_no ?: '—' }}
            </span>
        </div>

        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
            <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Acknowledgement Date (Ack Date)</span>
            <span class="font-mono font-medium text-slate-800 text-xs mt-1 block">
                {{ $invoice->eInvoice?->ack_date ? (is_string($invoice->eInvoice->ack_date) ? $invoice->eInvoice->ack_date : $invoice->eInvoice->ack_date->format('d/m/Y H:i:s')) : '—' }}
            </span>
        </div>

        <div class="sm:col-span-2 p-3 bg-slate-50 rounded-lg border border-slate-200">
            <div class="flex items-center justify-between">
                <span class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Invoice Reference Number (IRN)</span>
                @if($invoice->eInvoice?->irn)
                    <button type="button"
                            onclick="navigator.clipboard.writeText('{{ $invoice->eInvoice->irn }}'); alert('IRN copied to clipboard!')"
                            class="inline-flex items-center gap-1 text-[11px] font-semibold text-indigo-600 hover:text-indigo-800">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/></svg>
                        <span>Copy IRN</span>
                    </button>
                @endif
            </div>
            <div class="font-mono text-xs font-bold text-slate-800 truncate mt-1 select-all" title="{{ $invoice->eInvoice?->irn }}">
                {{ $invoice->eInvoice?->irn ?: '—' }}
            </div>
        </div>
    </div>
</div>

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
