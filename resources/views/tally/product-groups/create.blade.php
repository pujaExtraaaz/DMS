<x-tally::layouts.app title="New product group">
    <x-tally::shell.page title="New product group" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.product-groups.store') }}">
            @csrf
            @include('tally::product-groups._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create product group</button>
                <a class="btn" href="{{ tally_route('books.tally.product-groups.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
