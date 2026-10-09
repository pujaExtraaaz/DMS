<x-tally::layouts.app title="New unit">
    <x-tally::shell.page title="New unit" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.units.store') }}">
            @csrf
            @include('tally::units._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create unit</button>
                <a class="btn" href="{{ tally_route('books.tally.units.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
