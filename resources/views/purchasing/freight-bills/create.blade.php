@extends('layouts.dms')
@section('title', 'New Freight Bill')
@section('content')
<x-ui.page-header title="New Freight Bill">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.freight-bills.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('purchasing.freight-bills.store') }}" class="space-y-4">@csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input name="bill_date" label="Bill Date" type="date" :value="old('bill_date', now()->toDateString())" required />
            <x-ui.input name="transporter_name" label="Transporter" :value="old('transporter_name')" />
            <x-ui.input name="vehicle_no" label="Vehicle No" :value="old('vehicle_no')" />
            <x-ui.input name="lr_no" label="LR No" :value="old('lr_no')" />
            <x-ui.input name="amount" label="Amount" type="number" step="0.01" :value="old('amount')" required />
            <x-ui.input name="tax_amount" label="Tax" type="number" step="0.01" :value="old('tax_amount', 0)" />
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Link Purchase Invoice (optional)</label>
                <select name="purchase_invoice_id" class="block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">—</option>
                    @foreach($invoices as $inv)<option value="{{ $inv->id }}">{{ $inv->invoice_no }} — {{ $inv->supplier?->name }}</option>@endforeach
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-2">Allocation basis (how freight will be split across invoice lines when landed cost is generated)</label>
            @php $selected = old('allocation_basis', 'value'); @endphp
            <div class="flex flex-wrap gap-4 text-sm">
                @foreach([
                    'value' => 'By Value (line total ratio) — recommended for mixed goods',
                    'qty' => 'By Quantity',
                    'weight' => 'By Weight (needs item weight)',
                    'volume' => 'By Volume (needs item volume)',
                    'equal' => 'Equal split per line',
                    'manual' => 'Manual (enter per line during landed cost run)',
                ] as $key => $label)
                    <label class="flex items-center gap-2">
                        <input type="radio" name="allocation_basis" value="{{ $key }}" @checked($selected === $key) class="text-indigo-600">
                        <span>{{ $label }}</span>
                    </label>
                @endforeach
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
            <textarea name="notes" rows="2" class="block w-full rounded-lg border-gray-300 text-sm">{{ old('notes') }}</textarea>
        </div>

        <x-ui.button type="submit" variant="primary">Save Freight Bill</x-ui.button>
    </form>
</x-ui.card>
@endsection
