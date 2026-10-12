<x-tally::layouts.app title="Unbilled revenue">
    <x-tally::shell.page title="Unbilled revenue" section="Utilities" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.unbilled-revenue.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="accrued_income_ledger_id" label="Accrued income ledger" required>
                <select class="input" name="accrued_income_ledger_id" required>
                    <option value="">Select</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="revenue_ledger_id" label="Revenue ledger" required>
                <select class="input" name="revenue_ledger_id" required>
                    <option value="">Select</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required>
                <x-tally::form.input name="amount" required />
            </x-tally::form.field>
            <x-tally::form.field name="date" label="Date" required>
                <x-tally::form.input name="date" type="date" value="{{ now()->toDateString() }}" required />
            </x-tally::form.field>
            <label class="check"><input type="checkbox" name="auto_reverse" value="1" checked> Reverse next day</label>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Post accrual</button></div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
