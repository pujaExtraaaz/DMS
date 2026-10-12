<x-tally::layouts.app title="Tally Connector">
    <x-tally::shell.page title="Tally Connector (future)" section="Utilities" description="Native Books is live. This connector stays ready for a later TallyPrime sync.">
        <div class="stack">
            <p class="flash {{ $syncEnabled ? '' : 'is-error' }}">
                Sync is <strong>{{ $syncEnabled ? 'ENABLED' : 'DISABLED' }}</strong>.
                @unless ($syncEnabled)
                    Set <code>TALLY_SYNC_ENABLED=true</code> when you are ready to use the office connector again.
                @endunless
            </p>

            <div class="table-wrap">
                <table class="data">
                    <tbody>
                        <tr><th>Company (Tally)</th><td>{{ $status['company'] ?: '—' }}</td></tr>
                        <tr><th>Endpoint</th><td>{{ $status['endpoint'] ?: '—' }}</td></tr>
                        <tr><th>Use connector pull</th><td>{{ $status['use_connector'] ? 'Yes' : 'No (HTTP push)' }}</td></tr>
                        <tr><th>Connector token</th><td>{{ $status['token_configured'] ? 'Configured' : 'Missing' }}</td></tr>
                        <tr><th>Public API exposed</th><td>{{ $status['api_exposed'] ? 'Yes (/api/tally-connector)' : 'No' }}</td></tr>
                        <tr><th>Auto-enqueue observer</th><td>{{ $status['auto_enqueue'] ? 'Registered' : 'Not registered' }}</td></tr>
                        <tr><th>Pending queue</th><td>{{ $status['pending'] }}</td></tr>
                        <tr><th>Failed queue</th><td>{{ $status['failed'] }}</td></tr>
                        <tr><th>XML templates</th><td>{{ implode(', ', $status['templates']) }}</td></tr>
                    </tbody>
                </table>
            </div>

            <h3>Enable later</h3>
            <ol>
                @foreach ($status['enable_steps'] as $step)
                    <li>{{ $step }}</li>
                @endforeach
            </ol>

            <h3>Recent queue rows</h3>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>ID</th><th>Type</th><th>Doc</th><th>Status</th><th>Attempts</th><th>Error</th><th>Created</th></tr></thead>
                    <tbody>
                        @forelse ($recent as $row)
                            <tr>
                                <td>{{ $row->id }}</td>
                                <td>{{ $row->document_type }}</td>
                                <td>{{ $row->document_id }}</td>
                                <td>{{ $row->status }}</td>
                                <td>{{ $row->attempts }}</td>
                                <td>{{ \Illuminate\Support\Str::limit($row->last_error, 80) }}</td>
                                <td>{{ $row->created_at }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="7">No queue rows yet. Templates are ready under App\Domains\TallySync\Templates.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
