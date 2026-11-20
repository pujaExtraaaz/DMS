<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Services\QuotationService;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use InvalidArgumentException;

class QuotationController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected QuotationService $quotationService,
        protected DocumentNumberService $documentNumberService,
    ) {}

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
            ['quotation_no', 'notes', 'title'],
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
        $company = Company::defaultCompany();
        $companyId = $company?->id ?: auth()->user()?->company_id;
        $qtnSeries = $this->quotationService->resolveSeries($companyId ? (int) $companyId : null);
        $nextNumber = ($qtnSeries && ($qtnSeries->isAutomatic() || $qtnSeries->isManualOverride()))
            ? $this->quotationService->peekNumber($companyId ? (int) $companyId : null)
            : null;

        $item = new Quotation([
            'quotation_date' => now()->toDateString(),
            'valid_until' => now()->addDays(10)->toDateString(),
            'status' => 'draft',
            'quotation_no' => $nextNumber,
            'special_instructions' => $this->quotationService->defaultSpecialInstructions(),
            'terms_conditions' => $this->quotationService->defaultTerms($company),
            'company_id' => $company?->id,
        ]);

        return view('sales.quotations.form', $this->formData($item));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);
        $validated = $this->attachUploadedLineImages($request, $validated);
        $quotation = $this->quotationService->create($validated, $request->user());

        return $this->flashSuccess('Quotation created.', 'quotations.show', ['quotation' => $quotation]);
    }

    public function show(Quotation $quotation): View
    {
        $quotation->load(['customer.area', 'salesperson', 'company', 'items.product', 'items.uom', 'convertedOrder']);
        $canViewProfit = auth()->user()?->hasRole('super-admin')
            || auth()->user()?->can('quotations.view-profit');

        return view('sales.quotations.show', [
            'item' => $quotation,
            'canViewProfit' => (bool) $canViewProfit,
        ]);
    }

    public function preview(Quotation $quotation): View
    {
        $quotation->load(['customer', 'company', 'items.product', 'items.uom', 'salesperson']);

        return view('sales.quotations.preview', [
            'item' => $quotation,
            'company' => $quotation->resolveCompany(),
        ]);
    }

    public function edit(Quotation $quotation): View|RedirectResponse
    {
        if (! $quotation->isEditable()) {
            return $this->flashError('Only draft or sent quotations can be edited.', 'quotations.show', ['quotation' => $quotation]);
        }

        $quotation->load(['items.product']);

        return view('sales.quotations.form', $this->formData($quotation));
    }

    public function update(Request $request, Quotation $quotation): RedirectResponse
    {
        try {
            $validated = $this->validated($request);
            $validated = $this->attachUploadedLineImages($request, $validated);
            $quotation = $this->quotationService->update($quotation, $validated, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Quotation updated.', 'quotations.show', ['quotation' => $quotation]);
    }

    public function destroy(Request $request, Quotation $quotation): RedirectResponse
    {
        try {
            $this->quotationService->delete($quotation, $request->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage(), 'quotations.show', ['quotation' => $quotation]);
        }

        return $this->flashSuccess('Quotation deleted.', 'quotations.index');
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
            $quotation = $this->quotationService->accept($quotation, request()->user());
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        } catch (ValidationException $e) {
            return $this->flashError(collect($e->errors())->flatten()->first() ?: $e->getMessage());
        }

        $order = $quotation->convertedOrder;
        if ($order) {
            return $this->flashSuccess(
                'Quotation accepted and sales order '.$order->order_no.' created.',
                'orders.show',
                ['order' => $order]
            );
        }

        return $this->flashSuccess('Quotation accepted.', 'quotations.show', ['quotation' => $quotation]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function formData(Quotation $item): array
    {
        $products = Product::with('baseUom')
            ->where('is_active', true)
            ->where(function ($q) use ($item) {
                $q->where('is_hidden', false);
                if ($item->exists) {
                    $q->orWhereIn('id', $item->items->pluck('product_id'));
                }
            })
            ->orderBy('name')
            ->get();

        $productPayload = $products->map(function (Product $product) {
            $description = trim((string) ($product->description ?: $product->specification ?: $product->name));
            $selling = (float) ($product->selling_price ?? 0);
            $trade = (float) ($product->trade_price ?? 0);

            return [
                'id' => (string) $product->id,
                'name' => $product->name,
                'sku' => $product->sku ?: '',
                'base_uom_id' => $product->base_uom_id ? (string) $product->base_uom_id : '',
                'hsn_code' => $product->hsn_code ?: '',
                'tax_rate' => $product->tax_rate !== null ? (float) $product->tax_rate : 0,
                'mrp' => (float) $product->mrp,
                'selling_price' => $selling > 0 ? $selling : null,
                'trade_price' => $trade > 0 ? $trade : null,
                'description' => $description,
                'image_path' => $product->image_path ?: '',
                'image_url' => $product->image_path ? asset('storage/'.$product->image_path) : '',
            ];
        })->values();

        $companyId = $item->company_id ?: Company::defaultCompanyId() ?: auth()->user()?->company_id;
        $numberSeries = $this->quotationService->resolveSeries($companyId ? (int) $companyId : null);
        $nextNumber = ($numberSeries && ($numberSeries->isAutomatic() || $numberSeries->isManualOverride()))
            ? $this->quotationService->peekNumber($companyId ? (int) $companyId : null)
            : null;

        return [
            'item' => $item,
            'qtnSeries' => $numberSeries,
            'numberSeries' => $numberSeries,
            'nextNumber' => $nextNumber,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'products' => $products,
            'productPayload' => $productPayload,
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
            'defaultSpecialInstructions' => $this->quotationService->defaultSpecialInstructions(),
            'defaultTerms' => $this->quotationService->defaultTerms($item->resolveCompany()),
            'isManualNumbering' => (bool) ($numberSeries && $numberSeries->isManual()),
        ];
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'quotation_no' => 'nullable|string|max:50',
            'customer_id' => 'required|exists:customers,id',
            'company_id' => 'nullable|exists:companies,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'nullable|date|after_or_equal:quotation_date',
            'title' => 'nullable|string|max:255',
            'discount_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            'special_instructions' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'created_by_name' => 'nullable|string|max:100',
            'updated_by_name' => 'nullable|string|max:100',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|min:0.0001',
            'items.*.unit_price' => 'nullable|numeric|min:0',
            'items.*.description' => 'nullable|string',
            'items.*.hsn_code' => 'nullable|string|max:32',
            'items.*.mrp' => 'nullable|numeric|min:0',
            'items.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'items.*.existing_image_path' => 'nullable|string|max:500',
            'items.*.image' => 'nullable|image|mimes:jpg,jpeg,png,webp,gif|max:4096',
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    protected function attachUploadedLineImages(Request $request, array $validated): array
    {
        $files = $request->file('items', []);
        if (! is_array($files) || $files === []) {
            return $validated;
        }

        foreach ($files as $index => $fileBag) {
            if (! is_array($fileBag) || empty($fileBag['image'])) {
                continue;
            }

            if (! isset($validated['items'][$index])) {
                continue;
            }

            $validated['items'][$index]['uploaded_image_path'] = $fileBag['image']->store('quotations/items', 'public');
        }

        return $validated;
    }
}
