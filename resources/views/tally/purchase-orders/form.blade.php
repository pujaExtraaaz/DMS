<x-tally::layouts.app title="Purchase order">
    <form method="POST" action="{{ tally_route('books.tally.purchase-orders.store') }}" class="tally-voucher tally-vch" id="order-form">
        @csrf
        <header class="tally-vch-bar">
            <div class="tally-vch-kicker">Order Voucher Creation</div>
            <div class="tally-vch-company">{{ $company->name }}</div>
            <label class="tally-vch-date">
                <input class="input" name="order_date" type="date" value="{{ old('order_date', $year->start_date->toDateString()) }}" required>
            </label>
        </header>
        <div class="tally-vch-id">
            <div class="tally-vch-badge">Purchase Order <span>No. {{ $nextNumber }}</span></div>
            <label>Order no.
                <input class="input" name="reference_number" value="{{ old('reference_number', '1') }}" maxlength="50">
            </label>
        </div>

        <div class="form-grid">
            <x-tally::form.field name="supplier_ledger_id" label="Party A/c name" required>
                @include('tally::masters._picker', [
                    'name' => 'supplier_ledger_id',
                    'options' => $suppliers->mapWithKeys(fn ($supplier) => [$supplier->id => $supplier->name])->all(),
                    'current' => old('supplier_ledger_id'),
                    'meta' => $suppliers->mapWithKeys(fn ($supplier) => [$supplier->id => ['balance' => $balances[$supplier->id] ?? '']])->all(),
                    'placeholder' => 'Party',
                    'create' => tally_route('books.tally.ledgers.create'),
                ])
            </x-form.field>
            <p class="current-balance">Current balance <strong data-party-balance></strong></p>
            <x-tally::form.field name="purchase_ledger_id" label="Purchase ledger">
                @include('tally::masters._picker', [
                    'name' => 'purchase_ledger_id',
                    'options' => $purchaseLedgers->mapWithKeys(fn ($ledger) => [$ledger->id => $ledger->name])->all(),
                    'current' => old('purchase_ledger_id'),
                    'placeholder' => 'Purchase ledger',
                    'optional' => true,
                    'create' => tally_route('books.tally.ledgers.create'),
                ])
            </x-form.field>
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th>Name of Item</th>
                    <th class="money">Quantity</th>
                    <th class="money">Rate</th>
                    <th>per</th>
                    <th class="money">Disc %</th>
                    <th class="money">Amount</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 0; $i < 4; $i++)
                    <tr data-order-line>
                        <td>
                            <input class="input" name="lines[{{ $i }}][item_name]" value="{{ old('lines.'.$i.'.item_name') }}" placeholder="Item">
                            <div class="picker" data-picker>
                                @php $selected = $products->firstWhere('id', old('lines.'.$i.'.product_id')); @endphp
                                <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Item" value="{{ $selected->name ?? '' }}">
                                <select class="picker-store" name="lines[{{ $i }}][product_id]" tabindex="-1" aria-hidden="true">
                                    <option value="">—</option>
                                    @foreach ($products as $product)
                                        <option value="{{ $product->id }}" data-per="{{ $product->primaryUnit?->symbol ?: $product->primaryUnit?->name }}" @selected((string) old('lines.'.$i.'.product_id') === (string) $product->id)>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <div class="picker-list" hidden>
                                    @foreach ($products as $product)
                                        <button type="button" data-picker-choice data-value="{{ $product->id }}">{{ $product->name }}</button>
                                    @endforeach
                                </div>
                            </div>
                            <select class="input" name="lines[{{ $i }}][tax_rate_id]">
                                <option value="">Tax</option>
                                @foreach ($taxRates as $rate)
                                    <option value="{{ $rate->id }}" data-cgst="{{ $rate->cgst_rate }}" data-sgst="{{ $rate->sgst_rate }}" data-igst="{{ $rate->igst_rate }}" @selected((string) old('lines.'.$i.'.tax_rate_id') === (string) $rate->id)>{{ $rate->name }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td><input class="input money" data-qty name="lines[{{ $i }}][quantity]" value="{{ old('lines.'.$i.'.quantity') }}"></td>
                        <td><input class="input money" data-rate name="lines[{{ $i }}][rate]" value="{{ old('lines.'.$i.'.rate') }}"></td>
                        <td data-per>{{ $selected?->primaryUnit?->symbol ?: $selected?->primaryUnit?->name }}</td>
                        <td><input class="input money" data-disc name="lines[{{ $i }}][discount_percent]" value="{{ old('lines.'.$i.'.discount_percent') }}"></td>
                        <td class="money" data-line-amount>0.00</td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="tally-vch-close">
            <div>
                <label>Delivery Charges on Purchase
                    <input class="input money" name="delivery_charge" data-delivery value="{{ old('delivery_charge', '0.00') }}">
                </label>
                <p>CGST <strong data-cgst>0.00</strong></p>
                <p>SGST <strong data-sgst>0.00</strong></p>
                <p>IGST <strong data-igst>0.00</strong></p>
            </div>
            <label class="tally-narration">Narration
                <textarea class="tally-narration-box" name="narration">{{ old('narration') }}</textarea>
            </label>
            <strong data-order-total>0.00</strong>
        </div>
        @if ($errors->any())
            <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
        @endif
        <p class="form-note">This order stores the party, purchase ledger, items, discount percent, delivery charge, and GST from the selected tax rate. It does not receive stock or post a voucher.</p>
        <div class="tally-vch-actions">
            <a class="btn" data-esc href="{{ tally_route('books.tally.purchase-orders.index') }}">Q: Quit</a>
            <button class="btn btn-primary" type="submit">A: Accept</button>
        </div>
    </form>
    <script>
        (function () {
            const form = document.getElementById('order-form');
            const balances = @json($balances);

            function money(value) {
                const number = Number(value);
                return Number.isFinite(number) ? number : 0;
            }

            function paint() {
                let items = 0;
                let cgst = 0;
                let sgst = 0;
                let igst = 0;
                form.querySelectorAll('[data-order-line]').forEach(function (row) {
                    const qty = money(row.querySelector('input[data-qty]')?.value);
                    const rate = money(row.querySelector('input[data-rate]')?.value);
                    const disc = money(row.querySelector('input[data-disc]')?.value);
                    const gross = qty * rate;
                    const amount = Math.max(0, gross - (gross * disc / 100));
                    const tax = row.querySelector('[name$="[tax_rate_id]"]')?.selectedOptions?.[0];
                    row.querySelector('[data-line-amount]').textContent = amount.toFixed(2);
                    row.querySelector('[data-per]').textContent = row.querySelector('.picker-store')?.selectedOptions?.[0]?.dataset?.per || '';
                    items += amount;
                    cgst += amount * money(tax?.dataset?.cgst) / 100;
                    sgst += amount * money(tax?.dataset?.sgst) / 100;
                    igst += amount * money(tax?.dataset?.igst) / 100;
                });
                const delivery = money(form.querySelector('[data-delivery]')?.value);
                form.querySelector('[data-cgst]').textContent = cgst.toFixed(2);
                form.querySelector('[data-sgst]').textContent = sgst.toFixed(2);
                form.querySelector('[data-igst]').textContent = igst.toFixed(2);
                form.querySelector('[data-order-total]').textContent = (items + delivery + cgst + sgst + igst).toFixed(2);
                const party = form.querySelector('[name="supplier_ledger_id"]');
                const label = form.querySelector('[data-party-balance]');
                if (party && label) {
                    label.textContent = balances[party.value] || '';
                }
            }

            form.addEventListener('input', paint);
            form.addEventListener('change', paint);
            paint();
        })();
    </script>
</x-layouts.app>
