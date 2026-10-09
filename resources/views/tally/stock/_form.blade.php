@php
    $rows = old('lines', array_fill(0, 4, ['product_id' => '', 'godown_id' => '', 'quantity' => '', 'rate' => '', 'direction' => 'increase']));
@endphp
<div class="form-grid">
    <x-tally::form.field name="transaction_date" label="Date" required>
        <x-tally::form.input name="transaction_date" type="date" value="{{ old('transaction_date', $date) }}" required />
    </x-form.field>
    <x-tally::form.field name="narration" label="Narration" class="span-2">
        <x-tally::form.input name="narration" value="{{ old('narration') }}" maxlength="1000" />
    </x-form.field>
    @if ($type === \Tally\Inventory\StockTransactionType::Transfer)
        <x-tally::form.field name="source_godown_id" label="From godown" required>
            <select id="source_godown_id" name="source_godown_id" class="input" required>
                <option value="">Select godown</option>
                @foreach ($godowns as $godown)
                    <option value="{{ $godown->id }}" @selected((string) old('source_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                @endforeach
            </select>
        </x-form.field>
        <x-tally::form.field name="destination_godown_id" label="To godown" required>
            <select id="destination_godown_id" name="destination_godown_id" class="input" required>
                <option value="">Select godown</option>
                @foreach ($godowns as $godown)
                    <option value="{{ $godown->id }}" @selected((string) old('destination_godown_id') === (string) $godown->id)>{{ $godown->name }}</option>
                @endforeach
            </select>
        </x-form.field>
    @endif
    <x-tally::form.field name="barcode_scan" label="Scan barcode">
        <x-tally::form.input name="barcode_scan" data-barcode-scan data-barcode-scope="stock" data-barcode-url="{{ tally_route('books.tally.barcodes.lookup') }}" placeholder="Scan, then Enter" autocomplete="off" />
    </x-form.field>
</div>
@foreach ($errors->getMessages() as $field => $messages)
    @if ($field === 'lines' || $field === 'quantity')
        @foreach ($messages as $message)
            <p class="field-error">{{ $message }}</p>
        @endforeach
    @endif
@endforeach
<div class="table-wrap">
    <table class="data">
        <thead>
            <tr>
                <th>Product</th>
                @if ($type !== \Tally\Inventory\StockTransactionType::Transfer)
                    <th>Godown</th>
                @endif
                <th>Quantity</th>
                <th>Rate</th>
                <th class="money">Value</th>
                <th>Batch</th>
                <th>Mfg date</th>
                <th>Expiry</th>
                <th>Serial</th>
                @if ($type === \Tally\Inventory\StockTransactionType::Adjustment)
                    <th>Direction</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $index => $line)
                <tr>
                    <td>
                        <select class="input" name="lines[{{ $index }}][product_id]">
                            <option value="">Select product</option>
                            @foreach ($products as $product)
                                <option value="{{ $product->id }}" @selected((string) ($line['product_id'] ?? '') === (string) $product->id)>{{ $product->name }}</option>
                            @endforeach
                        </select>
                        @error('lines.'.$index.'.product_id')<p class="field-error">{{ $message }}</p>@enderror
                    </td>
                    @if ($type !== \Tally\Inventory\StockTransactionType::Transfer)
                        <td>
                            <select class="input" name="lines[{{ $index }}][godown_id]">
                                <option value="">Select godown</option>
                                @foreach ($godowns as $godown)
                                    <option value="{{ $godown->id }}" @selected((string) ($line['godown_id'] ?? '') === (string) $godown->id)>{{ $godown->name }}</option>
                                @endforeach
                            </select>
                            @error('lines.'.$index.'.godown_id')<p class="field-error">{{ $message }}</p>@enderror
                        </td>
                    @endif
                    <td>
                        <input class="input" data-qty name="lines[{{ $index }}][quantity]" value="{{ $line['quantity'] ?? '' }}">
                        @error('lines.'.$index.'.quantity')<p class="field-error">{{ $message }}</p>@enderror
                    </td>
                    <td>
                        <input class="input" data-rate name="lines[{{ $index }}][rate]" value="{{ $line['rate'] ?? '' }}">
                        @error('lines.'.$index.'.rate')<p class="field-error">{{ $message }}</p>@enderror
                    </td>
                    <td class="money" data-line-amount>0.00</td>
                    <td><input class="input" name="lines[{{ $index }}][batch_number]" value="{{ $line['batch_number'] ?? '' }}" maxlength="40"></td>
                    <td><input class="input" type="date" name="lines[{{ $index }}][manufactured_on]" value="{{ $line['manufactured_on'] ?? '' }}"></td>
                    <td><input class="input" type="date" name="lines[{{ $index }}][expires_on]" value="{{ $line['expires_on'] ?? '' }}"></td>
                    <td><input class="input" name="lines[{{ $index }}][serial_number]" value="{{ $line['serial_number'] ?? '' }}" maxlength="60"></td>
                    @if ($type === \Tally\Inventory\StockTransactionType::Adjustment)
                        <td>
                            <select class="input" name="lines[{{ $index }}][direction]">
                                <option value="increase" @selected(($line['direction'] ?? 'increase') === 'increase')>Increase</option>
                                <option value="decrease" @selected(($line['direction'] ?? '') === 'decrease')>Decrease</option>
                            </select>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<p class="form-note">Blank rows are ignored. Value is quantity × rate. Stock cannot fall below zero in a godown. Opening quantity on the product is not stored in a godown.</p>
<script>
    (function () {
        const table = document.currentScript.previousElementSibling.previousElementSibling;

        function cents(value) {
            const raw = String(value ?? '').trim();
            if (raw === '' || !/^\d+(\.\d{1,2})?$/.test(raw)) {
                return 0n;
            }
            const parts = raw.split('.');
            return (BigInt(parts[0]) * 100n) + BigInt((parts[1] || '').padEnd(2, '0').slice(0, 2));
        }

        function millis(value) {
            const raw = String(value ?? '').trim();
            if (raw === '' || !/^\d+(\.\d{1,4})?$/.test(raw)) {
                return 0n;
            }
            const parts = raw.split('.');
            return (BigInt(parts[0]) * 10000n) + BigInt((parts[1] || '').padEnd(4, '0').slice(0, 4));
        }

        function paint() {
            table.querySelectorAll('tbody tr').forEach(function (row) {
                const cell = row.querySelector('[data-line-amount]');
                if (!cell) {
                    return;
                }
                const amount = (millis(row.querySelector('input[data-qty]')?.value) * cents(row.querySelector('input[data-rate]')?.value) + 5000n) / 10000n;
                cell.textContent = (amount / 100n).toString() + '.' + (amount % 100n).toString().padStart(2, '0');
            });
        }

        table.closest('form').addEventListener('input', paint);
        paint();
    })();
</script>
