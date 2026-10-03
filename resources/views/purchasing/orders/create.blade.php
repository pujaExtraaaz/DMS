@extends('layouts.dms')
@section('title', 'New Purchase Order')
@section('content')
<x-ui.page-header title="New Purchase Order">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.orders.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<form method="POST" action="{{ route('purchasing.orders.store') }}" class="space-y-4">@csrf
<div class="grid grid-cols-1 md:grid-cols-3 gap-4">
    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Supplier</label>
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
<th class="px-3 py-2 text-left">UOM</th>
<th class="px-3 py-2 text-left">Qty</th>
<th class="px-3 py-2 text-left">Unit Cost</th>
<th class="px-3 py-2 text-left">Tax %</th>
<th class="px-3 py-2 text-left">CGST %</th>
<th class="px-3 py-2 text-left">SGST %</th>
<th></th>
</tr></thead>
<tbody id="po-lines"><tr>
<td class="px-3 py-2"><select name="items[0][product_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
<td class="px-3 py-2"><select name="items[0][uom_id]" class="block w-full rounded-lg border-gray-300 text-sm" required>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
<td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="block w-full rounded-lg border-gray-300 text-sm" value="1" required></td>
<td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][unit_cost]" class="block w-full rounded-lg border-gray-300 text-sm" required></td>
<td class="px-3 py-2">
    <input
        type="number"
        step="0.01"
        min="0"
        max="100"
        name="items[0][tax_percent]"
        class="po-tax block w-full rounded-lg border-gray-300 text-sm"
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
        class="po-cgst block w-full rounded-lg border-gray-300 text-sm"
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
        class="po-sgst block w-full rounded-lg border-gray-300 text-sm"
        value="0"
    >
</td>
<td></td>
</tr></tbody></table></div>
<div class="flex flex-wrap gap-3 items-center">
<x-ui.button type="button" variant="secondary" onclick="addPoRow()">Add Line</x-ui.button>
<label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="submit_for_approval" value="1" class="rounded border-gray-300"> Submit for approval</label>
<x-ui.button type="submit" variant="primary">Save PO</x-ui.button>
</div>
</form></x-ui.card>
@push('scripts')
<script>
let poi = 1;

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

    table.insertAdjacentHTML(
        'beforeend',
        table.rows[0].outerHTML.replace(
            /items\[0\]/g,
            'items[' + poi + ']'
        )
    );

    const row = table.rows[table.rows.length - 1];

    row.querySelectorAll('input').forEach(input => {
        if (
            input.classList.contains('po-tax') ||
            input.classList.contains('po-cgst') ||
            input.classList.contains('po-sgst')
        ) {
            input.value = '0';
        }
    });

    poi++;
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

    if (!row) {
        return;
    }

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
