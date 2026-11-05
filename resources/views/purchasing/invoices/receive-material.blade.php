@extends('layouts.dms')
@section('title', 'Receive Material — '.$invoice->invoice_no)
@section('content')
<x-ui.page-header :title="'Receive Material: '.$invoice->invoice_no">
    <x-slot name="actions">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.show', $invoice)">Back to Invoice</x-ui.button>
    </x-slot>
</x-ui.page-header>

<div class="mb-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
    <x-ui.card>
        <p class="text-xs text-slate-500">Supplier</p>
        <p class="font-medium">{{ $invoice->supplier?->name }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Supplier Invoice Date</p>
        <p class="font-medium">{{ $invoice->invoice_date?->format('d M Y') ?? '—' }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Current Status</p>
        <p class="font-medium">{{ $invoice->materialReceiptLabel() }}</p>
    </x-ui.card>
    <x-ui.card>
        <p class="text-xs text-slate-500">Warehouse</p>
        <p class="font-medium">{{ $invoice->warehouse?->name ?? '—' }}</p>
    </x-ui.card>
</div>

<x-ui.alert type="info" message="Recording a purchase invoice does not add stock. Enter quantities actually received below. Remaining qty stays In Transit." />

<form method="POST" action="{{ route('purchasing.invoices.receive-material.store', $invoice) }}" class="space-y-4 mt-4">
    @csrf
    <x-ui.card>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Actual Material Receipt Date *</label>
                <input type="date" name="actual_material_receipt_date" required
                       value="{{ old('actual_material_receipt_date', now()->toDateString()) }}"
                       class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                @error('actual_material_receipt_date')<p class="text-xs text-rose-600 mt-1">{{ $message }}</p>@enderror
            </div>
            <div class="flex items-end">
                <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                    <input type="checkbox" name="mark_all_received" value="1" @checked(old('mark_all_received'))
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                           onchange="document.querySelectorAll('.pi-receive-qty').forEach(el => { if (this.checked) { el.value = el.dataset.remaining; el.readOnly = true; } else { el.readOnly = false; } })">
                    Mark all remaining quantities as received
                </label>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Invoiced Qty</th>
                        <th class="px-3 py-2 text-right">Already Received</th>
                        <th class="px-3 py-2 text-right">In Transit</th>
                        <th class="px-3 py-2 text-right">Receive Now</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($invoice->items as $i => $item)
                        @php $remaining = $item->remainingQty(); @endphp
                        <tr>
                            <td class="px-3 py-2">
                                {{ $item->product?->name }}
                                <input type="hidden" name="items[{{ $i }}][id]" value="{{ $item->id }}">
                            </td>
                            <td class="px-3 py-2 text-right font-mono">{{ number_format((float) $item->quantity, 2) }}</td>
                            <td class="px-3 py-2 text-right font-mono">{{ number_format((float) $item->received_qty, 2) }}</td>
                            <td class="px-3 py-2 text-right font-mono text-amber-700">{{ number_format($remaining, 2) }}</td>
                            <td class="px-3 py-2 text-right">
                                <input type="number" step="0.0001" min="0" max="{{ $remaining }}"
                                       name="items[{{ $i }}][receive_qty]"
                                       value="{{ old('items.'.$i.'.receive_qty', $remaining > 0 ? $remaining : 0) }}"
                                       data-remaining="{{ $remaining }}"
                                       @disabled($remaining <= 0)
                                       class="pi-receive-qty w-28 rounded-lg border-gray-300 text-sm text-right @if($remaining <= 0) bg-slate-100 @endif">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @error('items')<p class="text-xs text-rose-600 mt-2">{{ $message }}</p>@enderror
    </x-ui.card>

    <div class="flex justify-end gap-3">
        <x-ui.button variant="secondary" :href="route('purchasing.invoices.show', $invoice)">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Confirm Material Receipt</x-ui.button>
    </div>
</form>
@endsection
