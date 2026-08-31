@extends('layouts.dms')
@section('title', 'Stock Report')
@section('content')
<x-ui.page-header title="Stock Report" />
<x-ui.card><form method="GET" class="mb-4"><label class="flex items-center gap-2 text-sm"><input type="checkbox" name="low_only" value="1" @checked(request('low_only')) class="rounded border-gray-300"> Low stock only</label><x-ui.button type="submit" variant="secondary">Filter</x-ui.button></form>
<x-ui.table><x-slot name="head"><tr><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">Product</th><th class="px-6 py-3 text-left text-xs font-semibold uppercase text-gray-500">UOM</th><th class="px-6 py-3 text-right text-xs font-semibold uppercase text-gray-500">Qty</th></tr></x-slot>
@foreach($stockLevels as $level)<tr><td class="px-6 py-4 text-sm">{{ $level->product->name }}</td><td class="px-6 py-4 text-sm">{{ $level->uom->code }}</td><td class="px-6 py-4 text-sm text-right">{{ $level->quantity }}</td></tr>@endforeach</x-ui.table></x-ui.card>
@endsection
