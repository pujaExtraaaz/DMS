<x-tally::layouts.app :title="$list->exists ? 'Alter price list' : 'New price list'">
    <x-tally::shell.page :title="$list->exists ? 'Alter price list' : 'New price list'" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ $list->exists ? tally_route('books.tally.price-lists.update', $list) : tally_route('books.tally.price-lists.store') }}">
            @csrf
            @if ($list->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name', $list->name) }}" required maxlength="120" />
                </x-form.field>
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $list->is_active ?? true))> Active</label>
            </div>
            <p class="form-note">A price level replaces the product's standard rate on a sales or purchase voucher when that level is selected.</p>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @for ($index = 0; $index < max(8, count($lines)); $index++)
                            @php $line = $lines[$index] ?? null; @endphp
                            <tr>
                                <td>
                                    <select class="input" name="lines[{{ $index }}][product_id]">
                                        <option value="">Select item</option>
                                        @foreach ($products as $product)
                                            <option value="{{ $product->id }}" @selected((string) old('lines.'.$index.'.product_id', $line->product_id ?? '') === (string) $product->id)>{{ $product->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input class="input money" name="lines[{{ $index }}][rate]" inputmode="decimal" value="{{ old('lines.'.$index.'.rate', $line->rate ?? '') }}">
                                </td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn" href="{{ tally_route('books.tally.price-lists.index') }}">Cancel</a>
        </form>
    </x-shell.page>
</x-layouts.app>
