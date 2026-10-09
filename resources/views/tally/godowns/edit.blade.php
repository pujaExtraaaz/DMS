<x-tally::layouts.app :title="'Edit '.$godown->name">
    <x-tally::shell.page :title="$godown->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.godowns.update', $godown) }}">
            @csrf
            @method('PUT')
            @include('tally::godowns._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save godown</button>
                <a class="btn" href="{{ tally_route('books.tally.godowns.show', $godown) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
