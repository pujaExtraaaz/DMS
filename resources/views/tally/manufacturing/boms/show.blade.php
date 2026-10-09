<x-tally::layouts.app :title="$bill->name">
    <x-tally::shell.page :title="$bill->name" section="Masters" :description="$bill->finishedProduct->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.boms.edit', $bill) }}">Edit</a>
            <a class="btn btn-primary" href="{{ tally_route('books.tally.manufacturing.create') }}">Manufacture</a>
        </x-slot:actions>
        <dl class="sheet">
            <div><dt>Status</dt><dd><x-tally::ui.status :active="$bill->is_active" /></dd></div>
            <div><dt>Wastage</dt><dd>{{ $bill->wastage_percent }}%</dd></div>
        </dl>
        <h2>Components</h2>
        <div class="table-wrap">
            <table class="data">
                <thead><tr><th>Component</th><th>Quantity</th><th>Unit</th><th>Extra wastage %</th></tr></thead>
                <tbody>
                    @foreach ($bill->lines as $line)
                        <tr>
                            <td>{{ $line->product->name }}</td>
                            <td>{{ $line->quantity }}</td>
                            <td>{{ $line->unit?->symbol ?? $line->product->primaryUnit->symbol }}</td>
                            <td>{{ $line->wastage_percent }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($bill->byproducts->isNotEmpty())
            <h2>By-products</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Product</th><th>Quantity per finished unit</th></tr></thead>
                    <tbody>
                        @foreach ($bill->byproducts as $line)
                            <tr><td>{{ $line->product->name }}</td><td>{{ $line->quantity }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
