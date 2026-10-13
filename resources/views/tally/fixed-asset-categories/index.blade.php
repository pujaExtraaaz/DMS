<x-tally::layouts.app title="Fixed asset categories">
    <x-tally::shell.page title="Fixed asset categories" section="Fixed assets" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.fixed-asset-categories.create') }}">New category</a>
        </x-slot:actions>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Name</th><th>Method</th><th>Life (yrs)</th><th>Residual %</th><th>Assets</th><th></th></tr></thead>
                <tbody>
                    @forelse ($categories as $category)
                        <tr>
                            <td>{{ $category->name }}</td>
                            <td>{{ $category->depreciation_method === 'wdv' ? 'WDV' : 'SLM' }}</td>
                            <td>{{ $category->useful_life_years }}</td>
                            <td>{{ $category->residual_percent }}</td>
                            <td>{{ $category->assets_count }}</td>
                            <td><a class="btn" href="{{ tally_route('books.tally.fixed-asset-categories.edit', $category) }}">Edit</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No categories yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $categories->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
