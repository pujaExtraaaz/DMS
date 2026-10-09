<x-tally::layouts.app title="New godown">
    <x-tally::shell.page title="New godown" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.godowns.store') }}">
            @csrf
            @include('tally::godowns._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create godown</button>
                <a class="btn" href="{{ tally_route('books.tally.godowns.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
