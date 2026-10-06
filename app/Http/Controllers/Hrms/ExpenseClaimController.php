<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\ExpenseClaim;
use App\Http\Controllers\Controller;
use App\Support\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExpenseClaimController extends Controller
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function index(Request $request): View
    {
        $items = ExpenseClaim::query()
            ->with(['employee', 'approver', 'settler'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->latest('claim_date')
            ->paginate(15)
            ->withQueryString();

        return view('hrms.expense-claims.index', [
            'items' => $items,
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(),
        ]);
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
            'receipt_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:10240',
        ]);

        $receiptPath = null;
        if ($request->hasFile('receipt_file')) {
            $receiptPath = $request->file('receipt_file')->store('expense_receipts/' . $data['employee_id'], 'local');
        }

        $data['receipt_path'] = $receiptPath;
        $data['status'] = 'pending';
        unset($data['receipt_file']);

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
        $this->auditLogService->record($expense_claim, 'rejected');

        return $this->flashSuccess('Expense claim rejected.', 'hrms.expense-claims.index');
    }

    public function settle(Request $request, ExpenseClaim $expense_claim): RedirectResponse
    {
        if ($expense_claim->status !== 'approved') {
            return $this->flashError('Only approved claims can be marked as settled.');
        }

        $data = $request->validate([
            'settlement_amount' => 'required|numeric|min:0.01',
            'settlement_reference' => 'nullable|string|max:100',
            'settlement_notes' => 'nullable|string',
        ]);

        $expense_claim->update([
            'status' => 'settled',
            'settlement_amount' => $data['settlement_amount'],
            'settlement_reference' => $data['settlement_reference'] ?? null,
            'settlement_notes' => $data['settlement_notes'] ?? null,
            'settled_by' => auth()->id(),
            'settled_at' => now(),
        ]);

        $this->auditLogService->record($expense_claim, 'settled');

        return $this->flashSuccess('Expense claim settled successfully.', 'hrms.expense-claims.index');
    }

    public function downloadReceipt(ExpenseClaim $expense_claim): StreamedResponse|RedirectResponse
    {
        $user = auth()->user();
        $isOwner = $user && $expense_claim->employee?->user_id === $user->id;
        $hasPermission = $user && ($user->hasRole('super-admin') || $user->can('hrms.view') || $user->can('hrms.manage'));

        if (! $isOwner && ! $hasPermission) {
            abort(403, 'Unauthorized access to receipt file.');
        }

        if (! $expense_claim->receipt_path || ! Storage::disk('local')->exists($expense_claim->receipt_path)) {
            return $this->flashError('Receipt file not found.');
        }

        $extension = pathinfo($expense_claim->receipt_path, PATHINFO_EXTENSION);
        $fileName = 'receipt_claim_' . $expense_claim->id . '.' . $extension;

        return Storage::disk('local')->download($expense_claim->receipt_path, $fileName);
    }
}
