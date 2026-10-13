<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Models\Ledger;
use Tally\Models\PayrollProvision;
use Tally\Payroll\PayrollProvisionService;

class PayrollProvisionController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', [
                'title' => 'Payroll provisions',
                'message' => 'Select a company first.',
            ]);
        }

        return view('tally::payroll-provisions.index', [
            'company' => $company,
            'provisions' => PayrollProvision::query()
                ->with(['expenseLedger', 'liabilityLedger'])
                ->where('company_id', $company->id)
                ->latest('id')
                ->paginate(25),
            'ledgers' => Ledger::query()->where('company_id', $company->id)->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PayrollProvisionService $service): RedirectResponse
    {
        $company = $context->company();
        abort_unless($company, 404);

        $data = $request->validate([
            'kind' => ['required', Rule::in(['gratuity', 'leave', 'bonus', 'encashment'])],
            'period' => ['nullable', 'string', 'max:7'],
            'amount' => ['required', 'numeric', 'gt:0'],
            'expense_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
            'liability_ledger_id' => ['nullable', 'integer', Rule::exists('acct_ledgers', 'id')->where('company_id', $company->id)],
        ]);

        $service->create($company, $data);

        return back()->with('status', 'Provision recorded.');
    }
}
