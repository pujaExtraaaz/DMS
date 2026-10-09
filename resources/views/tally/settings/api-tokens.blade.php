<x-tally::layouts.app title="API Tokens">
    <x-tally::shell.page title="API Tokens" section="Settings" description="Tokens let an external ERP, DMS, CRM, or shop call /api/v1. The secret is shown once.">
        @if ($plainToken)
            <p class="flash" role="status">{{ $plainToken }}</p>
        @endif

        <form class="filters" method="POST" action="{{ tally_route('books.tally.settings.api-tokens.store') }}">
            @csrf
            <x-tally::form.field name="name" label="Token name" required>
                <x-tally::form.input name="name" required maxlength="100" placeholder="Shop connector" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Create token</button>
        </form>

        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Last used</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($tokens as $token)
                        <tr>
                            <td>{{ $token->name }}</td>
                            <td>{{ $token->last_used_at?->format('d M Y H:i') ?? 'Never' }}</td>
                            <td>{{ $token->created_at?->format('d M Y H:i') }}</td>
                            <td>
                                <form method="POST" action="{{ tally_route('books.tally.settings.api-tokens.destroy', $token) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn" type="submit">Revoke</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No API tokens yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
