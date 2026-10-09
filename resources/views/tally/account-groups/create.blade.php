<x-tally::layouts.app title="New account group">
    <x-tally::shell.page title="New account group" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.account-groups.store') }}">
            @csrf
            @include('tally::account-groups._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create group</button>
                <a class="btn" href="{{ tally_route('books.tally.account-groups.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
