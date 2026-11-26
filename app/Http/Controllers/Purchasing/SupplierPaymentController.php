<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Organization\Services\FinancialYearService;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Domains\Purchasing\Models\SupplierPayment;
use App\Domains\Purchasing\Services\SupplierPaymentService;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SupplierPaymentController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected SupplierPaymentService $supplierPaymentService,
        protected FinancialYearService $financialYearService,
    ) {}

    public function index(Request $request): View
    {
        $query = SupplierPayment::query()
            ->with(['supplier', 'purchaseInvoice'])
            ->when($request->filled('supplier_id'), fn ($q) => $q->where('supplier_id', $request->supplier_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('method'), fn ($q) => $q->where('method', $request->method))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('payment_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('payment_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['payment_no', 'reference_no', 'method', 'status', 'notes'],
            ['supplier' => ['name'], 'purchaseInvoice' => ['invoice_no']]
        );

        $allowedSorts = [
            'payment_no' => 'payment_no',
            'amount' => 'amount',
            'method' => 'method',
            'status' => 'status',
            'payment_date' => 'payment_date',
            'supplier' => function ($q, $dir) {
                $q->join('customers', 'supplier_payments.supplier_id', '=', 'customers.id')
                    ->orderBy('customers.name', $dir)
                    ->select('supplier_payments.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'payment_date',
            defaultDirection: 'desc'
        );

        return view('purchasing.supplier-payments.index', [
            'items' => $query->paginate(15)->withQueryString(),
            'suppliers' => Customer::query()->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'supplier_id', 'status', 'method', 'date_from', 'date_to']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        return view('purchasing.supplier-payments.form', [
            'item' => new SupplierPayment([
                'payment_date' => now()->toDateString(),
                'supplier_id' => $request->input('supplier_id'),
                'purchase_invoice_id' => $request->input('purchase_invoice_id'),
                'method' => 'bank',
            ]),
            'suppliers' => Customer::query()->suppliers()->where('is_active', true)->orderBy('name')->get(),
            'invoices' => PurchaseInvoice::with('supplier')
                ->where('status', 'posted')
                ->orderByDesc('invoice_date')
                ->limit(200)
                ->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'method' => 'required|in:cash,upi,bank,cheque,other',
            'reference_no' => 'nullable|string|max:80',
            'notes' => 'nullable|string',
        ]);

        $validated['company_id'] = Customer::query()->whereKey($validated['supplier_id'])->value('company_id');

        try {
            $payment = $this->supplierPaymentService->create($validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Supplier payment recorded.', 'purchasing.supplier-payments.show', ['supplierPayment' => $payment]);
    }

    public function show(SupplierPayment $supplierPayment): View
    {
        $supplierPayment->load(['supplier', 'purchaseInvoice', 'recorder']);

        return view('purchasing.supplier-payments.show', ['item' => $supplierPayment]);
    }

    public function cancel(SupplierPayment $supplierPayment): RedirectResponse
    {
        try {
            $this->supplierPaymentService->cancel($supplierPayment, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Supplier payment cancelled.');
    }
}
