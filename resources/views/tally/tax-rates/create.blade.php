<x-tally::layouts.app title="New tax rate">
    <x-tally::shell.page title="New tax rate" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.tax-rates.store') }}">
            @csrf
            @include('tally::tax-rates._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create tax rate</button>
                <a class="btn" href="{{ tally_route('books.tally.tax-rates.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
