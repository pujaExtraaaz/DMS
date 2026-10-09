<?php

namespace Tally\Http\Controllers;

use Tally\Authorization\CompanyAccess;
use Tally\Context\WorkingCalendar;
use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Invoice;
use Tally\Models\Voucher;
use Tally\Reporting\DashboardAnalytics;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $workspace, DashboardAnalytics $analytics, WorkingCalendar $calendar): View
    {
        $company = $workspace->company();
        $year = $workspace->financialYear();
        $asOn = $request->string('as_on')->toString() ?: $calendar->date($company, $request->user(), $year);
        $cards = ($company && $year && $request->user()?->can('reports.view'))
            ? $analytics->build($company, $workspace->branchId(), $year, $asOn)
            : null;
        $ids = CompanyAccess::ids($request->user());
        $companies = Company::query()->when($ids !== null, fn ($query) => $query->whereIn('id', $ids === [] ? [0] : $ids));

        $lastEntry = null;

        if ($company) {
            $dates = array_filter([
                Voucher::query()->where('company_id', $company->id)->max('voucher_date'),
                Invoice::query()->where('company_id', $company->id)->max('invoice_date'),
            ]);
            $lastEntry = $dates === [] ? null : max($dates);
        }

        return view('tally::dashboard.index', [
            'companyTotal' => (clone $companies)->count(),
            'activeCompanyTotal' => (clone $companies)->where('is_active', true)->count(),
            'workspace' => $workspace,
            'cards' => $cards,
            'asOn' => $asOn,
            'screen' => $request->string('screen')->toString() === 'tiles' ? 'tiles' : 'gateway',
            'lastEntry' => $lastEntry,
        ]);
    }
}
