<x-tally::layouts.app title="Edit tax rate">
    <x-tally::shell.page :title="'Edit '.$rate->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.tax-rates.update', $rate) }}">
            @csrf
            @method('PUT')
            @include('tally::tax-rates._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save tax rate</button>
                <a class="btn" href="{{ tally_route('books.tally.tax-rates.show', $rate) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
