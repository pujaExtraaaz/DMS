<x-tally::layouts.app title="New company">
    <x-tally::shell.page title="New company" section="Settings">
        <form class="panel stack" method="POST" action="{{ tally_route('books.tally.companies.store') }}">
            @csrf
            @include('tally::companies._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Create company</button>
                <a class="btn" href="{{ tally_route('books.tally.companies.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
