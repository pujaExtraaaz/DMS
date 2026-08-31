<?php
/** Views scaffold - run: php bootstrap/dms_views.php */

$base = dirname(__DIR__);

function w(string $path, string $content): void {
    $dir = dirname($path);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents($path, $content);
    echo "View: $path\n";
}

$lineItemsScript = <<<'JS'
function lineItemRow(index) {
    return `<tr>
        <td class="px-3 py-2"><select name="items[${index}][product_id]" class="product-select block w-full rounded-lg border-gray-300 text-sm" required onchange="fetchPrice(this, ${index})"><option value="">Product</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
        <td class="px-3 py-2"><select name="items[${index}][uom_id]" class="uom-select block w-full rounded-lg border-gray-300 text-sm" required onchange="fetchPrice(this.closest('tr').querySelector('.product-select'), ${index})"><option value="">UOM</option>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
        <td class="px-3 py-2"><input type="number" step="0.0001" min="0.0001" name="items[${index}][quantity]" class="qty-input block w-full rounded-lg border-gray-300 text-sm" required onchange="fetchPrice(this.closest('tr').querySelector('.product-select'), ${index})"></td>
        <td class="px-3 py-2"><input type="number" step="0.01" min="0" name="items[${index}][unit_price]" class="price-input block w-full rounded-lg border-gray-300 text-sm" required readonly></td>
        <td class="px-3 py-2"><button type="button" onclick="this.closest('tr').remove()" class="text-red-600 text-sm">Remove</button></td>
    </tr>`;
}
let rowIndex = 1;
function addRow() { document.getElementById('line-items').insertAdjacentHTML('beforeend', lineItemRow(rowIndex++)); }
async function fetchPrice(productSelect, index) {
    const customerId = document.getElementById('customer_id')?.value;
    const row = productSelect.closest('tr');
    const productId = row.querySelector('.product-select')?.value;
    const uomId = row.querySelector('.uom-select')?.value;
    const qty = row.querySelector('.qty-input')?.value;
    if (!customerId || !productId || !uomId || !qty) return;
    const res = await fetch(`{{ route('orders.resolve-price') }}?customer_id=${customerId}&product_id=${productId}&uom_id=${uomId}&quantity=${qty}`);
    const data = await res.json();
    if (data.unit_price) row.querySelector('.price-input').value = data.unit_price;
}
JS;

// Orders views
w("$base/resources/views/orders/index.blade.php", <<<'BLADE'
@extends('layouts.dms')
@section('title', 'Orders')
@section('content')
<x-ui.page-header title="Orders" description="Pending and historical orders with filters.">
    <x-slot name="actions"><x-ui.button variant="primary" :href="route('orders.create')">Book Order</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="GET" class="grid grid-cols-1 md:grid-cols-6 gap-3 mb-4">
        <x-ui.select name="customer_id" label="Customer" placeholder="All"><option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(request('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="salesperson_id" label="Salesperson" placeholder="All"><option value=""></option>@foreach($salespersons as $s)<option value="{{ $s->id }}" @selected(request('salesperson_id')==$s->id)>{{ $s->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="area_id" label="Area" placeholder="All"><option value=""></option>@foreach($areas as $a)<option value="{{ $a->id }}" @selected(request('area_id')==$a->id)>{{ $a->name }}</option>@endforeach</x-ui.select>
        <x-ui.select name="status" label="Status" placeholder="All"><option value=""></option>@foreach($statuses as $st)<option value="{{ $st }}" @selected(request('status')==$st)>{{ ucfirst($st) }}</option>@endforeach</x-ui.select>
        <x-ui.input name="date_from" label="From" type="date" :value="request('date_from')" />
        <x-ui.input name="date_to" label="To" type="date" :value="request('date_to')" />
        <div class="md:col-span-6"><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></div>
    </form>
    <x-ui.table>
        <x-slot name="head"><tr>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Order</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Customer</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Date</th>
            <th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Status</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Total</th>
            <th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Actions</th>
        </tr></x-slot>
        @forelse($orders as $order)
        <tr class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium">{{ $order->order_no }}</td>
            <td class="px-6 py-4 text-sm">{{ $order->customer->name }}</td>
            <td class="px-6 py-4 text-sm">{{ $order->order_date->format('d M Y') }}</td>
            <td class="px-6 py-4 text-sm"><x-ui.badge variant="info">{{ ucfirst($order->status) }}</x-ui.badge></td>
            <td class="px-6 py-4 text-sm text-right">₹{{ number_format($order->grand_total, 2) }}</td>
            <td class="px-6 py-4 text-right"><x-ui.button variant="ghost" size="sm" :href="route('orders.show', $order)">View</x-ui.button></td>
        </tr>
        @empty
        <tr><td colspan="6" class="px-6 py-8"><x-ui.empty-state title="No orders" description="Book your first order." /></td></tr>
        @endforelse
    </x-ui.table>
    <div class="mt-4">{{ $orders->links() }}</div>
</x-ui.card>
@endsection
BLADE);

w("$base/resources/views/orders/create.blade.php", <<<'BLADE'
@extends('layouts.dms')
@section('title', 'Book Order')
@section('content')
<x-ui.page-header title="Book Order" description="Live pricing from price master.">
    <x-slot name="actions"><x-ui.button variant="secondary" :href="route('orders.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
    <form method="POST" action="{{ route('orders.store') }}" class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <x-ui.select name="customer_id" id="customer_id" label="Customer" required placeholder="Select customer" onchange="document.querySelectorAll('.product-select').forEach(s=>fetchPrice(s,0))">
                <option value=""></option>@foreach($customers as $c)<option value="{{ $c->id }}" @selected(old('customer_id')==$c->id)>{{ $c->name }}</option>@endforeach
            </x-ui.select>
            <x-ui.input name="order_date" label="Order Date" type="date" :value="old('order_date', now()->toDateString())" required />
            <x-ui.input name="discount_amount" label="Discount" type="number" step="0.01" :value="old('discount_amount', 0)" />
        </div>
        <div class="overflow-x-auto border rounded-lg">
            <table class="min-w-full text-sm"><thead class="bg-gray-50"><tr>
                <th class="px-3 py-2 text-left">Product</th><th class="px-3 py-2 text-left">UOM</th><th class="px-3 py-2 text-left">Qty</th><th class="px-3 py-2 text-left">Price</th><th></th>
            </tr></thead><tbody id="line-items">
                <tr>
                    <td class="px-3 py-2"><select name="items[0][product_id]" class="product-select block w-full rounded-lg border-gray-300 text-sm" required><option value="">Product</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
                    <td class="px-3 py-2"><select name="items[0][uom_id]" class="uom-select block w-full rounded-lg border-gray-300 text-sm" required><option value="">UOM</option>@foreach($uoms as $u)<option value="{{ $u->id }}">{{ $u->code }}</option>@endforeach</select></td>
                    <td class="px-3 py-2"><input type="number" step="0.0001" name="items[0][quantity]" class="qty-input block w-full rounded-lg border-gray-300 text-sm" required></td>
                    <td class="px-3 py-2"><input type="number" step="0.01" name="items[0][unit_price]" class="price-input block w-full rounded-lg border-gray-300 text-sm" required></td>
                    <td></td>
                </tr>
            </tbody></table>
        </div>
        <x-ui.button type="button" variant="secondary" onclick="addRow()">Add Line</x-ui.button>
        <x-ui.input name="notes" label="Notes" :value="old('notes')" />
        <x-ui.button type="submit" variant="primary">Book Order</x-ui.button>
    </form>
</x-ui.card>
@push('scripts')<script>{!! str_replace(['@foreach', '@endforeach'], ['/*', '*/'], file_get_contents(__DIR__.'/../../bootstrap/dms_line_items.js')) !!}</script>@endpush
@endsection
BLADE);

echo "Views scaffold part 1 done\n";
