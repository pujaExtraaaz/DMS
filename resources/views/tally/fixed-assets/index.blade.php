<x-tally::layouts.app title="Fixed assets">
    <x-tally::shell.page title="Fixed assets" section="Fixed assets" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.fixed-assets.create') }}">New asset</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Code</th><th>Name</th><th>Category</th><th class="money">Gross</th><th class="money">Book value</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($assets as $asset)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.fixed-assets.show', $asset) }}">{{ $asset->asset_code }}</a></td>
                            <td>{{ $asset->name }}</td>
                            <td>{{ $asset->category?->name ?: '—' }}</td>
                            <td class="money">{{ $asset->gross_value }}</td>
                            <td class="money">{{ $asset->book_value }}</td>
                            <td>{{ ucfirst($asset->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No fixed assets yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assets->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
