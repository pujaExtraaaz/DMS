<x-tally::layouts.app :title="$title">
    <x-tally::shell.page :title="$title" section="Reports" :description="$company->name">
        <form class="filters" method="GET">
            <x-tally::form.field name="product_id" label="Product">
                <select id="product_id" name="product_id" class="input">
                    <option value="">All products</option>
                    @foreach ($products as $product)
                        <option value="{{ $product->id }}" @selected((string) ($filters['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="batch_id" label="Batch">
                <select id="batch_id" name="batch_id" class="input">
                    <option value="">All batches</option>
                    @foreach ($batches as $batch)
                        <option value="{{ $batch->id }}" @selected((string) ($filters['batch_id'] ?? '') === (string) $batch->id)>{{ $batch->product->name }} · {{ $batch->batch_number }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <x-tally::form.field name="serial_id" label="Serial">
                <select id="serial_id" name="serial_id" class="input">
                    <option value="">All serials</option>
                    @foreach ($serials as $serial)
                        <option value="{{ $serial->id }}" @selected((string) ($filters['serial_id'] ?? '') === (string) $serial->id)>{{ $serial->serial_number }}</option>
                    @endforeach
                </select>
            </x-form.field>
            <button class="btn btn-primary" type="submit">Apply</button>
        </form>
        @if ($report['rows'] === [])
            <x-tally::ui.empty-state title="No movements" message="Batch and serial movements will appear here." />
        @else
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Date</th><th>Product</th><th>Godown</th><th>Batch</th><th>Serial</th><th>Quantity</th><th>Value</th><th>Reference</th></tr></thead>
                    <tbody>
                        @foreach ($report['rows'] as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-shell.page>
</x-layouts.app>
