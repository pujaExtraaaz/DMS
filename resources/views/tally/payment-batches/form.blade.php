<x-tally::layouts.app title="New payment batch">
    <x-tally::shell.page title="New payment batch" section="Payables" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.payment-batches.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="batch_date" label="Date" required>
                    <x-tally::form.input type="date" name="batch_date" value="{{ old('batch_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="bank_ledger_id" label="Bank / cash">
                    <select class="input" name="bank_ledger_id">
                        <option value="">Select</option>
                        @foreach ($bankLedgers as $ledger)
                            <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-tally::form.field>
            </div>
            @php $lines = old('lines', [['supplier_ledger_id' => '', 'amount' => '', 'tds_amount' => '0', 'reference' => '']]); @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Supplier</th><th>Amount</th><th>TDS</th><th>Reference</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td>
                                    <select class="input" name="lines[{{ $i }}][supplier_ledger_id]" required>
                                        <option value="">Select</option>
                                        @foreach ($suppliers as $id => $name)
                                            <option value="{{ $id }}" @selected((string) ($line['supplier_ledger_id'] ?? '') === (string) $id)>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="lines[{{ $i }}][amount]" value="{{ $line['amount'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][tds_amount]" value="{{ $line['tds_amount'] ?? '0' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][reference]" value="{{ $line['reference'] ?? '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.payment-batches.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
