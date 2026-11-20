<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\PayrollRun;
use App\Domains\Hrms\Models\PayrollRunLine;
use App\Domains\Hrms\Services\PayrollCalculator;
use App\Domains\Organization\Models\Branch;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use App\Support\ReportExporter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PayrollRunController extends Controller
{
    public function __construct(
        protected PayrollCalculator $calculator,
        protected AuditLogService $auditLogService
    ) {}

    public function index(): View
    {
        $items = PayrollRun::query()
            ->with(['company', 'branch', 'creator'])
            ->withCount('lines')
            ->latest()
            ->paginate(20);

        return view('hrms.payroll-runs.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.payroll-runs.form', [
            'item' => new PayrollRun([
                'year' => (int) now()->year,
                'month' => (int) now()->month,
                'company_id' => auth()->user()?->company_id,
            ]),
            'companies' => Company::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'branch_id' => 'nullable|exists:branches,id',
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
            'notes' => 'nullable|string',
        ]);
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();

        $run = PayrollRun::create($data);
        $this->calculator->computeRun($run);
        $this->auditLogService->record($run, 'created');

        return $this->flashSuccess('Payroll run computed as draft.', 'hrms.payroll-runs.show', [$run]);
    }

    public function show(PayrollRun $payroll_run): View
    {
        $payroll_run->load(['lines.employee.department', 'company', 'branch']);

        return view('hrms.payroll-runs.show', ['run' => $payroll_run]);
    }

    public function recompute(PayrollRun $payroll_run): RedirectResponse
    {
        if ($payroll_run->isLocked()) {
            return $this->flashError('Locked payroll cannot be recomputed.');
        }
        $this->calculator->computeRun($payroll_run);

        return $this->flashSuccess('Payroll recomputed.', 'hrms.payroll-runs.show', [$payroll_run]);
    }

    public function approve(PayrollRun $payroll_run): RedirectResponse
    {
        if ($payroll_run->status !== 'draft') {
            return $this->flashError('Only draft payroll can be approved.');
        }
        $payroll_run->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);
        $payroll_run->lines()->each(function (PayrollRunLine $line) use ($payroll_run) {
            // keep payroll_inputs in sync as approved
            \App\Domains\Hrms\Models\PayrollInput::query()
                ->where('employee_id', $line->employee_id)
                ->where('year', $payroll_run->year)
                ->where('month', $payroll_run->month)
                ->update(['status' => 'approved', 'net_payable' => $line->net_payable]);
        });
        $this->auditLogService->record($payroll_run, 'approved');

        return $this->flashSuccess('Payroll approved. Lock when ready to finalize.', 'hrms.payroll-runs.show', [$payroll_run]);
    }

    public function lock(PayrollRun $payroll_run): RedirectResponse
    {
        if ($payroll_run->status !== 'approved') {
            return $this->flashError('Approve payroll before locking.');
        }
        $payroll_run->update(['status' => 'locked']);

        return $this->flashSuccess('Payroll locked.', 'hrms.payroll-runs.show', [$payroll_run]);
    }

    public function bankFile(PayrollRun $payroll_run): StreamedResponse
    {
        $payroll_run->load('lines.employee');
        $headers = ['Employee Code', 'Name', 'Bank', 'Account', 'IFSC', 'Amount'];
        $rows = [];
        foreach ($payroll_run->lines as $line) {
            $e = $line->employee;
            $rows[] = [
                $e?->employee_code,
                $e?->name,
                $e?->bank_name,
                $e?->bank_account_number,
                $e?->bank_ifsc,
                number_format((float) $line->net_payable, 2, '.', ''),
            ];
        }

        return ReportExporter::csv("payroll-bank-{$payroll_run->year}-{$payroll_run->month}.csv", $headers, $rows);
    }

    public function payslip(PayrollRun $payroll_run, PayrollRunLine $line): View
    {
        abort_unless($line->payroll_run_id === $payroll_run->id, 404);
        $line->load('employee.department');
        $payroll_run->load(['company', 'branch']);

        return view('hrms.payroll-runs.payslip', [
            'run' => $payroll_run,
            'line' => $line,
        ]);
    }

    public function payslipPdf(PayrollRun $payroll_run, PayrollRunLine $line): Response
    {
        abort_unless($line->payroll_run_id === $payroll_run->id, 404);
        $line->load('employee.department');
        $payroll_run->load(['company', 'branch']);

        $pdf = Pdf::loadView('hrms.payroll-runs.payslip-pdf', [
            'run' => $payroll_run,
            'line' => $line,
        ])->setPaper('a4', 'portrait');

        $code = $line->employee?->employee_code ?? $line->employee_id;

        return $pdf->download("payslip-{$payroll_run->year}-{$payroll_run->month}-{$code}.pdf");
    }

    public function departmentReport(PayrollRun $payroll_run, Request $request): View|StreamedResponse|Response
    {
        $payroll_run->load(['lines.employee.department', 'company', 'branch']);

        $grouped = $payroll_run->lines
            ->groupBy(fn (PayrollRunLine $line) => $line->employee?->department?->name ?? 'Unassigned')
            ->sortKeys();

        $headers = ['Department', 'Employees', 'Gross', 'Deductions', 'Net'];
        $rows = [];
        foreach ($grouped as $dept => $lines) {
            $rows[] = [
                $dept,
                $lines->count(),
                number_format((float) $lines->sum('gross_earnings'), 2, '.', ''),
                number_format((float) $lines->sum('total_deductions'), 2, '.', ''),
                number_format((float) $lines->sum('net_payable'), 2, '.', ''),
            ];
        }

        if ($request->input('export') === 'csv') {
            return ReportExporter::csv(
                "payroll-dept-{$payroll_run->year}-{$payroll_run->month}.csv",
                $headers,
                $rows
            );
        }

        if ($request->input('export') === 'pdf') {
            return ReportExporter::pdf(
                "payroll-dept-{$payroll_run->year}-{$payroll_run->month}.pdf",
                'Payroll by Department '.$payroll_run->month.'/'.$payroll_run->year,
                $headers,
                $rows,
                [
                    'Company' => $payroll_run->company?->name,
                    'Branch' => $payroll_run->branch?->name ?? 'All',
                    'Status' => $payroll_run->status,
                ]
            );
        }

        return view('hrms.payroll-runs.department-report', [
            'run' => $payroll_run,
            'grouped' => $grouped,
            'rows' => $rows,
        ]);
    }

    public function employeeReport(PayrollRun $payroll_run, Request $request): View|StreamedResponse|Response
    {
        $payroll_run->load(['lines.employee.department', 'company', 'branch']);

        $headers = ['Code', 'Employee', 'Department', 'Payable', 'LWP', 'OT min', 'Gross', 'Ded', 'Net'];
        $rows = [];
        foreach ($payroll_run->lines->sortBy(fn ($l) => $l->employee?->name) as $line) {
            $rows[] = [
                $line->employee?->employee_code,
                $line->employee?->name,
                $line->employee?->department?->name,
                $line->payable_days,
                $line->lwp_days,
                $line->ot_minutes,
                number_format((float) $line->gross_earnings, 2, '.', ''),
                number_format((float) $line->total_deductions, 2, '.', ''),
                number_format((float) $line->net_payable, 2, '.', ''),
            ];
        }

        if ($request->input('export') === 'csv') {
            return ReportExporter::csv(
                "payroll-employees-{$payroll_run->year}-{$payroll_run->month}.csv",
                $headers,
                $rows
            );
        }

        if ($request->input('export') === 'pdf') {
            return ReportExporter::pdf(
                "payroll-employees-{$payroll_run->year}-{$payroll_run->month}.pdf",
                'Payroll Employee Report '.$payroll_run->month.'/'.$payroll_run->year,
                $headers,
                $rows
            );
        }

        return view('hrms.payroll-runs.employee-report', [
            'run' => $payroll_run,
            'rows' => $payroll_run->lines,
        ]);
    }
}
