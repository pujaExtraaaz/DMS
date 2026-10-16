@extends('layouts.dms')
@section('title', 'Edit Freight Bill')
@section('content')
<x-ui.page-header :title="'Edit Freight Bill: ' . $freightBill->freight_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.freight-bills.show', $freightBill)">View</x-ui.button>
        <x-ui.button variant="secondary" :href="route('purchasing.freight-bills.index')">Back to List</x-ui.button>
    </x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('purchasing.freight-bills.update', $freightBill) }}" class="space-y-5" x-data="{
        amount: {{ (float) old('amount', $freightBill->amount ?? 0) }},
        tax: {{ (float) old('tax_amount', $freightBill->tax_amount ?? 0) }},
        get total() {
            const a = parseFloat(this.amount) || 0;
            const t = parseFloat(this.tax) || 0;
            return (a + t).toFixed(2);
        }
    }">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input name="freight_no" label="Freight Bill No" :value="old('freight_no', $freightBill->freight_no)" required />
            <x-ui.input name="bill_date" label="Bill Date" type="date" :value="old('bill_date', $freightBill->bill_date?->format('Y-m-d'))" required />

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
                <select name="status" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="posted" @selected(old('status', $freightBill->status) === 'posted')>Posted</option>
                    <option value="draft" @selected(old('status', $freightBill->status) === 'draft')>Draft</option>
                </select>
            </div>

            <x-ui.input name="transporter_name" label="Transporter" :value="old('transporter_name', $freightBill->transporter_name)" />
            <x-ui.input name="vehicle_no" label="Vehicle No" :value="old('vehicle_no', $freightBill->vehicle_no)" />
            <x-ui.input name="lr_no" label="LR No" :value="old('lr_no', $freightBill->lr_no)" />

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Amount <span class="text-red-500">*</span></label>
                <input
                    type="number"
                    step="0.01"
                    name="amount"
                    x-model="amount"
                    required
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Tax Amount</label>
                <input
                    type="number"
                    step="0.01"
                    name="tax_amount"
                    x-model="tax"
                    class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>

            <div class="rounded-lg bg-slate-50 border border-slate-200 p-3 flex flex-col justify-center">
                <span class="text-xs font-medium text-slate-500 uppercase tracking-wider">Calculated Total</span>
                <span class="text-lg font-bold text-slate-900 mt-0.5">₹<span x-text="total"></span></span>
            </div>

            <div class="md:col-span-3">
                <label class="block text-sm font-medium text-slate-700 mb-1">Link Purchase Invoice (optional)</label>
                <select name="purchase_invoice_id" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">— No Linked Invoice —</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}" @selected(old('purchase_invoice_id', $linkedInvoiceId) == $inv->id)>
                            {{ $inv->invoice_no }} — {{ $inv->supplier?->name }} (₹{{ number_format($inv->grand_total ?? $inv->total_amount ?? 0, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Allocation Basis</label>
            @php $selected = old('allocation_basis', $freightBill->allocation_basis ?? 'value'); @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3 text-sm">
                @foreach([
                    'value' => 'By Value (line total ratio)',
                    'qty' => 'By Quantity',
                    'weight' => 'By Weight (needs item weight)',
                    'volume' => 'By Volume (needs item volume)',
                    'equal' => 'Equal split per line',
                    'manual' => 'Manual (enter per line during landed cost)',
                ] as $key => $label)
                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 cursor-pointer">
                        <input type="radio" name="allocation_basis" value="{{ $key }}" @checked($selected === $key) class="text-indigo-600 focus:ring-indigo-500">
                        <span class="text-xs text-slate-700 font-medium">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea name="notes" rows="3" class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes', $freightBill->notes) }}</textarea>
        </div>

        <div class="flex items-center gap-3 pt-3 border-t border-slate-100">
            <x-ui.button type="submit" variant="primary">Update Freight Bill</x-ui.button>
            <x-ui.button variant="secondary" :href="route('purchasing.freight-bills.index')">Cancel</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection

