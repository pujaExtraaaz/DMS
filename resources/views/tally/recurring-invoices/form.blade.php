<x-tally::layouts.app :title="$item->exists ? 'Edit recurring invoice' : 'New recurring invoice'">
    <x-tally::shell.page :title="$item->exists ? 'Edit recurring invoice' : 'New recurring invoice'" section="Transactions" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ $item->exists ? tally_route('books.tally.recurring-invoices.update', $item) : tally_route('books.tally.recurring-invoices.store') }}">
            @csrf
            @if ($item->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="name" label="Name" required><x-tally::form.input name="name" value="{{ old('name', $item->name) }}" required /></x-tally::form.field>
                <x-tally::form.field name="branch_id" label="Branch" required>
                    <select class="input" name="branch_id" required>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $item->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="customer_ledger_id" label="Customer" required>
                    <select class="input" name="customer_ledger_id" required>
                        @foreach ($partyLedgers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('customer_ledger_id', $item->customer_ledger_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="account_ledger_id" label="Sales ledger" required>
                    <select class="input" name="account_ledger_id" required>
                        @foreach ($accountLedgers as $id => $name)
                            <option value="{{ $id }}" @selected((string) old('account_ledger_id', $item->account_ledger_id) === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="frequency" label="Frequency">
                    <select class="input" name="frequency">
                        @foreach (['weekly','monthly','quarterly','yearly'] as $freq)
                            <option value="{{ $freq }}" @selected(old('frequency', $item->frequency) === $freq)>{{ ucfirst($freq) }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="next_run_on" label="Next run"><x-tally::form.input type="date" name="next_run_on" value="{{ old('next_run_on', optional($item->next_run_on)->toDateString() ?? now()->toDateString()) }}" /></x-tally::form.field>
                <x-tally::form.field name="invoice_category" label="Category">
                    <select class="input" name="invoice_category">
                        <option value="tax_invoice" @selected(old('invoice_category', $item->invoice_category) === 'tax_invoice')>Tax Invoice</option>
                        <option value="bill_of_supply" @selected(old('invoice_category', $item->invoice_category) === 'bill_of_supply')>Bill Of Supply</option>
                    </select>
                </x-tally::form.field>
                <label class="check"><input type="checkbox" name="auto_post" value="1" @checked(old('auto_post', $item->auto_post))> Auto-post</label>
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true))> Active</label>
            </div>
            @php $lines = old('lines', $item->lines?->map(fn ($l) => ['item_name'=>$l->item_name,'quantity'=>$l->quantity,'rate'=>$l->rate,'discount'=>$l->discount,'tax_rate'=>$l->tax_rate])->all() ?? [['item_name'=>'','quantity'=>'1','rate'=>'','discount'=>'0','tax_rate'=>'18']]); @endphp
            <div class="table-wrap">
                <table class="data">
                    <thead><tr><th>Item</th><th>Qty</th><th>Rate</th><th>Discount</th><th>Tax %</th></tr></thead>
                    <tbody>
                        @foreach ($lines as $i => $line)
                            <tr>
                                <td><input class="input" name="lines[{{ $i }}][item_name]" value="{{ $line['item_name'] ?? '' }}" required></td>
                                <td><input class="input" name="lines[{{ $i }}][quantity]" value="{{ $line['quantity'] ?? '1' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][rate]" value="{{ $line['rate'] ?? '' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][discount]" value="{{ $line['discount'] ?? '0' }}"></td>
                                <td><input class="input" name="lines[{{ $i }}][tax_rate]" value="{{ $line['tax_rate'] ?? '0' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.recurring-invoices.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
