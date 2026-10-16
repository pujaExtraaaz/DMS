@extends('layouts.dms')
@section('title', 'Edit Stock Adjustment - ' . $adjustment->adjustment_no)

@section('content')
<div class="space-y-4">
    <x-ui.page-header :title="'Edit Stock Adjustment: ' . $adjustment->adjustment_no">
        <x-slot name="actions">
            <x-ui.button variant="secondary" :href="route('inventory.adjustments.show', $adjustment)">View Details</x-ui.button>
            <x-ui.button variant="secondary" :href="route('inventory.adjustments.index')">Back to List</x-ui.button>
        </x-slot>
    </x-ui.page-header>

    <x-ui.card>
        <div class="mb-4 pb-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="text-sm font-bold text-slate-800 tracking-wider">Modify Draft Adjustment</h2>
                <p class="text-xs text-slate-500 mt-0.5">Adjustment No: <span class="font-mono font-semibold text-indigo-700">{{ $adjustment->adjustment_no }}</span> | Status: <span class="font-semibold uppercase text-amber-700">{{ $adjustment->status }}</span></p>
            </div>
            <span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                Draft Adjustment
            </span>
        </div>

        <form method="POST" action="{{ route('inventory.adjustments.update', $adjustment) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <x-ui.input
                    name="adjustment_date"
                    label="Date"
                    type="date"
                    :value="old('adjustment_date', $adjustment->adjustment_date?->format('Y-m-d'))"
                    required
                />

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Warehouse</label>
                    <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">Unassigned</option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id', $adjustment->warehouse_id) == $w->id)>{{ $w->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Reason <span class="text-rose-500">*</span></label>
                    <select name="reason" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        @foreach(['opening', 'damage', 'shrinkage', 'found', 'recount', 'other'] as $r)
                            <option value="{{ $r }}" @selected(old('reason', $adjustment->reason) === $r)>{{ ucfirst($r) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="p-3 rounded-lg bg-slate-50 border border-slate-200 text-xs text-slate-600 flex items-center justify-between">
                <span>Enter positive quantity (+) to increase stock, or negative quantity (-) to decrease stock.</span>
                <span class="font-semibold text-slate-700">All draft changes preserve original reference: {{ $adjustment->adjustment_no }}</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Adjustment Items <span class="text-rose-500">*</span></label>
                <div class="overflow-x-auto border border-slate-200 rounded-lg">
                    <table class="min-w-full text-xs">
                        <thead class="bg-slate-50 text-slate-600 border-b">
                            <tr>
                                <th class="px-3 py-2 text-left">Product</th>
                                <th class="px-3 py-2 text-left w-36">UOM</th>
                                <th class="px-3 py-2 text-left w-36">Qty (+ / -)</th>
                                <th class="px-3 py-2 text-left">Line Notes</th>
                                <th class="px-3 py-2 text-center w-16">Remove</th>
                            </tr>
                        </thead>
                        <tbody id="adj-lines" class="divide-y divide-slate-100">
                            @php
                                $oldItems = old('items', $adjustment->items->toArray());
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
                                        <input type="number" step="0.0001" name="items[{{ $idx }}][quantity]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono font-bold" value="{{ $item['quantity'] ?? 1 }}" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="items[{{ $idx }}][notes]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional line notes..." value="{{ $item['notes'] ?? '' }}">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick="removeAdjRow(this)" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Remove line">
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
                                        <input type="number" step="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500 font-mono font-bold" value="1" required>
                                    </td>
                                    <td class="px-3 py-2">
                                        <input type="text" name="items[0][notes]" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional line notes...">
                                    </td>
                                    <td class="px-3 py-2 text-center">
                                        <button type="button" onclick="removeAdjRow(this)" class="p-1 rounded text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Remove line">
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
                <label class="block text-xs font-semibold text-slate-700 tracking-wider mb-1">Remarks / Notes</label>
                <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Optional remarks...">{{ old('notes', $adjustment->notes) }}</textarea>
            </div>

            <div class="flex items-center justify-between pt-3 border-t border-slate-100">
                <x-ui.button type="button" variant="secondary" onclick="addAdjRow()">+ Add Line</x-ui.button>

                <div class="flex gap-2">
                    <button type="submit" name="action" value="draft" class="inline-flex items-center justify-center font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-white text-gray-700 border border-gray-300 hover:bg-gray-50 focus:ring-indigo-500 shadow-sm px-4 py-2 text-sm gap-2">
                        Save as Draft
                    </button>
                    <button type="submit" name="action" value="post" class="inline-flex items-center justify-center font-medium rounded-lg transition focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed bg-indigo-600 text-white hover:bg-indigo-700 focus:ring-indigo-500 shadow-sm px-4 py-2 text-sm gap-2">
                        Post Adjustment
                    </button>
                </div>
            </div>
        </form>
    </x-ui.card>
</div>

@push('scripts')
<script>
let adi = {{ count($oldItems ?? [1]) }};
function addAdjRow() {
    const t = document.getElementById('adj-lines');
    const firstRow = t.rows[0];
    if (!firstRow) return;
    const newRow = firstRow.cloneNode(true);
    newRow.querySelectorAll('input, select').forEach(el => {
        el.name = el.name.replace(/items\[\d+\]/, 'items[' + adi + ']');
        if (el.tagName === 'INPUT' && el.type === 'number') el.value = 1;
        if (el.tagName === 'INPUT' && el.type === 'text') el.value = '';
    });
    t.appendChild(newRow);
    adi++;
}

function removeAdjRow(btn) {
    const t = document.getElementById('adj-lines');
    if (t.rows.length <= 1) {
        alert('Stock adjustment must have at least one line item.');
        return;
    }
    btn.closest('tr').remove();
}
</script>
@endpush
@endsection

