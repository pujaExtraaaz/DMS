<x-tally::layouts.app title="Edit company">
    <x-tally::shell.page title="Edit company" section="Settings" :description="$company->name">
        <form class="panel stack" method="POST" action="{{ tally_route('books.tally.companies.update', $company) }}">
            @csrf
            @method('PUT')
            @include('tally::companies._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save company</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.show', $company) }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
