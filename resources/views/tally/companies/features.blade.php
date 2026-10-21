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
                    @include('tally::companies._yesno', ['name' => 'sales_credit_limit_action', 'label' => 'When the limit is crossed', 'value' => $creditAction, 'options' => ['Block', 'Warn'], 'values' => ['block', 'warn']])
                    @include('tally::companies._yesno', ['name' => 'print_amount_in_words', 'label' => 'Print amount in words', 'value' => $amountInWords, 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    @include('tally::companies._yesno', ['name' => 'use_cost_centres', 'label' => 'Use cost centres', 'value' => $company->usesCostCentres(), 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    @include('tally::companies._yesno', ['name' => 'use_bill_wise', 'label' => 'Bill-wise outstanding', 'value' => $company->usesBillWise(), 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    @include('tally::companies._yesno', ['name' => 'lock_closed_financial_year', 'label' => 'Lock a closed financial year', 'value' => $company->locksClosedYear(), 'options' => ['No', 'Yes'], 'values' => ['0', '1']])
                    <x-tally::form.field name="display_date_format" label="Date format">
                        <select id="display_date_format" name="display_date_format" class="input">
                            @foreach (['d M Y' => '01 Oct 2026', 'd/m/Y' => '01/10/2026', 'Y-m-d' => '2026-10-01'] as $format => $sample)
                                <option value="{{ $format }}" @selected(old('display_date_format', $dateFormat) === $format)>{{ $sample }}</option>
                            @endforeach
                        </select>
                    </x-tally::form.field>
                    <x-tally::form.field name="dashboard_as_on" label="Dashboard as-on date">
                        <x-tally::form.input name="dashboard_as_on" type="date" value="{{ old('dashboard_as_on', $dashboardAsOn) }}" />
                    </x-tally::form.field>
                    @include('tally::companies._yesno', [
                        'name' => 'accounting_basis',
                        'label' => 'Reporting basis',
                        'value' => $company->accounting_basis ?? 'accrual',
                        'options' => ['Accrual', 'Cash'],
                        'values' => ['accrual', 'cash'],
                    ])
                    <x-tally::form.field name="currency_symbol" label="Currency symbol">
                        <input class="input" name="currency_symbol" value="{{ old('currency_symbol', $company->currency_symbol ?: '₹') }}" maxlength="8">
                    </x-tally::form.field>
                    <x-tally::form.field name="currency_formal_name" label="Currency formal name">
                        <input class="input" name="currency_formal_name" value="{{ old('currency_formal_name', $company->currency_formal_name ?: 'INR') }}" maxlength="20">
                    </x-tally::form.field>
                    <x-tally::form.field name="default_credit_days" label="Default credit days for a new customer">
                        <input class="input" name="default_credit_days" value="{{ old('default_credit_days', $company->default_credit_days) }}" inputmode="numeric">
                    </x-tally::form.field>
                    <x-tally::form.field name="sales_terms" label="Sales terms">
                        <textarea class="input" name="sales_terms" rows="3" maxlength="2000">{{ old('sales_terms', $company->sales_terms) }}</textarea>
                    </x-tally::form.field>
                    <x-tally::form.field name="purchase_terms" label="Purchase terms">
                        <textarea class="input" name="purchase_terms" rows="3" maxlength="2000">{{ old('purchase_terms', $company->purchase_terms) }}</textarea>
                    </x-tally::form.field>
                    @include('tally::companies._ledger', ['name' => 'retained_earnings_ledger_id', 'label' => 'Retained earnings', 'company' => $company, 'ledgers' => $ledgerOptions['retained_earnings_ledger_id'], 'empty' => 'No capital ledger'])
                    @include('tally::companies._ledger', ['name' => 'customer_advance_ledger_id', 'label' => 'Customer advance', 'company' => $company, 'ledgers' => $ledgerOptions['customer_advance_ledger_id'], 'empty' => 'No current liability ledger'])
                    @include('tally::companies._ledger', ['name' => 'bad_debt_ledger_id', 'label' => 'Bad debt', 'company' => $company, 'ledgers' => $ledgerOptions['bad_debt_ledger_id'], 'empty' => 'No indirect expense ledger'])
                    @include('tally::companies._ledger', ['name' => 'doubtful_debt_ledger_id', 'label' => 'Doubtful debt provision', 'company' => $company, 'ledgers' => $ledgerOptions['doubtful_debt_ledger_id'], 'empty' => 'No current liability ledger'])
                    @include('tally::companies._ledger', ['name' => 'retention_ledger_id', 'label' => 'Retention', 'company' => $company, 'ledgers' => $ledgerOptions['retention_ledger_id'], 'empty' => 'No current liability ledger'])
                    @include('tally::companies._ledger', ['name' => 'commission_expense_ledger_id', 'label' => 'Commission expense', 'company' => $company, 'ledgers' => $ledgerOptions['commission_expense_ledger_id'], 'empty' => 'No indirect expense ledger'])
                    @include('tally::companies._ledger', ['name' => 'commission_payable_ledger_id', 'label' => 'Commission payable', 'company' => $company, 'ledgers' => $ledgerOptions['commission_payable_ledger_id'], 'empty' => 'No current liability ledger'])
                    @include('tally::companies._ledger', ['name' => 'round_off_ledger_id', 'label' => 'Round off', 'company' => $company, 'ledgers' => $ledgerOptions['round_off_ledger_id'], 'empty' => 'No indirect income or expense ledger'])
                    <p class="form-note">With a round-off ledger, an invoice total rounds to the nearest rupee and the paisa posts to that ledger. A voucher that is off by ₹1.00 or less uses the same ledger.</p>
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
                    @include('tally::companies._yesno', [
                        'name' => 'inventory_valuation_method',
                        'label' => 'Stock valuation method',
                        'value' => $company->inventory_valuation_method ?? 'weighted_average',
                        'options' => ['Weighted average', 'FIFO'],
                        'values' => ['weighted_average', 'fifo'],
                    ])
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
