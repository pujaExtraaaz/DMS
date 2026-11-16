<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ChequeBookController extends Controller
{
    public function index(Request $request, Company $company): JsonResponse
    {
        $accountNo = trim((string) $request->input('bank_account_no', ''));

        $query = $company->chequeBooks()->orderBy('from_numeric');

        if ($accountNo !== '') {
            $query->where('bank_account_no', $accountNo);
        }

        $items = $query->get();

        return response()->json([
            'success' => true,
            'items' => $items,
            'company_id' => $company->id,
            'bank_account_no' => $accountNo,
        ]);
    }

    public function store(Request $request, Company $company): RedirectResponse|JsonResponse
    {
        $data = $this->validateChequeBook($request, $company);

        $chequeBook = ChequeBook::create(array_merge($data, [
            'company_id' => $company->id,
            'created_by' => auth()->id(),
        ]));

        $name = $chequeBook->cheque_book_name ?: 'Cheque Book';
        $message = "{$name} ({$chequeBook->from_number} - {$chequeBook->to_number}) added successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $chequeBook,
            ], 201);
        }

        return back()->with('status', $message);
    }

    public function update(Request $request, Company $company, ChequeBook $chequeBook): RedirectResponse|JsonResponse
    {
        abort_unless((int) $chequeBook->company_id === (int) $company->id, 404);

        $data = $this->validateChequeBook($request, $company, $chequeBook);

        $chequeBook->update($data);

        $name = $chequeBook->cheque_book_name ?: 'Cheque Book';
        $message = "{$name} ({$chequeBook->from_number} - {$chequeBook->to_number}) updated successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $chequeBook->fresh(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function destroy(Request $request, Company $company, ChequeBook $chequeBook): RedirectResponse|JsonResponse
    {
        abort_unless((int) $chequeBook->company_id === (int) $company->id, 404);

        $name = $chequeBook->cheque_book_name ?: 'Cheque Book';
        $range = "{$chequeBook->from_number} - {$chequeBook->to_number}";
        $chequeBook->delete();

        $message = "{$name} ({$range}) deleted successfully.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    public function toggleStatus(Request $request, Company $company, ChequeBook $chequeBook): RedirectResponse|JsonResponse
    {
        abort_unless((int) $chequeBook->company_id === (int) $company->id, 404);

        $newStatus = $chequeBook->status === 'active' ? 'inactive' : 'active';
        $chequeBook->update(['status' => $newStatus]);

        $message = "Cheque book status updated to " . ucfirst($newStatus) . ".";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'status' => $newStatus,
                'item' => $chequeBook,
            ]);
        }

        return back()->with('status', $message);
    }

    protected function validateChequeBook(Request $request, Company $company, ?ChequeBook $existing = null): array
    {
        $validated = $request->validate([
            'bank_account_no' => 'required|string|max:64',
            'bank_name' => 'nullable|string|max:100',
            'cheque_book_name' => 'nullable|string|max:150',
            'from_number' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'to_number' => ['required', 'string', 'max:30', 'regex:/^[0-9]+$/'],
            'issue_date' => 'nullable|date',
            'status' => 'nullable|in:active,inactive',
            'remarks' => 'nullable|string|max:500',
        ], [
            'from_number.regex' => 'Starting cheque number must contain digits only.',
            'to_number.regex' => 'Ending cheque number must contain digits only.',
        ]);

        $bankAccountNo = trim((string) $validated['bank_account_no']);
        $fromNumber = trim((string) $validated['from_number']);
        $toNumber = trim((string) $validated['to_number']);

        $fromNumeric = (int) $fromNumber;
        $toNumeric = (int) $toNumber;

        if ($toNumeric < $fromNumeric) {
            throw ValidationException::withMessages([
                'to_number' => ['Ending cheque number must be greater than or equal to starting cheque number.'],
            ]);
        }

        // Prevent overlapping cheque ranges for this bank account
        $hasOverlap = ChequeBook::hasOverlap(
            $company->id,
            $bankAccountNo,
            $fromNumeric,
            $toNumeric,
            $existing?->id
        );

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'from_number' => ["Cheque number range ({$fromNumber} - {$toNumber}) overlaps with an existing cheque book for this bank account."],
            ]);
        }

        $totalCheques = $toNumeric - $fromNumeric + 1;

        return [
            'bank_account_no' => $bankAccountNo,
            'bank_name' => filled($validated['bank_name'] ?? null) ? trim((string) $validated['bank_name']) : null,
            'cheque_book_name' => filled($validated['cheque_book_name'] ?? null) ? trim((string) $validated['cheque_book_name']) : null,
            'from_number' => $fromNumber,
            'to_number' => $toNumber,
            'from_numeric' => $fromNumeric,
            'to_numeric' => $toNumeric,
            'total_cheques' => $totalCheques,
            'status' => $validated['status'] ?? ($existing?->status ?? 'active'),
            'issue_date' => filled($validated['issue_date'] ?? null) ? $validated['issue_date'] : null,
            'remarks' => filled($validated['remarks'] ?? null) ? trim((string) $validated['remarks']) : null,
        ];
    }
}

