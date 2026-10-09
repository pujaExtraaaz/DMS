<x-tally::layouts.app title="Cost categories">
    <x-tally::shell.page title="Cost categories" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.cost-categories.create') }}">New category</a>
        </x-slot:actions>
        <form class="filters" method="GET">
            <x-tally::form.field name="q" label="Search"><x-tally::form.input name="q" value="{{ $filters['q'] }}" /></x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($categories->isEmpty())
            <x-tally::ui.empty-state title="No cost categories" message="Group cost centres under a category." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Code</th><th>Centres</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>{{ $category->code ?: '—' }}</td>
                                <td>{{ $category->centres_count }}</td>
                                <td><x-tally::ui.status :active="$category->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.cost-categories.edit', $category)"
                                        :active="$category->is_active"
                                        :toggle="tally_route('books.tally.cost-categories.activation', $category)"
                                        :delete="tally_route('books.tally.cost-categories.destroy', $category)"
                                        delete-reason="This category still has cost centres and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $categories->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
