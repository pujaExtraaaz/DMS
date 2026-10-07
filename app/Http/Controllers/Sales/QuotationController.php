<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Services\QuotationService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

use App\Support\Traits\SortableAndSearchable;

class QuotationController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected QuotationService $quotationService) {}

    public function index(Request $request): View
    {
        $query = Quotation::query()
            ->with(['customer', 'salesperson'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('quotation_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('quotation_date', '<=', $request->date_to));

        $this->applySearch(
            $query,
            $request->input('search') ?: $request->input('q'),
            ['quotation_no', 'notes'],
            ['customer' => ['name', 'code']]
        );

        $allowedSorts = [
            'quotation_no' => 'quotation_no',
            'quotation_date' => 'quotation_date',
            'status' => 'status',
            'grand_total' => 'grand_total',
            'created_at' => 'created_at',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'quotations.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('quotations.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'quotation_date',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('sales.quotations.index', [
            'items' => $items,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search', $request->string('q')),
            'status' => $request->string('status'),
            'customerId' => $request->input('customer_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('sales.quotations.form', [
            'item' => new Quotation([
                'quotation_date' => now()->toDateString(),
                'status' => 'draft',
            ]),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('baseUom')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $quotation = $this->quotationService->create($validated, $request->user());

        return $this->flashSuccess('Quotation created.', 'quotations.show', ['quotation' => $quotation]);
    }

    public function show(Quotation $quotation): View
    {
        $quotation->load(['customer.area', 'salesperson', 'items.product', 'items.uom']);
        $canViewProfit = auth()->user()?->hasRole('super-admin')
            || auth()->user()?->can('quotations.view-profit');

        return view('sales.quotations.show', [
            'item' => $quotation,
            'canViewProfit' => (bool) $canViewProfit,
        ]);
    }

    public function edit(Quotation $quotation): View
    {
        $quotation->load(['items']);

        return view('sales.quotations.form', [
            'item' => $quotation,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::with('baseUom')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        try {
            $validated = $this->validated($request);
            $quotation = $this->quotationService->update($quotation, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Quotation updated.', 'quotations.show', ['quotation' => $quotation]);
    }

    public function send(Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->markSent($quotation, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Quotation marked as sent.');
    }

    public function accept(Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->accept($quotation, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Quotation accepted.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:quotation_date',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'created_by_name' => 'nullable|string|max:100',
            'updated_by_name' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
        ]);
    }
}
