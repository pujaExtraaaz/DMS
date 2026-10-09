<x-tally::layouts.app :title="'Edit '.$unit->name">
    <x-tally::shell.page :title="$unit->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.units.update', $unit) }}">
            @csrf
            @method('PUT')
            @include('tally::units._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save unit</button>
                <a class="btn" href="{{ tally_route('books.tally.units.show', $unit) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
