@extends('layouts.dms')
@section('title', 'Tally Connector')
@section('content')
@php
    $statusVariant = [
        'pending' => 'warning',
        'sent' => 'success',
        'failed' => 'danger',
        'skipped' => 'neutral',
    ];
    $canQueueActions = Route::has('tally.queue.show') && Route::has('tally.queue.retry');
@endphp

<x-ui.page-header title="Tally Connector" description="Windows bridge between this DMS and TallyPrime on port 9000. The bridge polls every 5 to 10 seconds." />

<x-ui.card class="mb-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900">Download for the Tally PC</p>
            <p class="mt-1 text-sm text-gray-500">ZIP includes tally_connector.exe, config.json, config.example.json, and install-task.ps1. Keep Tally open on port 9000.</p>
        </div>
        <div class="shrink-0">
            @if ($exeReady)
                <x-ui.button variant="primary" size="lg" :href="route('tally-connector.download')">
                    Download Tally Connector
                </x-ui.button>
            @else
                <p class="rounded-lg border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-900">EXE not on server — run <code>tools/tally-connector/publish.ps1</code>.</p>
            @endif
        </div>
    </div>
</x-ui.card>

<x-ui.card class="mb-6">
    <p class="inline-flex items-center gap-2 text-sm font-medium {{ $syncEnabled ? 'text-emerald-700' : 'text-rose-700' }}">
        @if ($syncEnabled)
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-emerald-100 text-emerald-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </span>
            Sync is enabled
        @else
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-rose-100 text-rose-700">
                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </span>
            Sync is disabled
        @endif
    </p>
    @unless ($syncEnabled)
        <p class="mt-2 text-sm text-rose-700">Set <code class="rounded bg-rose-100 px-1">TALLY_SYNC_ENABLED=true</code> in server <code class="rounded bg-rose-100 px-1">.env</code>, then run <code class="rounded bg-rose-100 px-1">php artisan config:clear</code>.</p>
    @else
        <p class="mt-2 text-sm text-slate-600">Leave the connector EXE running after download.</p>
    @endunless
</x-ui.card>

<div class="mb-6 grid gap-3 sm:grid-cols-3">
    <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500 text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-800">Pending</p>
            <p class="text-xl font-semibold tabular-nums text-amber-950">{{ $status['pending'] }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-500 text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </span>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-800">Sent</p>
            <p class="text-xl font-semibold tabular-nums text-emerald-950">{{ $queueCounts['sent'] ?? 0 }}</p>
        </div>
    </div>
    <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 px-4 py-3">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-rose-500 text-white">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </span>
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-800">Failed</p>
            <p class="text-xl font-semibold tabular-nums text-rose-950">{{ $status['failed'] }}</p>
        </div>
    </div>
</div>

<div class="mb-6 grid gap-6 lg:grid-cols-2">
    <x-ui.card title="Connection">
        <div class="overflow-hidden rounded-lg border border-slate-200">
            <table class="w-full table-fixed border-collapse text-sm">
                <colgroup>
                    <col class="w-48" />
                    <col />
                </colgroup>
                <tbody>
                    @foreach ([
                        'Company (Tally)' => $status['company'] ?: '—',
                        'Endpoint' => $status['endpoint'] ?: '—',
                        'Use connector pull' => $status['use_connector'] ? 'Yes' : 'No (HTTP push)',
                        'Connector token' => $status['token_configured'] ? 'Configured' : 'Missing',
                        'Public API' => $status['api_exposed'] ? 'Yes (/api/tally-connector)' : 'No',
                        'Auto-enqueue' => $status['auto_enqueue'] ? 'Registered' : 'Not registered',
                        'Pending queue' => (string) $status['pending'],
                        'Failed queue' => (string) $status['failed'],
                        'XML templates' => implode(', ', $status['templates']),
                    ] as $label => $value)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <th class="border-r border-slate-200 bg-slate-50 px-4 py-2.5 text-left align-top text-xs font-semibold text-slate-500">{{ $label }}</th>
                            <td class="px-4 py-2.5 align-top font-medium text-slate-900 break-all">{{ $value }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card title="Setup">
        <ol class="list-decimal space-y-1.5 pl-5 text-sm text-slate-700">
            @foreach ($status['enable_steps'] as $step)
                <li>{{ $step }}</li>
            @endforeach
        </ol>
        <pre class="mt-4 overflow-x-auto rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-800">{
  "dms_url": "{{ rtrim(config('app.url'), '/') }}",
  "connector_token": "same value as TALLY_CONNECTOR_TOKEN",
  "tally_url": "http://127.0.0.1:9000",
  "company": "Exact Tally company name",
  "poll_seconds": 8
}</pre>
    </x-ui.card>
</div>

<x-ui.card class="mb-6" title="What syncs">
    <p class="text-sm text-slate-700">Outbound from DMS (Tally Queue): masters (stock items, ledgers, units, godowns), sales and purchase invoices, receipts/payments, credit notes, purchase orders, sales orders, quotations, receipt notes (GRN), Books stock journals, freight/landed-cost journals, and standalone cleared cheques. Inbound poll also refreshes Books masters and reports (day book, P&amp;L, trial balance, balance sheet, GST packs). Receipt Notes from Tally become Books goods receipts — not cash receipts. Sales/purchase orders and quotations stay DMS-owned on import. Conflicts are listed below; neither side is overwritten.</p>
</x-ui.card>

<x-ui.card class="mb-6" title="Imported reports">
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-sky-50 text-left text-xs font-semibold uppercase tracking-wide text-sky-800">
                    <th class="border-r border-slate-200 px-4 py-2.5">Report</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Period</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Lines</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Updated</th>
                    <th class="px-4 py-2.5">Note</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reports as $report)
                    <tr class="border-b border-slate-200 last:border-b-0">
                        <td class="border-r border-slate-200 px-4 py-2.5 text-slate-900">{{ $report->title }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5 text-slate-700">{{ $report->period }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5 tabular-nums">{{ $report->rows }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5 text-slate-600">{{ $report->updated_at }}</td>
                        <td class="px-4 py-2.5 text-slate-600">{{ $report->message ?: ($report->status === 'ok' ? 'Imported' : $report->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-4 text-slate-500" colspan="5">No report has been imported yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.card class="mb-6" title="Conflicts">
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-amber-50 text-left text-xs font-semibold uppercase tracking-wide text-amber-900">
                    <th class="border-r border-slate-200 px-4 py-2.5">When</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Section</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Name</th>
                    <th class="px-4 py-2.5">Reason</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($conflicts as $conflict)
                    <tr class="border-b border-slate-200 last:border-b-0">
                        <td class="border-r border-slate-200 px-4 py-2.5 whitespace-nowrap text-slate-600">{{ $conflict->created_at }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5">{{ $conflict->collection }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5 text-slate-900">{{ $conflict->name }}</td>
                        <td class="px-4 py-2.5 text-slate-600">{{ $conflict->reason }}</td>
                    </tr>
                @empty
                    <tr><td class="px-4 py-4 text-slate-500" colspan="4">No open conflicts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

{{-- Sync queue on this page (no separate Open queue button) --}}
<x-ui.card title="Sync queue">
    <p class="mb-3 text-sm text-slate-600">Documents waiting for, or already delivered to, Tally. Retry failed or pending rows after fixing ledger/stock names.</p>
    <div class="overflow-x-auto rounded-lg border border-slate-200">
        <table class="min-w-full border-collapse text-sm">
            <thead>
                <tr class="border-b border-slate-200 bg-teal-50 text-left text-xs font-semibold uppercase tracking-wide text-teal-900">
                    <th class="border-r border-slate-200 px-4 py-2.5">ID</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Document</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Type</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Doc ID</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Status</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Attempts</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Error</th>
                    <th class="border-r border-slate-200 px-4 py-2.5">Created</th>
                    @if ($canQueueActions)
                        <th class="px-4 py-2.5"></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse ($recent as $row)
                    @php $label = ($queueLabels[$row->document_type] ?? null) ?: str_replace('_', ' ', (string) $row->document_type); @endphp
                    <tr class="border-b border-slate-200 last:border-b-0">
                        <td class="border-r border-slate-200 px-4 py-2.5 tabular-nums text-slate-600">#{{ $row->id }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5 font-medium text-slate-900">{{ $label }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5"><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $row->document_type }}</code></td>
                        <td class="border-r border-slate-200 px-4 py-2.5 tabular-nums">{{ $row->document_id }}</td>
                        <td class="border-r border-slate-200 px-4 py-2.5">
                            <x-ui.badge :variant="$statusVariant[$row->status] ?? 'neutral'" size="sm">{{ $row->status }}</x-ui.badge>
                        </td>
                        <td class="border-r border-slate-200 px-4 py-2.5 tabular-nums">{{ $row->attempts }}</td>
                        <td class="border-r border-slate-200 max-w-xs truncate px-4 py-2.5 text-xs text-rose-600" title="{{ $row->last_error }}">{{ $row->last_error ? \Illuminate\Support\Str::limit($row->last_error, 60) : '—' }}</td>
                        <td class="{{ $canQueueActions ? 'border-r border-slate-200 ' : '' }}px-4 py-2.5 whitespace-nowrap text-slate-600">{{ $row->created_at }}</td>
                        @if ($canQueueActions)
                            <td class="px-4 py-2.5 text-right whitespace-nowrap">
                                <a href="{{ route('tally.queue.show', $row) }}" class="text-sm font-medium text-slate-600 hover:text-slate-900">View</a>
                                @if (in_array($row->status, ['failed', 'pending', 'skipped'], true))
                                    <form class="ml-2 inline" method="POST" action="{{ route('tally.queue.retry', $row) }}">
                                        @csrf
                                        <button type="submit" class="text-sm font-medium text-teal-700 hover:text-teal-900">Retry</button>
                                    </form>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td class="px-4 py-6 text-center text-slate-500" colspan="{{ $canQueueActions ? 9 : 8 }}">Queue empty. Post or approve a document while sync is enabled.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection
