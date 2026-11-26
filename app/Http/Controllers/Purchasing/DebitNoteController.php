<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\DebitNote;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Services\DebitNoteService;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DebitNoteController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected DebitNoteService $debitNoteService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $query = DebitNote::query()
            ->with(['supplier', 'purchaseInvoice'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->reason))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('debit_note_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('debit_note_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['debit_note_no', 'reason', 'status', 'notes'],
            ['supplier' => ['name'], 'purchaseInvoice' => ['invoice_no']]
        );

        $allowedSorts = [
            'debit_note_no' => 'debit_note_no',
            'reason' => 'reason',
            'status' => 'status',
            'grand_total' => 'grand_total',
            'debit_note_date' => 'debit_note_date',
            'supplier' => function ($q, $dir) {
                $q->join('customers', 'debit_notes.supplier_id', '=', 'customers.id')
                    ->orderBy('customers.name', $dir)
                    ->select('debit_notes.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'debit_note_date',
            defaultDirection: 'desc'
        );

        return view('purchasing.debit-notes.index', [
            'items' => $query->paginate(15)->withQueryString(),
            'suppliers' => Customer::query()->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'supplier_id', 'status', 'reason', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('purchasing.debit-notes.form', [
            'item' => new DebitNote([
                'debit_note_date' => now()->toDateString(),
                'supplier_id' => $request->input('supplier_id'),
                'purchase_invoice_id' => $request->input('purchase_invoice_id'),
                'reason' => 'return',
            ]),
            'suppliers' => Customer::query()->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'invoices' => PurchaseInvoice::with('supplier')->orderByDesc('invoice_date')->limit(200)->get(),
            'products' => Product::where('is_active', true)->where('is_hidden', false)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'debit_note_date' => 'required|date',
            'reason' => 'required|in:return,price,damage,shortage,other',
            'affects_stock' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_cost' => 'required|numeric|min:0',
            'items.*.tax_amount' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
        ]);

        $this->financialYearService->assertOpen($validated['debit_note_date']);
        $validated['affects_stock'] = $request->boolean('affects_stock');
        $validated['company_id'] = Customer::query()->whereKey($validated['supplier_id'])->value('company_id');

        $note = $this->debitNoteService->create($validated, $request->user());

        return $this->flashSuccess('Debit note created.', 'purchasing.debit-notes.show', ['debitNote' => $note]);
    }

    public function show(DebitNote $debitNote): View
    {
        $debitNote->load(['supplier', 'purchaseInvoice', 'items.product', 'items.uom', 'approver', 'warehouse']);

        return view('purchasing.debit-notes.show', ['item' => $debitNote]);
    }

    public function approve(DebitNote $debitNote): RedirectResponse
    {
        try {
            $this->debitNoteService->approve($debitNote, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Debit note approved.');
    }

    public function post(DebitNote $debitNote): RedirectResponse
    {
        try {
            $this->debitNoteService->post($debitNote, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Debit note posted to payables.');
    }

    public function cancel(DebitNote $debitNote): RedirectResponse
    {
        try {
            $this->debitNoteService->cancel($debitNote, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Debit note cancelled.');
    }
}
