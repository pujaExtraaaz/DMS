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
                </x-tally::form.field>
                <x-tally::form.field name="name" label="Name" required>
                    <x-tally::form.input name="name" value="{{ old('name', $party->ledger?->name ?? '') }}" required />
                </x-tally::form.field>
                <x-tally::form.field name="code" label="Code">
                    <x-tally::form.input name="code" value="{{ old('code', $party->ledger?->code ?? '') }}" maxlength="32" />
                </x-tally::form.field>
                <x-tally::form.field name="legal_name" label="Legal name">
                    <x-tally::form.input name="legal_name" value="{{ old('legal_name', $party->legal_name) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="contact_person" label="Contact person">
                    <x-tally::form.input name="contact_person" value="{{ old('contact_person', $party->contact_person) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="phone" label="Phone">
                    <x-tally::form.input name="phone" value="{{ old('phone', $party->phone) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="mobile" label="Mobile">
                    <x-tally::form.input name="mobile" value="{{ old('mobile', $party->mobile) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="email" label="Email">
                    <x-tally::form.input name="email" type="email" value="{{ old('email', $party->email) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="billing_address" label="Billing address" class="span-2">
                    <x-tally::form.input name="billing_address" value="{{ old('billing_address', $party->billing_address) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="shipping_address" label="Shipping address" class="span-2">
                    <x-tally::form.input name="shipping_address" value="{{ old('shipping_address', $party->shipping_address) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="state" label="State">
                    <x-tally::form.input name="state" value="{{ old('state', $party->state) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="country" label="Country">
                    <x-tally::form.input name="country" value="{{ old('country', $party->country) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="gstin" label="GSTIN">
                    <div class="gstin-fetch">
                        <x-tally::form.input name="gstin" value="{{ old('gstin', $party->gstin) }}" maxlength="15" data-gstin />
                        <button class="btn" type="button" data-gstin-fetch="{{ tally_route('books.tally.gstin.lookup') }}">Fetch</button>
                    </div>
                    <p class="form-note" data-gstin-note></p>
                </x-tally::form.field>
                <x-tally::form.field name="pan" label="PAN">
                    <x-tally::form.input name="pan" value="{{ old('pan', $party->pan) }}" maxlength="10" />
                </x-tally::form.field>
                <x-tally::form.field name="gst_registration_type" label="GST registration">
                    <select id="gst_registration_type" name="gst_registration_type" class="input">
                        <option value="">Not set</option>
                        @foreach ($registrations as $registration)
                            <option value="{{ $registration->value }}" @selected(old('gst_registration_type', $party->gst_registration_type?->value) === $registration->value)>{{ $registration->label() }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="branch_id" label="Branch">
                    <select id="branch_id" name="branch_id" class="input">
                        <option value="">All branches</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((string) old('branch_id', $party->branch_id) === (string) $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="payment_term_id" label="Payment terms">
                    <select id="payment_term_id" name="payment_term_id" class="input">
                        <option value="">Use credit days</option>
                        @foreach (($paymentTerms ?? []) as $term)
                            <option value="{{ $term->id }}" @selected((string) old('payment_term_id', $party->payment_term_id) === (string) $term->id)>{{ $term->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="credit_limit" label="Credit limit">
                    <x-tally::form.input name="credit_limit" value="{{ old('credit_limit', $party->credit_limit) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="credit_days" label="Credit days">
                    <x-tally::form.input name="credit_days" value="{{ old('credit_days', $party->credit_days ?? $company->default_credit_days) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="default_discount_percent" label="Default discount %">
                    <x-tally::form.input name="default_discount_percent" value="{{ old('default_discount_percent', $party->default_discount_percent) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="salesperson_name" label="Salesperson">
                    <x-tally::form.input name="salesperson_name" value="{{ old('salesperson_name', $party->salesperson_name) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="commission_percent" label="Commission %">
                    <x-tally::form.input name="commission_percent" value="{{ old('commission_percent', $party->commission_percent) }}" />
                </x-tally::form.field>
                <x-tally::form.field name="opening_balance" label="Opening balance">
                    <x-tally::form.input name="opening_balance" value="{{ old('opening_balance', $party->ledger?->opening_balance ?? '0.00') }}" />
                </x-tally::form.field>
                <x-tally::form.field name="opening_balance_type" label="Opening side" required>
                    <select id="opening_balance_type" name="opening_balance_type" class="input" required>
                        @foreach ($balanceTypes as $balance)
                            <option value="{{ $balance->value }}" @selected(old('opening_balance_type', $party->ledger?->opening_balance_type?->value ?? 'debit') === $balance->value)>{{ $balance->label() }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <label class="check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active', $party->is_active ?? true))> Active</label>
            </div>
            <div class="form-actions">
                <button class="btn btn-primary" type="submit">Save</button>
                <a class="btn" href="{{ tally_route('books.tally.parties.index') }}">Cancel</a>
            </div>
        </form>

        @if ($party->exists)
            <h3 class="text-sm font-semibold text-slate-700 mt-4">Billing / shipping addresses</h3>
            <form method="POST" action="{{ tally_route('books.tally.parties.addresses.store', $party) }}" class="form-grid panel" style="margin-top:0.5rem">
                @csrf
                <x-tally::form.field name="label" label="Label"><x-tally::form.input name="label" /></x-tally::form.field>
                <x-tally::form.field name="kind" label="Kind">
                    <select class="input" name="kind">
                        <option value="billing">Billing</option>
                        <option value="shipping">Shipping</option>
                        <option value="both">Both</option>
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="line1" label="Address line 1" required><x-tally::form.input name="line1" required /></x-tally::form.field>
                <x-tally::form.field name="city" label="City"><x-tally::form.input name="city" /></x-tally::form.field>
                <x-tally::form.field name="state" label="State"><x-tally::form.input name="state" value="{{ $party->state }}" /></x-tally::form.field>
                <x-tally::form.field name="pincode" label="Pincode"><x-tally::form.input name="pincode" /></x-tally::form.field>
                <label class="check"><input type="checkbox" name="is_default_billing" value="1"> Default billing</label>
                <label class="check"><input type="checkbox" name="is_default_shipping" value="1"> Default shipping</label>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Add address</button></div>
            </form>
            <div class="table-wrap" style="margin-top:0.75rem">
                <table class="data">
                    <thead><tr><th>Label</th><th>Kind</th><th>Address</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($party->addresses as $address)
                            <tr>
                                <td>{{ $address->label ?: '—' }}</td>
                                <td>{{ $address->kind }}</td>
                                <td>{{ $address->singleLine() }}</td>
                                <td>
                                    <form method="POST" action="{{ tally_route('books.tally.parties.addresses.destroy', [$party, $address]) }}">
                                        @csrf @method('DELETE')
                                        <button class="btn" type="submit">Remove</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4">No extra addresses yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <h3 class="text-sm font-semibold text-slate-700 mt-4">Customer price lists</h3>
            <form method="POST" action="{{ tally_route('books.tally.parties.price-lists.store', $party) }}" class="form-grid panel" style="margin-top:0.5rem">
                @csrf
                <x-tally::form.field name="price_list_id" label="Price list" required>
                    <select class="input" name="price_list_id" required>
                        <option value="">Select</option>
                        @foreach (($priceLists ?? []) as $list)
                            <option value="{{ $list->id }}">{{ $list->name }}</option>
                        @endforeach
                    </select>
                </x-tally::form.field>
                <x-tally::form.field name="valid_from" label="Valid from"><x-tally::form.input name="valid_from" type="date" /></x-tally::form.field>
                <x-tally::form.field name="valid_to" label="Valid to"><x-tally::form.input name="valid_to" type="date" /></x-tally::form.field>
                <div class="form-actions"><button class="btn btn-primary" type="submit">Assign price list</button></div>
            </form>
            <div class="table-wrap" style="margin-top:0.75rem">
                <table class="data">
                    <thead><tr><th>Price List</th><th>From</th><th>To</th></tr></thead>
                    <tbody>
                        @forelse ($party->priceLists as $assignment)
                            <tr>
                                <td>{{ $assignment->priceList?->name }}</td>
                                <td>{{ $assignment->valid_from?->format('d M Y') ?: '—' }}</td>
                                <td>{{ $assignment->valid_to?->format('d M Y') ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3">No party price lists yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-tally::shell.page>
</x-tally::layouts.app>
