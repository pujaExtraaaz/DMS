<x-tally::layouts.app title="Tax rates">
    <x-tally::shell.page title="Tax rates" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.tax-rates.create') }}">New tax rate</a>
        </x-slot:actions>
        <form class="filters" method="GET" action="{{ tally_route('books.tally.tax-rates.index') }}">
            <x-tally::form.field name="q" label="Search">
                <x-tally::form.input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name or code" />
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
            <a class="btn" href="{{ tally_route('books.tally.tax-rates.index') }}">Reset</a>
        </form>
        @if ($rates->isEmpty())
            <x-tally::ui.empty-state title="No tax rates" message="Add CGST, SGST, IGST, and cess percentages for this company.">
                <a class="btn btn-primary" href="{{ tally_route('books.tally.tax-rates.create') }}">New tax rate</a>
            </x-ui.empty-state>
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name</th><th>Category</th><th>CGST</th><th>SGST</th><th>IGST</th><th>Cess</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        @foreach ($rates as $rate)
                            <tr>
                                <td><a href="{{ tally_route('books.tally.tax-rates.show', $rate) }}">{{ $rate->name }}</a></td>
                                <td>{{ $rate->category->name ?? '—' }}</td>
                                <td class="money">{{ $rate->trimmed('cgst_rate') }}%</td>
                                <td class="money">{{ $rate->trimmed('sgst_rate') }}%</td>
                                <td class="money">{{ $rate->trimmed('igst_rate') }}%</td>
                                <td class="money">{{ $rate->trimmed('cess_rate') }}%</td>
                                <td><x-tally::ui.status :active="$rate->is_active" /></td>
                                <td>
                                    <x-tally::ui.record-actions
                                        :view="tally_route('books.tally.tax-rates.show', $rate)"
                                        :edit="tally_route('books.tally.tax-rates.edit', $rate)"
                                        :active="$rate->is_active"
                                        :toggle="tally_route('books.tally.tax-rates.activation', $rate)"
                                        :delete="tally_route('books.tally.tax-rates.destroy', $rate)"
                                        delete-reason="This tax rate is used by products or invoices and cannot be deleted."
                                    />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $rates->links() }}
        @endif
    </x-shell.page>
</x-layouts.app>
