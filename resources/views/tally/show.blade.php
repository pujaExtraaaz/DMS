@extends('layouts.dms')
@section('title', 'Tally Queue #'.$item->id)
@section('content')
<x-ui.page-header :title="'Tally Queue #'.$item->id">
<x-slot name="actions"><x-ui.button variant="secondary" :href="route('tally.queue.index')">Back</x-ui.button></x-slot>
</x-ui.page-header>
<x-ui.card>
<dl class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm mb-4">
<div><dt class="text-slate-500">Document</dt><dd>{{ $item->document_type }} #{{ $item->document_id }}</dd></div>
<div><dt class="text-slate-500">Status</dt><dd>{{ ucfirst($item->status) }}</dd></div>
<div><dt class="text-slate-500">Attempts</dt><dd>{{ $item->attempts }}</dd></div>
<div><dt class="text-slate-500">Last error</dt><dd class="text-red-600">{{ $item->last_error ?: '—' }}</dd></div>
</dl>
<pre class="bg-slate-900 text-slate-100 text-xs p-4 rounded-lg overflow-x-auto whitespace-pre-wrap">{{ $item->payload }}</pre>
</x-ui.card>
@endsection
