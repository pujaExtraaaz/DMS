<x-tally::layouts.app title="TDS compliance">
    <x-tally::shell.page title="TDS compliance" section="Tax" :description="$company->name">
        <form method="POST" action="{{ tally_route('books.tally.tax.tds-compliance.challans.store') }}" class="form-grid panel">
            @csrf
            <h3>TDS challan</h3>
            <x-tally::form.field name="return_period" label="Period" required><x-tally::form.input name="return_period" value="{{ old('return_period', now()->format('Y-m')) }}" required /></x-tally::form.field>
            <x-tally::form.field name="section" label="Section"><x-tally::form.input name="section" value="{{ old('section') }}" /></x-tally::form.field>
            <x-tally::form.field name="challan_number" label="Challan no."><x-tally::form.input name="challan_number" value="{{ old('challan_number') }}" /></x-tally::form.field>
            <x-tally::form.field name="bsr_code" label="BSR"><x-tally::form.input name="bsr_code" value="{{ old('bsr_code') }}" /></x-tally::form.field>
            <x-tally::form.field name="deposit_date" label="Deposit date" required><x-tally::form.input type="date" name="deposit_date" value="{{ old('deposit_date', now()->toDateString()) }}" required /></x-tally::form.field>
            <x-tally::form.field name="amount" label="Amount" required><x-tally::form.input name="amount" value="{{ old('amount') }}" required /></x-tally::form.field>
            <x-tally::form.field name="interest" label="Interest"><x-tally::form.input name="interest" value="{{ old('interest', '0') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save challan</button></div>
        </form>

        <form method="POST" action="{{ tally_route('books.tally.tax.tds-compliance.certificates.store') }}" class="form-grid panel">
            @csrf
            <h3>TDS certificate</h3>
            <x-tally::form.field name="party_ledger_id" label="Party" required>
                <select class="input" name="party_ledger_id" required>
                    @foreach ($ledgers as $ledger)
                        <option value="{{ $ledger->id }}">{{ $ledger->name }}</option>
                    @endforeach
                </select>
            </x-tally::form.field>
            <x-tally::form.field name="certificate_number" label="Certificate no." required><x-tally::form.input name="certificate_number" value="{{ old('certificate_number') }}" required /></x-tally::form.field>
            <x-tally::form.field name="form_type" label="Form"><x-tally::form.input name="form_type" value="{{ old('form_type', '16A') }}" /></x-tally::form.field>
            <x-tally::form.field name="financial_year" label="FY" required><x-tally::form.input name="financial_year" value="{{ old('financial_year', '2025-26') }}" required /></x-tally::form.field>
            <x-tally::form.field name="quarter" label="Quarter"><x-tally::form.input name="quarter" value="{{ old('quarter') }}" /></x-tally::form.field>
            <x-tally::form.field name="tds_amount" label="TDS amount" required><x-tally::form.input name="tds_amount" value="{{ old('tds_amount') }}" required /></x-tally::form.field>
            <x-tally::form.field name="issue_date" label="Issue date"><x-tally::form.input type="date" name="issue_date" value="{{ old('issue_date') }}" /></x-tally::form.field>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save certificate</button></div>
        </form>

        <div class="table-wrap">
            <h3>Challans</h3>
            <table class="data">
                <thead><tr><th>Period</th><th>Section</th><th>Challan</th><th>Date</th><th class="money">Amount</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($challans as $challan)
                        <tr>
                            <td>{{ $challan->return_period }}</td>
                            <td>{{ $challan->section }}</td>
                            <td>{{ $challan->challan_number }}</td>
                            <td>{{ $challan->deposit_date?->format('d M Y') }}</td>
                            <td class="money">{{ $challan->amount }}</td>
                            <td>{{ $challan->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No TDS challans.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $challans->links() }}
        </div>

        <div class="table-wrap">
            <h3>Certificates</h3>
            <table class="data">
                <thead><tr><th>Number</th><th>Party</th><th>Form</th><th>FY</th><th class="money">TDS</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse ($certificates as $certificate)
                        <tr>
                            <td>{{ $certificate->certificate_number }}</td>
                            <td>{{ $certificate->partyLedger?->name }}</td>
                            <td>{{ $certificate->form_type }}</td>
                            <td>{{ $certificate->financial_year }} {{ $certificate->quarter }}</td>
                            <td class="money">{{ $certificate->tds_amount }}</td>
                            <td>{{ $certificate->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6">No TDS certificates.</td></tr>
                    @endforelse
                </tbody>
            </table>
            {{ $certificates->links() }}
        </div>
    </x-tally::shell.page>
</x-tally::layouts.app>
