<x-tally::layouts.app title="Integrations">
    <x-tally::shell.page title="Integrations" section="Settings" :description="$company->name">
        <x-slot:actions><a class="btn btn-primary" href="{{ tally_route('books.tally.integrations.create') }}">New integration</a></x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Type</th><th>Direction</th><th>Status</th><th>Last sync</th><th></th></tr></thead>
                <tbody>
                    @forelse ($integrations as $integration)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.integrations.show', $integration) }}">{{ $integration->name }}</a><br><span class="muted">{{ $integration->external_system }}</span></td>
                            <td>{{ $integration->type }}</td>
                            <td>{{ $integration->direction }}</td>
                            <td>{{ $integration->last_status ?: 'No sync yet' }}</td>
                            <td>{{ $integration->last_synced_at?->format('d M Y H:i') ?: '—' }}</td>
                            <td>
                                <a href="{{ tally_route('books.tally.integrations.edit', $integration) }}">Alter</a>
                                <form method="POST" action="{{ tally_route('books.tally.integrations.destroy', $integration) }}" onsubmit="return confirm('Delete this integration?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No integrations for this company.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $integrations->links() }}
    </x-shell.page>
</x-layouts.app>
