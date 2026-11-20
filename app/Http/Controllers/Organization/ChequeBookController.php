<?php

namespace App\Http\Controllers\Organization;

use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ChequeBookController extends Controller
{
    /**
     * Dedicated Cheque Book Management screen (Banking / Books / Setup).
     */
    public function manage(Request $request): View|JsonResponse
    {
        $companyId = $request->input('company_id') ?: (auth()->user()?->company_id ?: Company::query()->where('is_default', true)->value('id'));
        $company = $companyId ? Company::find($companyId) : Company::first();

        abort_unless($company, 404, 'No company configured. Please set up a company first.');

        $bankAccounts = collect($company->bank_accounts ?? [])->filter(function ($b) {
            return filled($b['bank_account_no'] ?? null);
        })->values()->all();

        $selectedAccount = trim((string) $request->input('bank_account_no', ''));
        $statusFilter = trim((string) $request->input('status', ''));
        $search = trim((string) $request->input('search', ''));

        $query = ChequeBook::query()
            ->where('company_id', $company->id)
            ->when($selectedAccount !== '', fn ($q) => $q->where('bank_account_no', $selectedAccount))
            ->when($statusFilter !== '', fn ($q) => $q->where('status', $statusFilter))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($sub) use ($search) {
                    $sub->where('cheque_book_name', 'like', "%{$search}%")
                        ->orWhere('from_number', 'like', "%{$search}%")
                        ->orWhere('to_number', 'like', "%{$search}%")
                        ->orWhere('bank_name', 'like', "%{$search}%")
                        ->orWhere('bank_account_no', 'like', "%{$search}%")
                        ->orWhere('remarks', 'like', "%{$search}%");
                });
            })
            ->orderBy('bank_account_no')
            ->orderBy('from_numeric');

        $items = $query->paginate(15)->withQueryString();

        // Calculate summary statistics across all cheque books of the company (or filtered account)
        $statsBooks = ChequeBook::query()
            ->where('company_id', $company->id)
            ->when($selectedAccount !== '', fn ($q) => $q->where('bank_account_no', $selectedAccount))
            ->get();

        $totalBooks = $statsBooks->count();
        $totalLeaves = $statsBooks->sum('total_cheques');
        $totalUsed = $statsBooks->sum(fn ($b) => $b->used_count);
        $totalCancelled = $statsBooks->sum(fn ($b) => $b->cancelled_count);
        $totalRemaining = $statsBooks->sum(fn ($b) => $b->remaining_count);
        $overallUtilization = $totalLeaves > 0 ? round((($totalUsed + $totalCancelled) / $totalLeaves) * 100, 1) : 0;

        $stats = [
            'total_books' => $totalBooks,
            'total_leaves' => $totalLeaves,
            'total_used' => $totalUsed,
            'total_cancelled' => $totalCancelled,
            'total_remaining' => $totalRemaining,
            'utilization_percent' => $overallUtilization,
        ];

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'statistics' => $stats,
                'company' => $company,
                'bank_accounts' => $bankAccounts,
            ]);
        }

        return view('banking.cheque-books.index', [
            'company' => $company,
            'bankAccounts' => $bankAccounts,
            'items' => $items,
            'stats' => $stats,
            'selectedAccount' => $selectedAccount,
            'statusFilter' => $statusFilter,
            'search' => $search,
        ]);
    }

    /**
     * API/JSON: List cheque books for a given company.
     */
    public function index(Request $request, Company $company): JsonResponse
    {
        $accountNo = trim((string) $request->input('bank_account_no', ''));

        $query = $company->chequeBooks()->orderBy('from_numeric');

        if ($accountNo !== '') {
            $query->where('bank_account_no', $accountNo);
        }

        $items = $query->get()->map(function (ChequeBook $cb) {
            return array_merge($cb->toArray(), [
                'used_count' => $cb->used_count,
                'cancelled_count' => $cb->cancelled_count,
                'remaining_count' => $cb->remaining_count,
                'utilization_percent' => $cb->utilization_percent,
                'next_available_cheque_number' => $cb->next_available_cheque_number,
            ]);
        });

        return response()->json([
            'success' => true,
            'items' => $items,
            'company_id' => $company->id,
            'bank_account_no' => $accountNo,
        ]);
    }

    /**
     * API/JSON: Get available active cheque numbers for a specific bank account.
     */
    public function availableNumbers(Request $request): JsonResponse
    {
        $accountNo = trim((string) $request->input('bank_account_no', ''));
        $companyId = $request->input('company_id') ?: (auth()->user()?->company_id ?: Company::query()->where('is_default', true)->value('id'));

        if ($accountNo === '') {
            return response()->json([
                'success' => false,
                'message' => 'Bank account number is required.',
                'books' => [],
                'suggested_cheque_no' => null,
            ], 422);
        }

        $books = ChequeBook::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('bank_account_no', $accountNo)
            ->where('status', 'active')
            ->orderBy('from_numeric')
            ->get();

        $booksData = [];
        $firstSuggested = null;

        foreach ($books as $cb) {
            $avail = $cb->availableChequeNumbers();
            if ($firstSuggested === null && ! empty($avail)) {
                $firstSuggested = $avail[0];
            }

            $booksData[] = [
                'id' => $cb->id,
                'name' => $cb->cheque_book_name ?: "Book ({$cb->from_number} - {$cb->to_number})",
                'from_number' => $cb->from_number,
                'to_number' => $cb->to_number,
                'total_cheques' => $cb->total_cheques,
                'used_count' => $cb->used_count,
                'cancelled_count' => $cb->cancelled_count,
                'remaining_count' => count($avail),
                'next_available' => $cb->next_available_cheque_number,
                'available_numbers' => $avail,
            ];
        }

        return response()->json([
            'success' => true,
            'bank_account_no' => $accountNo,
            'books' => $booksData,
            'suggested_cheque_no' => $firstSuggested,
        ]);
    }

    /**
     * Store a new cheque book.
     */
    public function store(Request $request, ?Company $company = null): RedirectResponse|JsonResponse
    {
        if (! $company) {
            $companyId = $request->input('company_id') ?: (auth()->user()?->company_id ?: Company::query()->where('is_default', true)->value('id'));
            $company = $companyId ? Company::findOrFail($companyId) : Company::firstOrFail();
        }

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

    /**
     * Update an existing cheque book.
     */
    public function update(Request $request, mixed $param1, mixed $param2 = null): RedirectResponse|JsonResponse
    {
        $chequeBook = ($param2 instanceof ChequeBook)
            ? $param2
            : (($param1 instanceof ChequeBook) ? $param1 : ChequeBook::findOrFail($param2 ?: $param1));

        $company = $chequeBook->company;
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

    /**
     * Delete a cheque book.
     */
    public function destroy(Request $request, mixed $param1, mixed $param2 = null): RedirectResponse|JsonResponse
    {
        $chequeBook = ($param2 instanceof ChequeBook)
            ? $param2
            : (($param1 instanceof ChequeBook) ? $param1 : ChequeBook::findOrFail($param2 ?: $param1));

        if ($chequeBook->used_count > 0) {
            $msg = "Cannot delete cheque book ({$chequeBook->from_number} - {$chequeBook->to_number}) because {$chequeBook->used_count} cheque leaves have already been issued.";
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return back()->with('error', $msg);
        }

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

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(Request $request, mixed $param1, mixed $param2 = null): RedirectResponse|JsonResponse
    {
        $chequeBook = ($param2 instanceof ChequeBook)
            ? $param2
            : (($param1 instanceof ChequeBook) ? $param1 : ChequeBook::findOrFail($param2 ?: $param1));

        $newStatus = $chequeBook->status === 'active' ? 'inactive' : 'active';
        $chequeBook->update(['status' => $newStatus]);

        $message = 'Cheque book status updated to '.ucfirst($newStatus).'.';

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

    /**
     * Cancel / void an individual unused cheque leaf.
     */
    public function cancelLeaf(Request $request, ChequeBook $chequeBook): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'cheque_number' => ['required', 'string', 'max:30'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $chequeNo = trim((string) $validated['cheque_number']);
        $reason = trim((string) ($validated['reason'] ?? 'Leaf spoiled / cancelled by user'));

        try {
            $cancelledRecord = $chequeBook->cancelLeaf($chequeNo, $reason, auth()->id());
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque leaf #{$chequeNo} has been marked as cancelled/spoiled.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $cancelledRecord,
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
