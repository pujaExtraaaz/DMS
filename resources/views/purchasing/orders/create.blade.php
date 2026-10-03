@extends('layouts.dms')
@section('title', 'New Purchase Order')
@section('content')
<div x-data>
    <x-ui.page-header title="New Purchase Order">
        <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Back</x-ui.button></x-slot>
    </x-ui.page-header>
    <x-ui.card>
    <form method="POST" action="{{ route('purchasing.orders.store') }}" class="space-y-4">@csrf
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <div class="flex items-center justify-between mb-1">
                <label class="block text-sm font-medium text-slate-700">Supplier *</label>
                <button type="button" @click="$dispatch('open-quick-add-supplier')"
                        class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    + Add Supplier
                </button>
            </div>
            <select name="supplier_id" class="block w-full rounded-lg border-gray-300 text-sm" required>
                <option value="">Select supplier</option>
                @foreach($suppliers as $s)<option value="{{ $s->id }}" @selected(old('supplier_id')==$s->id)>{{ $s->name }}</option>@endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
            <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm">
                <option value="">Optional</option>
                @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->name }}</option>@endforeach
            </select>
        </div>
        <x-ui.input name="po_date" label="PO Date" type="date" :value="old('po_date', now()->toDateString())" required />
        <x-ui.input name="expected_date" label="Expected Date" type="date" :value="old('expected_date')" />
    </div>
    <div class="overflow-x-auto border rounded-lg">
    <table class="min-w-full text-sm">
    <thead class="bg-gray-50"><tr>
    <th class="px-3 py-2 text-left">Product</th>
    <th class="px-3 py-2 text-left">Batch Name</th>
    <th class="px-3 py-2 text-left">UOM</th>
    <th class="px-3 py-2 text-left">Qty</th>
    <th class="px-3 py-2 text-left">Unit Cost</th>
    <th class="px-3 py-2 text-left">Tax %</th>
    <th class="px-3 py-2 text-left">CGST %</th>
    <th class="px-3 py-2 text-left">SGST %</th>
    <th class="px-3 py-2"></th>
    </tr></thead>
    <tbody id="po-lines"><tr>
    <td class="px-3 py-2">
        <select name="items[0][product_id]" class="po-product block w-52 rounded-lg border-gray-300 text-sm" required onchange="handlePoProductChange(this)">
            <option value="">Select product</option>
            @foreach($products as $p)
                <option value="{{ $p->id }}" data-tax="{{ $p->tax_rate ?? 0 }}" data-cost="{{ $p->purchase_price ?? 0 }}" data-uom="{{ $p->base_uom_id }}">{{ $p->name }}</option>
            @endforeach
        </select>
    </td>
    <td class="px-3 py-2">
        <input type="text" name="items[0][batch_no]" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional">
    </td>
    <td class="px-3 py-2">
        <select name="items[0][uom_id]" class="po-uom block w-24 rounded-lg border-gray-300 text-sm" required>
            @foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach
        </select>
    </td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="block w-20 rounded-lg border-gray-300 text-sm" value="1" required></td>
    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][unit_cost]" class="po-cost block w-28 rounded-lg border-gray-300 text-sm" value="0" required></td>
    <td class="px-3 py-2">
        <input
            type="number"
            step="0.01"
            min="0"
            max="100"
            name="items[0][tax_percent]"
            class="po-tax block w-20 rounded-lg border-gray-300 text-sm"
            value="0"
        >
    </td>
    <td class="px-3 py-2">
        <input
            type="number"
            step="0.01"
            min="0"
            max="100"
            name="items[0][cgst_percent]"
            class="po-cgst block w-20 rounded-lg border-gray-300 text-sm"
            value="0"
        >
    </td>
    <td class="px-3 py-2">
        <input
            type="number"
            step="0.01"
            min="0"
            max="100"
            name="items[0][sgst_percent]"
            class="po-sgst block w-20 rounded-lg border-gray-300 text-sm"
            value="0"
        >
    </td>
    <td class="px-3 py-2 text-right">
        <button type="button" onclick="removePoRow(this)" class="text-xs text-red-600 hover:text-red-800 po-remove-btn" style="display:none;">Remove</button>
    </td>
    </tr></tbody></table></div>
    <div class="flex flex-wrap gap-3 items-center">
    <x-ui.button type="button" variant="secondary" onclick="addPoRow()">Add Line</x-ui.button>
    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="submit_for_approval" value="1" class="rounded border-gray-300"> Submit for approval</label>
    <x-ui.button type="submit" variant="primary">Save PO</x-ui.button>
    </div>
    </form></x-ui.card>
    <x-quick-add-supplier targetSelect="supplier_id" />
</div>
@push('scripts')
<script>
let poi = 1;

function handlePoProductChange(select) {
    const row = select.closest('tr');
    if (!row) return;

    const opt = select.options[select.selectedIndex];
    if (!opt || !opt.value) return;

    const taxRate = parseFloat(opt.getAttribute('data-tax')) || 0;
    const cost = parseFloat(opt.getAttribute('data-cost')) || 0;
    const uomId = opt.getAttribute('data-uom');

    const taxInput = row.querySelector('.po-tax');
    const cgstInput = row.querySelector('.po-cgst');
    const sgstInput = row.querySelector('.po-sgst');
    const costInput = row.querySelector('.po-cost');
    const uomSelect = row.querySelector('.po-uom');

    if (taxInput) taxInput.value = taxRate;
    if (cgstInput) cgstInput.value = (taxRate / 2).toFixed(2);
    if (sgstInput) sgstInput.value = (taxRate / 2).toFixed(2);
    if (costInput && (!costInput.value || costInput.value === '0')) costInput.value = cost;
    if (uomSelect && uomId) uomSelect.value = uomId;
}

function syncPoTax(row, changed) {
    const tax = row.querySelector('.po-tax');
    const cgst = row.querySelector('.po-cgst');
    const sgst = row.querySelector('.po-sgst');

    if (!tax || !cgst || !sgst) {
        return;
    }

    const taxValue = parseFloat(tax.value) || 0;

    if (changed === 'tax') {
        const half = (taxValue / 2).toFixed(2);
        cgst.value = half;
        sgst.value = half;
        return;
    }

    const cgstValue = parseFloat(cgst.value) || 0;

    if (changed === 'cgst') {
        sgst.value = Math.max(0, taxValue - cgstValue).toFixed(2);
        return;
    }

    if (changed === 'sgst') {
        cgst.value = Math.max(0, taxValue - (parseFloat(sgst.value) || 0)).toFixed(2);
    }
}

function addPoRow() {
    const table = document.getElementById('po-lines');
    const firstRow = table.rows[0];
    const newRow = firstRow.cloneNode(true);

    newRow.innerHTML = newRow.innerHTML.replace(/items\[0\]/g, 'items[' + poi + ']');
    
    newRow.querySelectorAll('input').forEach(input => {
        if (input.name.includes('[quantity]')) {
            input.value = '1';
        } else if (input.name.includes('[batch_no]')) {
            input.value = '';
        } else {
            input.value = '0';
        }
    });
    newRow.querySelectorAll('select').forEach(sel => {
        if (sel.classList.contains('po-product')) {
            sel.selectedIndex = 0;
        }
    });

    const removeBtn = newRow.querySelector('.po-remove-btn');
    if (removeBtn) removeBtn.style.display = 'inline';

    table.appendChild(newRow);
    poi++;
    updateRemoveButtons();
}

function removePoRow(btn) {
    const table = document.getElementById('po-lines');
    if (table.rows.length > 1) {
        btn.closest('tr').remove();
    }
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const table = document.getElementById('po-lines');
    const rows = table.rows;
    for (let i = 0; i < rows.length; i++) {
        const btn = rows[i].querySelector('.po-remove-btn');
        if (btn) {
            btn.style.display = rows.length > 1 ? 'inline' : 'none';
        }
    }
}

document.addEventListener('input', function (event) {
    const input = event.target;

    if (
        !input.classList.contains('po-tax') &&
        !input.classList.contains('po-cgst') &&
        !input.classList.contains('po-sgst')
    ) {
        return;
    }

    const row = input.closest('tr');
    if (!row) return;

    if (input.classList.contains('po-tax')) {
        syncPoTax(row, 'tax');
    } else if (input.classList.contains('po-cgst')) {
        syncPoTax(row, 'cgst');
    } else if (input.classList.contains('po-sgst')) {
        syncPoTax(row, 'sgst');
    }
});
</script>
@endpush
@endsection