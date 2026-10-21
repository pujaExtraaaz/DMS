<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkspaceContext;
use Tally\Preferences\PreferenceStore;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function edit(): RedirectResponse
    {
        return redirect()->to(tally_route('books.tally.companies.features', ['section' => 'accounting']));
    }

    public function update(Request $request, WorkspaceContext $context, PreferenceStore $preferences): RedirectResponse
    {
        $data = $request->validate([
            'display_date_format' => ['required', 'in:d M Y,d/m/Y,Y-m-d'],
            'sales_enforce_credit_limit' => ['sometimes', 'boolean'],
            'inventory_repeat_scan_increments' => ['sometimes', 'boolean'],
            'print_amount_in_words' => ['sometimes', 'boolean'],
            'dashboard_as_on' => ['nullable', 'date'],
        ]);
        $company = $context->company();
        $user = $request->user();
        $preferences->put($company, null, 'sales.enforce_credit_limit', $request->boolean('sales_enforce_credit_limit'));
        $preferences->put($company, null, 'inventory.repeat_scan_increments', $request->boolean('inventory_repeat_scan_increments'));
        $preferences->put($company, null, 'print.amount_in_words', $request->boolean('print_amount_in_words'));
        $preferences->put(null, $user, 'display.date_format', $data['display_date_format']);
        $preferences->put(null, $user, 'dashboard.as_on', $data['dashboard_as_on'] ?? '');

        return back()->with('status', 'Preferences saved.');
    }
}
