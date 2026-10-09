<x-tally::layouts.app title="Bank accounts">
    <x-tally::shell.page title="Bank accounts" section="Transactions" :description="$company->name">
        <x-slot:actions>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.ledgers.create') }}">New ledger</a>
        </x-slot:actions>
        <p class="form-note">A bank account is a ledger under Bank Accounts. Its opening balance is the opening bank balance.</p>
        <div class="table-wrap">
            <table class="data">
                <thead>
                    <tr>
                        <th>Ledger</th>
                        <th>Bank</th>
                        <th>Account number</th>
                        <th>IFSC</th>
                        <th class="money">Opening bank balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ledgers as $ledger)
                        <tr>
                            <td><a href="{{ tally_route('books.tally.ledgers.show', $ledger) }}">{{ $ledger->name }}</a></td>
                            <td>{{ $ledger->bankAccount->bank_name ?? '—' }}</td>
                            <td>{{ $ledger->bankAccount->account_number ?? '—' }}</td>
                            <td>{{ $ledger->bankAccount->ifsc ?? '—' }}</td>
                            <td class="money">{{ $ledger->openingBalanceLabel() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No bank ledgers yet. Create a ledger in the Bank Accounts group.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-shell.page>
</x-layouts.app>
