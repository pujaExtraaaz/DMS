@extends('layouts.dms')
@section('title', 'Edit Stock Transfer - ' . $transfer->transfer_no)

@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="'Edit Stock Transfer: ' . $transfer->transfer_no">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('inventory.transfers.show', $transfer)">View Details</x-ui.button>
            <x-ui.button variant="secondary" :href="route('inventory.transfers.index', ['tab' => 'location'])">Back to List</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    @if($transfer->status === 'in_transit')
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-800 flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <div>
                <span class="font-bold">In-Transit Transfer Notice:</span> This transfer is currently marked as <span class="font-semibold">In Transit</span>. Modifying items or source warehouses will safely reverse the prior dispatched quantities back to the original source warehouse and re-dispatch the updated quantities automatically.
            </div>
        </div>
    @endif

    <x-ui.card>
        <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-800 tracking-wider">Modify Transfer Information</h2>
                <p class="text-xs text-slate-500 mt-0.5">Transfer No: <span class="font-mono font-semibold text-indigo-700">{{ $transfer->transfer_no }}</span> | Status: <span class="font-medium uppercase">{{ str_replace('_', ' ', $transfer->status) }}</span></p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-indigo-50 text-indigo-700 border border-indigo-200">
                {{ ucfirst(str_replace('_', ' ', $transfer->status)) }}
            </span>
        </div>

        <form method="POST" action="{{ route('inventory.transfers.update', $transfer) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    name="transfer_date"
                    label="Date"
                    type="date"
                    :value="old('transfer_date', $transfer->transfer_date?->format('Y-m-d'))"
                    required
                />

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        From Warehouse <span class="text-rose-500">*</span>
                    </label>
                    <select name="from_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select source warehouse...</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('from_warehouse_id', $transfer->from_warehouse_id) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">
                        To Warehouse <span class="text-rose-500">*</span>
                    </label>
                    <select name="to_warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">Select destination warehouse...</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('to_warehouse_id', $transfer->to_warehouse_id) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Transfer Items <span class="text-rose-500">*</span></label>
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b">
                            <tr>
                                <th class="px-3 py-2 text-left">Product</th>
                                <th class="px-3 py-2 text-left w-36">UOM</th>
                                <th class="px-3 py-2 text-left w-36">Quantity</th>
                                <th class="px-3 py-2 text-center w-16">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="tr-lines" class="divide-y divide-slate-100">
                            @php
                                $oldItems = old('items', $transfer->items->toArray());
                            @endphp
                            @forelse($oldItems as $idx => $item)
                                <tr>
                                    <td class="px-3 py-2">
                                        <select name="items[{{ $idx }}][product_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            <option value="">Select product...</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}" @selected(($item['product_id'] ?? null) == $p->id)>{{ $p->name }} ({{ $p->sku }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select name="items[{{ $idx }}][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            @foreach($uoms as $u)
                                                <option value="{{ $u->id }}" @selected(($item['uom_id'] ?? null) == $u->id)>{{ $u->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.0001" min="0.0001" name="items[{{ $idx }}][quantity]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ $item['quantity'] ?? 1 }}" required>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick="removeTrRow(this)" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Remove line">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-3 py-2">
                                        <select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            <option value="">Select product...</option>
                                            @foreach($products as $p)
                                                <option value="{{ $p->id }}">{{ $p->name }} ({{ $p->sku }})</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                            @foreach($uoms as $u)
                                                <option value="{{ $u->id }}">{{ $u->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="number" step="0.0001" min="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" value="1" required>
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick="removeTrRow(this)" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Remove line">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Notes / Remarks</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional notes about this transfer...">{{ old('notes', $transfer->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                @if($transfer->status === 'draft')
                    <label class="inline-flex items-center gap-2 text-xs font-medium text-slate-700 cursor-pointer">
                        <input type="checkbox" name="dispatch_now" value="1" class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        Dispatch now (deduct stock from source immediately)
                    </label>
                @else
                    <div></div>
                @endif

                <div class="flex gap-2">
                    <x-ui.button type="button" variant="secondary" onclick="addTrRow()">+ Add Line</x-ui.button>
                    <x-ui.button type="submit" variant="primary">Save Changes</x-ui.button>
                </div>
            </div>
        </form>
    </x-ui.card>
</div>

@push('scripts')
<script>
let tri = {{ count($oldItems ?? [1]) }};
function addTrRow() {
    const t = document.getElementById('tr-lines');
    const firstRow = t.rows[0];
    if (!firstRow) return;
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(el => {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + tri + ']');
        if (el.tagName === 'INPUT') el.value = 1;
    });
    t.appendChild(newRow);
    tri++;
}

function removeTrRow(btn) {
    const t = document.getElementById('tr-lines');
    if (t.rows.length <= 1) {
        alert('A stock transfer must contain at least one line item.');
        return;
    }
    btn.closest('tr').remove();
}
</script>
@endpush
@endsection

