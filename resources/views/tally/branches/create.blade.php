<x-tally::layouts.app title="New branch">
    <x-tally::shell.page title="New branch" section="Settings" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.companies.branches.store', $company) }}">
            @csrf
            @include('tally::branches._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create branch</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.branches.index', $company) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
