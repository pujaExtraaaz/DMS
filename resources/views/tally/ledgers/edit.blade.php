<x-tally::layouts.app :title="'Edit '.$ledger->name">
    <x-tally::shell.page :title="$ledger->name" section="Masters" :description="$company->name">
        <form class="panel tally-master" method="POST" action="{{ tally_route('books.tally.ledgers.update', $ledger) }}">
            @csrf
            @method('PUT')
            @include('tally::ledgers._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save ledger</button>
                <a class="btn" href="{{ tally_route('books.tally.ledgers.show', $ledger) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
