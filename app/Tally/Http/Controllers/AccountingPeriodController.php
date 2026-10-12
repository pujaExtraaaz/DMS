<?php

namespace Tally\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Tally\Accounting\PeriodLockService;
use Tally\Context\WorkspaceContext;
use Tally\Models\AccountingPeriod;

class AccountingPeriodController extends Controller
{
    public function index(WorkspaceContext $context, PeriodLockService $periods): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', [
                'title' => 'Accounting periods',
                'message' => 'Select a company and financial year before managing period locks.',
            ]);
        }

        return view('tally::periods.index', [
            'company' => $company,
            'year' => $year,
            'periods' => $periods->forYear($company, $year),
        ]);
    }

    public function lock(Request $request, WorkspaceContext $context, AccountingPeriod $period, PeriodLockService $periods): RedirectResponse
    {
        $this->authorizePeriod($context, $period);
        /** @var User $user */
        $user = $request->user();
        $periods->lock($period, $user);

        return back()->with('status', $period->name.' locked.');
    }

    public function unlock(WorkspaceContext $context, AccountingPeriod $period, PeriodLockService $periods): RedirectResponse
    {
        $this->authorizePeriod($context, $period);
        $periods->unlock($period);

        return back()->with('status', $period->name.' unlocked.');
    }

    public function lockThrough(Request $request, WorkspaceContext $context, PeriodLockService $periods): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();
        abort_unless($company && $year, 404);

        $data = $request->validate([
            'through_date' => ['required', 'date'],
        ]);

        /** @var User $user */
        $user = $request->user();
        $count = $periods->lockThrough($company, $year, $data['through_date'], $user);

        return back()->with('status', "Locked {$count} period(s).");
    }

    private function authorizePeriod(WorkspaceContext $context, AccountingPeriod $period): void
    {
        abort_unless($context->company() && $period->company_id === $context->company()->id, 404);
    }
}
