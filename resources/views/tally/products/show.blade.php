<x-tally::layouts.app :title="$product->name">
    <x-tally::shell.page :title="$product->name" section="Masters" :description="$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.products.edit', $product) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.products.destroy', $product) }}" onsubmit="return confirm('Delete this product?');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>
        <section class="panel">
            <dl class="kv">
                <dt>SKU</dt><dd>{{ $product->code }}</dd>
                <dt>Barcode</dt><dd>{{ $product->barcode ?: '—' }} @if ($product->barcode || $product->barcodes->isNotEmpty())<a href="{{ tally_route('books.tally.products.barcode', $product) }}" target="_blank">Print</a>@endif</dd>
                <dt>HSN / SAC</dt><dd>{{ $product->hsnSac?->label() ?? '—' }}</dd>
                <dt>Tax rate</dt><dd>{{ $product->taxRate?->name ?? '—' }}</dd>
                <dt>Group</dt><dd><a href="{{ tally_route('books.tally.product-groups.show', $product->productGroup) }}">{{ $product->productGroup->name }}</a></dd>
                <dt>Primary unit</dt><dd><a href="{{ tally_route('books.tally.units.show', $product->primaryUnit) }}">{{ $product->primaryUnit->label() }}</a></dd>
                <dt>Alternate unit</dt><dd>{{ $product->alternateUnit ? $product->alternateUnit->label() : '—' }}</dd>
                <dt>Conversion factor</dt><dd>{{ $product->conversion_factor ? rtrim(rtrim($product->conversion_factor, '0'), '.') : '—' }}</dd>
                <dt>Purchase rate</dt><dd>{{ $product->purchase_rate }}</dd>
                <dt>Sales rate</dt><dd>{{ $product->sales_rate }}</dd>
                <dt>Opening quantity</dt><dd>{{ $product->trimmedQuantity('opening_quantity') }}</dd>
                <dt>Opening rate</dt><dd>{{ $product->opening_rate }}</dd>
                <dt>Opening value</dt><dd>{{ $product->opening_value }}</dd>
                <dt>Minimum stock</dt><dd>{{ $product->trimmedQuantity('minimum_stock') }}</dd>
                <dt>Reorder level</dt><dd>{{ $product->trimmedQuantity('reorder_level') }}</dd>
                <dt>Status</dt><dd><x-tally::ui.status :active="$product->is_active" /></dd>
            </dl>
        </section>
    </x-shell.page>
</x-layouts.app>
