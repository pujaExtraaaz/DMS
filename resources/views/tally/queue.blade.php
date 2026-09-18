@extends('layouts.dms')
@section('title', 'Tally Sync Queue')
@section('content')
<x-ui.page-header title="Tally Sync Queue" description="Pending XML vouchers wait for the office Tally Connector bridge">
<x-slot name="actions">
<a href="{{ asset('downloads/tally-connector.zip') }}" class="inline-flex items-center rounded-lg bg-teal-600 px-3 py-2 text-sm font-semibold text-white hover:bg-teal-700">Download Connector</a>
<form method="GET" class="flex gap-2">
<select name="status" class="rounded-lg border-gray-300 text-sm">
<option value="">All</option>
@foreach(['pending','sent','failed','skipped'] as $s)<option value="{{ $s }}" @selected(request('status')==$s)>{{ ucfirst($s) }}</option>@endforeach
</select>
<x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
</form>
</x-slot>
</x-ui.page-header>

<x-ui.card class="mb-4">
    <h3 class="text-sm font-semibold text-slate-900 mb-2">How to use the Connector ZIP</h3>
    <ol class="list-decimal list-inside text-sm text-slate-600 space-y-1.5">
        <li>Click <strong>Download Connector</strong> and unzip on the Windows PC where Tally is installed (not on the web server).</li>
        <li>Install <a class="text-indigo-600 underline" href="https://nodejs.org/" target="_blank" rel="noopener">Node.js 18+ LTS</a> if needed.</li>
        <li>Open <code class="bg-slate-100 px-1 rounded">SETUP-GUIDE.html</code> inside the ZIP for the full walkthrough (or read <code class="bg-slate-100 px-1 rounded">README.txt</code>).</li>
        <li>Run <code class="bg-slate-100 px-1 rounded">start.bat</code> → fill <code class="bg-slate-100 px-1 rounded">config.json</code> with DMS URL, connector token, and <code class="bg-slate-100 px-1 rounded">http://127.0.0.1:9000</code>.</li>
        <li>Keep Tally open (company loaded, HTTP port 9000). Leave the connector window running.</li>
        <li>Post an invoice in DMS → watch this queue move from <strong>Pending</strong> → <strong>Sent</strong>.</li>
    </ol>
    <p class="mt-3 text-xs text-slate-500">Also documented in Product Guide → section <a class="text-indigo-600 underline" href="{{ url('/docs/CLIENT_REQUIREMENTS.html#tally-connector') }}" target="_blank">11. Tally Connector ZIP</a>.</p>
    @if(config('services.tally.use_connector'))
        <p class="mt-2 text-xs text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">Connector mode is <strong>ON</strong>. Queue items stay Pending until the office bridge sends them to Tally.</p>
    @else
        <p class="mt-2 text-xs text-amber-700 bg-amber-50 rounded-lg px-3 py-2">Connector mode is OFF. Ask admin to set <code>TALLY_USE_CONNECTOR=true</code> in server <code>.env</code>.</p>
    @endif
</x-ui.card>

<x-ui.card>
<div class="overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200 text-sm">
<thead class="bg-slate-50"><tr>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">ID</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Document</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Status</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Attempts</th>
<th class="px-3 py-2 text-left text-xs font-semibold uppercase text-slate-500">Error</th>
<th class="px-3 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $item)
<tr>
<td class="px-3 py-2">#{{ $item->id }}</td>
<td class="px-3 py-2">{{ $item->document_type }} #{{ $item->document_id }}</td>
<td class="px-3 py-2">{{ ucfirst($item->status) }}</td>
<td class="px-3 py-2">{{ $item->attempts }}</td>
<td class="px-3 py-2 text-xs text-red-600 max-w-xs truncate">{{ $item->last_error }}</td>
<td class="px-3 py-2 text-right space-x-2 whitespace-nowrap">
<x-ui.button size="sm" variant="secondary" :href="route('tally.queue.show', $item)">View</x-ui.button>
@if(in_array($item->status, ['failed','pending','skipped'], true))
<form class="inline" method="POST" action="{{ route('tally.queue.retry', $item) }}">@csrf<button class="text-indigo-600 text-sm font-medium">Retry</button></form>
@endif
</td>
</tr>
@empty
<tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">Queue empty.</td></tr>
@endforelse
</tbody></table></div>
<div class="mt-4">{{ $items->links() }}</div>
</x-ui.card>
@endsection
