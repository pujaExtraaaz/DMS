<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Workspace">
        <x-tally::ui.empty-state :title="$title" :message="$message">
            <a class="btn" href="{{ tally_route('books.tally.companies.index') }}">View companies</a>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.companies.create') }}">Create company</a>
        </x-ui.empty-state>
    </x-shell.page>
</x-layouts.app>
