<x-tally::layouts.app title="Contingent liabilities">
    <x-tally::shell.page title="Contingent liabilities" section="Liabilities" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.contingent-liabilities.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="kind" label="Kind">
                <select class="input" name="kind">
                    @foreach (['guarantee','contingent','provision'] as $kind)<option value="{{ $kind }}">{{ ucfirst($kind) }}</option>@endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="description" label="Description" required><x-tally::form.input name="description" required /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" required /></x-tally::form.field>
            <x-tally::form.field name="as_of_date" label="As of" required><x-tally::form.input type="date" name="as_of_date" :value="now()->toDateString()" required /></x-tally::form.field>
            <x-tally::form.field name="counterparty" label="Counterparty"><x-tally::form.input name="counterparty" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Add</button></div>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Kind</th><th>Description</th><th class="money">Amount</th><th>As Of</th></tr></thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td>{{ ucfirst($item->kind) }}</td>
                            <td>{{ $item->description }}</td>
                            <td class="money">{{ $item->amount }}</td>
                            <td>{{ $item->as_of_date->format('d M Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4">None recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $items->links() }}
    </x-tally::shell.page>
</x-tally::layouts.app>
