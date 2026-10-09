<x-tally::layouts.app :title="$order ? 'Alter sales order' : 'Sales order'">
    <x-tally::shell.page :title="$order ? 'Alter '.$order->number : 'Sales order'" section="Transactions" :description="$company->name">
        <form method="POST" action="{{ $order ? tally_route('books.tally.sales-orders.update', $order) : tally_route('books.tally.sales-orders.store') }}" class="tally-voucher tally-vch">
            @csrf
            @if ($order) @method('PUT') @endif
            <header class="tally-vch-bar">
                <div class="tally-vch-kicker">Order Voucher Creation</div>
                <div class="tally-vch-company">{{ $company->name }}</div>
            </header>
            <div class="tally-vch-id">
                <div class="tally-vch-badge">Sales Order <span>No. {{ $order?->number ?? 'New' }}</span></div>
            </div>
            <div class="form-grid">
                <x-tally::form.field name="order_date" label="Date" required>
                    <x-tally::form.input name="order_date" type="date" value="{{ old('order_date', optional($order?->order_date)->toDateString() ?? $year->start_date->toDateString()) }}" required />
                </x-form.field>
                <x-tally::form.field name="customer_ledger_id" label="Party A/c name" required>
                    @php $selected = $customers->firstWhere('id', old('customer_ledger_id', $order?->customer_ledger_id)); @endphp
                    <div class="picker" data-picker data-create-url="{{ tally_route('books.tally.ledgers.create') }}">
                        <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Customer" value="{{ $selected->name ?? '' }}" required>
                        <select class="picker-store" id="customer_ledger_id" name="customer_ledger_id" tabindex="-1" aria-hidden="true">
                            <option value="">Select customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}" @selected((string) old('customer_ledger_id', $order?->customer_ledger_id) === (string) $customer->id)>{{ $customer->name }}</option>
                            @endforeach
                        </select>
                        <div class="picker-list" hidden>
                            @foreach ($customers as $customer)
                                <button type="button" data-picker-choice data-value="{{ $customer->id }}">{{ $customer->name }}</button>
                            @endforeach
                        </div>
                    </div>
                </x-form.field>
                <x-tally::form.field name="reference_number" label="Reference">
                    <x-tally::form.input name="reference_number" value="{{ old('reference_number', $order?->reference_number) }}" />
                </x-form.field>
                <x-tally::form.field name="narration" label="Narration">
                    <x-tally::form.input name="narration" value="{{ old('narration', $order?->narration) }}" />
                </x-form.field>
            </div>
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Name of Item</th><th>Product</th><th>Tax</th><th class="money">Quantity</th><th class="money">Rate</th><th class="money">Disc</th><th class="money">Amount</th></tr></thead>
                    <tbody>
                        @for ($i = 0; $i < 4; $i++)
                            @php $line = $order?->lines[$i] ?? null; @endphp
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ old('lines.'.$i.'.item_name', $line?->item_name) }}"></td>
                                <td>
                                    @php $selectedProduct = $products->firstWhere('id', old('lines.'.$i.'.product_id', $line?->product_id)); @endphp
                                    <div class="picker" data-picker>
                                        <input class="input picker-query" data-picker-query autocomplete="off" placeholder="Item" value="{{ $selectedProduct->name ?? '' }}">
                                        <select class="picker-store" name="lines[{{ $i }}][product_id]" tabindex="-1" aria-hidden="true">
                                            <option value="">—</option>
                                            @foreach ($products as $product)
                                                <option value="{{ $product->id }}" @selected((string) old('lines.'.$i.'.product_id', $line?->product_id) === (string) $product->id)>{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                        <div class="picker-list" hidden>
                                            @foreach ($products as $product)
                                                <button type="button" data-picker-choice data-value="{{ $product->id }}">{{ $product->name }}</button>
                                            @endforeach
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <select class="input" name="lines[{{ $i }}][tax_rate_id]">
                                        <option value="">—</option>
                                        @foreach ($taxRates as $rate)
                                            <option value="{{ $rate->id }}" @selected((string) old('lines.'.$i.'.tax_rate_id', $line?->tax_rate_id) === (string) $rate->id)>{{ $rate->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td><input class="input money" data-qty name="lines[{{ $i }}][quantity]" value="{{ old('lines.'.$i.'.quantity', $line?->quantity) }}"></td>
                                <td><input class="input money" data-rate name="lines[{{ $i }}][rate]" value="{{ old('lines.'.$i.'.rate', $line?->rate) }}"></td>
                                <td><input class="input money" data-discount name="lines[{{ $i }}][discount]" value="{{ old('lines.'.$i.'.discount', $line?->discount) }}"></td>
                                <td class="money" data-line-amount>0.00</td>
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
            @if ($errors->any())
                <p class="flash is-error" role="alert">{{ $errors->first() }}</p>
            @endif
            <p class="form-note">Saving an order does not issue stock or post a sales voucher.</p>
            <button class="btn btn-primary" type="submit">Accept</button>
        </form>
        <script>
            (function () {
                const form = document.querySelector('form.tally-voucher');

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

                function money(amount) {
                    const negative = amount < 0n;
                    const abs = negative ? -amount : amount;
                    return (negative ? '-' : '') + (abs / 100n).toString() + '.' + (abs % 100n).toString().padStart(2, '0');
                }

                function paint() {
                    form.querySelectorAll('tbody tr').forEach(function (row) {
                        const cell = row.querySelector('[data-line-amount]');
                        if (!cell) {
                            return;
                        }
                        const gross = (millis(row.querySelector('input[data-qty]')?.value) * cents(row.querySelector('input[data-rate]')?.value) + 5000n) / 10000n;
                        const discount = cents(row.querySelector('input[data-discount]')?.value);
                        cell.textContent = money(gross - discount);
                    });
                }

                form.addEventListener('input', paint);
                paint();
            })();
        </script>
    </x-shell.page>
</x-layouts.app>
