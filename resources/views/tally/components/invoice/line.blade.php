@props(['index', 'line', 'products' => [], 'godowns' => [], 'taxRates' => [], 'hsnSacs' => [], 'purchase' => false])
<tr>
    <td>
        @php
            $selectedProduct = collect($products)->first(fn ($product) => (string) $product->id === (string) ($line['product_id'] ?? ''));
        @endphp
        <div class="picker" data-picker>
            <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Stock item" value="{{ $selectedProduct->name ?? '' }}">
            <select class="picker-store" name="lines[{{ $index }}][product_id]" tabindex="-1" aria-hidden="true">
                <option value="">No stock item</option>
                @foreach ($products as $product)
                    <option value="{{ $product->id }}" data-rate="{{ $purchase ? $product->purchase_rate : $product->sales_rate }}" data-hsn="{{ $product->hsn_sac_id }}" data-tax="{{ $product->tax_rate_id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                @endforeach
            </select>
            <div class="picker-list" hidden>
                <button type="button" data-picker-choice data-value="">No stock item</button>
                @foreach ($products as $product)
                    <button type="button" data-picker-choice data-value="{{ $product->id }}" data-code="{{ $product->name }}">{{ $product->name }}</button>
                @endforeach
                <a href="{{ tally_route('books.tally.products.create') }}">Create</a>
            </div>
            <a class="picker-alter" data-picker-alter data-base="{{ url('/masters/products/__ID__/edit') }}" href="{{ $selectedProduct ? tally_route('books.tally.products.edit', $selectedProduct) : '#' }}" @unless ($selectedProduct) hidden @endunless>Alter</a>
        </div>
        <input class="input item-name" name="lines[{{ $index }}][item_name]" value="{{ $line['item_name'] ?? '' }}" maxlength="200" placeholder="Item or product" data-item>
        @include('tally::masters._hsn', [
            'name' => 'lines['.$index.'][hsn_sac_id]',
            'hsnSacs' => $hsnSacs,
            'current' => $line['hsn_sac_id'] ?? '',
        ])
        <select class="input" name="lines[{{ $index }}][godown_id]">
            <option value="">Godown</option>
            @foreach ($godowns as $godown)
                <option value="{{ $godown->id }}" @selected((string) ($line['godown_id'] ?? '') === (string) $godown->id)>{{ $godown->name }}</option>
            @endforeach
        </select>
        @error('lines.'.$index.'.item_name')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </td>
    <td>
        <x-tally::invoice.quantity :name="'lines['.$index.'][quantity]'" :value="$line['quantity'] ?? ''" />
        @error('lines.'.$index.'.quantity')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </td>
    <td>
        <x-tally::invoice.rate :name="'lines['.$index.'][rate]'" :value="$line['rate'] ?? ''" />
        @error('lines.'.$index.'.rate')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </td>
    <td>
        <x-tally::invoice.discount :name="'lines['.$index.'][discount]'" :value="$line['discount'] ?? ''" />
        @error('lines.'.$index.'.discount')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </td>
    <td>
        <x-tally::invoice.tax :name="'lines['.$index.'][tax_amount]'" :value="$line['tax_amount'] ?? ''" />
        <select class="input" name="lines[{{ $index }}][tax_rate_id]">
            <option value="">No tax rate</option>
            @foreach ($taxRates as $taxRate)
                <option value="{{ $taxRate->id }}" data-cgst="{{ $taxRate->cgst_rate }}" data-sgst="{{ $taxRate->sgst_rate }}" data-igst="{{ $taxRate->igst_rate }}" data-cess="{{ $taxRate->cess_rate }}" @selected((string) ($line['tax_rate_id'] ?? '') === (string) $taxRate->id)>{{ $taxRate->name }}</option>
            @endforeach
        </select>
        @error('lines.'.$index.'.tax_amount')
            <p class="field-error">{{ $message }}</p>
        @enderror
    </td>
    <td class="money" data-line-total>{{ $line['line_total'] ?? '0.00' }}</td>
    <td><button class="btn" type="button" data-remove-line>Remove</button></td>
</tr>
