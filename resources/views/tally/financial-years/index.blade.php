<x-tally::layouts.app :title="'Financial years · '.$company->name">
    <x-tally::shell.page title="Financial years" section="Settings" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.financial-years.create', $company) }}">New financial year</a>
        </x-slot:actions>
        @if ($financialYears->isEmpty())
            <x-tally::ui.empty-state title="No financial years" message="Add a financial year for this company.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.financial-years.create', $company) }}">New financial year</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Start</th>
                            <th>End</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($financialYears as $year)
                            <tr>
                                <td>
                                    <a href="{{ tally_route('books.tally.companies.financial-years.show', [$company, $year]) }}">{{ $year->name }}</a>
                                    @if ($workspace->financialYear()?->is($year))
                                        <span class="tag">Current</span>
                                    @endif
                                </td>
                                <td>{{ $year->start_date->format('d M Y') }}</td>
                                <td>{{ $year->end_date->format('d M Y') }}</td>
                                <td><x-tally::ui.status :active="$year->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :edit="tally_route('books.tally.companies.financial-years.edit', [$company, $year])"
                                        :active="$year->is_active"
                                        :toggle="tally_route('books.tally.companies.financial-years.activation', [$company, $year])"
                                        :delete="tally_route('books.tally.companies.financial-years.destroy', [$company, $year])"
                                        delete-reason="This financial year has vouchers, invoices, or stock and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $financialYears->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
