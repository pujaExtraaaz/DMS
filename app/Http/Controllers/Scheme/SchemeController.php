<?php

namespace App\Http\Controllers\Scheme;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Product;
use App\Domains\Payment\Models\CreditNote;
use App\Domains\Sales\Models\Invoice;
use App\Domains\Scheme\Models\Scheme;
use App\Domains\Scheme\Services\SchemeService;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class SchemeController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected SchemeService $schemeService,
    ) {}

    public function index(Request $request): View
    {
        $query = Scheme::query()
            ->with('brand')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('scheme_type'), fn ($q) => $q->where('scheme_type', $request->scheme_type))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->brand_id));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['code', 'name', 'status', 'description'],
            ['brand' => ['name']]
        );

        $allowedSorts = [
            'code' => 'code',
            'name' => 'name',
            'status' => 'status',
            'starts_on' => 'starts_on',
            'brand' => function ($q, $dir) {
                $q->leftJoin('brands', 'schemes.brand_id', '=', 'brands.id')
                  ->orderBy('brands.name', $dir)
                  ->select('schemes.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('schemes.index', [
            'items' => $items,
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'status', 'brand_id', 'scheme_type']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('schemes.form', [
            'item' => new Scheme([
                'status' => 'draft',
                'basis' => 'value',
                'net_credit_notes' => true,
                'starts_on' => now()->toDateString(),
                'ends_on' => now()->endOfMonth()->toDateString(),
            ]),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->orderBy('name')->limit(500)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:40|unique:schemes,code',
            'name' => 'required|string|max:255',
            'scheme_type' => 'nullable|string|in:brand_based,product_based,brand,product',
            'brand_id' => 'nullable|exists:brands,id',
            'starts_on' => 'required|date',
            'ends_on' => 'required|date|after_or_equal:starts_on',
            'status' => 'nullable|in:draft,active,closed,cancelled',
            'basis' => 'required|in:quantity,value',
            'net_credit_notes' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id',
            'slabs' => 'nullable|array',
            'slabs.*.from_value' => 'required_with:slabs|numeric|min:0',
            'slabs.*.to_value' => 'nullable|numeric|min:0',
            'slabs.*.benefit_percent' => 'nullable|numeric|min:0',
            'slabs.*.benefit_amount' => 'nullable|numeric|min:0',
        ]);

        if (! empty($validated['scheme_type'])) {
            $validated['scheme_type'] = match ($validated['scheme_type']) {
                'brand', 'brand_based' => 'brand_based',
                'product', 'product_based' => 'product_based',
                default => null,
            };

            if ($validated['scheme_type'] === 'brand_based') {
                $validated['product_ids'] = [];
            } elseif ($validated['scheme_type'] === 'product_based') {
                $validated['brand_id'] = null;
            }
        } else {
            $validated['scheme_type'] = null;
        }

        $validated['net_credit_notes'] = $request->boolean('net_credit_notes');
        $scheme = $this->schemeService->create($validated, $request->user());

        return $this->flashSuccess('Scheme created.', 'schemes.show', ['scheme' => $scheme]);
    }

    public function show(Scheme $scheme): View
    {
        $scheme->load([
            'brand',
            'slabs',
            'products.product',
            'achievements.customer',
            'achievements.invoice',
            'settlements.customer',
        ]);

        return view('schemes.show', ['item' => $scheme]);
    }

    public function activate(Scheme $scheme): RedirectResponse
    {
        if (! in_array($scheme->status, ['draft', 'cancelled'], true)) {
            return $this->flashError('Only draft schemes can be activated.');
        }

        $scheme->update(['status' => 'active']);

        return $this->flashSuccess('Scheme activated.');
    }

    public function finalize(Scheme $scheme): RedirectResponse
    {
        try {
            $this->schemeService->finalizeScheme($scheme);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Scheme finalized from provisional snapshots.');
    }

<<<<<<< HEAD
    public function provisional(Request $request, Scheme $scheme): RedirectResponse
    {
        if ($scheme->status !== 'active') {
            return $this->flashError('Only active schemes can calculate provisional achievements.');
        }

        $data = $request->validate([
            'invoice_id' => 'required|integer|exists:invoices,id',
        ]);

        $invoice = \App\Domains\Sales\Models\Invoice::query()->findOrFail($data['invoice_id']);

        try {
            $created = $this->schemeService->provisionalForInvoice($invoice);
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        $count = is_countable($created) ? count($created) : 0;

        return $this->flashSuccess(
            $count > 0
                ? "Provisional calculation created {$count} achievement row(s)."
                : 'No provisional achievements matched this invoice.'
        );
=======
    public function calculateProvisional(Request $request, Scheme $scheme): RedirectResponse
    {
        $request->validate([
            'invoice_id' => 'required',
        ]);

        $invoiceIdentifier = trim((string) $request->input('invoice_id'));
        $invoice = Invoice::with(['items.product', 'customer'])
            ->where('id', $invoiceIdentifier)
            ->orWhere('invoice_no', $invoiceIdentifier)
            ->first();

        if (! $invoice) {
            return $this->flashError("Invoice '{$invoiceIdentifier}' was not found.");
        }

        if ($scheme->status !== 'active') {
            return $this->flashError('Provisional achievements can only be calculated for active schemes.');
        }

        if ($invoice->status === 'cancelled') {
            return $this->flashError('Cannot calculate scheme achievement for a cancelled invoice.');
        }

        $evalItems = $invoice->items->map(fn ($item) => [
            'product_id' => $item->product_id,
            'brand_id' => $item->product?->brand_id,
            'quantity' => (float) $item->quantity,
            'unit_price' => (float) $item->unit_price,
            'discount_amount' => (float) $item->discount_amount,
            'line_total' => (float) $item->line_total,
        ])->all();

        $eval = $this->schemeService->evaluateScheme($scheme, $invoice->invoice_date, $evalItems);

        if (! $eval['eligible']) {
            return $this->flashError($eval['reason'] ?? 'Invoice is not eligible for this scheme.');
        }

        $qualified = $eval['qualified'];
        $slab = $eval['slab'];
        $benefit = $eval['benefit'];

        if ($scheme->net_credit_notes) {
            $cn = (float) CreditNote::query()
                ->where('customer_id', $invoice->customer_id)
                ->where('invoice_id', $invoice->id)
                ->where('status', 'posted')
                ->sum('grand_total');

            if ($cn > 0) {
                $qualified = max(0, $qualified - $cn);
                $slab = $this->schemeService->matchSlab($scheme, $qualified);
                $benefit = $slab ? $this->schemeService->benefitFor($slab, $qualified) : 0;
                if (! $slab || $benefit <= 0) {
                    return $this->flashError("After deducting credit notes (₹" . number_format($cn, 2) . "), invoice no longer qualifies for this scheme.");
                }
            }
        }

        $this->schemeService->applySchemeToInvoice(
            $invoice,
            $scheme,
            $qualified,
            $slab,
            $benefit
        );

        $invoice->update([
            'scheme_id' => $scheme->id,
            'scheme_benefit_amount' => $benefit,
        ]);

        return $this->flashSuccess("Provisional achievement (₹" . number_format($benefit, 2) . ") calculated successfully for invoice {$invoice->invoice_no}.");
>>>>>>> aa321ea5d5d641e2b160405edaf720832da9a01f
    }
}

