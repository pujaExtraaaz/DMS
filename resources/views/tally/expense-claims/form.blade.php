<x-tally::layouts.app title="New expense claim">
    <x-tally::shell.page title="New expense claim" section="Payables" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.expense-claims.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="claim_date" label="Date" required>
                    <x-tally::form.input type="date" name="claim_date" value="{{ old('claim_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="employee_ledger_id" label="Employee ledger">
                    <select class="input" name="employee_ledger_id">
                        <option value="">Select</option>
                        @foreach ($ledgers as $ledger)
                            <option value="{{ $ledger->id }}" @selected((string) old('employee_ledger_id') === (string) $ledger->id)>{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="category" label="Category">
                    <x-tally::form.input name="category" value="{{ old('category') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-tally::form.field>
            </div>
            @php $lines = old('lines', [['expense_date' => now()->toDateString(), 'description' => '', 'amount' => '', 'category' => '']]); @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Date</th><th>Category</th><th>Description</th><th>Amount</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" type="date" name="lines[{{ $i }}][expense_date]" value="{{ $line['expense_date'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][category]" value="{{ $line['category'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][description]" value="{{ $line['description'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][amount]" value="{{ $line['amount'] ?? '' }}" required></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.expense-claims.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
