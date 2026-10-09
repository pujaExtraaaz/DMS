<x-tally::layouts.app title="New HSN / SAC">
    <x-tally::shell.page title="New HSN / SAC" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.hsn-sacs.store') }}">
            @csrf
            @include('tally::hsn-sacs._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create HSN / SAC</button>
                <a class="btn" href="{{ tally_route('books.tally.hsn-sacs.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
