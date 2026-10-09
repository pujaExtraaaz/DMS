<x-tally::layouts.app title="Banking Activities">
    <div class="gateway">
        <nav class="gateway-menu" data-key-menu aria-label="Banking Activities">
            <h2>Banking Activities</h2>
            @if ($selected)
                <p class="gateway-group">{{ $selected->name }}</p>
                @if (($figures[$selected->id] ?? '') !== '')
                    <p class="gateway-group">Balance {{ $figures[$selected->id] }}</p>
                @endif
                <a class="is-current" href="{{ tally_route('books.tally.banking.transactions', ['ledger_id' => $selected->id]) }}">Transactions</a>
                <a href="{{ tally_route('books.tally.banking.reconciliation', ['ledger_id' => $selected->id]) }}">Reconciliation</a>
                <a href="{{ tally_route('books.tally.bank-statements.index') }}">Imported Bank Data</a>
                <a data-esc href="{{ tally_route('books.tally.banking.activities') }}">Quit</a>
            @else
                <p class="gateway-group">Bank Accounts</p>
                @forelse ($ledgers as $ledger)
                    <a @if ($loop->first) class="is-current" @endif href="{{ tally_route('books.tally.banking.activities', ['ledger_id' => $ledger->id]) }}">
                        {{ $ledger->name }}
                        @if (($figures[$ledger->id] ?? '') !== '')
                            <span>{{ $figures[$ledger->id] }}</span>
                        @endif
                    </a>
                @empty
                    <p class="gateway-group">No bank ledger in this company.</p>
                @endforelse
                <a data-esc href="{{ tally_route('books.tally.banking.menu') }}">Quit</a>
            @endif
        </nav>
    </div>
</x-layouts.app>
