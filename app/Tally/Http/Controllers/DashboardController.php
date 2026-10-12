<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Tally\Authorization\CompanyAccess;
use Tally\Context\WorkingCalendar;
use Tally\Context\WorkspaceContext;
use Tally\Models\Company;
use Tally\Models\Invoice;
use Tally\Models\Voucher;
use Tally\Reporting\DashboardAnalytics;

class DashboardController extends Controller
{
    public function __invoke(Request $request, WorkspaceContext $workspace, DashboardAnalytics $analytics, WorkingCalendar $calendar): View
    {
        $company = $workspace->company();
        $year = $workspace->financialYear();
        $asOn = $request->string('as_on')->toString() ?: $calendar->date($company, $request->user(), $year);
        $cards = ($company && $year)
            ? $analytics->build($company, $workspace->branchId(), $year, $asOn)
            : null;
        $ids = CompanyAccess::ids($request->user());
        $companies = Company::query()->when($ids !== null, fn ($query) => $query->whereIn('id', $ids === [] ? [0] : $ids));

        $lastEntry = null;
        $recentVouchers = collect();

        if ($company) {
            $dates = array_filter([
                Voucher::query()->where('company_id', $company->id)->max('voucher_date'),
                Invoice::query()->where('company_id', $company->id)->max('invoice_date'),
            ]);
            $lastEntry = $dates === [] ? null : max($dates);

            $recentVouchers = Voucher::query()
                ->where('company_id', $company->id)
                ->when($workspace->branchId(), fn ($q, $branchId) => $q->where('branch_id', $branchId))
                ->latest('voucher_date')
                ->latest('id')
                ->limit(8)
                ->get(['id', 'voucher_number', 'voucher_type', 'voucher_date', 'status', 'narration']);
        }

        return view('tally::dashboard.index', [
            'companyTotal' => (clone $companies)->count(),
            'activeCompanyTotal' => (clone $companies)->where('is_active', true)->count(),
            'workspace' => $workspace,
            'cards' => $cards,
            'asOn' => $asOn,
            'lastEntry' => $lastEntry,
            'recentVouchers' => $recentVouchers,
        ]);
    }
}
