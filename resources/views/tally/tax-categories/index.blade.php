<x-tally::layouts.app title="Tax categories">
    <x-tally::shell.page title="Tax categories" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.tax-categories.create') }}">New tax category</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.tax-categories.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or code" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.tax-categories.index') }}">Reset</a>
        </form>
        @if ($categories->isEmpty())
            <x-tally::ui.empty-state title="No tax categories" message="Add a category before creating tax rates.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.tax-categories.create') }}">New tax category</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Code</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.tax-categories.show', $category) }}">{{ $category->name }}</a></td>
                                <td>{{ $category->code ?: '—' }}</td>
                                <td><x-tally::ui.status :active="$category->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.tax-categories.show', $category)"
                                        :edit="tally_route('books.tally.tax-categories.edit', $category)"
                                        :active="$category->is_active"
                                        :toggle="tally_route('books.tally.tax-categories.activation', $category)"
                                        :delete="tally_route('books.tally.tax-categories.destroy', $category)"
                                        delete-reason="This tax category is used by tax rates and cannot be deleted."
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
