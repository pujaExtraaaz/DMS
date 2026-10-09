<x-tally::layouts.app :title="'Company Alteration'">
    <x-tally::shell.page title="Company Alteration" section="Settings">
        <form class="tally-co" method="POST" action="{{ tally_route('books.tally.companies.update', $company) }}">
            @csrf
            @method('PUT')
            @include('tally::companies._form')
            <div class="tally-co-actions">
                <a class="btn" href="{{ tally_route('books.tally.companies.show', $company) }}">Q: Quit</a>
                <button class="btn btn-primary" type="submit">A: Accept</button>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
