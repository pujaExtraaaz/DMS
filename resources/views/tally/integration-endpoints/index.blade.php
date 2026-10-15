<x-tally::layouts.app title="Integration endpoints">
    <x-tally::shell.page title="Integration endpoints" section="Integrations" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.integration-endpoints.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" required /></x-tally::form.field>
            <x-tally::form.field name="kind" label="Kind" required>
                <select class="input" name="kind" required>
                    @foreach (['webhook','bank_feed','payment_api','gateway','crm','payroll','expense'] as $kind)
                        <option value="{{ $kind }}">{{ $kind }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="url" label="URL / file path"><x-tally::form.input name="url" placeholder="https://… or C:\feeds\bank.csv" /></x-tally::form.field>
            <x-tally::form.field name="bank_account_id" label="Bank account (feeds)">
                <select class="input" name="bank_account_id">
                    <option value="">—</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->bank_name }} · {{ $account->account_number }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="auth_header" label="Auth header"><x-tally::form.input name="auth_header" placeholder="Bearer …" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Kind</th><th>Bank</th><th>Last Sync</th><th>Active</th><th></th></tr></thead>
                <tbody>
                    @forelse ($endpoints as $endpoint)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.integration-endpoints.show', $endpoint) }}">{{ $endpoint->name }}</a></td>
                            <td>{{ $endpoint->kind }}</td>
                            <td>{{ $endpoint->bankAccount?->bank_name ?? '—' }}</td>
                            <td>{{ $endpoint->last_synced_at?->format('d M Y H:i') ?? '—' }}</td>
                            <td>{{ $endpoint->is_active ? 'Yes' : 'No' }}</td>
                            <td>
                                @if ($endpoint->kind === 'bank_feed')
                                    <form method="POST" action="{{ tally_route('books.tally.integration-endpoints.sync', $endpoint) }}">
                                        @csrf
                                        <button class="btn" type="submit">Sync now</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No endpoints.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $endpoints->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
