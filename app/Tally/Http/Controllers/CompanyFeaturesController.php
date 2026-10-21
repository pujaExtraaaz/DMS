<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\FeatureLedgerOptions;
use Tally\Context\WorkspaceContext;
use Tally\Preferences\PreferenceStore;
use Tally\Tax\GstRegistrationType;
use Tally\Tax\TaxPricing;
use Tally\Tax\TaxRounding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CompanyFeaturesController extends Controller
{
    public function edit(Request $request, WorkspaceContext $context, PreferenceStore $preferences): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Company Features',
                'message' => 'Select a company before changing company features.',
            ]);
        }

        $section = $request->string('section')->toString();

        if (! in_array($section, ['', 'accounting', 'inventory', 'statutory', 'payroll'], true)) {
            $section = '';
        }

        return view('tally::companies.features', [
            'company' => $company,
            'section' => $section,
            'creditLimit' => $preferences->enabled($company, 'sales.enforce_credit_limit'),
            'repeatScan' => $preferences->enabled($company, 'inventory.repeat_scan_increments'),
            'amountInWords' => $preferences->enabled($company, 'print.amount_in_words'),
            'payroll' => $preferences->enabled($company, 'payroll.enabled'),
            'pf' => $preferences->enabled($company, 'payroll.pf'),
            'esi' => $preferences->enabled($company, 'payroll.esi'),
            'pfRate' => (string) $preferences->get($company, null, 'payroll.pf_rate'),
            'esiRate' => (string) $preferences->get($company, null, 'payroll.esi_rate'),
            'pfCeiling' => (string) $preferences->get($company, null, 'payroll.pf_ceiling'),
            'esiCeiling' => (string) $preferences->get($company, null, 'payroll.esi_ceiling'),
            'employerPfRate' => (string) $preferences->get($company, null, 'payroll.pf_employer_rate'),
            'employerEsiRate' => (string) $preferences->get($company, null, 'payroll.esi_employer_rate'),
            'creditAction' => (string) $preferences->get($company, null, 'sales.credit_limit_action') ?: 'block',
            'dateFormat' => $preferences->dateFormat($company, $request->user()),
            'dashboardAsOn' => (string) $preferences->get($company, $request->user(), 'dashboard.as_on'),
            'ledgerOptions' => app(FeatureLedgerOptions::class)->forCompany($company),
        ]);
    }

    public function update(Request $request, WorkspaceContext $context, PreferenceStore $preferences): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return back()->with('error', 'Select a company before changing company features.');
        }

        $section = $request->string('section')->toString();

        if ($section === 'accounting') {
            $request->validate([
                'sales_enforce_credit_limit' => ['required', 'boolean'],
                'sales_credit_limit_action' => ['required', 'in:block,warn'],
                'print_amount_in_words' => ['required', 'boolean'],
                'use_cost_centres' => ['required', 'boolean'],
                'use_bill_wise' => ['required', 'boolean'],
                'lock_closed_financial_year' => ['required', 'boolean'],
                'accounting_basis' => ['nullable', 'in:accrual,cash'],
                'currency_symbol' => ['required', 'string', 'max:8'],
                'currency_formal_name' => ['required', 'string', 'max:20'],
                'sales_terms' => ['nullable', 'string', 'max:2000'],
                'purchase_terms' => ['nullable', 'string', 'max:2000'],
                'default_credit_days' => ['nullable', 'integer', 'min:0', 'max:3650'],
                ...app(FeatureLedgerOptions::class)->rules($company),
                'collection_reminder_days_before' => ['nullable', 'integer', 'min:0', 'max:365'],
                'collection_reminder_days_after' => ['nullable', 'integer', 'min:0', 'max:365'],
                'display_date_format' => ['required', 'in:d M Y,d/m/Y,Y-m-d'],
                'dashboard_as_on' => ['nullable', 'date'],
            ]);
            $preferences->put($company, null, 'sales.enforce_credit_limit', $request->boolean('sales_enforce_credit_limit'));
            $preferences->put($company, null, 'sales.credit_limit_action', $request->input('sales_credit_limit_action') === 'warn' ? 'warn' : 'block');
            $preferences->put($company, null, 'print.amount_in_words', $request->boolean('print_amount_in_words'));
            $preferences->put(null, $request->user(), 'display.date_format', $request->input('display_date_format'));
            $asOn = $request->input('dashboard_as_on') ?: '';
            $preferences->put(null, $request->user(), 'dashboard.as_on', $asOn);
            $preferences->put($company, null, 'dashboard.as_on', $asOn);
            $symbol = trim((string) $request->input('currency_symbol')) ?: '₹';
            $formal = trim((string) $request->input('currency_formal_name')) ?: 'INR';
            $company->update([
                'accounting_basis' => $request->input('accounting_basis', $company->accounting_basis ?? 'accrual'),
                'use_cost_centres' => $request->boolean('use_cost_centres'),
                'use_bill_wise' => $request->boolean('use_bill_wise'),
                'lock_closed_financial_year' => $request->boolean('lock_closed_financial_year'),
                'currency_symbol' => $symbol,
                'currency_formal_name' => $formal,
                'sales_terms' => trim((string) $request->input('sales_terms')) ?: null,
                'purchase_terms' => trim((string) $request->input('purchase_terms')) ?: null,
                'default_credit_days' => $request->filled('default_credit_days') ? (int) $request->input('default_credit_days') : null,
                'retained_earnings_ledger_id' => $request->input('retained_earnings_ledger_id') ?: null,
                'customer_advance_ledger_id' => $request->input('customer_advance_ledger_id') ?: null,
                'bad_debt_ledger_id' => $request->input('bad_debt_ledger_id') ?: null,
                'doubtful_debt_ledger_id' => $request->input('doubtful_debt_ledger_id') ?: null,
                'retention_ledger_id' => $request->input('retention_ledger_id') ?: null,
                'commission_expense_ledger_id' => $request->input('commission_expense_ledger_id') ?: null,
                'commission_payable_ledger_id' => $request->input('commission_payable_ledger_id') ?: null,
                'round_off_ledger_id' => $request->input('round_off_ledger_id') ?: null,
                'collection_reminder_days_before' => $request->input('collection_reminder_days_before') !== null && $request->input('collection_reminder_days_before') !== ''
                    ? (int) $request->input('collection_reminder_days_before')
                    : null,
                'collection_reminder_days_after' => $request->input('collection_reminder_days_after') !== null && $request->input('collection_reminder_days_after') !== ''
                    ? (int) $request->input('collection_reminder_days_after')
                    : null,
            ]);

            if (Schema::hasTable('acct_currencies')) {
                $company->currencies()->where('is_base', true)->update(['symbol' => $symbol]);
            }

            return back()->with('status', 'Accounting features saved.');
        }

        if ($section === 'inventory') {
            $request->validate([
                'allow_negative_stock' => ['required', 'boolean'],
                'inventory_repeat_scan_increments' => ['required', 'boolean'],
                'inventory_valuation_method' => ['nullable', 'in:weighted_average,fifo'],
            ]);
            $company->update([
                'allow_negative_stock' => $request->boolean('allow_negative_stock'),
                'inventory_valuation_method' => $request->input('inventory_valuation_method', $company->inventory_valuation_method ?? 'weighted_average'),
            ]);
            $preferences->put($company, null, 'inventory.repeat_scan_increments', $request->boolean('inventory_repeat_scan_increments'));

            return back()->with('status', 'Inventory features saved.');
        }

        if ($section === 'statutory') {
            $data = $request->validate([
                'gst_registration_type' => ['required', 'in:regular,composition,unregistered,consumer'],
                'tax_pricing' => ['required', 'in:exclusive,inclusive'],
                'tax_rounding' => ['required', 'in:paisa,rupee'],
            ]);
            $company->update([
                'gst_registration_type' => GstRegistrationType::from($data['gst_registration_type']),
                'tax_pricing' => TaxPricing::from($data['tax_pricing']),
                'tax_rounding' => TaxRounding::from($data['tax_rounding']),
            ]);

            return back()->with('status', 'Statutory features saved.');
        }

        if ($section === 'payroll') {
            $data = $request->validate([
                'payroll_enabled' => ['required', 'boolean'],
                'payroll_pf' => ['required', 'boolean'],
                'payroll_esi' => ['required', 'boolean'],
                'payroll_pf_rate' => ['required', 'numeric', 'gte:0'],
                'payroll_esi_rate' => ['required', 'numeric', 'gte:0'],
                'payroll_pf_ceiling' => ['required', 'numeric', 'gte:0'],
                'payroll_esi_ceiling' => ['required', 'numeric', 'gte:0'],
                'payroll_pf_employer_rate' => ['required', 'numeric', 'gte:0'],
                'payroll_esi_employer_rate' => ['required', 'numeric', 'gte:0'],
            ]);
            $preferences->put($company, null, 'payroll.enabled', $request->boolean('payroll_enabled'));
            $preferences->put($company, null, 'payroll.pf', $request->boolean('payroll_pf'));
            $preferences->put($company, null, 'payroll.esi', $request->boolean('payroll_esi'));
            $preferences->put($company, null, 'payroll.pf_rate', (string) $data['payroll_pf_rate']);
            $preferences->put($company, null, 'payroll.esi_rate', (string) $data['payroll_esi_rate']);
            $preferences->put($company, null, 'payroll.pf_ceiling', (string) $data['payroll_pf_ceiling']);
            $preferences->put($company, null, 'payroll.esi_ceiling', (string) $data['payroll_esi_ceiling']);
            $preferences->put($company, null, 'payroll.pf_employer_rate', (string) $data['payroll_pf_employer_rate']);
            $preferences->put($company, null, 'payroll.esi_employer_rate', (string) $data['payroll_esi_employer_rate']);

            return back()->with('status', 'Payroll features saved.');
        }

        $data = $request->validate([
            'allow_negative_stock' => ['required', 'boolean'],
            'gst_registration_type' => ['required', 'in:regular,composition,unregistered,consumer'],
            'sales_enforce_credit_limit' => ['required', 'boolean'],
            'inventory_repeat_scan_increments' => ['required', 'boolean'],
            'print_amount_in_words' => ['required', 'boolean'],
            'tax_pricing' => ['required', 'in:exclusive,inclusive'],
            'tax_rounding' => ['required', 'in:paisa,rupee'],
            'payroll_enabled' => ['required', 'boolean'],
        ]);

        $company->update([
            'allow_negative_stock' => $request->boolean('allow_negative_stock'),
            'gst_registration_type' => GstRegistrationType::from($data['gst_registration_type']),
            'tax_pricing' => TaxPricing::from($data['tax_pricing']),
            'tax_rounding' => TaxRounding::from($data['tax_rounding']),
        ]);
        $preferences->put($company, null, 'sales.enforce_credit_limit', $request->boolean('sales_enforce_credit_limit'));
        $preferences->put($company, null, 'inventory.repeat_scan_increments', $request->boolean('inventory_repeat_scan_increments'));
        $preferences->put($company, null, 'print.amount_in_words', $request->boolean('print_amount_in_words'));
        $preferences->put($company, null, 'payroll.enabled', $request->boolean('payroll_enabled'));

        return back()->with('status', 'Company features saved.');
    }
}
