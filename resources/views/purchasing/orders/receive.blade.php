@extends('layouts.dms')
@section('title', 'Receive '.$order->po_no)
@section('content')
<x-ui.page-header :title="'Partial / Full receive against '.$order->po_no" description="Capture batch, expiry, and per-batch selling price at inward. Leave qty blank on any line to skip it in this partial receive.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('purchasing.orders.show', $order)">Back</x-ui.button></x-slot>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ route('purchasing.orders.receive.store', $order) }}" class="space-y-4">@csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.input name="inward_date" label="Inward Date" type="date" :value="old('inward_date', now()->toDateString())" required />
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Warehouse</label>
                <select name="warehouse_id" class="block w-full rounded-lg border-gray-300 text-sm">
                    <option value="">{{ $order->warehouse?->name ?? 'Default / unassigned' }}</option>
                    @foreach($warehouses as $w)<option value="{{ $w->id }}" @selected(old('warehouse_id', $order->warehouse_id)==$w->id)>{{ $w->name }}</option>@endforeach
                </select>
            </div>
            <x-ui.input name="supplier_challan_no" label="Supplier Challan" :value="old('supplier_challan_no')" />
        </div>

        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-3 py-2 text-left">Product</th>
                        <th class="px-3 py-2 text-right">Ordered</th>
                        <th class="px-3 py-2 text-right">Already Rec.</th>
                        <th class="px-3 py-2 text-right">Remaining</th>
                        <th class="px-3 py-2 text-left">Received</th>
                        <th class="px-3 py-2 text-left">Accepted</th>
                        <th class="px-3 py-2 text-left">Rejected</th>
                        <th class="px-3 py-2 text-left">Rejection Reason</th>
                        <th class="px-3 py-2 text-left">Batch</th>
                        <th class="px-3 py-2 text-left">Expiry</th>
                        <th class="px-3 py-2 text-left">Batch Selling ₹</th>
                        <th class="px-3 py-2 text-left">Batch MRP ₹</th>
                        <th class="px-3 py-2 text-left">Serials (comma-sep)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $i => $item)
                        @php $remaining = $item->remainingQty(); @endphp
                        <tr class="border-t">
                            <td class="px-3 py-2">
                                {{ $item->product?->name }} <span class="text-xs text-slate-500">({{ $item->uom?->code }})</span>
                                <input type="hidden" name="items[{{ $i }}][purchase_order_item_id]" value="{{ $item->id }}">
                            </td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->quantity, 2) }}</td>
                            <td class="px-3 py-2 text-right">{{ number_format($item->received_qty, 2) }}</td>
                            <td class="px-3 py-2 text-right font-semibold {{ $remaining <= 0 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ number_format($remaining, 2) }}
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001" name="items[{{ $i }}][received_qty]"
                                       value="{{ $remaining > 0 ? $remaining : 0 }}"
                                       class="block w-24 rounded-lg border-gray-300 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001" name="items[{{ $i }}][accepted_qty]"
                                       value="{{ $remaining > 0 ? $remaining : 0 }}"
                                       class="block w-24 rounded-lg border-gray-300 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.0001" name="items[{{ $i }}][rejected_qty]" value="0" class="block w-20 rounded-lg border-gray-300 text-sm">
                            </td>
                            <td class="px-3 py-2">
                                <input type="text" name="items[{{ $i }}][rejection_reason]" class="block w-40 rounded-lg border-gray-300 text-sm" placeholder="If any">
                            </td>
                            <td class="px-3 py-2"><input type="text" name="items[{{ $i }}][batch_no]" class="block w-28 rounded-lg border-gray-300 text-sm" placeholder="Optional"></td>
                            <td class="px-3 py-2"><input type="date" name="items[{{ $i }}][expiry_date]" class="block w-36 rounded-lg border-gray-300 text-sm"></td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" name="items[{{ $i }}][batch_selling_price]" class="block w-28 rounded-lg border-gray-300 text-sm"
                                       placeholder="{{ number_format((float) $item->product?->selling_price ?? 0, 2) }}">
                            </td>
                            <td class="px-3 py-2">
                                <input type="number" step="0.01" name="items[{{ $i }}][batch_mrp]" class="block w-28 rounded-lg border-gray-300 text-sm"
                                       placeholder="{{ number_format((float) $item->product?->calculation_mrp ?? 0, 2) }}">
                            </td>
                            <td class="px-3 py-2"><input type="text" name="items[{{ $i }}][serials]" class="block w-52 rounded-lg border-gray-300 text-sm" placeholder="SN1, SN2"></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-xs text-slate-500">Batch Selling Price / MRP entered here are stored against this batch. If not provided, the product master price applies at billing.</p>

        <x-ui.button type="submit" variant="primary">Post Inward</x-ui.button>
    </form>
</x-ui.card>
@endsection
