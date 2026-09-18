@extends('layouts.dms')
@section('title', 'Tally Sync')
@section('content')
<x-ui.page-header title="Tally Sync">
<x-slot name="actions"><x-ui.button variant="primary" :href="route('tally.queue.index')">Open Queue</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<p class="text-sm text-slate-600 mb-4">Posted invoices, payments, credit notes and purchase invoices can be queued as Tally-compatible XML. Draft documents are never pushed.</p>
@isset($items)
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Document</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Updated</th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2">{{ $item->document_type }} #{{ $item->document_id }}</td>
<td class="px-3 py-2">{{ ucfirst($item->status) }}</td>
<td class="px-3 py-2">{{ $item->updated_at }}</td>
</tr>
@empty
<tr><td colspan="3" class="px-3 py-6 text-center text-slate-500">No sync rows.</td></tr>
@endforelse
</tbody></table></div>
@endisset
</x-ui.card>
@endsection
