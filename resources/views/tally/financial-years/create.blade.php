<x-tally::layouts.app title="New financial year">
    <x-tally::shell.page title="New financial year" section="Settings" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.companies.financial-years.store', $company) }}">
            @csrf
            @include('tally::financial-years._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create financial year</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.financial-years.index', $company) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
