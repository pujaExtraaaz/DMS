<x-tally::layouts.app title="New ledger">
    <x-tally::shell.page title="New ledger" section="Masters" :description="$company->name">
        <form class="panel tally-master" method="POST" action="{{ tally_route('books.tally.ledgers.store') }}">
            @csrf
            @include('tally::ledgers._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create ledger</button>
                <a class="btn" href="{{ tally_route('books.tally.ledgers.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
