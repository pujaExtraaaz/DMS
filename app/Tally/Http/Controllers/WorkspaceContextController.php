<?php

namespace Tally\Http\Controllers;

use Tally\Context\WorkingCalendar;
use Tally\Context\WorkspaceContext;
use Tally\Models\Branch;
use Tally\Models\Company;
use Tally\Models\FinancialYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceContextController extends Controller
{
    public function updateCompany(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => [
                'required',
                'integer',
                Rule::exists('companies', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
        ]);

        $company = Company::query()->findOrFail($data['company_id']);
        $context->setCompany($company);

        return back()->with('status', 'Working company set to '.$company->name.'.');
    }

    public function updateBranch(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return back()->withErrors([
                'branch_id' => 'Select a company before choosing a branch.',
            ]);
        }

        $data = $request->validate([
            'branch_id' => [
                'required',
                'integer',
                Rule::exists('branches', 'id')->where(fn ($query) => $query
                    ->where('company_id', $company->id)
                    ->where('is_active', true)),
            ],
        ]);

        $branch = Branch::query()->findOrFail($data['branch_id']);
        $context->setBranch($branch);

        return back()->with('status', 'Working branch set to '.$branch->name.'.');
    }

    public function updateFinancialYear(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();

        if (! $company) {
            return back()->withErrors([
                'financial_year_id' => 'Select a company before choosing a financial year.',
            ]);
        }

        $data = $request->validate([
            'financial_year_id' => [
                'required',
                'integer',
                Rule::exists('financial_years', 'id')->where(fn ($query) => $query
                    ->where('company_id', $company->id)
                    ->where('is_active', true)),
            ],
        ]);

        $financialYear = FinancialYear::query()->findOrFail($data['financial_year_id']);
        $context->setFinancialYear($financialYear);

        return back()->with('status', 'Working financial year set to '.$financialYear->name.'.');
    }

    public function updateDate(Request $request, WorkspaceContext $context, WorkingCalendar $calendar): RedirectResponse|JsonResponse
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return back()->withErrors(['as_on' => 'Select a company and financial year before changing the date.']);
        }

        $data = $request->validate([
            'as_on' => ['required', 'date'],
        ]);
        $date = date('Y-m-d', strtotime($data['as_on']));

        if ($date < $year->start_date->toDateString() || $date > $year->end_date->toDateString()) {
            return back()->withErrors(['as_on' => 'The date must fall inside '.$year->name.'.']);
        }

        $calendar->saveDate($company, $date);

        if ($request->expectsJson()) {
            return response()->json(['date' => $date]);
        }

        return back()->with('status', 'Current date set to '.date('j-M-Y', strtotime($date)).'.');
    }

    public function updatePeriod(Request $request, WorkspaceContext $context, WorkingCalendar $calendar): RedirectResponse
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return back()->withErrors(['period_from' => 'Select a company and financial year before changing the period.']);
        }

        $data = $request->validate([
            'period_from' => ['required', 'date'],
            'period_to' => ['required', 'date', 'after_or_equal:period_from'],
        ]);
        $from = date('Y-m-d', strtotime($data['period_from']));
        $to = date('Y-m-d', strtotime($data['period_to']));
        $start = $year->start_date->toDateString();
        $end = $year->end_date->toDateString();

        if ($from < $start || $to > $end) {
            return back()->withErrors(['period_from' => 'The period must fall inside '.$year->name.'.']);
        }

        $calendar->savePeriod($company, $from, $to);

        return back()->with('status', 'Current period set to '.date('j-M-y', strtotime($from)).' to '.date('j-M-y', strtotime($to)).'.');
    }
}
