<x-tally::layouts.app title="Collection reminders">
    <x-tally::shell.page title="Collection reminders" section="Receivables" :description="$company->name">
        <x-slot:actions>
            <form method="POST" action="{{ tally_route('books.tally.collection-reminders.run') }}">
                @csrf
                <button class="btn btn-primary" type="submit">Run queue now</button>
            </form>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>When</th><th>Customer</th><th>Bill</th><th>Channel</th><th>Status</th><th>Message</th></tr></thead>
                <tbody>
                    @forelse ($reminders as $reminder)
                        <tr>
                            <td>{{ $reminder->sent_at?->format('d M Y H:i') ?? $reminder->created_at?->format('d M Y H:i') }}</td>
                            <td>{{ $reminder->ledger?->name }}</td>
                            <td>{{ $reminder->bill?->bill_number }}</td>
                            <td>{{ $reminder->channel }}</td>
                            <td>{{ $reminder->status }}</td>
                            <td>{{ $reminder->message }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No reminders queued yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $reminders->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
