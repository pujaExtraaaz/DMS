<x-tally::layouts.app :title="$integration->name">
    <x-tally::shell.page :title="$integration->name" section="Settings" :description="$integration->external_system ?: $integration->type">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.integrations.sync', $integration) }}">@csrf<button class="btn btn-primary" type="submit">Sync now</button></form>
            <form method="POST" action="{{ tally_route('books.tally.integrations.retry', $integration) }}">@csrf<button class="btn" type="submit">Retry failed</button></form>
            <a class="btn" href="{{ tally_route('books.tally.integrations.edit', $integration) }}">Alter</a>
            <form method="POST" action="{{ tally_route('books.tally.integrations.destroy', $integration) }}" onsubmit="return confirm('Delete this integration?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <dl class="kv">
            <dt>Type</dt><dd>{{ $integration->type }}</dd>
            <dt>Direction</dt><dd>{{ $integration->direction }}</dd>
            <dt>Last status</dt><dd>{{ $integration->last_status ?: 'No sync yet' }}</dd>
            <dt>Last sync</dt><dd>{{ $integration->last_synced_at?->format('d M Y H:i') ?: '—' }}</dd>
            <dt>Webhook</dt><dd>{{ $integration->endpoint?->url }}</dd>
            <dt>Credentials</dt><dd>{{ $integration->hasCredentials() ? 'Stored' : 'None' }}</dd>
            <dt>Last error</dt><dd>{{ $integration->last_error ?: '—' }}</dd>
        </dl>
        <h2>Sync history</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>When</th><th>Status</th><th>Message</th></tr></thead>
                <tbody>
                    @forelse ($syncs as $sync)
                        <tr>
                            <td>{{ $sync->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $sync->status }}</td>
                            <td>{{ $sync->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3">No syncs yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $syncs->links() }}
        <h2>Deliveries</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Event</th><th>Status</th><th>Attempts</th><th>Error</th></tr></thead>
                <tbody>
                    @forelse ($deliveries as $delivery)
                        <tr>
                            <td>{{ $delivery->event }}</td>
                            <td>{{ $delivery->status }}</td>
                            <td>{{ $delivery->attempts }}</td>
                            <td>{{ $delivery->last_error ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No deliveries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if (method_exists($deliveries, 'links'))
            {{ $deliveries->links() }}
        @endif
        <h2>External ids</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Source</th><th>External id</th><th>Status</th><th>Record</th></tr></thead>
                <tbody>
                    @forelse ($references as $reference)
                        <tr>
                            <td>{{ $reference->source }}</td>
                            <td>{{ $reference->external_reference_id }}</td>
                            <td>{{ $reference->sync_status?->value ?? $reference->sync_status }}</td>
                            <td>{{ class_basename($reference->referenceable_type) }} {{ $reference->referenceable_id }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">No external ids for this company.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
