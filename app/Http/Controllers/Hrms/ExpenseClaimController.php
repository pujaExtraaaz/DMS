<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\ExpenseClaim;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseClaimController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $items = ExpenseClaim::query()
            ->with(['employee', 'approver'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('claim_date')
            ->paginate(15)
            ->withQueryString();

        return view('hrms.expense-claims.index', compact('items'));
    }

    public function create(): View
    {
        return view('hrms.expense-claims.form', [
            'item' => new ExpenseClaim(['claim_date' => now()->toDateString()]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'claim_date' => 'required|date',
            'claim_type' => 'required|string|max:80',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string',
        ]);
        $data['status'] = 'pending';

        $claim = ExpenseClaim::create($data);
        $this->auditLogService->record($claim, 'created');

        return $this->flashSuccess('Expense claim submitted.', 'hrms.expense-claims.index');
    }

    public function approve(Request $request, ExpenseClaim $expense_claim): RedirectResponse
    {
        if ($expense_claim->status !== 'pending') {
            return $this->flashError('Only pending claims can be approved.');
        }

        $expense_claim->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);
        $this->auditLogService->record($expense_claim, 'approved');

        return $this->flashSuccess('Expense claim approved.', 'hrms.expense-claims.index');
    }

    public function reject(Request $request, ExpenseClaim $expense_claim): RedirectResponse
    {
        if ($expense_claim->status !== 'pending') {
            return $this->flashError('Only pending claims can be rejected.');
        }

        $expense_claim->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_notes' => $request->input('approval_notes'),
        ]);

        return $this->flashSuccess('Expense claim rejected.', 'hrms.expense-claims.index');
    }
}
