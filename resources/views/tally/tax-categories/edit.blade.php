<x-tally::layouts.app title="Edit tax category">
    <x-tally::shell.page :title="'Edit '.$category->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.tax-categories.update', $category) }}">
            @csrf
            @method('PUT')
            @include('tally::tax-categories._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save tax category</button>
                <a class="btn" href="{{ tally_route('books.tally.tax-categories.show', $category) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
