<x-tally::layouts.app title="Company Features">
    <x-tally::shell.page title="Company Features" section="Company" :description="$company->name">
        @if ($section === '')
            <div class="gateway">
                <nav class="gateway-menu" data-key-menu aria-label="Company Features">
                    <h2>Company Features</h2>
                    <a class="is-current" href="{{ tally_route('books.tally.companies.features', ['section' => 'accounting']) }}">Accounting Features</a>
                    <a href="{{ tally_route('books.tally.companies.features', ['section' => 'inventory']) }}">Inventory Features</a>
                    <a href="{{ tally_route('books.tally.companies.features', ['section' => 'statutory']) }}">Statutory &amp; Taxation</a>
                    <a href="{{ tally_route('books.tally.companies.features', ['section' => 'payroll']) }}">Payroll Features</a>
                    <a href="{{ tally_route('books.tally.dashboard') }}">Quit</a>
                </nav>
            </div>
        @else
            <form class="tally-master" id="features-form" method="POST" action="{{ tally_route('books.tally.companies.features.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="section" value="{{ $section }}">
                @if ($section === 'accounting')
                    <p class="gateway-group">Accounting Features</p>
                    @include('tally::companies._yesno', ['name' => 'sales_enforce_credit_limit', 'label' => 'Enforce credit limit', 'value' => $creditLimit, 'options' => ['No', 'Yes'], 'values' => ['0', '1'], 'autofocus' => true])
                    @include('tally::companies._yesno', ['name' => 'print_amount_in_words', 'label' => 'Print amount in words', 'value' => $amountInWords, 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    @include('tally::companies._yesno', [
                        'name' => 'accounting_basis',
                        'label' => 'Reporting basis',
                        'value' => $company->accounting_basis ?? 'accrual',
                        'options' => ['Accrual', 'Cash'],
                        'values' => ['accrual', 'cash'],
                    ])
                    <x-tally::form.field name="retained_earnings_ledger_id" label="Retained earnings ledger id">
                        <input class="input" name="retained_earnings_ledger_id" value="{{ old('retained_earnings_ledger_id', $company->retained_earnings_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="customer_advance_ledger_id" label="Customer advance ledger id">
                        <input class="input" name="customer_advance_ledger_id" value="{{ old('customer_advance_ledger_id', $company->customer_advance_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="bad_debt_ledger_id" label="Bad debt ledger id">
                        <input class="input" name="bad_debt_ledger_id" value="{{ old('bad_debt_ledger_id', $company->bad_debt_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="doubtful_debt_ledger_id" label="Doubtful debt provision ledger id">
                        <input class="input" name="doubtful_debt_ledger_id" value="{{ old('doubtful_debt_ledger_id', $company->doubtful_debt_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="retention_ledger_id" label="Retention ledger id">
                        <input class="input" name="retention_ledger_id" value="{{ old('retention_ledger_id', $company->retention_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="commission_expense_ledger_id" label="Commission expense ledger id">
                        <input class="input" name="commission_expense_ledger_id" value="{{ old('commission_expense_ledger_id', $company->commission_expense_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="commission_payable_ledger_id" label="Commission payable ledger id">
                        <input class="input" name="commission_payable_ledger_id" value="{{ old('commission_payable_ledger_id', $company->commission_payable_ledger_id) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="collection_reminder_days_before" label="Reminder days before due">
                        <input class="input" name="collection_reminder_days_before" value="{{ old('collection_reminder_days_before', $company->collection_reminder_days_before) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="collection_reminder_days_after" label="Reminder days after due">
                        <input class="input" name="collection_reminder_days_after" value="{{ old('collection_reminder_days_after', $company->collection_reminder_days_after) }}" inputmode="numeric">
                    </x-tally::form.field>
                @elseif ($section === 'inventory')
                    <p class="gateway-group">Inventory Features</p>
                    @include('tally::companies._yesno', ['name' => 'allow_negative_stock', 'label' => 'Allow negative stock', 'value' => $company->allow_negative_stock, 'options' => ['No', 'Yes'], 'values' => ['0', '1'], 'autofocus' => true])
                    @include('tally::companies._yesno', ['name' => 'inventory_repeat_scan_increments', 'label' => 'Repeat barcode scan increases quantity', 'value' => $repeatScan, 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                @elseif ($section === 'statutory')
                    <p class="gateway-group">Statutory &amp; Taxation</p>
                    @include('tally::companies._yesno', [
                        'name' => 'gst_registration_type',
                        'label' => 'GST registration',
                        'value' => $company->gst_registration_type?->value ?? 'regular',
                        'options' => ['Regular', 'Composition', 'Unregistered', 'Consumer'],
                        'values' => ['regular', 'composition', 'unregistered', 'consumer'],
                        'autofocus' => true,
                    ])
                    @include('tally::companies._yesno', [
                        'name' => 'tax_pricing',
                        'label' => 'Tax pricing',
                        'value' => $company->tax_pricing?->value ?? 'exclusive',
                        'options' => ['Exclusive', 'Inclusive'],
                        'values' => ['exclusive', 'inclusive'],
                    ])
                    @include('tally::companies._yesno', [
                        'name' => 'tax_rounding',
                        'label' => 'Tax rounding',
                        'value' => $company->tax_rounding?->value ?? 'paisa',
                        'options' => ['Paisa', 'Rupee'],
                        'values' => ['paisa', 'rupee'],
                    ])
                @else
                    <p class="gateway-group">Payroll Features</p>
                    @include('tally::companies._yesno', ['name' => 'payroll_enabled', 'label' => 'Maintain payroll', 'value' => $payroll, 'options' => ['No', 'Yes'], 'values' => ['0', '1'], 'autofocus' => true])
                    @include('tally::companies._yesno', ['name' => 'payroll_pf', 'label' => 'Calculate PF', 'value' => $pf, 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    @include('tally::companies._yesno', ['name' => 'payroll_esi', 'label' => 'Calculate ESI', 'value' => $esi, 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    <x-tally::form.field name="payroll_pf_rate" label="PF employee rate %">
                        <input class="input" name="payroll_pf_rate" value="{{ $pfRate }}" inputmode="decimal">
                    </x-form.field>
                    <x-tally::form.field name="payroll_pf_ceiling" label="PF wage ceiling">
                        <input class="input" name="payroll_pf_ceiling" value="{{ $pfCeiling }}" inputmode="decimal">
                    </x-form.field>
                    <x-tally::form.field name="payroll_esi_rate" label="ESI employee rate %">
                        <input class="input" name="payroll_esi_rate" value="{{ $esiRate }}" inputmode="decimal">
                    </x-form.field>
                    <x-tally::form.field name="payroll_esi_ceiling" label="ESI wage ceiling">
                        <input class="input" name="payroll_esi_ceiling" value="{{ $esiCeiling }}" inputmode="decimal">
                    </x-form.field>
                    <x-tally::form.field name="payroll_pf_employer_rate" label="PF employer rate %">
                        <input class="input" name="payroll_pf_employer_rate" value="{{ $employerPfRate }}" inputmode="decimal">
                    </x-form.field>
                    <x-tally::form.field name="payroll_esi_employer_rate" label="ESI employer rate %">
                        <input class="input" name="payroll_esi_employer_rate" value="{{ $employerEsiRate }}" inputmode="decimal">
                    </x-form.field>
                @endif
                <div class="form-actions">
                    <button class="btn btn-primary" type="submit">Accept</button>
                    <a class="btn" href="{{ tally_route('books.tally.companies.features') }}">Quit</a>
                </div>
            </form>
            <p class="form-note">Arrow keys change the highlighted value. Y and N set Yes or No. Enter moves to the next line. Accept saves this section for this company.</p>
        @endif
    </x-shell.page>
</x-layouts.app>
