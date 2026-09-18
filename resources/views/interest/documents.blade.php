@extends('layouts.dms')
@section('title', 'Interest Documents')
@section('content')
<x-ui.page-header title="Interest Documents"><x-slot name="actions"><x-ui.button variant="secondary" :href="route('interest.index')">Ledgers</x-ui.button></x-slot></x-ui.page-header>
<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Document</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Party</th>
<th class="px-3 py-2 text-right text-xs font-semibold uppercase text-slate-500">Amount</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Date</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2 font-medium">{{ $item->document_no }}</td>
<td class="px-3 py-2">{{ $item->customer?->name }}</td>
<td class="px-3 py-2 text-right">{{ number_format((float)$item->amount, 2) }}</td>
<td class="px-3 py-2">{{ optional($item->document_date)->format('d M Y') ?? $item->created_at?->format('d M Y') }}</td>
</tr>
@empty
<tr><td colspan="4" class="px-3 py-6 text-center text-slate-500">No documents.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
