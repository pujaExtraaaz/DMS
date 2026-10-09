<x-tally::layouts.app title="Companies">
    <x-tally::shell.page title="Companies" section="Settings">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.create') }}">New company</a>
        </x-slot:actions>

        @if ($companies->isEmpty())
            <x-tally::ui.empty-state title="No companies" message="The company list is empty.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.create') }}">Create the first company</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>City</th>
                            <th>GSTIN</th>
                            <th>Branches</th>
                            <th>Years</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($companies as $company)
                            <tr>
                                <td>
                                    <a href="{{ tally_route('books.tally.companies.show', $company) }}">{{ $company->name }}</a>
                                    @if ($workspace->company()?->is($company))
                                        <span class="tag">Current</span>
                                    @endif
                                </td>
                                <td>{{ $company->city ?: '—' }}</td>
                                <td>{{ $company->gstin ?: '—' }}</td>
                                <td>{{ $company->branches_count }}</td>
                                <td>{{ $company->financial_years_count }}</td>
                                <td><x-tally::ui.status :active="$company->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.companies.edit', $company)"
                                        :active="$company->is_active"
                                        :toggle="tally_route('books.tally.companies.activation', $company)"
                                        :lifecycle="true"
                                        :delete="tally_route('books.tally.companies.destroy', $company)"
                                        confirm="Delete this company and its books? This cannot be undone."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $companies->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
