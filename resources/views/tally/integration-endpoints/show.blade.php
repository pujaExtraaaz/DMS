<x-tally::layouts.app title="Integration endpoint">
    <x-tally::shell.page title="{{ $endpoint->name }}" section="Integrations" :description="$company->name">
        <x-slot:actions>
            @if ($endpoint->kind === 'bank_feed')
                <form method="POST" action="{{ tally_route('books.tally.integration-endpoints.sync', $endpoint) }}">
                    @csrf
                    <button class="btn btn-primary" type="submit">Sync now</button>
                </form>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.integration-endpoints.index') }}">Back</a>
        </x-slot:actions>
        <dl class="meta">
            <div><dt>Kind</dt><dd>{{ $endpoint->kind }}</dd></div>
            <div><dt>URL / path</dt><dd>{{ $endpoint->url ?? '—' }}</dd></div>
            <div><dt>Bank account</dt><dd>{{ $endpoint->bankAccount?->bank_name ?? '—' }} {{ $endpoint->bankAccount?->account_number }}</dd></div>
            <div><dt>Last sync</dt><dd>{{ $endpoint->last_synced_at?->format('d M Y H:i') ?? '—' }}</dd></div>
            <div><dt>Last error</dt><dd>{{ $endpoint->last_error ?? '—' }}</dd></div>
            <div><dt>Retries</dt><dd>{{ $endpoint->retry_count }}</dd></div>
        </dl>
    </x-tally::shell.page>
</x-tally::layouts.app>
