<x-tally::layouts.app :title="'Edit '.$branch->name">
    <x-tally::shell.page :title="'Edit '.$branch->name" section="Settings" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.companies.branches.update', [$company, $branch]) }}">
            @csrf
            @method('PUT')
            @include('tally::branches._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save branch</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.branches.show', [$company, $branch]) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
