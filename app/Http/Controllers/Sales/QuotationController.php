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

class QuotationController extends Controller
{
    public function __construct(protected QuotationService $quotationService) {}

    public function index(Request $request): View
    {
        $items = Quotation::query()
            ->with(['customer', 'salesperson'])
            ->when($request->filled('q'), function ($q) use ($request) {
                $search = trim($request->q);
                $q->where(function ($inner) use ($search) {
                    $inner->where('quotation_no', 'like', "%{$search}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"));
                });
            })
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest('quotation_date')
            ->paginate(15)
            ->withQueryString();

        return view('sales.quotations.index', compact('items'));
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
