<x-tally::layouts.app title="Bank Statement Import">
    <x-tally::shell.page title="Bank Statement Import" section="Banking" :description="$company->name">
        <form class="stack" method="POST" action="{{ tally_route('books.tally.bank-statements.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="bank_account_id" label="Bank account" required>
                    <select id="bank_account_id" name="bank_account_id" class="input" required>
                        @foreach ($accounts as $account)
                            <option value="{{ $account->id }}" @selected((string) $accountId === (string) $account->id)>{{ $account->bank_name }} · {{ $account->account_number }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="file" label="CSV file" required>
                    <input id="file" class="input" type="file" name="file" accept=".csv,text/csv" required>
                </x-form.field>
            </div>
            <p class="form-note">Columns: Date, Narration, Debit, Credit. Optional: Value Date, Reference. A repeated line for the same account is skipped.</p>
            <button class="btn btn-primary" type="submit">Import</button>
            <a class="btn" href="{{ tally_route('books.tally.banking.reconciliation') }}">Bank reconciliation</a>
        </form>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Date</th><th>Value date</th><th>Narration</th><th class="money">Debit</th><th class="money">Credit</th><th>Reference</th><th>Matched voucher</th></tr></thead>
                <tbody>
                    @forelse ($lines as $line)
                        <tr>
                            <td>{{ $line->statement_date->format('d M Y') }}</td>
                            <td>{{ $line->value_date?->format('d M Y') }}</td>
                            <td>{{ $line->narration }}</td>
                            <td class="money">{{ $line->debit }}</td>
                            <td class="money">{{ $line->credit }}</td>
                            <td>{{ $line->reference }}</td>
                            <td>
                                @if ($line->voucherEntry?->voucher)
                                    <a href="{{ tally_route('books.tally.vouchers.show', $line->voucherEntry->voucher) }}">{{ $line->voucherEntry->voucher->voucher_number }}</a>
                                @else
                                    <form method="POST" action="{{ tally_route('books.tally.bank-statements.match', $line) }}">
                                        @csrf
                                        <input class="input" name="voucher_entry_id" placeholder="Voucher line id" required>
                                        <button class="btn" type="submit">Match</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7">No imported lines.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $lines->links() }}
    </x-shell.page>
</x-layouts.app>
