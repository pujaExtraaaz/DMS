<x-tally::layouts.app :title="$bill->exists ? 'Edit BOM' : 'New BOM'">
    <x-tally::shell.page :title="$bill->exists ? 'Edit bill of materials' : 'New bill of materials'" section="Masters" :description="$company->name">
        <form method="POST" action="{{ $bill->exists ? tally_route('books.tally.boms.update', $bill) : tally_route('books.tally.boms.store') }}" class="stack">
            @csrf
            @if ($bill->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name', $bill->name) }}" required />
                </x-form.field>
                <x-tally::form.field name="finished_product_id" label="Finished product" required>
                    <select id="finished_product_id" name="finished_product_id" class="input" required>
                        <option value="">Select product</option>
                        @foreach ($products as $product)
                            <option value="{{ $product->id }}" @selected((string) old('finished_product_id', $bill->finished_product_id) === (string) $product->id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="wastage_percent" label="Wastage %">
                    <x-tally::form.input name="wastage_percent" value="{{ old('wastage_percent', $bill->wastage_percent ?? '0') }}" />
                </x-form.field>
                <x-tally::form.field name="is_active" label="Status">
                    <label><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $bill->is_active))> Active</label>
                </x-form.field>
            </div>
            @error('lines')<p class="field-error">{{ $message }}</p>@enderror
            <h2>Components</h2>
            <p>Quantities are per one finished unit. Choose the alternate unit when the quantity is in that unit.</p>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Component</th><th>Quantity</th><th>Unit</th><th>Extra wastage %</th></tr></thead>
                    <tbody>
                        @foreach (old('lines', $bill->exists ? $bill->lines->map->only(['product_id', 'quantity', 'unit_id', 'wastage_percent'])->all() : array_fill(0, 4, [])) as $index => $line)
                            <tr>
                                <td>
                                    <select class="input" name="lines[{{ $index }}][product_id]">
                                        <option value="">Select component</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="lines[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}"></td>
                                <td>
                                    <select class="input" name="lines[{{ $index }}][unit_id]">
                                        <option value="">Primary unit</option>
                                        @foreach ($units as $unit)
                                            <option value="{{ $unit->id }}" @selected((string) ($line['unit_id'] ?? '') === (string) $unit->id)>{{ $unit->symbol }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="lines[{{ $index }}][wastage_percent]" value="{{ $line['wastage_percent'] ?? '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <h2>By-products / scrap</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>By-product</th><th>Quantity per finished unit</th></tr></thead>
                    <tbody>
                        @foreach (old('byproducts', $bill->exists ? $bill->byproducts->map->only(['product_id', 'quantity'])->all() : array_fill(0, 2, [])) as $index => $line)
                            <tr>
                                <td>
                                    <select class="input" name="byproducts[{{ $index }}][product_id]">
                                        <option value="">None</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input" name="byproducts[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.boms.index') }}">Cancel</a>
            </div>
        </form>
    </x-shell.page>
</x-layouts.app>
