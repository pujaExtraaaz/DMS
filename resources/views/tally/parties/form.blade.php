<x-tally::layouts.app :title="$party->exists ? 'Edit party' : 'New party'">
    <x-tally::shell.page :title="$party->exists ? 'Edit party' : 'New party'" section="Masters" :description="$company->name">
        <form class="panel" method="POST" action="{{ $party->exists ? tally_route('books.tally.parties.update', $party) : tally_route('books.tally.parties.store') }}">
            @csrf
            @if ($party->exists) @method('PUT') @endif
            <div class="form-grid">
                <x-tally::form.field name="type" label="Type" required>
                    <select id="type" name="type" class="input" required>
                        @foreach ($types as $value => $label)
                            <option value="{{ $value }}" @selected(old('type', $party->type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name', $party->ledger?->name ?? '') }}" required />
                </x-form.field>
                <x-tally::form.field name="code" label="Code">
                    <x-tally::form.input name="code" value="{{ old('code', $party->ledger?->code ?? '') }}" maxlength="32" />
                </x-form.field>
                <x-tally::form.field name="legal_name" label="Legal name">
                    <x-tally::form.input name="legal_name" value="{{ old('legal_name', $party->legal_name) }}" />
                </x-form.field>
                <x-tally::form.field name="contact_person" label="Contact person">
                    <x-tally::form.input name="contact_person" value="{{ old('contact_person', $party->contact_person) }}" />
                </x-form.field>
                <x-tally::form.field name="phone" label="Phone">
                    <x-tally::form.input name="phone" value="{{ old('phone', $party->phone) }}" />
                </x-form.field>
                <x-tally::form.field name="mobile" label="Mobile">
                    <x-tally::form.input name="mobile" value="{{ old('mobile', $party->mobile) }}" />
                </x-form.field>
                <x-tally::form.field name="email" label="Email">
                    <x-tally::form.input name="email" type="email" value="{{ old('email', $party->email) }}" />
                </x-form.field>
                <x-tally::form.field name="billing_address" label="Billing address" class="span-2">
                    <x-tally::form.input name="billing_address" value="{{ old('billing_address', $party->billing_address) }}" />
                </x-form.field>
                <x-tally::form.field name="shipping_address" label="Shipping address" class="span-2">
                    <x-tally::form.input name="shipping_address" value="{{ old('shipping_address', $party->shipping_address) }}" />
                </x-form.field>
                <x-tally::form.field name="state" label="State">
                    <x-tally::form.input name="state" value="{{ old('state', $party->state) }}" />
                </x-form.field>
                <x-tally::form.field name="country" label="Country">
                    <x-tally::form.input name="country" value="{{ old('country', $party->country) }}" />
                </x-form.field>
                <x-tally::form.field name="gstin" label="GSTIN">
                    <div class="gstin-fetch">
                        <x-tally::form.input name="gstin" value="{{ old('gstin', $party->gstin) }}" maxlength="15" data-gstin />
                        <button class="btn" type="button" data-gstin-fetch="{{ tally_route('books.tally.gstin.lookup') }}">Fetch</button>
                    </div>
                    <p class="form-note" data-gstin-note></p>
                </x-form.field>
                <x-tally::form.field name="pan" label="PAN">
                    <x-tally::form.input name="pan" value="{{ old('pan', $party->pan) }}" maxlength="10" />
                </x-form.field>
                <x-tally::form.field name="gst_registration_type" label="GST registration">
                    <select id="gst_registration_type" name="gst_registration_type" class="input">
                        <option value="">Not set</option>
                        @foreach ($registrations as $registration)
                            <option value="{{ $registration->value }}" @selected(old('gst_registration_type', $party->gst_registration_type?->value) === $registration->value)>{{ $registration->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="branch_id" label="Branch">
                    <select id="branch_id" name="branch_id" class="input">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $party->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <x-tally::form.field name="credit_limit" label="Credit limit">
                    <x-tally::form.input name="credit_limit" value="{{ old('credit_limit', $party->credit_limit) }}" />
                </x-form.field>
                <x-tally::form.field name="credit_days" label="Credit days">
                    <x-tally::form.input name="credit_days" value="{{ old('credit_days', $party->credit_days) }}" />
                </x-form.field>
                <x-tally::form.field name="opening_balance" label="Opening balance">
                    <x-tally::form.input name="opening_balance" value="{{ old('opening_balance', $party->ledger?->opening_balance ?? '0.00') }}" />
                </x-form.field>
                <x-tally::form.field name="opening_balance_type" label="Opening side" required>
                    <select id="opening_balance_type" name="opening_balance_type" class="input" required>
                        @foreach ($balanceTypes as $balance)
                            <option value="{{ $balance->value }}" @selected(old('opening_balance_type', $party->ledger?->opening_balance_type?->value ?? 'debit') === $balance->value)>{{ $balance->label() }}</option>
                        @endforeach
                    </select>
                </x-form.field>
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $party->is_active ?? true))> Active</label>
            </div>
            <p class="form-note">Opening balance is stored on the linked ledger. Party screens do not keep a second balance.</p>
            <button class="btn btn-primary" type="submit">Save</button>
            <a class="btn" href="{{ tally_route('books.tally.parties.index') }}">Cancel</a>
        </form>
    </x-shell.page>
</x-layouts.app>
