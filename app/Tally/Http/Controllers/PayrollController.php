<?php

namespace Tally\Http\Controllers;

use Tally\Accounting\Money;
use Tally\Accounting\VoucherEngine;
use Tally\Accounting\VoucherType;
use Tally\Context\WorkspaceContext;
use Tally\Models\Attendance;
use Tally\Models\Employee;
use Tally\Models\Ledger;
use Tally\Models\PayrollRun;
use Tally\Payroll\PayrollService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayrollController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'Payroll', 'message' => 'Select a company and financial year.']);
        }

        return view('tally::payroll.index', [
            'company' => $company,
            'runs' => PayrollRun::query()->where('company_id', $company->id)->where('financial_year_id', $year->id)->latest('period_end')->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company || ! $context->branch() || ! $context->financialYear()) {
            return view('tally::workspace.needs-company', ['title' => 'Payroll', 'message' => 'Select a company, branch, and financial year.']);
        }

        return view('tally::payroll.form', [
            'company' => $company,
            'year' => $context->financialYear(),
            'employees' => Employee::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'ledgers' => $company->ledgers()->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, PayrollService $payroll): RedirectResponse
    {
        $company = $context->company();
        $data = $request->validate([
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after_or_equal:period_start'],
            'salary_ledger_id' => ['required', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
            'deduction_ledger_id' => ['nullable', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer'],
        ]);

        $run = PayrollRun::query()->create([
            'company_id' => $company->id,
            'branch_id' => $context->branch()->id,
            'financial_year_id' => $context->financialYear()->id,
            'period_start' => $data['period_start'],
            'period_end' => $data['period_end'],
            'salary_ledger_id' => $data['salary_ledger_id'],
            'deduction_ledger_id' => $data['deduction_ledger_id'] ?? null,
            'created_by' => $request->user()->id,
            'status' => 'draft',
        ]);
        $run->lines()->createMany($payroll->linesFromEmployees($company, $data['employee_ids'], $data['period_start'], $data['period_end']));

        return redirect()->route('books.tally.payroll.show', $run)->with('status', 'Payroll draft saved.');
    }

    public function show(WorkspaceContext $context, PayrollRun $payroll): View
    {
        abort_unless($payroll->company_id === $context->company()?->id, 404);
        $payroll->load(['lines.employee', 'voucher']);

        return view('tally::payroll.show', ['run' => $payroll]);
    }

    public function process(WorkspaceContext $context, PayrollService $service, PayrollRun $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $context->company()?->id, 404);
        $payroll->load(['branch', 'financialYear']);
        $service->process($context->company(), request()->user(), $payroll);

        return redirect()->route('books.tally.payroll.show', $payroll)->with('status', 'Payroll posted as a journal.');
    }

    public function attendance(WorkspaceContext $context): View
    {
        $company = $context->company();

        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Attendance', 'message' => 'Select a company.']);
        }

        return view('tally::payroll.attendance', [
            'company' => $company,
            'employees' => Employee::query()->where('company_id', $company->id)->orderBy('name')->get(),
            'rows' => Attendance::query()->with('employee')->where('company_id', $company->id)->orderByDesc('attendance_date')->limit(100)->get(),
        ]);
    }

    public function storeAttendance(Request $request, WorkspaceContext $context): RedirectResponse
    {
        $company = $context->company();
        $data = $request->validate([
            'attendance_date' => ['required', 'date'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', Rule::exists('employees', 'id')->where('company_id', $company->id)],
            'rows.*.attendance_type' => ['nullable', Rule::in(['present', 'absent', 'leave', 'half'])],
        ]);
        $marked = array_values(array_filter($data['rows'], fn (array $row) => ($row['attendance_type'] ?? '') !== ''));

        if ($marked === []) {
            return back()->withErrors(['rows' => 'Mark at least one employee.'])->withInput();
        }

        foreach ($marked as $row) {
            Attendance::query()->updateOrCreate(
                ['employee_id' => $row['employee_id'], 'attendance_date' => $data['attendance_date']],
                ['company_id' => $company->id, 'attendance_type' => $row['attendance_type']],
            );
        }

        return back()->with('status', 'Attendance saved.');
    }

    public function report(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'Payroll Report', 'message' => 'Select a company and financial year.']);
        }

        $runs = PayrollRun::query()
            ->with('lines.employee')
            ->where('company_id', $company->id)
            ->where('financial_year_id', $year->id)
            ->where('status', 'processed')
            ->orderBy('period_end')
            ->get();

        return view('tally::payroll.report', ['company' => $company, 'runs' => $runs]);
    }

    public function challan(WorkspaceContext $context): View
    {
        $company = $context->company();
        $year = $context->financialYear();

        if (! $company || ! $year) {
            return view('tally::workspace.needs-company', ['title' => 'PF and ESI Challan', 'message' => 'Select a company and financial year.']);
        }

        return view('tally::payroll.challan', [
            'company' => $company,
            'runs' => PayrollRun::query()->with(['lines', 'statutoryVoucher'])->where('company_id', $company->id)->where('financial_year_id', $year->id)->where('status', 'processed')->orderBy('period_end')->get(),
            'banks' => $company->ledgers()->with('accountGroup.parent')->where('is_active', true)->orderBy('name')->get()->filter(fn (Ledger $ledger) => $ledger->isCashOrBank())->values(),
        ]);
    }

    public function storeChallan(Request $request, WorkspaceContext $context, VoucherEngine $engine, PayrollRun $payroll): RedirectResponse
    {
        $company = $context->company();
        abort_unless($payroll->company_id === $company?->id, 404);
        $payroll->load(['lines', 'branch', 'financialYear']);

        if ($payroll->statutory_voucher_id || $payroll->status !== 'processed' || ! $payroll->deduction_ledger_id) {
            return back()->withErrors(['payroll' => 'This payroll has no unpaid PF or ESI challan.']);
        }

        $total = 0;

        foreach ($payroll->lines as $line) {
            $total += Money::cents((string) $line->pf_amount) + Money::cents((string) $line->employer_pf_amount);
            $total += Money::cents((string) $line->esi_amount) + Money::cents((string) $line->employer_esi_amount);
        }

        $data = $request->validate([
            'bank_ledger_id' => ['required', Rule::exists('ledgers', 'id')->where('company_id', $company->id)],
        ]);

        if ($total <= 0) {
            return back()->withErrors(['payroll' => 'No PF or ESI to pay on this payroll.']);
        }

        $voucher = $engine->save($company, $payroll->branch, $payroll->financialYear, $request->user(), VoucherType::Payment, [
            'voucher_date' => $payroll->period_end->toDateString(),
            'narration' => 'PF and ESI challan '.$payroll->period_start->format('d M Y').' to '.$payroll->period_end->format('d M Y'),
            'entries' => [
                ['ledger_id' => $payroll->deduction_ledger_id, 'debit' => Money::format($total), 'credit' => '0.00'],
                ['ledger_id' => $data['bank_ledger_id'], 'debit' => '0.00', 'credit' => Money::format($total)],
            ],
        ], true);
        $payroll->update(['statutory_voucher_id' => $voucher->id]);

        return back()->with('status', 'Challan posted as '.$voucher->voucher_number.'.');
    }

    public function destroy(WorkspaceContext $context, PayrollRun $payroll): RedirectResponse
    {
        abort_unless($payroll->company_id === $context->company()?->id, 404);

        if ($payroll->status === 'processed') {
            return back()->with('error', 'A processed payroll is posted to the books and cannot be deleted.');
        }

        $payroll->delete();

        return redirect()->route('books.tally.payroll.index')->with('status', 'Payroll draft deleted.');
    }
}
