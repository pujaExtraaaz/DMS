<x-tally::layouts.app title="Recurring journals">
    <x-tally::shell.page title="Recurring journals" section="Utilities" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.recurring.create') }}">New recurring</a>
        </x-slot:actions>
        @if ($items->isEmpty())
            <x-tally::ui.empty-state title="No recurring vouchers" message="Create a template to generate journals on a schedule." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Type</th><th>Frequency</th><th>Next Run</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($items as $item)
                            <tr>
                                <td>{{ $item->name }}</td>
                                <td>{{ $item->voucher_type?->label() }}</td>
                                <td>{{ $item->frequency }}</td>
                                <td>{{ $item->next_run_on?->format('d M Y') }}</td>
                                <td>{{ $item->is_active ? 'Active' : 'Paused' }}</td>
                                <td class="actions">
                                    <a class="btn" href="{{ tally_route('books.tally.recurring.edit', $item) }}">Edit</a>
                                    <form method="POST" action="{{ tally_route('books.tally.recurring.run', $item) }}">@csrf<button class="btn btn-primary" type="submit">Run now</button></form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $items->links() }}
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
