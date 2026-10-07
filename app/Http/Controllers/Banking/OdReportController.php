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

        // Selected account number
        $selectedAccountNo = $request->input(
            'account_number',
            $odAccounts->first()?->account_number ?? $companyBankAccounts->first()['account_number'] ?? null
        );

        // Date range handling based on financial year and user presets
        [$dateFrom, $dateTo, $datePreset] = $this->resolveDateRange($request, $company);

        // Find or build OD Account instance
        $odAccount = $odAccounts->firstWhere('account_number', $selectedAccountNo);
        $selectedBank = $companyBankAccounts->firstWhere('account_number', $selectedAccountNo);

        if (! $odAccount && $selectedAccountNo) {
            // Unconfigured OD account: create in-memory object for reporting
            $odAccount = new OdAccount([
                'company_id' => $company?->id ?? 1,
                'account_number' => $selectedAccountNo,
                'bank_name' => $selectedBank['bank_name'] ?? 'Bank Account',
                'ifsc_code' => $selectedBank['ifsc'] ?? null,
                'od_limit' => 0.00,
                'interest_rate' => 0.00,
                'interest_calculation_method' => 'daily_simple',
                'effective_from' => $dateFrom,
                'status' => 'inactive',
            ]);
        }

        // Calculate OD position and period interest
        $reportData = null;
        if ($odAccount) {
            $reportData = $this->calculationService->calculate(
                odAccount: $odAccount,
                fromDate: $dateFrom,
                toDate: $dateTo,
                search: $request->input('search'),
                typeFilter: $request->input('type')
            );
        }

        // Handle Exports (CSV, PDF, Excel)
        if ($request->filled('export') && $reportData) {
            return $this->handleExport($request, $reportData, $company);
        }

        return view('banking.od.report', [
            'company' => $company,
            'companyBankAccounts' => $companyBankAccounts,
            'odAccounts' => $odAccounts,
            'selectedAccountNo' => $selectedAccountNo,
            'selectedBank' => $selectedBank,
            'odAccount' => $odAccount,
            'reportData' => $reportData,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'datePreset' => $datePreset,
            'search' => $request->input('search'),
            'typeFilter' => $request->input('type'),
        ]);
    }

    protected function handleExport(Request $request, array $reportData, ?Company $company): Response
    {
        $exportFormat = strtolower($request->input('export'));
        $odAccount = $reportData['odAccount'];
        $cleanAcc = preg_replace('/[^A-Za-z0-9]/', '', $odAccount->account_number);
        $filename = "OD-Report-{$cleanAcc}-{$reportData['fromDate']}-to-{$reportData['toDate']}";

        if ($exportFormat === 'pdf') {
            $pdf = Pdf::loadView('banking.od.pdf', [
                'company' => $company,
                'report' => $reportData,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("{$filename}.pdf");
        }

        // CSV / Excel export
        $headers = [
            'Date',
            'Transaction No',
            'Particulars / Description',
            'Transaction Type',
            'Debit (Withdrawal)',
            'Credit (Deposit)',
            'OD Utilized',
            'Available OD',
            'Interest Rate (%)',
            'Days',
            'Daily Interest',
            'Cumulative Interest',
        ];

        $rows = $reportData['rows']->map(function ($row) {
            $dateStr = $row['date'] instanceof Carbon ? $row['date']->format('d/m/Y') : (string) $row['date'];

            return [
                $dateStr,
                $row['transaction_no'],
                $row['description'],
                ucfirst(str_replace('_', ' ', $row['transaction_type'])),
                $row['debit'] > 0 ? number_format((float) $row['debit'], 2, '.', '') : '-',
                $row['credit'] > 0 ? number_format((float) $row['credit'], 2, '.', '') : '-',
                number_format((float) $row['od_utilized'], 2, '.', ''),
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
     * Resolve date range defaulting to current Financial Year or requested preset.
     */
    protected function resolveDateRange(Request $request, ?Company $company): array
    {
        $preset = $request->input('preset', 'fy');

        $fy = FinancialYear::query()
            ->when($company?->id, fn ($q) => $q->where('company_id', $company->id))
            ->where('is_current', true)
            ->first();

        $fyStart = $fy?->starts_on?->toDateString() ?? now()->startOfYear()->toDateString();
        $fyEnd = $fy?->ends_on?->toDateString() ?? now()->toDateString();

        if ($preset === 'current_month') {
            $from = now()->startOfMonth()->toDateString();
            $to = now()->endOfMonth()->toDateString();
        } elseif ($preset === 'previous_month') {
            $from = now()->subMonth()->startOfMonth()->toDateString();
            $to = now()->subMonth()->endOfMonth()->toDateString();
        } elseif ($preset === 'custom' && $request->filled('date_from') && $request->filled('date_to')) {
            $from = $request->input('date_from');
            $to = $request->input('date_to');
        } else {
            // Default to FY
            $from = $request->input('date_from', $fyStart);
            $to = $request->input('date_to', $fyEnd);
            $preset = 'fy';
        }

        return [$from, $to, $preset];
    }

    protected function resolveCompany(Request $request): ?Company
    {
        $user = $request->user();

        return Company::find($user?->company_id) ?? Company::first();
    }
}

