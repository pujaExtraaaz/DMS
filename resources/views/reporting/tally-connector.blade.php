@extends('layouts.dms')
@section('title', 'Tally Connector')
@section('content')
<x-ui.page-header title="Tally Connector" description="Windows bridge between this DMS and TallyPrime on port 9000. The bridge polls every 5 to 10 seconds." />

<x-ui.card class="mb-6">
    <p class="text-sm {{ $syncEnabled ? 'text-emerald-700' : 'text-rose-700' }}">
        Sync is <strong>{{ $syncEnabled ? 'enabled' : 'disabled' }}</strong>.
        @unless ($syncEnabled)
            The exe is ready to run after TALLY_SYNC_ENABLED is turned on for this server. It is not pointed at the live site.
        @endunless
    </p>
    <div class="mt-4">
        @if ($exeReady)
            <a href="{{ route('tally-connector.download') }}" class="inline-flex rounded-md bg-slate-900 px-3 py-2 text-sm font-medium text-white">Download Tally Connector folder</a>
            <p class="mt-2 text-sm text-slate-600">Unzip tally-connector.zip. The folder contains tally_connector.exe, config.json, config.example.json, and install-task.ps1.</p>
        @else
            <p class="text-sm text-slate-600">The exe has not been built yet. On a machine with the .NET 8 SDK, run tools/tally-connector/publish.ps1.</p>
        @endif
    </div>
</x-ui.card>

<x-ui.card class="mb-6" title="Connection">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <tbody class="divide-y divide-slate-100">
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Company (Tally)</th><td class="px-3 py-2">{{ $status['company'] ?: '—' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Endpoint</th><td class="px-3 py-2">{{ $status['endpoint'] ?: '—' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Use connector pull</th><td class="px-3 py-2">{{ $status['use_connector'] ? 'Yes' : 'No (HTTP push)' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Connector token</th><td class="px-3 py-2">{{ $status['token_configured'] ? 'Configured' : 'Missing' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Public API</th><td class="px-3 py-2">{{ $status['api_exposed'] ? 'Yes (/api/tally-connector)' : 'No' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Auto-enqueue</th><td class="px-3 py-2">{{ $status['auto_enqueue'] ? 'Registered' : 'Not registered' }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Pending queue</th><td class="px-3 py-2">{{ $status['pending'] }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">Failed queue</th><td class="px-3 py-2">{{ $status['failed'] }}</td></tr>
                <tr><th class="px-3 py-2 text-left font-medium text-slate-500">XML templates</th><td class="px-3 py-2">{{ implode(', ', $status['templates']) }}</td></tr>
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.card class="mb-6" title="What syncs">
    <p class="text-sm text-slate-700">Each poll checks masters and reports. Masters: groups, ledgers, bank accounts, units, godowns, stock groups, stock items, cost categories, cost centres, voucher types, and vouchers of every type (sales, purchase, receipt, payment, contra, journal, credit note, debit note, stock journal, and the other stock voucher types). Reports: day book, profit and loss, trial balance, balance sheet, cash flow, funds flow, ratio analysis, GST sales, GST purchase, GSTR-1, GSTR-3B, and GSTR-2B. Day book and GST reports use the current month. Profit and loss and the other statements use the open financial year. If both sides changed the same record, it is listed below and neither side is overwritten.</p>
</x-ui.card>

<x-ui.card class="mb-6" title="Imported reports">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">Report</th>
                    <th class="px-3 py-2">Period</th>
                    <th class="px-3 py-2">Lines</th>
                    <th class="px-3 py-2">Updated</th>
                    <th class="px-3 py-2">Note</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($reports as $report)
                    <tr>
                        <td class="px-3 py-2">{{ $report->title }}</td>
                        <td class="px-3 py-2">{{ $report->period }}</td>
                        <td class="px-3 py-2">{{ $report->rows }}</td>
                        <td class="px-3 py-2">{{ $report->updated_at }}</td>
                        <td class="px-3 py-2">{{ $report->message ?: ($report->status === 'ok' ? 'Imported' : $report->status) }}</td>
                    </tr>
                @empty
                    <tr><td class="px-3 py-3 text-slate-500" colspan="5">No report has been imported yet. The next connector poll fills this list.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.card class="mb-6" title="Setup">
    <ol class="list-decimal space-y-1 pl-5 text-sm text-slate-700">
        @foreach ($status['enable_steps'] as $step)
            <li>{{ $step }}</li>
        @endforeach
    </ol>
    <pre class="mt-4 overflow-x-auto rounded bg-slate-950 p-3 text-xs text-slate-100">{
  "dms_url": "http://127.0.0.1:8000",
  "connector_token": "same value as TALLY_CONNECTOR_TOKEN",
  "tally_url": "http://127.0.0.1:9000",
  "company": "Exact Tally company name",
  "poll_seconds": 8
}</pre>
</x-ui.card>

<x-ui.card class="mb-6" title="Conflicts">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">When</th>
                    <th class="px-3 py-2">Section</th>
                    <th class="px-3 py-2">Name</th>
                    <th class="px-3 py-2">Reason</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($conflicts as $conflict)
                    <tr>
                        <td class="px-3 py-2">{{ $conflict->created_at }}</td>
                        <td class="px-3 py-2">{{ $conflict->collection }}</td>
                        <td class="px-3 py-2">{{ $conflict->name }}</td>
                        <td class="px-3 py-2">{{ $conflict->reason }}</td>
                    </tr>
                @empty
                    <tr><td class="px-3 py-3 text-slate-500" colspan="4">No open conflicts.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>

<x-ui.card title="Recent queue rows">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-3 py-2">ID</th>
                    <th class="px-3 py-2">Type</th>
                    <th class="px-3 py-2">Doc</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2">Attempts</th>
                    <th class="px-3 py-2">Error</th>
                    <th class="px-3 py-2">Created</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($recent as $row)
                    <tr>
                        <td class="px-3 py-2">{{ $row->id }}</td>
                        <td class="px-3 py-2">{{ $row->document_type }}</td>
                        <td class="px-3 py-2">{{ $row->document_id }}</td>
                        <td class="px-3 py-2">{{ $row->status }}</td>
                        <td class="px-3 py-2">{{ $row->attempts }}</td>
                        <td class="px-3 py-2">{{ \Illuminate\Support\Str::limit($row->last_error, 80) }}</td>
                        <td class="px-3 py-2">{{ $row->created_at }}</td>
                    </tr>
                @empty
                    <tr><td class="px-3 py-3 text-slate-500" colspan="7">No queue rows yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-ui.card>
@endsection