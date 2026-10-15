<x-tally::layouts.app title="Cash forecast">
    <x-tally::shell.page title="Cash forecast" section="Banking" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.banking.forecasts.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name') }}" required /></x-tally::form.field>
            <x-tally::form.field name="as_of_date" label="As of" required><x-tally::form.input type="date" name="as_of_date" value="{{ old('as_of_date', now()->toDateString()) }}" required /></x-tally::form.field>
            <x-tally::form.field name="horizon_days" label="Horizon (days)"><x-tally::form.input type="number" name="horizon_days" value="{{ old('horizon_days', 30) }}" /></x-tally::form.field>
            <x-tally::form.field name="opening_cash" label="Opening cash" required><x-tally::form.input name="opening_cash" value="{{ old('opening_cash', '0.00') }}" required /></x-tally::form.field>
            <x-tally::form.field name="projected_inflows" label="Projected inflows"><x-tally::form.input name="projected_inflows" value="{{ old('projected_inflows', '0.00') }}" /></x-tally::form.field>
            <x-tally::form.field name="projected_outflows" label="Projected outflows"><x-tally::form.input name="projected_outflows" value="{{ old('projected_outflows', '0.00') }}" /></x-tally::form.field>
            <x-tally::form.field name="scenario" label="Scenario"><x-tally::form.input name="scenario" value="{{ old('scenario', 'base') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save forecast</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Name</th><th>As Of</th><th>Horizon</th><th>Scenario</th>
                        <th class="money">Opening</th><th class="money">In</th><th class="money">Out</th><th class="money">Closing</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($forecasts as $forecast)
                        <tr>
                            <td>{{ $forecast->name }}</td>
                            <td>{{ $forecast->as_of_date?->format('d M Y') }}</td>
                            <td>{{ $forecast->horizon_days }}d</td>
                            <td>{{ $forecast->scenario }}</td>
                            <td class="money">{{ $forecast->opening_cash }}</td>
                            <td class="money">{{ $forecast->projected_inflows }}</td>
                            <td class="money">{{ $forecast->projected_outflows }}</td>
                            <td class="money">{{ $forecast->closing_cash }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8">No cash forecasts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $forecasts->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
