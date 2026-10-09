<x-tally::layouts.app :title="$ledger->name">
    <x-tally::shell.page :title="$ledger->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            @if ($previousLedger)
                <a class="btn" rel="prev" href="{{ tally_route('books.tally.ledgers.show', $previousLedger) }}">Previous</a>
            @endif
            @if ($nextLedger)
                <a class="btn" rel="next" href="{{ tally_route('books.tally.ledgers.show', $nextLedger) }}">Next</a>
            @endif
            <a class="btn" href="{{ tally_route('books.tally.ledgers.edit', $ledger) }}">Edit</a>
            @if ($ledger->canBeDeleted())
                <form method="POST" action="{{ tally_route('books.tally.ledgers.destroy', $ledger) }}" onsubmit="return confirm('Delete this ledger?');">
                    @csrf
                    @method('DELETE')
                    <button class="btn" type="submit">Delete</button>
                </form>
            @endif
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>Code</dt><dd>{{ $ledger->code ?: '—' }}</dd>
                <dt>Group</dt><dd><a href="{{ tally_route('books.tally.account-groups.show', $ledger->accountGroup) }}">{{ $ledger->accountGroup->name }}</a></dd>
                <dt>{{ $ledger->isBank() ? 'Opening bank balance' : 'Opening balance' }}</dt><dd>{{ $ledger->openingBalanceLabel() }}</dd>
                <dt>Address</dt><dd>{{ $ledger->address ?: '—' }}</dd>
                <dt>Phone</dt><dd>{{ $ledger->phone ?: '—' }}</dd>
                <dt>Email</dt><dd>{{ $ledger->email ?: '—' }}</dd>
                <dt>State</dt><dd>{{ $ledger->state ?: '—' }}</dd>
                <dt>GSTIN</dt><dd>{{ $ledger->gstin ?: '—' }}</dd>
                <dt>GST registration</dt><dd>{{ $ledger->gst_registration_type?->label() ?? '—' }}</dd>
                <dt>PAN</dt><dd>{{ $ledger->pan ?: '—' }}</dd>
                <dt>Credit limit</dt><dd>{{ $ledger->credit_limit !== null ? number_format((float) $ledger->credit_limit, 2) : '—' }}</dd>
                <dt>Credit days</dt><dd>{{ $ledger->credit_days ?? '—' }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$ledger->is_active" /></dd>
                @if ($ledger->bankAccount)
                    <dt>Bank name</dt><dd>{{ $ledger->bankAccount->bank_name }}</dd>
                    <dt>Account number</dt><dd>{{ $ledger->bankAccount->account_number }}</dd>
                    <dt>IFSC</dt><dd>{{ $ledger->bankAccount->ifsc }}</dd>
                @endif
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
