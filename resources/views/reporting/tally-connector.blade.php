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

@if (session('success'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('success') }}</div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900">{{ session('error') }}</div>
@endif

<x-ui.card class="mb-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="min-w-0">
            <p class="text-sm font-semibold text-gray-900">Download for the Tally PC</p>
            <p class="mt-1 text-sm text-gray-500">ZIP includes tally_connector.exe, config.json, config.example.json, and install-task.ps1. Keep Tally open on port 9000.</p>
        </div>
        <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
            @if ($syncEnabled)
                <form method="POST" action="{{ route('tally-connector.pull') }}" onsubmit="return confirm('Re-import all masters and vouchers from Tally into DMS?');">
                    @csrf
                    <x-ui.button variant="secondary" size="lg" type="submit">
                        Pull everything from Tally
                    </x-ui.button>
                </form>
            @endif
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
    <p class="text-sm text-slate-700">Two bridges stay separate: DMS &lt;-&gt; Accounting Books, and DMS &lt;-&gt; TallyPrime. Accounting Books never talks to Tally. Outbound Tally sync sends DMS products, customers, UOMs, warehouses, invoices, payments, credit notes, purchase invoices, purchase orders, sales orders, quotations, and goods receipts. Inbound Tally changes update DMS units, warehouses, party ledgers, and stock items; Books then follows through DMS-to-Books sync. Day book, P&amp;L, GST, and the other report pulls are snapshots only. Conflicts are listed below; neither side is overwritten.</p>
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

<div class="mb-6 space-y-6">
    <x-ui.card title="DMS → Tally">
        <p class="mb-3 text-sm text-slate-600">Outbound queue — what this DMS sent (or will send) into TallyPrime.</p>
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-teal-50 text-left text-xs font-semibold uppercase tracking-wide text-teal-900">
                        <th class="border-r border-slate-200 px-3 py-2">Synced item</th>
                        <th class="border-r border-slate-200 px-3 py-2">Status</th>
                        <th class="border-r border-slate-200 px-3 py-2">Error</th>
                        <th class="border-r border-slate-200 px-3 py-2">When</th>
                        @if ($canQueueActions)
                            <th class="px-3 py-2"></th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($outbound as $row)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <td class="border-r border-slate-200 px-3 py-2 font-medium text-slate-900">
                                <span title="#{{ $row->id }} · {{ $row->document_type }} #{{ $row->document_id }}">{{ $row->synced_item }}</span>
                            </td>
                            <td class="border-r border-slate-200 px-3 py-2">
                                <x-ui.badge :variant="$statusVariant[$row->status] ?? 'neutral'" size="sm">{{ $row->status }}</x-ui.badge>
                                @if ((int) $row->attempts > 1)
                                    <span class="ml-1 text-xs text-slate-500">×{{ $row->attempts }}</span>
                                @endif
                            </td>
                            <td class="border-r border-slate-200 max-w-[10rem] truncate px-3 py-2 text-xs text-rose-600" title="{{ $row->last_error }}">{{ $row->last_error ? \Illuminate\Support\Str::limit($row->last_error, 40) : '—' }}</td>
                            <td class="{{ $canQueueActions ? 'border-r border-slate-200 ' : '' }}px-3 py-2 whitespace-nowrap text-xs text-slate-600">{{ $row->sent_at ?: $row->created_at }}</td>
                            @if ($canQueueActions)
                                <td class="px-3 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('tally.queue.show', $row->id) }}" class="text-xs font-medium text-slate-600 hover:text-slate-900">View</a>
                                    @if (in_array($row->status, ['failed', 'pending', 'skipped'], true))
                                        <form class="ml-2 inline" method="POST" action="{{ route('tally.queue.retry', $row->id) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-medium text-teal-700 hover:text-teal-900">Retry</button>
                                        </form>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-5 text-center text-slate-500" colspan="{{ $canQueueActions ? 5 : 4 }}">Nothing queued yet. Save or post a DMS document while sync is on.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>

    <x-ui.card title="Tally → DMS">
        <p class="mb-3 text-sm text-slate-600">Inbound pull — masters and vouchers brought in from Tally by the connector.</p>
        @if ($inboundCursors->isNotEmpty())
            <div class="mb-3 flex flex-wrap gap-1.5">
                @foreach ($inboundCursors as $cursor)
                    <span class="inline-flex items-center gap-1 rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] text-slate-600" title="Updated {{ $cursor->updated_at }}">
                        <span class="font-medium text-slate-800">{{ \App\Domains\TallySync\Support\SyncCoverage::labelForInboundCollection($cursor->collection) }}</span>
                        <span class="tabular-nums text-slate-500">{{ $cursor->alter_id }}</span>
                    </span>
                @endforeach
            </div>
        @endif
        <div class="overflow-x-auto rounded-lg border border-slate-200">
            <table class="min-w-full border-collapse text-sm">
                <thead>
                    <tr class="border-b border-slate-200 bg-indigo-50 text-left text-xs font-semibold uppercase tracking-wide text-indigo-900">
                        <th class="border-r border-slate-200 px-3 py-2">Synced item</th>
                        <th class="border-r border-slate-200 px-3 py-2">Action</th>
                        <th class="px-3 py-2">When</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($inbound as $row)
                        <tr class="border-b border-slate-200 last:border-b-0">
                            <td class="border-r border-slate-200 px-3 py-2 font-medium text-slate-900">{{ $row->item ?: \App\Domains\TallySync\Support\SyncCoverage::labelForInboundCollection($row->collection ?? null) }}</td>
                            <td class="border-r border-slate-200 px-3 py-2">
                                <x-ui.badge :variant="match($row->action ?? '') { 'created' => 'success', 'updated' => 'info', 'conflict' => 'danger', 'batch' => 'neutral', default => 'neutral' }" size="sm">{{ $row->action ?? 'synced' }}</x-ui.badge>
                            </td>
                            <td class="px-3 py-2 whitespace-nowrap text-xs text-slate-600">{{ $row->created_at }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-3 py-5 text-center text-slate-500" colspan="3">No inbound activity yet. Keep the connector running, or use Pull everything from Tally.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
