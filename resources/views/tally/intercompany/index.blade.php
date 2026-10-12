<x-tally::layouts.app title="Intercompany">
    <x-tally::shell.page title="Intercompany" section="Utilities" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.ledger-mirrors.index') }}">Ledger mirrors</a>
        </x-slot:actions>

        <form method="GET" class="filters">
            <x-tally::form.field name="from" label="From"><x-tally::form.input type="date" name="from" value="{{ $from }}" /></x-tally::form.field>
            <x-tally::form.field name="to" label="To"><x-tally::form.input type="date" name="to" value="{{ $to }}" /></x-tally::form.field>
            <button class="btn" type="submit">Filter</button>
        </form>

        <div class="form-grid" style="margin-bottom:1rem">
            <div class="panel"><strong>IC total</strong><div>{{ $worksheet['total'] ?? '0.00' }}</div></div>
            <div class="panel"><strong>Eliminated</strong><div>{{ $worksheet['eliminated_total'] ?? '0.00' }}</div></div>
            <div class="panel"><strong>Open</strong><div>{{ $worksheet['open_total'] ?? '0.00' }}</div></div>
        </div>

        <h3>Consolidation worksheet</h3>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Source</th>
                        <th>Counterparty</th>
                        <th>Amount</th>
                        <th>Vouchers</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($rows as $row)
                        <tr>
                            <td>{{ $row['date'] }}</td>
                            <td>
                                {{ $row['source_company'] }}
                                @if (! empty($row['source_voucher_id']))
                                    <a href="{{ tally_route('books.tally.vouchers.show', $row['source_voucher_id']) }}">#{{ $row['source_voucher_id'] }}</a>
                                @endif
                            </td>
                            <td>
                                {{ $row['counterparty'] }}
                                @if (! empty($row['mirror_voucher_id']))
                                    <a href="{{ tally_route('books.tally.vouchers.show', $row['mirror_voucher_id']) }}">#{{ $row['mirror_voucher_id'] }}</a>
                                @endif
                            </td>
                            <td>{{ $row['amount'] }}</td>
                            <td>{{ $row['voucher_count'] }}</td>
                            <td>{{ ($row['eliminated'] ?? false) ? 'Eliminated' : 'Open' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No intercompany pairs in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <h3>Post elimination journals</h3>
        <form method="POST" action="{{ tally_route('books.tally.intercompany.eliminate') }}" class="filters">
            @csrf
            <input type="hidden" name="from" value="{{ $from }}">
            <input type="hidden" name="to" value="{{ $to }}">
            <select class="input" name="elimination_ledger_id" required>
                <option value="">Clearing / elimination ledger</option>
                @foreach ($ledgers as $ledger)
                    <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                @endforeach
            </select>
            <button class="btn" type="submit">Post eliminations for range</button>
        </form>

        <h3>Post paired journal</h3>
        <form method="POST" action="{{ tally_route('books.tally.intercompany.store') }}" class="stack">
            @csrf
            <x-tally::form.field name="counterparty_company_id" label="Counterparty company">
                <select class="input" name="counterparty_company_id" required>
                    @foreach ($companies as $c)
                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="voucher_date" label="Date"><x-tally::form.input type="date" name="voucher_date" value="{{ old('voucher_date', now()->toDateString()) }}" /></x-tally::form.field>
            <x-tally::form.field name="ledger_id" label="Ledger">
                <select class="input" name="ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <div class="form-grid">
                <x-tally::form.field name="amount" label="Amount"><x-tally::form.input name="amount" required /></x-tally::form.field>
                <x-tally::form.field name="side" label="Side">
                    <select class="input" name="side"><option value="debit">Debit</option><option value="credit">Credit</option></select>
                </x-tally::form.field>
            </div>
            <x-tally::form.field name="source_ic_ledger_id" label="IC ledger (optional)">
                <select class="input" name="source_ic_ledger_id">
                    <option value="">Auto</option>
                    @foreach ($icLedgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="narration" label="Narration"><x-tally::form.input name="narration" /></x-tally::form.field>
            <button class="btn btn-primary" type="submit">Post pair</button>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
