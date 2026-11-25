<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Organization\Models\Company;
use App\Domains\Sales\Models\Quotation;
use App\Domains\Sales\Services\QuotationService;
use App\Http\Controllers\Controller;
use App\Support\DocumentExporter;
use App\Support\DocumentNumberService;
use App\Support\ReportExporter;
use App\Support\Traits\SortableAndSearchable;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Http\JsonResponse;
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

    public function index(Request $request): View|Response|StreamedResponse
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

        if ($export = $this->exportQuotations($request, $query)) {
            return $export;
        }

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

    public function destroy(Request $request, Quotation $quotation): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();
        if (! $user || (! $user->hasRole('super-admin') &&
            ! $user->can('quotations.delete') &&
            ! $user->can('quotations.manage') &&
            ! $user->can('sales.delete') &&
            ! $user->can('sales.manage'))) {
            $message = 'Unauthorized: You do not have permission to delete quotations.';
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }
            return back()->with('error', $message);
        }

        try {
            $this->quotationService->delete($quotation, $user);

            $message = 'Quotation deleted successfully.';

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'quotations.index')->with('success', $message);
        } catch (\InvalidArgumentException $e) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            $message = 'Failed to delete quotation: ' . $e->getMessage();
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }
            return back()->with('error', $message);
        }
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

    public function export(Quotation $quotation, string $format): Response|StreamedResponse
    {
        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            abort(404, 'Unsupported export format.');
        }

        return DocumentExporter::exportQuotation($quotation, $format);
    }

    protected function exportQuotations(Request $request, $query): Response|StreamedResponse|null
    {
        $format = strtolower((string) ($request->input('export') ?: $request->input('format') ?: ''));
        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return null;
        }

        $records = (clone $query)
            ->with(['customer', 'salesperson', 'convertedOrder', 'items.product', 'items.uom'])
            ->get();

        $quotationRows = $records->map(function (Quotation $q) {
            return [
                'Quotation No' => $q->quotation_no,
                'Quotation Date' => optional($q->quotation_date)->format('Y-m-d'),
                'Valid Until' => optional($q->valid_until)->format('Y-m-d'),
                'Status' => ucfirst((string) $q->status),
                'Title' => $q->title ?? '',
                'Customer Code' => $q->customer?->code ?? '',
                'Customer Name' => $q->customer?->name ?? '',
                'Customer Phone' => $q->customer?->phone ?? '',
                'Customer Email' => $q->customer?->email ?? '',
                'Customer GSTIN' => $q->customer?->tax_number ?? '',
                'Customer PAN' => $q->customer?->pan_number ?? '',
                'Customer Address' => trim(($q->customer?->address ?? '') . ' ' . ($q->customer?->city ?? '') . ' ' . ($q->customer?->pincode ?? '')),
                'Salesperson' => $q->salesperson?->name ?? '',
                'Subtotal (₹)' => number_format((float) $q->subtotal, 2, '.', ''),
                'Discount Amount (₹)' => number_format((float) $q->discount_amount, 2, '.', ''),
                'Tax Amount (₹)' => number_format((float) $q->tax_amount, 2, '.', ''),
                'Grand Total (₹)' => number_format((float) $q->grand_total, 2, '.', ''),
                'Estimated Profit (₹)' => number_format((float) $q->estimated_profit, 2, '.', ''),
                'Converted Order No' => $q->convertedOrder?->order_no ?? '',
                'Notes' => $q->notes ?? '',
                'Special Instructions' => $q->special_instructions ?? '',
                'Terms & Conditions' => $q->terms_conditions ?? '',
                'Created By' => $q->created_by_name ?? '',
                'Created At' => optional($q->created_at)->format('Y-m-d H:i:s'),
            ];
        })->all();

        $quotationCols = [
            'Quotation No' => 'Quotation No',
            'Quotation Date' => 'Quotation Date',
            'Valid Until' => 'Valid Until',
            'Status' => 'Status',
            'Title' => 'Title',
            'Customer Code' => 'Customer Code',
            'Customer Name' => 'Customer Name',
            'Customer Phone' => 'Customer Phone',
            'Customer Email' => 'Customer Email',
            'Customer GSTIN' => 'Customer GSTIN',
            'Customer PAN' => 'Customer PAN',
            'Customer Address' => 'Customer Address',
            'Salesperson' => 'Salesperson',
            'Subtotal (₹)' => 'Subtotal (₹)',
            'Discount Amount (₹)' => 'Discount Amount (₹)',
            'Tax Amount (₹)' => 'Tax Amount (₹)',
            'Grand Total (₹)' => 'Grand Total (₹)',
            'Estimated Profit (₹)' => 'Estimated Profit (₹)',
            'Converted Order No' => 'Converted Order No',
            'Notes' => 'Notes',
            'Special Instructions' => 'Special Instructions',
            'Terms & Conditions' => 'Terms & Conditions',
            'Created By' => 'Created By',
            'Created At' => 'Created At',
        ];

        if ($format === 'xlsx') {
            $itemRows = [];
            foreach ($records as $q) {
                $sr = 1;
                foreach ($q->items as $item) {
                    $itemRows[] = [
                        'Quotation No' => $q->quotation_no,
                        'Quotation Date' => optional($q->quotation_date)->format('Y-m-d'),
                        'Customer Name' => $q->customer?->name ?? '',
                        'Item #' => $item->sort_order ?: $sr++,
                        'Product Code' => $item->product?->code ?? '',
                        'Product Name' => $item->product?->name ?? '',
                        'Description' => $item->description ?? '',
                        'HSN/SAC' => $item->hsn_code ?? '',
                        'Quantity' => (float) $item->quantity,
                        'UOM' => $item->uom?->code ?? $item->uom?->name ?? '',
                        'Unit Price (₹)' => number_format((float) $item->unit_price, 2, '.', ''),
                        'MRP (₹)' => number_format((float) $item->mrp, 2, '.', ''),
                        'Discount (₹)' => number_format((float) $item->discount_amount, 2, '.', ''),
                        'Tax Rate (%)' => number_format((float) $item->tax_rate, 2, '.', ''),
                        'Line Total (₹)' => number_format((float) $item->line_total, 2, '.', ''),
                        'Est Landed Cost (₹)' => number_format((float) $item->estimated_landed_cost, 2, '.', ''),
                        'Est Profit (₹)' => number_format((float) $item->estimated_profit, 2, '.', ''),
                    ];
                }
            }

            $itemCols = [
                'Quotation No' => 'Quotation No',
                'Quotation Date' => 'Quotation Date',
                'Customer Name' => 'Customer Name',
                'Item #' => 'Item #',
                'Product Code' => 'Product Code',
                'Product Name' => 'Product Name',
                'Description' => 'Description',
                'HSN/SAC' => 'HSN/SAC',
                'Quantity' => 'Quantity',
                'UOM' => 'UOM',
                'Unit Price (₹)' => 'Unit Price (₹)',
                'MRP (₹)' => 'MRP (₹)',
                'Discount (₹)' => 'Discount (₹)',
                'Tax Rate (%)' => 'Tax Rate (%)',
                'Line Total (₹)' => 'Line Total (₹)',
                'Est Landed Cost (₹)' => 'Est Landed Cost (₹)',
                'Est Profit (₹)' => 'Est Profit (₹)',
            ];

            return DocumentExporter::exportMultiSheetExcel([
                'Quotations' => [
                    'columns' => $quotationCols,
                    'rows' => $quotationRows,
                ],
                'Quotation Items' => [
                    'columns' => $itemCols,
                    'rows' => $itemRows,
                ],
            ], 'quotations-master-' . now()->format('Y-m-d'));
        }

        if ($format === 'csv') {
            return ReportExporter::csv($quotationRows, array_values($quotationCols), 'quotations-' . now()->format('Y-m-d') . '.csv');
        }

        return ReportExporter::pdf($quotationRows, $quotationCols, 'Quotations Master Register', 'quotations-' . now()->format('Y-m-d') . '.pdf');
    }
}
