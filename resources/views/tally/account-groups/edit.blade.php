<x-tally::layouts.app :title="'Edit '.$group->name">
    <x-tally::shell.page :title="$group->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.account-groups.update', $group) }}">
            @csrf
            @method('PUT')
            @include('tally::account-groups._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save group</button>
                <a class="btn" href="{{ tally_route('books.tally.account-groups.show', $group) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
