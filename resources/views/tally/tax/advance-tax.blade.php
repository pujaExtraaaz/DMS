<x-tally::layouts.app title="Advance tax">
    <x-tally::shell.page title="Advance tax" section="Tax" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.advance-tax.store') }}" class="form-grid panel">
            @csrf
            <x-tally::form.field name="payment_date" label="Payment date" required>
                <x-tally::form.input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required>
                <x-tally::form.input name="amount" value="{{ old('amount') }}" required />
            </x-tally::form.field>
            <x-tally::form.field name="advance_tax_ledger_id" label="Advance tax ledger" required>
                <select class="input" name="advance_tax_ledger_id" required>
                    <option value="">—</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected((string) old('advance_tax_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="bank_ledger_id" label="Bank ledger" required>
                <select class="input" name="bank_ledger_id" required>
                    <option value="">—</option>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}" @selected((string) old('bank_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="challan_number" label="Challan no.">
                <x-tally::form.input name="challan_number" value="{{ old('challan_number') }}" />
            </x-tally::form.field>
            <x-tally::form.field name="narration" label="Narration">
                <x-tally::form.input name="narration" value="{{ old('narration') }}" />
            </x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Post payment</button></div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
