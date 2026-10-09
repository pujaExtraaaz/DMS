<x-tally::layouts.app :title="'Edit '.$product->name">
    <x-tally::shell.page :title="$product->name" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ tally_route('books.tally.products.update', $product) }}">
            @csrf
            @method('PUT')
            @include('tally::products._form')
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save product</button>
                <a class="btn" href="{{ tally_route('books.tally.products.show', $product) }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
