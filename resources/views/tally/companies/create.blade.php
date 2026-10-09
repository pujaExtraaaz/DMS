<x-tally::layouts.app title="Company Creation">
    <x-tally::shell.page title="Company Creation" section="Settings">
        <form class="tally-co" method="POST" action="{{ tally_route('books.tally.companies.store') }}">
            @csrf
            @include('tally::companies._form')
            <div class="tally-co-actions">
                <a class="btn" href="{{ tally_route('books.tally.companies.index') }}">Q: Quit</a>
                <button class="btn btn-primary" type="submit">A: Accept</button>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
