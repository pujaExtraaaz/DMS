<x-tally::layouts.app :title="$company->name">
    <x-tally::shell.page :title="$company->name" section="Settings">
        <x-slot:actions>
            <a class="btn" href="{{ tally_route('books.tally.companies.edit', $company) }}">Edit</a>
            <form method="POST" action="{{ tally_route('books.tally.companies.activation', $company) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="is_active" value="{{ $company->is_active ? '0' : '1' }}">
                <button class="btn" type="submit">{{ $company->is_active ? 'Deactivate' : 'Activate' }}</button>
            </form>
            <form method="POST" action="{{ tally_route('books.tally.companies.destroy', $company) }}" onsubmit="return confirm('Delete this company and its books? This cannot be undone.');">
                @csrf
                @method('DELETE')
                <button class="btn" type="submit">Delete</button>
            </form>
        </x-slot:actions>

        <div class="sheet-grid">
            <section class="panel">
                <h2>Registration</h2>
                <dl class="kv">
                    <dt>Mailing name</dt><dd>{{ $company->mailing_name ?: '—' }}</dd>
                    <dt>Legal name</dt><dd>{{ $company->legal_name ?: '—' }}</dd>
                    <dt>Address</dt><dd>{{ $company->address ?: '—' }}</dd>
                    <dt>City</dt><dd>{{ $company->city ?: '—' }}</dd>
                    <dt>State</dt><dd>{{ $company->state ?: '—' }}</dd>
                    <dt>Country</dt><dd>{{ $company->country }}</dd>
                    <dt>Pincode</dt><dd>{{ $company->pincode ?: '—' }}</dd>
                    <dt>Telephone</dt><dd>{{ $company->phone ?: '—' }}</dd>
                    <dt>Mobile</dt><dd>{{ $company->mobile ?: '—' }}</dd>
                    <dt>Fax</dt><dd>{{ $company->fax ?: '—' }}</dd>
                    <dt>E-mail</dt><dd>{{ $company->email ?: '—' }}</dd>
                    <dt>Website</dt><dd>{{ $company->website ?: '—' }}</dd>
                    <dt>Books beginning from</dt><dd>{{ ($company->books_beginning_from ?? $company->financial_year_start)->format('j-M-y') }}</dd>
                    <dt>Base currency</dt><dd>{{ $company->currency_symbol ?: '₹' }} {{ $company->currency_formal_name ?: 'INR' }}</dd>
                    <dt>GSTIN</dt><dd>{{ $company->gstin ?: '—' }}</dd>
                    <dt>GST registration</dt><dd>{{ $company->gst_registration_type?->label() ?? '—' }}</dd>
                    <dt>Tax pricing</dt><dd>{{ $company->tax_pricing?->label() ?? 'Tax exclusive' }}</dd>
                    <dt>Tax rounding</dt><dd>{{ $company->tax_rounding?->label() ?? 'Nearest paisa' }}</dd>
                    <dt>Negative stock</dt><dd>{{ $company->allow_negative_stock ? 'Allowed' : 'Blocked' }}</dd>
                    <dt>PAN</dt><dd>{{ $company->pan ?: '—' }}</dd>
                    <dt>Books period</dt><dd>{{ $company->financial_year_start->format('d M Y') }} – {{ $company->financial_year_end->format('d M Y') }}</dd>
                    <dt>Status</dt><dd><x-tally::ui.status :active="$company->is_active" /></dd>
                </dl>
            </section>
            <div class="stack">
                <section class="panel">
                    <header class="panel-head">
                        <h2>Branches</h2>
                        <a href="{{ tally_route('books.tally.companies.branches.index', $company) }}">Manage</a>
                    </header>
                    @forelse ($company->branches as $branch)
                        <p><a href="{{ tally_route('books.tally.companies.branches.show', [$company, $branch]) }}">{{ $branch->name }}</a> <span class="muted">{{ $branch->code }}</span></p>
                    @empty
                        <p class="muted">No branches yet. <a href="{{ tally_route('books.tally.companies.branches.create', $company) }}">Add one</a>.</p>
                    @endforelse
                </section>
                <section class="panel">
                    <header class="panel-head">
                        <h2>Financial years</h2>
                        <a href="{{ tally_route('books.tally.companies.financial-years.index', $company) }}">Manage</a>
                    </header>
                    @forelse ($company->financialYears as $year)
                        <p><a href="{{ tally_route('books.tally.companies.financial-years.show', [$company, $year]) }}">{{ $year->name }}</a> <span class="muted">{{ $year->rangeLabel() }}</span></p>
                    @empty
                        <p class="muted">No financial years yet.</p>
                    @endforelse
                </section>
            </div>
        </div>
    </x-shell.page>
</x-layouts.app>
