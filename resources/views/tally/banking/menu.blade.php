<x-tally::layouts.app title="Banking">
    <div class="gateway">
        <nav class="gateway-menu" data-key-menu aria-label="Banking">
            <h2>Banking</h2>
            <p class="gateway-group">Reconciliation / E-Payments</p>
            <a class="is-current" href="{{ tally_route('books.tally.banking.activities') }}">Banking Activities</a>
            <a href="{{ tally_route('books.tally.bank-statements.index') }}">Imported Bank Data</a>
            <p class="gateway-group">Cheque</p>
            <a href="{{ tally_route('books.tally.banking.cheques.printing') }}">Cheque Printing</a>
            <a href="{{ tally_route('books.tally.banking.cheques') }}">Cheque Register</a>
            <a href="{{ tally_route('books.tally.banking.cheques.post-dated') }}">Post-Dated Summary</a>
            <p class="gateway-group">Other Reports</p>
            <a href="{{ tally_route('books.tally.banking.deposits') }}">Deposit Slip</a>
            <a href="{{ tally_route('books.tally.banking.advices') }}">Payment Advice</a>
            <a href="{{ tally_route('books.tally.banking.gateway') }}">Payment Gateway Reconciliation</a>
            <a data-esc href="{{ tally_route('books.tally.dashboard') }}">Quit</a>
        </nav>
    </div>
</x-layouts.app>
