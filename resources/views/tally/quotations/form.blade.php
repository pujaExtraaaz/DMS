<x-tally::layouts.app :title="$quotation->exists ? 'Edit quotation' : 'New quotation'">
    <x-tally::shell.page :title="$quotation->exists ? 'Edit quotation' : 'New quotation'" section="Transactions" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ $quotation->exists ? tally_route('books.tally.quotations.update', $quotation) : tally_route('books.tally.quotations.store') }}">
            @csrf
            @if ($quotation->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="quote_date" label="Date" required>
                    <x-tally::form.input type="date" name="quote_date" value="{{ old('quote_date', optional($quotation->quote_date)->toDateString() ?? now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="valid_until" label="Valid until">
                    <x-tally::form.input type="date" name="valid_until" value="{{ old('valid_until', optional($quotation->valid_until)->toDateString()) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="customer_ledger_id" label="Customer" required>
                    <select class="input" name="customer_ledger_id" required>
                        <option value="">Select</option>
                        @foreach ($partyLedgers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('customer_ledger_id', $quotation->customer_ledger_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="reference_number" label="Reference">
                    <x-tally::form.input name="reference_number" value="{{ old('reference_number', $quotation->reference_number) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="status" label="Status">
                    <select class="input" name="status">
                        @foreach (['draft','sent','accepted','expired','cancelled'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $quotation->status ?? 'draft') === $status)>{{ ucfirst($status) }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration', $quotation->narration) }}" />
                </x-tally::form.field>
            </div>
            @php
                $lines = old('lines', $quotation->lines?->map(fn ($l) => [
                    'item_name' => $l->item_name, 'quantity' => $l->quantity, 'rate' => $l->rate,
                    'discount' => $l->discount, 'tax_amount' => $l->tax_amount,
                ])->all() ?? [['item_name'=>'','quantity'=>'1','rate'=>'','discount'=>'0','tax_amount'=>'0']]);
            @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? '1' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][rate]" value="{{ $line['rate'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][discount]" value="{{ $line['discount'] ?? '0' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][tax_amount]" value="{{ $line['tax_amount'] ?? '0' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.quotations.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
