<x-tally::layouts.app :title="$party->ledger->name">
    <x-tally::shell.page :title="$party->ledger->name" section="Masters" :description="ucfirst($party->type).' · '.$company->name">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.reports.ledger', ['ledger_id' => $party->ledger_id]) }}">Ledger</a>
            <a class="btn" href="{{ tally_route('books.tally.reports.outstanding', ['ledger_id' => $party->ledger_id]) }}">Outstanding</a>
            <a class="btn" href="{{ tally_route('books.tally.parties.edit', $party) }}">Edit</a>
        </x-slot:actions>
        <dl class="kv">
            <dt>Code</dt><dd>{{ $party->ledger->code ?: '—' }}</dd>
            <dt>Legal name</dt><dd>{{ $party->legal_name ?: '—' }}</dd>
            <dt>Contact</dt><dd>{{ $party->contact_person ?: '—' }}</dd>
            <dt>Phone</dt><dd>{{ $party->phone ?: '—' }}</dd>
            <dt>Mobile</dt><dd>{{ $party->mobile ?: '—' }}</dd>
            <dt>Email</dt><dd>{{ $party->email ?: '—' }}</dd>
            <dt>Billing</dt><dd>{{ $party->billing_address ?: '—' }}</dd>
            <dt>Shipping</dt><dd>{{ $party->shipping_address ?: '—' }}</dd>
            <dt>State</dt><dd>{{ $party->state ?: '—' }} {{ $party->country }}</dd>
            <dt>GSTIN</dt><dd>{{ $party->gstin ?: '—' }}</dd>
            <dt>PAN</dt><dd>{{ $party->pan ?: '—' }}</dd>
            <dt>GST type</dt><dd>{{ $party->gst_registration_type?->label() ?: '—' }}</dd>
            <dt>Credit</dt><dd>{{ $party->credit_limit ?: '—' }} / {{ $party->credit_days ?? '—' }} days</dd>
            <dt>Opening</dt><dd>{{ $party->ledger->openingBalanceLabel() }}</dd>
            <dt>Branch</dt><dd>{{ $party->branch?->name ?: 'All branches' }}</dd>
            <dt>Status</dt><dd>{{ $party->is_active ? 'Active' : 'Inactive' }}</dd>
        </dl>
        <p class="form-note">Balances, bills, and GST use the linked ledger {{ $party->ledger->name }}. An inactive party stays on old invoices and drops out of new selections.</p>
    </x-shell.page>
</x-layouts.app>
