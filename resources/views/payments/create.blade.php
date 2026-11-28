@extends('layouts.dms')
@section('title', 'Record Payment')
@section('content')
@php
    $invoicePayload = $invoices->map(fn ($inv) => [
        'id' => $inv->id,
        'customer_id' => $inv->customer_id,
        'invoice_no' => $inv->invoice_no,
        'customer_name' => $inv->customer?->name,
        'due' => round((float) $inv->grand_total - (float) $inv->paid_amount, 2),
    ])->values();

    $bankPayload = $bankLedgers->map(fn ($ledger) => [
        'id' => $ledger->id,
        'name' => $ledger->name,
        'bank_name' => $ledger->bankAccount?->bank_name,
        'account_number' => $ledger->bankAccount?->account_number,
        'ifsc' => $ledger->bankAccount?->ifsc,
    ])->values();

    $upiPayload = collect($upiAccounts ?? [])->values();
@endphp
<x-ui.page-header title="Record Payment">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('payments.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form
    method="POST"
    action="{{ route('payments.store') }}"
    class="space-y-4 max-w-xl"
    x-data="paymentForm({
        method: @js(old('method', 'cash')),
        customerId: @js(old('customer_id', $invoice?->customer_id)),
        invoiceId: @js(old('invoice_id', $invoice?->id)),
        bankLedgerId: @js(old('bank_ledger_id')),
        upiId: @js(old('upi_id')),
        amount: @js(old('amount', $invoice ? round((float) $invoice->grand_total - (float) $invoice->paid_amount, 2) : '')),
        invoices: @js($invoicePayload),
        banks: @js($bankPayload),
        upis: @js($upiPayload),
    })"
>
@csrf

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Customer <span class="text-rose-500">*</span></label>
    <select name="customer_id" x-model="customerId" @change="onCustomerChange()" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Select customer</option>
        @foreach($customers as $customer)
            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
        @endforeach
    </select>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Invoice <span class="text-rose-500">*</span></label>
    <select name="invoice_id" x-model="invoiceId" @change="onInvoiceChange()" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="">Select invoice</option>
        <template x-for="inv in filteredInvoices" :key="inv.id">
            <option :value="inv.id" x-text="`${inv.invoice_no} — ₹${Number(inv.due).toLocaleString('en-IN', {minimumFractionDigits: 2})} due`"></option>
        </template>
    </select>
    <p class="mt-1 text-xs text-slate-500" x-show="customerId && filteredInvoices.length === 0">No open invoices for this customer.</p>
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Amount <span class="text-rose-500">*</span></label>
    <input type="number" name="amount" step="0.01" min="0.01" x-model="amount" required class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
</div>

<div>
    <label class="block text-sm font-medium text-slate-700 mb-1">Method <span class="text-rose-500">*</span></label>
    <select name="method" x-model="method" required class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
        <option value="cash">Cash</option>
        <option value="upi">UPI</option>
        <option value="bank">Bank</option>
        <option value="cheque">Cheque</option>
        <option value="other">Other</option>
    </select>
</div>

<div x-show="method === 'bank'" x-cloak class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Bank account <span class="text-rose-500">*</span></label>
        <select name="bank_ledger_id" x-model="bankLedgerId" :required="method === 'bank'" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Select bank account</option>
            <template x-for="bank in banks" :key="bank.id">
                <option :value="bank.id" x-text="bank.account_number ? `${bank.name} — ${bank.account_number}` : bank.name"></option>
            </template>
        </select>
        <p class="mt-1 text-xs text-amber-700" x-show="banks.length === 0">No bank accounts are set up in Bank Master for this company yet.</p>
    </div>
    <div x-show="selectedBank" x-cloak class="rounded-lg border border-indigo-100 bg-white p-3 text-sm text-slate-700 space-y-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-800">Bank details</p>
        <p><span class="text-slate-500">Ledger:</span> <span x-text="selectedBank?.name || '—'"></span></p>
        <p><span class="text-slate-500">Bank:</span> <span x-text="selectedBank?.bank_name || '—'"></span></p>
        <p><span class="text-slate-500">A/C No:</span> <span x-text="selectedBank?.account_number || '—'"></span></p>
        <p><span class="text-slate-500">IFSC:</span> <span x-text="selectedBank?.ifsc || '—'"></span></p>
    </div>
</div>

<div x-show="method === 'upi'" x-cloak class="space-y-3 rounded-xl border border-slate-200 bg-slate-50/70 p-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">UPI ID <span class="text-rose-500">*</span></label>
        <select name="upi_id" x-model="upiId" :required="method === 'upi'" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
            <option value="">Select UPI</option>
            <template x-for="upi in upis" :key="upi.upi_id">
                <option :value="upi.upi_id" x-text="`${upi.upi_id}${upi.bank_name ? ' — ' + upi.bank_name : ''}`"></option>
            </template>
        </select>
        <p class="mt-1 text-xs text-amber-700" x-show="upis.length === 0">No UPI IDs are set up in Company bank accounts yet.</p>
    </div>
    <div x-show="selectedUpi" x-cloak class="rounded-lg border border-indigo-100 bg-white p-3 text-sm text-slate-700 space-y-1">
        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-800">UPI details</p>
        <p><span class="text-slate-500">UPI ID:</span> <span x-text="selectedUpi?.upi_id || '—'"></span></p>
        <p><span class="text-slate-500">Bank:</span> <span x-text="selectedUpi?.bank_name || '—'"></span></p>
        <p><span class="text-slate-500">A/C No:</span> <span x-text="selectedUpi?.account_number || '—'"></span></p>
        <p><span class="text-slate-500">IFSC:</span> <span x-text="selectedUpi?.ifsc || '—'"></span></p>
        <p><span class="text-slate-500">Branch:</span> <span x-text="selectedUpi?.branch || '—'"></span></p>
    </div>
</div>

<div x-show="method === 'cheque'" x-cloak class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4 space-y-3">
    <h4 class="text-xs font-semibold text-indigo-900 uppercase tracking-wider">Cheque Information</h4>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div>
            <x-ui.input name="cheque_no" label="Cheque Number" :value="old('cheque_no')" placeholder="e.g. 000124" />
        </div>
        <div>
            <x-ui.input name="cheque_date" label="Cheque Date" type="date" :value="old('cheque_date', now()->toDateString())" />
        </div>
    </div>
    <div>
        <x-ui.input name="cheque_bank_name" label="Drawee Bank Name & Branch" :value="old('cheque_bank_name')" placeholder="e.g. HDFC Bank, MG Road" />
    </div>
    <p class="text-xs text-slate-500">A corresponding entry will automatically be created in the <strong>Cheque Register</strong>.</p>
</div>

<x-ui.input name="paid_at" label="Paid At" type="datetime-local" :value="old('paid_at', now()->format('Y-m-d\TH:i'))" />
<x-ui.input name="notes" label="Notes" :value="old('notes')" />
<x-ui.button type="submit" variant="primary">Save Payment</x-ui.button>
</form>
</x-ui.card>

<script>
function paymentForm(config) {
    return {
        method: config.method || 'cash',
        customerId: config.customerId ? String(config.customerId) : '',
        invoiceId: config.invoiceId ? String(config.invoiceId) : '',
        bankLedgerId: config.bankLedgerId ? String(config.bankLedgerId) : '',
        upiId: config.upiId || '',
        amount: config.amount !== undefined && config.amount !== null ? String(config.amount) : '',
        invoices: config.invoices || [],
        banks: config.banks || [],
        upis: config.upis || [],
        get filteredInvoices() {
            if (!this.customerId) return this.invoices;
            return this.invoices.filter(inv => String(inv.customer_id) === String(this.customerId));
        },
        get selectedBank() {
            return this.banks.find(b => String(b.id) === String(this.bankLedgerId)) || null;
        },
        get selectedUpi() {
            return this.upis.find(u => String(u.upi_id) === String(this.upiId)) || null;
        },
        init() {
            if (this.invoiceId) {
                const inv = this.invoices.find(i => String(i.id) === String(this.invoiceId));
                if (inv) {
                    this.customerId = String(inv.customer_id);
                    if (this.amount === '' || this.amount === null) this.amount = inv.due;
                }
            }
        },
        onCustomerChange() {
            const stillValid = this.filteredInvoices.some(inv => String(inv.id) === String(this.invoiceId));
            if (!stillValid) {
                this.invoiceId = '';
                this.amount = '';
            }
            if (this.filteredInvoices.length === 1) {
                this.invoiceId = String(this.filteredInvoices[0].id);
                this.onInvoiceChange();
            }
        },
        onInvoiceChange() {
            const inv = this.invoices.find(i => String(i.id) === String(this.invoiceId));
            if (inv) {
                this.customerId = String(inv.customer_id);
                this.amount = inv.due;
            }
        },
    };
}
</script>
@endsection
