<x-tally::layouts.app :title="'Branches · '.$company->name">
    <x-tally::shell.page title="Branches" section="Settings" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.branches.create', $company) }}">New branch</a>
        </x-slot:actions>
        @if ($branches->isEmpty())
            <x-tally::ui.empty-state title="No branches" message="Add a branch for this company.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.branches.create', $company) }}">New branch</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>City</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td>{{ $branch->code }}</td>
                                <td>
                                    <a href="{{ tally_route('books.tally.companies.branches.show', [$company, $branch]) }}">{{ $branch->name }}</a>
                                    @if ($workspace->branch()?->is($branch))
                                        <span class="tag">Current</span>
                                    @endif
                                </td>
                                <td>{{ $branch->city ?: '—' }}</td>
                                <td><x-tally::ui.status :active="$branch->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.companies.branches.edit', [$company, $branch])"
                                        :active="$branch->is_active"
                                        :toggle="tally_route('books.tally.companies.branches.activation', [$company, $branch])"
                                        :delete="tally_route('books.tally.companies.branches.destroy', [$company, $branch])"
                                        delete-reason="This branch has vouchers, invoices, or stock and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $branches->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
