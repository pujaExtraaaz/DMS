<?php

namespace App\Http\Controllers\Payment;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Payment\Services\CreditNoteService;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class CreditNoteController extends Controller
{
    public function __construct(protected CreditNoteService $creditNoteService) {}

    public function index(Request $request): View
    {
        $items = CreditNote::query()
            ->with(['customer', 'invoice'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('credit_note_date')
            ->paginate(15)
            ->withQueryString();

        return view('payments.credit-notes.index', [
            'items' => $items,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('payments.credit-notes.form', [
            'item' => new CreditNote([
                'credit_note_date' => now()->toDateString(),
                'customer_id' => $request->input('customer_id'),
                'invoice_id' => $request->input('invoice_id'),
                'reason' => 'return',
            ]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'invoices' => Invoice::with('customer')->orderByDesc('invoice_date')->limit(200)->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'credit_note_date' => 'required|date',
            'reason' => 'required|in:return,price,scheme,damage,settlement,interest_reversal,other',
            'affects_stock' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        $validated['affects_stock'] = $request->boolean('affects_stock');
        $creditNote = $this->creditNoteService->create($validated, $request->user());

        return $this->flashSuccess('Credit note created.', 'credit-notes.show', ['credit_note' => $creditNote]);
    }

    public function show(CreditNote $credit_note): View
    {
        $credit_note->load(['customer', 'invoice', 'items.product', 'items.uom', 'approver']);

        return view('payments.credit-notes.show', ['item' => $credit_note]);
    }

    public function approve(CreditNote $credit_note): RedirectResponse
    {
        try {
            $this->creditNoteService->approve($credit_note, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Credit note approved.');
    }

    public function post(CreditNote $credit_note): RedirectResponse
    {
        try {
            $this->creditNoteService->post($credit_note, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Credit note posted to receivables.');
    }
}
