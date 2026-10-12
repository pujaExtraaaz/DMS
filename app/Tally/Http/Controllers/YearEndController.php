<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Accounting\YearEndCloseService;
use Tally\Context\WorkspaceContext;
use Tally\Models\Ledger;

class YearEndController extends Controller
{
    public function show(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Year-end close',
                'message' => 'Select a company and financial year before closing the year.',
            ]);
        }

        $ledgers = Ledger::query()
            ->where('company_id', $company->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('tally::year-end.show', [
            'company' => $company,
            'year' => $year,
            'ledgers' => $ledgers,
        ]);
    }

    public function close(Request $request, WorkspaceContext $context, YearEndCloseService $service): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);

        $data = $request->validate([
            'retained_earnings_ledger_id' => ['nullable', 'integer', 'exists:acct_ledgers,id'],
            'confirm' => ['accepted'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $service->close(
            $company,
            $year,
            $user,
            $data['retained_earnings_ledger_id'] ?? null,
        );

        return redirect()
            ->route('books.tally.financial-years.current')
            ->with('status', 'Financial year '.$year->name.' closed. Opening balances migrated to the next year.');
    }
}
