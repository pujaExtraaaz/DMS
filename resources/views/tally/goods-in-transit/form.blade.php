<x-tally::layouts.app title="New goods in transit">
    <x-tally::shell.page title="New goods in transit" section="Inventory" :description="$company->name">
        <form method="POST" class="panel" action="{{ tally_route('books.tally.goods-in-transit.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="product_id" label="Product" required>
                    <select class="input" name="product_id" required>
                        <option value="">Select</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('product_id') === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="quantity" label="Quantity" required>
                    <x-tally::form.input name="quantity" value="{{ old('quantity') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="from_godown_id" label="From godown">
                    <select class="input" name="from_godown_id">
                        <option value="">—</option>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) old('from_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="to_godown_id" label="To godown">
                    <select class="input" name="to_godown_id">
                        <option value="">—</option>
                        @foreach ($godowns as $godown)
                            <option value="{{ $godown->id }}" @selected((string) old('to_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="dispatch_date" label="Dispatch date" required>
                    <x-tally::form.input type="date" name="dispatch_date" value="{{ old('dispatch_date', optional($record->dispatch_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="expected_receipt_date" label="Expected receipt">
                    <x-tally::form.input type="date" name="expected_receipt_date" value="{{ old('expected_receipt_date') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="reference" label="Reference">
                    <x-tally::form.input name="reference" value="{{ old('reference') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="is_consignment" label="Consignment">
                    <label><input type="checkbox" name="is_consignment" value="1" @checked(old('is_consignment'))> Consignment stock</label>
                </x-tally::form.field>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.goods-in-transit.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
