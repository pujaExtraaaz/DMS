<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Purchasing\Models\PurchaseComparison;
use App\Domains\Purchasing\Models\PurchaseComparisonFile;
use App\Domains\Purchasing\Services\PurchaseComparisonService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PurchaseComparisonController extends Controller
{
    public function __construct(private readonly PurchaseComparisonService $comparisons) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $sheets = PurchaseComparison::query()
            ->with(['preparer', 'vendors.supplier'])
            ->withCount('items')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('number', 'like', "%{$search}%")
                        ->orWhere('indent_no', 'like', "%{$search}%")
                        ->orWhere('customer_project', 'like', "%{$search}%");
                });
            })
            ->latest('comparison_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('purchasing.comparisons.index', compact('sheets', 'search'));
    }

    public function create(): View
    {
        return view('purchasing.comparisons.form', $this->formData());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $sheet = $this->comparisons->save(null, $data, $request->user(), $this->uploadedFiles($request));

        if ($request->boolean('submit_for_approval')) {
            $this->comparisons->submit($sheet, $request->user());
        }

        return $this->flashSuccess('Purchase comparison saved.', 'purchasing.comparisons.show', ['comparison' => $sheet]);
    }

    public function show(PurchaseComparison $comparison): View
    {
        $comparison->load(['preparer', 'editor', 'approver', 'events.user', 'orders.purchaseOrder', 'orders.vendor.supplier']);
        $matrix = $this->comparisons->present($comparison);

        return view('purchasing.comparisons.show', compact('comparison', 'matrix'));
    }

    public function edit(PurchaseComparison $comparison): View
    {
        return view('purchasing.comparisons.form', $this->formData($comparison));
    }

    public function update(Request $request, PurchaseComparison $comparison): RedirectResponse
    {
        $data = $this->validated($request);
        $sheet = $this->comparisons->save(
            $comparison,
            $data,
            $request->user(),
            $this->uploadedFiles($request),
            array_map('intval', $request->input('remove_files', [])),
        );

        if ($request->boolean('submit_for_approval')) {
            $this->comparisons->submit($sheet, $request->user());
        }

        return $this->flashSuccess('Purchase comparison saved.', 'purchasing.comparisons.show', ['comparison' => $sheet]);
    }

    public function submit(Request $request, PurchaseComparison $comparison): RedirectResponse
    {
        $this->comparisons->submit($comparison, $request->user());

        return $this->flashSuccess('Comparison sent for approval.', 'purchasing.comparisons.show', ['comparison' => $comparison]);
    }

    public function approve(Request $request, PurchaseComparison $comparison): RedirectResponse
    {
        $data = $request->validate(['executive_remarks' => ['nullable', 'string', 'max:2000']]);
        $this->comparisons->approve($comparison, $request->user(), $data['executive_remarks'] ?? null);

        return $this->flashSuccess('Comparison approved.', 'purchasing.comparisons.show', ['comparison' => $comparison]);
    }

    public function reject(Request $request, PurchaseComparison $comparison): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $this->comparisons->reject($comparison, $request->user(), $data['rejection_reason']);

        return $this->flashSuccess('Comparison rejected.', 'purchasing.comparisons.show', ['comparison' => $comparison]);
    }

    public function convert(Request $request, PurchaseComparison $comparison): RedirectResponse
    {
        $links = $this->comparisons->convertToPurchaseOrders($comparison, $request->user());
        $count = count($links);

        return $this->flashSuccess(
            $count === 1 ? 'Purchase order created.' : "{$count} purchase orders created.",
            'purchasing.comparisons.show',
            ['comparison' => $comparison],
        );
    }

    public function supplier(Customer $customer): JsonResponse
    {
        abort_unless($customer->isSupplier(), 404);

        return response()->json($this->comparisons->supplierSnapshot($customer));
    }

    public function file(PurchaseComparison $comparison, PurchaseComparisonFile $file): StreamedResponse
    {
        abort_unless($file->vendor && (int) $file->vendor->purchase_comparison_id === (int) $comparison->id, 404);

        return response()->download(storage_path('app/'.$file->path), $file->original_name);
    }

    public function print(PurchaseComparison $comparison): View
    {
        $comparison->load(['preparer', 'approver']);
        $matrix = $this->comparisons->present($comparison);

        return view('purchasing.comparisons.print', compact('comparison', 'matrix'));
    }

    public function pdf(PurchaseComparison $comparison)
    {
        $comparison->load(['preparer', 'approver']);
        $matrix = $this->comparisons->present($comparison);
        $pdf = Pdf::loadView('purchasing.comparisons.print', [
            'comparison' => $comparison,
            'matrix' => $matrix,
            'forPdf' => true,
        ])->setPaper('a4', 'landscape');

        return $pdf->download($comparison->number.'.pdf');
    }

    public function excel(PurchaseComparison $comparison): StreamedResponse
    {
        $matrix = $this->comparisons->present($comparison);
        $sheet = new Spreadsheet();
        $grid = $sheet->getActiveSheet();
        $grid->setTitle('Comparison');

        $perVendor = ['basic rate', 'net rate', 'freight', 'other charges', 'pre-GST', 'GST amount', 'landed cost', 'per unit', 'warranty', 'delivery', 'stock'];
        $headers = ['Item', 'Model / SKU', 'Required qty'];
        foreach ($matrix['vendors'] as $vendor) {
            foreach ($perVendor as $label) {
                $headers[] = $vendor->supplier?->name.' '.$label;
            }
        }
        array_push($headers, 'Lowest basic', 'Highest basic', 'Lowest net', 'Lowest landed', 'Lowest landed vendor');
        $grid->fromArray($headers, null, 'A1');
        $grid->getStyle('A1:'.$grid->getHighestColumn().'1')->getFont()->setBold(true);
        $grid->getStyle('A1:'.$grid->getHighestColumn().'1')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('1E3A5F');
        $grid->getStyle('A1:'.$grid->getHighestColumn().'1')->getFont()->getColor()->setRGB('FFFFFF');

        $rowNumber = 2;
        $groupSize = count($perVendor);
        foreach ($matrix['rows'] as $row) {
            $line = [$row['item']->item_name, $row['item']->model_sku, (float) $row['item']->required_quantity];
            foreach ($matrix['vendors'] as $vendor) {
                $cell = $row['cells'][$vendor->id];
                if (! $cell['math']) {
                    $line = array_merge($line, array_fill(0, $groupSize, 'Not quoted'));
                    continue;
                }
                $line[] = $cell['math']['basic_rate'];
                $line[] = $cell['math']['net_rate'];
                $line[] = $cell['math']['freight'];
                $line[] = $cell['math']['charges'];
                $line[] = $cell['math']['pre_gst'];
                $line[] = $cell['math']['gst_amount'];
                $line[] = $cell['math']['landed'];
                $line[] = $cell['math']['per_unit'];
                $line[] = trim(($cell['quote']->warranty_period ?? '').' '.str_replace('_', ' ', (string) $cell['quote']->warranty_type));
                $line[] = $cell['quote']->delivery_time ?: $cell['quote']->delivery_date?->format('d M Y');
                $line[] = $cell['quote']->stock_availability ? str_replace('_', ' ', $cell['quote']->stock_availability) : '';
            }
            $line[] = $row['min_basic'];
            $line[] = $row['max_basic'];
            $line[] = $row['min_net'];
            $line[] = $row['min_landed'];
            $line[] = $row['lowest_vendor'];
            $grid->fromArray($line, null, 'A'.$rowNumber);

            $column = 4;
            foreach ($matrix['vendors'] as $vendor) {
                if ($row['cells'][$vendor->id]['is_lowest'] ?? false) {
                    $cell = Coordinate::stringFromColumnIndex($column + 6).$rowNumber;
                    $grid->getStyle($cell)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('BBF7D0');
                }
                $column += $groupSize;
            }
            $rowNumber++;
        }

        $lastColumn = Coordinate::columnIndexFromString($grid->getHighestColumn());
        for ($index = 1; $index <= $lastColumn; $index++) {
            $grid->getColumnDimension(Coordinate::stringFromColumnIndex($index))->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($sheet) {
            (new Xlsx($sheet))->save('php://output');
        }, $comparison->number.'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(?PurchaseComparison $sheet = null): array
    {
        $sheet?->load(['vendors.files', 'items.quotes', 'items.product']);

        $products = Product::query()
            ->with(['brand:id,name', 'category:id,name', 'subCategory:id,name'])
            ->orderBy('name')
            ->limit(1500)
            ->get(['id', 'name', 'sku', 'model', 'brand_id', 'category_id', 'sub_category_id', 'base_uom_id', 'tax_rate', 'purchase_price'])
            ->map(fn (Product $product) => [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'model' => $product->model,
                'brand' => $product->brand?->name,
                'category' => $product->category?->name,
                'subcategory' => $product->subCategory?->name,
                'uom_id' => $product->base_uom_id,
                'tax_rate' => $product->tax_rate,
                'purchase_price' => $product->purchase_price,
            ])
            ->values();

        return [
            'sheet' => $sheet,
            'suppliers' => Customer::query()->suppliers()->orderBy('name')->get(['id', 'name', 'gstin']),
            'uoms' => Uom::query()->orderBy('name')->get(['id', 'name']),
            'products' => $products,
            'state' => old() ? $this->stateFromOld() : $this->stateFromSheet($sheet),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $validator = validator($request->all(), [
            'comparison_date' => ['required', 'date'],
            'indent_no' => ['nullable', 'string', 'max:60'],
            'customer_project' => ['nullable', 'string', 'max:255'],
            'required_delivery_date' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'executive_remarks' => ['nullable', 'string', 'max:5000'],
            'vendors' => ['required', 'array', 'min:2', 'max:10'],
            'vendors.*.id' => ['nullable', 'integer'],
            'vendors.*.supplier_id' => ['required', 'integer', Rule::exists('customers', 'id')],
            'vendors.*.billing_address' => ['nullable', 'string', 'max:2000'],
            'vendors.*.dispatch_address' => ['nullable', 'string', 'max:2000'],
            'vendors.*.contact_person' => ['nullable', 'string', 'max:255'],
            'vendors.*.mobile' => ['nullable', 'string', 'max:30'],
            'vendors.*.email' => ['nullable', 'email', 'max:255'],
            'vendors.*.gstin' => ['nullable', 'string', 'max:20'],
            'vendors.*.quotation_number' => ['nullable', 'string', 'max:60'],
            'vendors.*.quotation_date' => ['nullable', 'date'],
            'vendors.*.valid_until' => ['nullable', 'date'],
            'vendors.*.credit_period' => ['nullable', 'string', 'max:40'],
            'vendors.*.advance_payment' => ['nullable', 'string', 'max:80'],
            'vendors.*.credit_limit_note' => ['nullable', 'string', 'max:255'],
            'vendors.*.replacement_policy' => ['nullable', 'string', 'max:255'],
            'vendors.*.return_policy' => ['nullable', 'string', 'max:255'],
            'vendors.*.installation_support' => ['nullable', 'string', 'max:255'],
            'vendors.*.transporter' => ['nullable', 'string', 'max:255'],
            'vendors.*.freight_paid_by' => ['nullable', Rule::in(['vendor', 'company'])],
            'vendors.*.delivery_location' => ['nullable', 'string', 'max:255'],
            'vendors.*.partial_delivery' => ['nullable', 'boolean'],
            'vendors.*.files' => ['nullable', 'array'],
            'vendors.*.files.*' => ['file', 'mimes:pdf,xls,xlsx,jpg,jpeg,png,webp', 'max:8192'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.category_name' => ['nullable', 'string', 'max:255'],
            'items.*.subcategory_name' => ['nullable', 'string', 'max:255'],
            'items.*.brand_name' => ['nullable', 'string', 'max:255'],
            'items.*.product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'items.*.uom_id' => ['nullable', 'integer', Rule::exists('uoms', 'id')],
            'items.*.model_sku' => ['nullable', 'string', 'max:255'],
            'items.*.description' => ['nullable', 'string', 'max:2000'],
            'items.*.required_quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.selected_vendor_index' => ['nullable', 'integer', 'min:0'],
            'items.*.selection_reason' => ['nullable', 'string', 'max:2000'],
            'items.*.final_rate' => ['nullable', 'numeric', 'gte:0'],
            'items.*.final_freight' => ['nullable', 'numeric', 'gte:0'],
            'items.*.final_amount' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes' => ['required', 'array'],
            'items.*.quotes.*.is_quoted' => ['nullable', 'boolean'],
            'items.*.quotes.*.quoted_quantity' => ['nullable', 'numeric', 'gt:0'],
            'items.*.quotes.*.basic_rate' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.discount_type' => ['nullable', Rule::in(['percent', 'amount'])],
            'items.*.quotes.*.discount_value' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.gst_rate' => ['nullable', 'numeric', 'gte:0', 'lte:100'],
            'items.*.quotes.*.freight_type' => ['nullable', Rule::in(['free', 'extra'])],
            'items.*.quotes.*.freight_mode' => ['nullable', Rule::in(['amount', 'percent'])],
            'items.*.quotes.*.freight_value' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.packing_charges' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.insurance_charges' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.other_charges' => ['nullable', 'numeric', 'gte:0'],
            'items.*.quotes.*.warranty_period' => ['nullable', 'string', 'max:60'],
            'items.*.quotes.*.warranty_type' => ['nullable', Rule::in(['manufacturer', 'seller', 'on_site', 'carry_in'])],
            'items.*.quotes.*.stock_availability' => ['nullable', Rule::in(['ready', 'limited', 'against_order'])],
            'items.*.quotes.*.delivery_time' => ['nullable', 'string', 'max:60'],
            'items.*.quotes.*.delivery_date' => ['nullable', 'date'],
            'items.*.quotes.*.payment_terms' => ['nullable', 'string', 'max:255'],
            'items.*.quotes.*.price_validity' => ['nullable', 'string', 'max:60'],
            'items.*.quotes.*.country_of_origin' => ['nullable', 'string', 'max:60'],
            'items.*.quotes.*.remarks' => ['nullable', 'string', 'max:2000'],
        ]);

        $validator->after(function ($validator) use ($request) {
            foreach ($request->input('items', []) as $itemIndex => $item) {
                foreach ($item['quotes'] ?? [] as $quoteIndex => $quote) {
                    if (empty($quote['is_quoted'])) {
                        continue;
                    }
                    if ($quote['quoted_quantity'] === null || $quote['quoted_quantity'] === '' || $quote['basic_rate'] === null || $quote['basic_rate'] === '') {
                        $validator->errors()->add("items.$itemIndex.quotes.$quoteIndex.basic_rate", 'A quoted line needs quantity and a basic rate.');
                    }
                }
            }
        });

        $data = $validator->validate();

        $vendorCount = count($data['vendors']);
        foreach ($data['items'] as $index => $item) {
            if (count($item['quotes'] ?? []) !== $vendorCount) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "items.$index.quotes" => 'Each item needs one quote cell for every vendor.',
                ]);
            }
        }

        return $data;
    }

    /**
     * @return array<int, array<int, \Illuminate\Http\UploadedFile>>
     */
    private function uploadedFiles(Request $request): array
    {
        $files = [];
        foreach ($request->file('vendors', []) as $index => $vendor) {
            $uploads = $vendor['files'] ?? [];
            $files[$index] = is_array($uploads) ? array_values($uploads) : [$uploads];
        }

        return $files;
    }

    /**
     * @return array<string, mixed>
     */
    private function stateFromOld(): array
    {
        return [
            'comparison_date' => old('comparison_date'),
            'indent_no' => old('indent_no'),
            'customer_project' => old('customer_project'),
            'required_delivery_date' => old('required_delivery_date'),
            'remarks' => old('remarks'),
            'executive_remarks' => old('executive_remarks'),
            'vendors' => array_values(old('vendors', [])),
            'items' => array_values(old('items', [])),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function stateFromSheet(?PurchaseComparison $sheet): array
    {
        if (! $sheet) {
            return [
                'comparison_date' => now()->toDateString(),
                'indent_no' => '',
                'customer_project' => '',
                'required_delivery_date' => '',
                'remarks' => '',
                'executive_remarks' => '',
                'vendors' => [$this->blankVendor(), $this->blankVendor()],
                'items' => [$this->blankItem(2)],
            ];
        }

        $vendorIndex = $sheet->vendors->pluck('id')->flip();

        return [
            'comparison_date' => $sheet->comparison_date?->toDateString(),
            'indent_no' => $sheet->indent_no,
            'customer_project' => $sheet->customer_project,
            'required_delivery_date' => $sheet->required_delivery_date?->toDateString(),
            'remarks' => $sheet->remarks,
            'executive_remarks' => $sheet->executive_remarks,
            'vendors' => $sheet->vendors->map(fn ($vendor) => [
                'id' => $vendor->id,
                'supplier_id' => $vendor->supplier_id,
                'billing_address' => $vendor->billing_address,
                'dispatch_address' => $vendor->dispatch_address,
                'contact_person' => $vendor->contact_person,
                'mobile' => $vendor->mobile,
                'email' => $vendor->email,
                'gstin' => $vendor->gstin,
                'quotation_number' => $vendor->quotation_number,
                'quotation_date' => $vendor->quotation_date?->toDateString(),
                'valid_until' => $vendor->valid_until?->toDateString(),
                'credit_period' => $vendor->credit_period,
                'advance_payment' => $vendor->advance_payment,
                'credit_limit_note' => $vendor->credit_limit_note,
                'replacement_policy' => $vendor->replacement_policy,
                'return_policy' => $vendor->return_policy,
                'installation_support' => $vendor->installation_support,
                'transporter' => $vendor->transporter,
                'freight_paid_by' => $vendor->freight_paid_by,
                'delivery_location' => $vendor->delivery_location,
                'partial_delivery' => $vendor->partial_delivery,
                'files' => $vendor->files->map(fn ($file) => ['id' => $file->id, 'name' => $file->original_name])->values(),
            ])->values()->all(),
            'items' => $sheet->items->map(function ($item) use ($vendorIndex) {
                $quotes = [];
                foreach ($vendorIndex as $vendorId => $index) {
                    $quote = $item->quotes->firstWhere('purchase_comparison_vendor_id', $vendorId);
                    $quotes[$index] = $quote ? [
                        'is_quoted' => $quote->is_quoted,
                        'quoted_quantity' => $quote->quoted_quantity,
                        'basic_rate' => $quote->basic_rate,
                        'discount_type' => $quote->discount_type,
                        'discount_value' => $quote->discount_value,
                        'gst_rate' => $quote->gst_rate,
                        'freight_type' => $quote->freight_type,
                        'freight_mode' => $quote->freight_mode,
                        'freight_value' => $quote->freight_value,
                        'packing_charges' => $quote->packing_charges,
                        'insurance_charges' => $quote->insurance_charges,
                        'other_charges' => $quote->other_charges,
                        'warranty_period' => $quote->warranty_period,
                        'warranty_type' => $quote->warranty_type,
                        'stock_availability' => $quote->stock_availability,
                        'delivery_time' => $quote->delivery_time,
                        'delivery_date' => $quote->delivery_date?->toDateString(),
                        'payment_terms' => $quote->payment_terms,
                        'price_validity' => $quote->price_validity,
                        'country_of_origin' => $quote->country_of_origin,
                        'remarks' => $quote->remarks,
                    ] : $this->blankQuote();
                }
                ksort($quotes);

                return [
                    'item_name' => $item->item_name,
                    'category_name' => $item->category_name,
                    'subcategory_name' => $item->subcategory_name,
                    'brand_name' => $item->brand_name,
                    'product_id' => $item->product_id,
                    'uom_id' => $item->uom_id,
                    'model_sku' => $item->model_sku,
                    'description' => $item->description,
                    'required_quantity' => $item->required_quantity,
                    'selected_vendor_index' => $item->selected_vendor_id ? ($vendorIndex[$item->selected_vendor_id] ?? '') : '',
                    'selection_reason' => $item->selection_reason,
                    'final_rate' => $item->final_rate,
                    'final_freight' => $item->final_freight,
                    'final_amount' => $item->final_amount,
                    'quotes' => array_values($quotes),
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankVendor(): array
    {
        return [
            'id' => null,
            'supplier_id' => '',
            'billing_address' => '',
            'dispatch_address' => '',
            'contact_person' => '',
            'mobile' => '',
            'email' => '',
            'gstin' => '',
            'quotation_number' => '',
            'quotation_date' => '',
            'valid_until' => '',
            'credit_period' => '',
            'advance_payment' => '',
            'credit_limit_note' => '',
            'replacement_policy' => '',
            'return_policy' => '',
            'installation_support' => '',
            'transporter' => '',
            'freight_paid_by' => 'vendor',
            'delivery_location' => '',
            'partial_delivery' => false,
            'files' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankItem(int $vendors): array
    {
        return [
            'item_name' => '',
            'category_name' => '',
            'subcategory_name' => '',
            'brand_name' => '',
            'product_id' => '',
            'uom_id' => '',
            'model_sku' => '',
            'description' => '',
            'required_quantity' => 1,
            'selected_vendor_index' => '',
            'selection_reason' => '',
            'final_rate' => '',
            'final_freight' => '',
            'final_amount' => '',
            'quotes' => array_fill(0, $vendors, $this->blankQuote()),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function blankQuote(): array
    {
        return [
            'is_quoted' => false,
            'quoted_quantity' => '',
            'basic_rate' => '',
            'discount_type' => 'percent',
            'discount_value' => 0,
            'gst_rate' => 0,
            'freight_type' => 'free',
            'freight_mode' => 'amount',
            'freight_value' => 0,
            'packing_charges' => 0,
            'insurance_charges' => 0,
            'other_charges' => 0,
            'warranty_period' => '',
            'warranty_type' => '',
            'stock_availability' => '',
            'delivery_time' => '',
            'delivery_date' => '',
            'payment_terms' => '',
            'price_validity' => '',
            'country_of_origin' => '',
            'remarks' => '',
        ];
    }
}
