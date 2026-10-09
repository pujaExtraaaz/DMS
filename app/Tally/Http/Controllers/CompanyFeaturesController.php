<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Preferences\PreferenceStore;
use Tally\Tax\GstRegistrationType;
use Tally\Tax\TaxPricing;
use Tally\Tax\TaxRounding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'print_amount_in_words' => ['required', 'boolean'],
            ]);
            $preferences->put($company, null, 'sales.enforce_credit_limit', $request->boolean('sales_enforce_credit_limit'));
            $preferences->put($company, null, 'print.amount_in_words', $request->boolean('print_amount_in_words'));

            return back()->with('status', 'Accounting features saved.');
        }

        if ($section === 'inventory') {
            $request->validate([
                'allow_negative_stock' => ['required', 'boolean'],
                'inventory_repeat_scan_increments' => ['required', 'boolean'],
            ]);
            $company->update(['allow_negative_stock' => $request->boolean('allow_negative_stock')]);
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
