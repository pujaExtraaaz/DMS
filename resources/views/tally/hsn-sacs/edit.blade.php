<x-tally::layouts.app title="Edit HSN / SAC">
    <x-tally::shell.page :title="'Edit '.$record->code" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.hsn-sacs.update', $record) }}">
            @csrf
            @method('PUT')
            @include('tally::hsn-sacs._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save HSN / SAC</button>
                <a class="btn" href="{{ tally_route('books.tally.hsn-sacs.show', $record) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
