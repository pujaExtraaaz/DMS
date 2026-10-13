<x-tally::layouts.app title="Profitability">
    <x-tally::shell.page title="Profitability" section="Analytics" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.profitability.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="dimension" label="Dimension" required>
                <select class="input" name="dimension" required>
                    @foreach (['product','customer','branch','project','segment'] as $dim)<option value="{{ $dim }}">{{ ucfirst($dim) }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="dimension_key" label="Key" required><x-tally::form.input name="dimension_key" required /></x-tally::form.field>
            <x-tally::form.field name="dimension_label" label="Label" required><x-tally::form.input name="dimension_label" required /></x-tally::form.field>
            <x-tally::form.field name="from_date" label="From" required><x-tally::form.input type="date" name="from_date" required /></x-tally::form.field>
            <x-tally::form.field name="to_date" label="To" required><x-tally::form.input type="date" name="to_date" required /></x-tally::form.field>
            <x-tally::form.field name="revenue" label="Revenue" required><x-tally::form.input name="revenue" required /></x-tally::form.field>
            <x-tally::form.field name="cost" label="Cost" required><x-tally::form.input name="cost" required /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Snapshot</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Dimension</th><th>Label</th><th class="money">Revenue</th><th class="money">Cost</th><th class="money">Contribution</th><th>Margin %</th></tr></thead>
                <tbody>
                    @forelse ($snapshots as $row)
                        <tr>
                            <td>{{ $row->dimension }}</td>
                            <td>{{ $row->dimension_label }}</td>
                            <td class="money">{{ $row->revenue }}</td>
                            <td class="money">{{ $row->cost }}</td>
                            <td class="money">{{ $row->contribution }}</td>
                            <td>{{ $row->margin_percent }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No snapshots.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $snapshots->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
