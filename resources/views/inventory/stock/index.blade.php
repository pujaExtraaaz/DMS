@extends('layouts.dms')
@section('title', 'Stock Levels')
@section('content')
<x-ui.page-header title="Stock Levels" description="Current inventory and recent movements." />
<x-ui.card>
    <form method="GET" class="flex flex-row items-end gap-4 mb-6">
        <div class="flex-1">
            <x-ui.select name="product_id" label="Product" placeholder="All"><option value=""></option>@foreach($products as $p)<option value="{{ $p->id }}" @selected(request('product_id')==$p->id)>{{ $p->name }}</option>@endforeach</x-ui.select>
        </div>
        <x-ui.button type="submit" variant="secondary" class="whitespace-nowrap">Filter</x-ui.button>
    </form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Quantity</th></tr></x-slot>
@forelse($stockLevels as $level)<tr><td class="px-6 py-4 text-sm">{{ $level->product->name }}</td><td class="px-6 py-4 text-sm">{{ $level->uom->code }}</td><td class="px-6 py-4 text-sm text-right"><x-ui.badge :variant="$level->quantity < 10 ? 'danger' : 'success'">{{ $level->quantity }}</x-ui.badge></td></tr>@empty<tr><td colspan="3" class="px-6 py-8"><x-ui.empty-state title="No stock records" /></td></tr>@endforelse</x-ui.table>
<div class="mt-4">{{ $stockLevels->links() }}</div></x-ui.card>
<x-ui.card class="mt-6" title="Recent Movements"><x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Type</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Balance</th></tr></x-slot>
@foreach($movements as $m)<tr><td class="px-6 py-4 text-sm">{{ $m->product->name }}</td><td class="px-6 py-4 text-sm">{{ $m->type }}</td><td class="px-6 py-4 text-sm text-right">{{ $m->quantity }}</td><td class="px-6 py-4 text-sm text-right">{{ $m->balance_after }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
