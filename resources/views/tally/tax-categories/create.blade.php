<x-tally::layouts.app title="New tax category">
    <x-tally::shell.page title="New tax category" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.tax-categories.store') }}">
            @csrf
            @include('tally::tax-categories._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create tax category</button>
                <a class="btn" href="{{ tally_route('books.tally.tax-categories.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
