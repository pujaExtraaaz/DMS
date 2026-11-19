<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Models\ChequeBook;
use App\Domains\Organization\Models\Company;
use App\Domains\Payment\Models\Cheque;
use App\Domains\Payment\Services\ChequeService;
use App\Http\Controllers\Controller;
use App\Support\ReportExporter;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChequeController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected ChequeService $chequeService) {}

    public function index(Request $request): View|StreamedResponse|JsonResponse
    {
        $companyId = auth()->user()?->company_id ?: Company::where('is_default', true)->value('id');
        $company = $companyId ? Company::find($companyId) : Company::first();

        $bankAccounts = collect($company?->bank_accounts ?? [])->filter(function ($b) {
            return filled($b['bank_account_no'] ?? null);
        })->values()->all();

        $query = Cheque::query()
            ->with(['customer', 'recorder', 'chequeBook'])
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->when($request->filled('bank_account_no'), fn ($q) => $q->where('bank_account_no', trim((string) $request->bank_account_no)))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('cheque_type'), function ($q) use ($request) {
                $type = $request->cheque_type;
                if ($type === Cheque::TYPE_ISSUED) {
                    $q->where(fn ($sub) => $sub->where('cheque_type', Cheque::TYPE_ISSUED)->orWhere('direction', 'issued_to_vendor'));
                } elseif ($type === Cheque::TYPE_RECEIVED) {
                    $q->where(fn ($sub) => $sub->where('cheque_type', Cheque::TYPE_RECEIVED)->orWhere('direction', '!=', 'issued_to_vendor'));
                }
            })
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'pending') {
                    $q->whereIn('status', ['pending', Cheque::STATUS_ISSUED, Cheque::STATUS_RECEIVED]);
                } else {
                    $q->where('status', $request->status);
                }
            })
            ->when($request->filled('purpose'), fn ($q) => $q->where('purpose', $request->purpose))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('cheque_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('cheque_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['cheque_no', 'bank_name', 'branch_name', 'purpose', 'status', 'notes', 'reference_voucher', 'bank_account_no'],
            ['customer' => ['name']]
        );

        $allowedSorts = [
            'cheque_no' => 'cheque_no',
            'bank_account_no' => 'bank_account_no',
            'purpose' => 'purpose',
            'status' => 'status',
            'amount' => 'amount',
            'cheque_date' => 'cheque_date',
            'deposit_date' => 'deposit_date',
            'created_at' => 'created_at',
            'customer' => function ($q, $dir) {
                $q->leftJoin('customers', 'cheques.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('cheques.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'created_at',
            defaultDirection: 'desc'
        );

        // CSV Export if requested
        if ($request->input('export') === 'csv' || $request->routeIs('cheques.export')) {
            return $this->exportCsv($query->get());
        }

        $items = $query->paginate(20)->withQueryString();

        $stats = $this->chequeService->getRegisterStatistics($companyId, $request->input('bank_account_no'));

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'items' => $items,
                'statistics' => $stats,
                'bank_accounts' => $bankAccounts,
            ]);
        }

        return view('payments.cheques.index', [
            'items' => $items,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'bankAccounts' => $bankAccounts,
            'company' => $company,
            'stats' => $stats,
            'filters' => $request->only(['search', 'bank_account_no', 'customer_id', 'cheque_type', 'status', 'purpose', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $companyId = auth()->user()?->company_id ?: Company::where('is_default', true)->value('id');
        $company = $companyId ? Company::find($companyId) : Company::first();

        $bankAccounts = collect($company?->bank_accounts ?? [])->filter(function ($b) {
            return filled($b['bank_account_no'] ?? null);
        })->values()->all();

        $chequeBooks = ChequeBook::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'active')
            ->orderBy('bank_account_no')
            ->orderBy('from_numeric')
            ->get();

        $type = $request->input('type', Cheque::TYPE_ISSUED);

        return view('payments.cheques.form', [
            'item' => new Cheque([
                'cheque_type' => $type,
                'direction' => $type === Cheque::TYPE_ISSUED ? 'issued_to_vendor' : 'received_from_client',
                'purpose' => 'payment',
                'cheque_date' => now()->toDateString(),
            ]),
            'company' => $company,
            'bankAccounts' => $bankAccounts,
            'chequeBooks' => $chequeBooks,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'cheque_type' => 'required|in:issued,received',
            'cheque_no' => 'required|string|max:50',
            'bank_account_no' => 'nullable|string|max:64',
            'cheque_book_id' => 'nullable|integer',
            'customer_id' => 'nullable|exists:customers,id',
            'purpose' => 'nullable|string|max:100',
            'direction' => 'nullable|in:received_from_client,received_from_vendor,issued_to_vendor',
            'amount' => 'required|numeric|min:0.01',
            'bank_name' => 'nullable|string|max:255',
            'branch_name' => 'nullable|string|max:255',
            'cheque_date' => 'nullable|date',
            'deposit_date' => 'nullable|date',
            'reference_voucher' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $companyId = auth()->user()?->company_id ?: Company::where('is_default', true)->value('id');
        $validated['company_id'] = $companyId;

        if ($validated['cheque_type'] === Cheque::TYPE_ISSUED && empty($validated['bank_account_no'])) {
            return back()->withInput()->with('error', 'Please select a company bank account when issuing a cheque.');
        }

        try {
            $cheque = $this->chequeService->create($validated);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->withInput()->with('error', $e->getMessage());
        }

        $typeLabel = $cheque->cheque_type === Cheque::TYPE_ISSUED ? 'issued' : 'received';
        $message = "Cheque #{$cheque->cheque_no} ({$typeLabel}) recorded successfully in Cheque Register.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $cheque,
            ], 201);
        }

        return redirect()->route('cheques.index')->with('status', $message);
    }

    public function show(Cheque $cheque): View
    {
        $cheque->load(['customer', 'recorder', 'bounces.recorder', 'payment', 'chequeBook']);

        return view('payments.cheques.show', ['item' => $cheque]);
    }

    public function deposit(Request $request, Cheque $cheque): RedirectResponse|JsonResponse
    {
        $depositDate = $request->input('deposit_date') ?: now()->toDateString();

        try {
            $this->chequeService->deposit($cheque, $depositDate);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque #{$cheque->cheque_no} marked as deposited on {$depositDate}.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'item' => $cheque->fresh()]);
        }

        return back()->with('status', $message);
    }

    public function clear(Request $request, Cheque $cheque): RedirectResponse|JsonResponse
    {
        $clearDate = $request->input('clearance_date') ?: now()->toDateString();

        try {
            $this->chequeService->clear($cheque, $clearDate);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque #{$cheque->cheque_no} marked as cleared.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'item' => $cheque->fresh()]);
        }

        return back()->with('status', $message);
    }

    public function bounce(Request $request, Cheque $cheque): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'reason' => 'nullable|string|max:255',
            'charges' => 'nullable|numeric|min:0',
        ]);

        try {
            $bounce = $this->chequeService->bounce(
                $cheque,
                $validated['reason'] ?? null,
                (float) ($validated['charges'] ?? 0)
            );
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque #{$cheque->cheque_no} bounce recorded. Party bounce count updated.";

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $cheque->fresh(),
                'bounce' => $bounce,
            ]);
        }

        return back()->with('status', $message);
    }

    public function cancel(Request $request, Cheque $cheque): RedirectResponse|JsonResponse
    {
        $reason = $request->input('reason');

        try {
            $this->chequeService->cancel($cheque, $reason);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque #{$cheque->cheque_no} cancelled.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'item' => $cheque->fresh()]);
        }

        return back()->with('status', $message);
    }

    public function markStale(Request $request, Cheque $cheque): RedirectResponse|JsonResponse
    {
        $reason = $request->input('reason');

        try {
            $this->chequeService->markStale($cheque, $reason);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return back()->with('error', $e->getMessage());
        }

        $message = "Cheque #{$cheque->cheque_no} marked as stale / expired.";

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message, 'item' => $cheque->fresh()]);
        }

        return back()->with('status', $message);
    }

    /**
     * Export Cheque Register entries as CSV matching the exact 11 columns required.
     */
    protected function exportCsv(iterable $cheques): StreamedResponse
    {
        $headers = [
            'Cheque Number',
            'Bank Account',
            'Cheque Date',
            'Party Name',
            'Cheque Type',
            'Amount (₹)',
            'Purpose',
            'Reference Voucher',
            'Deposit Date',
            'Status',
            'Remarks',
        ];

        $rows = [];
        foreach ($cheques as $cheque) {
            $rows[] = [
                $cheque->cheque_no,
                $cheque->bank_account_no ?: ($cheque->bank_name ?: '—'),
                $cheque->cheque_date ? $cheque->cheque_date->format('Y-m-d') : '—',
                $cheque->customer?->name ?: '—',
                ucfirst($cheque->cheque_type ?: ($cheque->direction === 'issued_to_vendor' ? 'Issued' : 'Received')),
                number_format((float) $cheque->amount, 2, '.', ''),
                ucfirst($cheque->purpose ?: '—'),
                $cheque->reference_voucher ?: ($cheque->payment?->payment_no ?: '—'),
                $cheque->deposit_date ? $cheque->deposit_date->format('Y-m-d') : '—',
                $cheque->status_label,
                $cheque->notes ?: ($cheque->cancelled_reason ?: ''),
            ];
        }

        $filename = 'cheque_register_'.now()->format('Ymd_His').'.csv';

        return ReportExporter::csv($filename, $headers, $rows);
    }
}
