<x-tally::layouts.app title="New landed cost">
    <x-tally::shell.page title="New landed cost" section="Purchasing" :description="$company->name">
        <form method="POST" class="panel stack" action="{{ tally_route('books.tally.landed-costs.store') }}">
            @csrf
            <div class="form-grid">
                <x-tally::form.field name="voucher_date" label="Date" required>
                    <x-tally::form.input type="date" name="voucher_date" value="{{ old('voucher_date', now()->toDateString()) }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="freight" label="Freight">
                    <x-tally::form.input name="freight" value="{{ old('freight', '0') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="duty" label="Duty">
                    <x-tally::form.input name="duty" value="{{ old('duty', '0') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="other_charges" label="Other charges">
                    <x-tally::form.input name="other_charges" value="{{ old('other_charges', '0') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="allocation_basis" label="Allocation">
                    <select class="input" name="allocation_basis">
                        <option value="value" @selected(old('allocation_basis', 'value') === 'value')>Value</option>
                        <option value="qty" @selected(old('allocation_basis') === 'qty')>Quantity</option>
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="narration" label="Narration" class="span-full">
                    <x-tally::form.input name="narration" value="{{ old('narration') }}" />
                </x-tally::form.field>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.landed-costs.index') }}">Cancel</a>
            </div>
        </form>
    </x-tally::shell.page>
</x-tally::layouts.app>
