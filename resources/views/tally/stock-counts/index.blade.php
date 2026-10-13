<x-tally::layouts.app title="Stock counts">
    <x-tally::shell.page title="Stock counts" section="Inventory" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.stock-counts.create') }}">New stock count</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Number</th><th>Date</th><th>Godown</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($counts as $count)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.stock-counts.show', $count) }}">{{ $count->number }}</a></td>
                            <td>{{ $count->count_date->format('d M Y') }}</td>
                            <td>{{ $count->godown?->name ?: 'All' }}</td>
                            <td>{{ ucfirst($count->status) }}</td>
                            <td>
                                @if ($count->isDraft())
                                    <a class="btn" href="{{ tally_route('books.tally.stock-counts.edit', $count) }}">Edit</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No stock counts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $counts->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
