<x-tally::layouts.app title="New product">
    <x-tally::shell.page title="New product" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.products.store') }}">
            @csrf
            @include('tally::products._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create product</button>
                <a class="btn" href="{{ tally_route('books.tally.products.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
