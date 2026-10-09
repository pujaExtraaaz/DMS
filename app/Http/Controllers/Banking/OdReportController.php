<?php

namespace App\Http\Controllers\Banking;

use App\Domains\Banking\Models\OdAccount;
use App\Domains\Banking\Services\OdInterestCalculationService;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\FinancialYear;
use App\Http\Controllers\Controller;
use App\Support\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class OdReportController extends Controller
{
    public function __construct(
        protected OdInterestCalculationService $calculationService
    ) {}

    public function index(Request $request): View|Response
    {
        $company = $this->resolveCompany($request);
        $companyBankAccounts = $company ? $company->getCompanyBankAccounts() : collect();

        // Configured OD accounts for the company
        $odAccounts = $company
            ? OdAccount::forCompany($company->id)->get()
            : collect();

        // Valid account numbers for tenant safety
        $validAccountNumbers = $companyBankAccounts->pluck('account_number')
            ->concat($odAccounts->pluck('account_number'))
            ->unique()
            ->all();

        // Selected account number (prevent cross-tenant account leakage)
        $requestedAccountNo = $request->input('account_number');
        $selectedAccountNo = null;
        if ($requestedAccountNo && in_array($requestedAccountNo, $validAccountNumbers, true)) {
            $selectedAccountNo = $requestedAccountNo;
        }

        // Date range handling and validation
        [$dateFrom, $dateTo, $datePreset, $dateError] = $this->resolveDateRange($request, $company);

        // Find or build OD Account instance
        $odAccount = $odAccounts->firstWhere('account_number', $selectedAccountNo);
        $selectedBank = $companyBankAccounts->firstWhere('account_number', $selectedAccountNo);

        if (! $odAccount && $selectedAccountNo) {
            $odAccount = new OdAccount([
                'company_id' => $company?->id ?? 1,
                'account_number' => $selectedAccountNo,
                'bank_name' => $selectedBank['bank_name'] ?? 'Bank Account',
                'ifsc_code' => $selectedBank['ifsc'] ?? null,
                'od_limit' => (float) ($selectedBank['od_limit'] ?? 0.0),
                'interest_rate' => (float) ($selectedBank['interest_rate'] ?? 0.0),
                'interest_calculation_method' => 'daily_simple',
                'effective_from' => $dateFrom ?: now()->toDateString(),
                'status' => 'active',
            ]);
        } elseif ($odAccount && $selectedBank) {
            // Ensure OD limit and rate reflect latest from Company Profile if OdAccount was not updated
            if ((float) $odAccount->od_limit == 0 && (float) ($selectedBank['od_limit'] ?? 0) > 0) {
                $odAccount->od_limit = (float) $selectedBank['od_limit'];
            }
            if ((float) $odAccount->interest_rate == 0 && (float) ($selectedBank['interest_rate'] ?? 0) > 0) {
                $odAccount->interest_rate = (float) $selectedBank['interest_rate'];
            }
        }

        $hasOdConfig = $odAccount && ((float) $odAccount->od_limit > 0 || (float) $odAccount->interest_rate > 0);
        $configuredOdLimit = $hasOdConfig ? (float) $odAccount->od_limit : 0.0;
        $annualInterestRate = $hasOdConfig ? (float) $odAccount->interest_rate : 0.0;
        $estimatedAnnualInterest = $hasOdConfig ? round(($configuredOdLimit * $annualInterestRate) / 100, 2) : 0.0;
        $estimatedMonthlyInterest = $hasOdConfig ? round($estimatedAnnualInterest / 12, 2) : 0.0;

        // Calculate OD position and period interest
        $reportData = null;
        $paginatedRows = null;
        if ($odAccount && ! $dateError) {
            $reportData = $this->calculationService->calculate(
                odAccount: $odAccount,
                fromDate: $dateFrom,
                toDate: $dateTo,
                search: $request->input('search'),
                typeFilter: $request->input('type')
            );

            // Handle Exports (CSV, PDF, Excel)
            if ($request->filled('export')) {
                return $this->handleExport($request, $reportData, $company);
            }

            // Pagination for large transaction sets (50 per page)
            $perPage = 50;
            $page = (int) $request->input('page', 1);
            $displayRowsCollection = $reportData['displayRows'];
            $paginatedRows = new \Illuminate\Pagination\LengthAwarePaginator(
                $displayRowsCollection->forPage($page, $perPage)->values(),
                $displayRowsCollection->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            );
        }

        return view('banking.od.report', [
            'company' => $company,
            'companyBankAccounts' => $companyBankAccounts,
            'odAccounts' => $odAccounts,
            'selectedAccountNo' => $selectedAccountNo,
            'selectedBank' => $selectedBank,
            'odAccount' => $odAccount,
            'hasOdConfig' => $hasOdConfig,
            'configuredOdLimit' => $configuredOdLimit,
            'annualInterestRate' => $annualInterestRate,
            'estimatedAnnualInterest' => $estimatedAnnualInterest,
            'estimatedMonthlyInterest' => $estimatedMonthlyInterest,
            'reportData' => $reportData,
            'paginatedRows' => $paginatedRows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'datePreset' => $datePreset,
            'dateError' => $dateError,
            'search' => $request->input('search'),
            'typeFilter' => $request->input('type'),
        ]);
    }

    protected function handleExport(Request $request, array $reportData, ?Company $company): Response
    {
        $exportFormat = strtolower($request->input('export'));
        $odAccount = $reportData['odAccount'];
        $cleanAcc = preg_replace('/[^A-Za-z0-9]/', '', $odAccount->account_number);
        $fromStr = $reportData['fromDate'] ?? 'all';
        $toStr = $reportData['toDate'] ?? 'all';
        $filename = "OD-Report-{$cleanAcc}-{$fromStr}-to-{$toStr}";

        if ($exportFormat === 'pdf') {
            $pdf = Pdf::loadView('banking.od.pdf', [
                'company' => $company,
                'report' => $reportData,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("{$filename}.pdf");
        }

        // CSV / Excel export
        $headers = [
            'Sr. No.',
            'Transaction Date',
            'Voucher / Reference Number',
            'Transaction Type',
            'Description / Particulars',
            'Debit (₹)',
            'Credit (₹)',
            'Running Balance (₹)',
            'OD Limit (₹)',
            'Available OD Limit (₹)',
            'Interest Rate (%)',
            'Days',
            'Daily Interest (₹)',
            'Cumulative Interest (₹)',
        ];

        $exportRows = $reportData['displayRows']->isNotEmpty() ? $reportData['displayRows'] : $reportData['rows'];

        $rows = $exportRows->map(function ($row, $index) {
            $dateStr = $row['date'] instanceof Carbon ? $row['date']->format('d/m/Y') : (string) $row['date'];

            return [
                $row['sr_no'] ?? ($index + 1),
                $dateStr,
                $row['transaction_no'],
                ucfirst(str_replace('_', ' ', $row['transaction_type'])),
                $row['description'],
                $row['debit'] > 0 ? number_format((float) $row['debit'], 2, '.', '') : '-',
                $row['credit'] > 0 ? number_format((float) $row['credit'], 2, '.', '') : '-',
                number_format((float) ($row['running_balance'] ?? $row['od_utilized']), 2, '.', ''),
                number_format((float) $row['od_limit'], 2, '.', ''),
                number_format((float) $row['available_od'], 2, '.', ''),
                number_format((float) $row['interest_rate'], 2, '.', '').'%',
                $row['days'],
                number_format((float) $row['daily_interest'], 2, '.', ''),
                number_format((float) $row['cumulative_interest'], 2, '.', ''),
            ];
        })->all();

        return ReportExporter::csv("{$filename}.csv", $headers, $rows);
    }

    /**
     * Resolve date range defaulting to all available transactions unless a preset or date is requested.
     */
    protected function resolveDateRange(Request $request, ?Company $company): array
    {
        $preset = $request->input('preset');
        $from = null;
        $to = null;
        $dateError = null;

        if ($preset === 'current_month') {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
        } elseif ($preset === 'previous_month') {
            $from = now()->subMonth()->startOfMonth()->toDateString();
            $to = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($preset === 'fy') {
            $fy = FinancialYear::query()
                ->when($company?->id, fn ($q) => $q->where('company_id', $company->id))
                ->where('is_current', true)
                ->first();

            $from = $fy?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString();
            $to = $fy?->ends_on?->toDateString() ?? now()->toDateString();
        } elseif ($request->filled('date_from') || $request->filled('date_to')) {
            $from = $request->input('date_from') ?: null;
            $to = $request->input('date_to') ?: null;
            $preset = 'custom';
        }

        // Validate that From Date is not later than To Date
        if ($from && $to && Carbon::parse($from)->gt(Carbon::parse($to))) {
            $dateError = 'From Date cannot be later than To Date.';
        }

        return [$from, $to, $preset, $dateError];
    }

    protected function resolveCompany(Request $request): ?Company
    {
        $user = $request->user();

        return Company::find($user?->company_id) ?? Company::first();
    }
}

