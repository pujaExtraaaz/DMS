<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Organization\Models\Company;
use App\Domains\Organization\Models\Warehouse;
use App\Domains\Purchasing\Models\PurchaseOrder;
use App\Domains\Purchasing\Models\PurchaseOrderItem;
use App\Domains\Purchasing\Models\ReceiptNote;
use App\Domains\Purchasing\Models\ReceiptNoteItem;
use App\Domains\Purchasing\Services\ReceiptNoteService;
use App\Http\Controllers\Controller;
use App\Support\DocumentExporter;
use App\Support\ReportExporter;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReceiptNoteController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected ReceiptNoteService $receiptNoteService,
    ) {}

    public function index(Request $request): View|Response|StreamedResponse
    {
        $user = $request->user();
        $query = ReceiptNote::query()
            ->with(['supplier', 'warehouse', 'purchaseOrder', 'creator'])
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id));

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->input('supplier_id'));
        }

        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->input('warehouse_id'));
        }

        if ($request->filled('search')) {
            $term = trim((string) $request->input('search'));
            $query->where(function ($q) use ($term) {
                $q->where('receipt_note_no', 'like', "%{$term}%")
                    ->orWhere('supplier_challan_no', 'like', "%{$term}%")
                    ->orWhere('reference_no', 'like', "%{$term}%")
                    ->orWhereHas('supplier', fn ($sq) => $sq->where('name', 'like', "%{$term}%"));
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('receipt_date', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('receipt_date', '<=', $request->input('date_to'));
        }

        if ($export = $this->exportReceiptNotes($request, $query)) {
            return $export;
        }

        // Metrics for summary cards
        $metricsQuery = clone $query;
        $totalCount = (clone $query)->count();
        $receivedCount = (clone $query)->whereIn('status', [ReceiptNote::STATUS_RECEIVED, ReceiptNote::STATUS_PARTIALLY_INVOICED, ReceiptNote::STATUS_FULLY_INVOICED])->count();
        $pendingInvoiceCount = (clone $query)->whereIn('status', [ReceiptNote::STATUS_RECEIVED, ReceiptNote::STATUS_PARTIALLY_INVOICED])->count();
        $returnedCount = (clone $query)->whereIn('status', [ReceiptNote::STATUS_PARTIALLY_RETURNED, ReceiptNote::STATUS_FULLY_RETURNED])->count();

        $receiptNotes = $query->latest('receipt_date')->latest('id')->paginate(15)->withQueryString();

        $suppliers = Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        return view('purchasing.receipt-notes.index', [
            'receiptNotes' => $receiptNotes,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'metrics' => [
                'total' => $totalCount,
                'received' => $receivedCount,
                'pending_invoice' => $pendingInvoiceCount,
                'returned' => $returnedCount,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $user = $request->user();
        $preselectedPoId = $request->input('purchase_order_id');
        $preselectedPo = null;

        if ($preselectedPoId) {
            $preselectedPo = PurchaseOrder::with(['items.product.baseUom', 'supplier', 'warehouse'])->find($preselectedPoId);
        }

        $suppliers = Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();

        $purchaseOrders = PurchaseOrder::query()
            ->when($user?->company_id, fn ($q) => $q->where('company_id', $user->company_id))
            ->whereIn('status', ['approved', 'partially_received'])
            ->with(['supplier', 'items'])
            ->latest('po_date')
            ->get();

        $products = Product::with(['baseUom'])
            ->where('is_active', true)
            ->where('is_hidden', false)
            ->orderBy('name')
            ->get();

        return view('purchasing.receipt-notes.create', [
            'preselectedPo' => $preselectedPo,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'purchaseOrders' => $purchaseOrders,
            'products' => $products,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_note_no' => 'nullable|string|max:60',
            'receipt_date' => 'required|date',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'supplier_challan_no' => 'nullable|string|max:100',
            'supplier_challan_date' => 'nullable|date',
            'received_by' => 'nullable|string|max:100',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'confirm_immediately' => 'nullable|boolean',
            'attachment' => 'nullable|file|max:10240',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.purchase_order_item_id' => 'nullable|exists:purchase_order_items,id',
            'items.*.ordered_qty' => 'nullable|numeric|min:0',
            'items.*.challan_qty' => 'nullable|numeric|min:0',
            'items.*.received_qty' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:80',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.serials' => 'nullable',
            'items.*.remarks' => 'nullable|string|max:255',
        ]);

        $status = $request->boolean('confirm_immediately', true)
            ? ReceiptNote::STATUS_RECEIVED
            : ReceiptNote::STATUS_DRAFT;

        $validated['status'] = $status;
        $validated['confirm_immediately'] = $request->boolean('confirm_immediately', true);

        try {
            $note = $this->receiptNoteService->createReceiptNote($validated, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        $msg = $note->isReceived()
            ? "Receipt Note {$note->receipt_note_no} created and confirmed. Material added to warehouse stock."
            : "Receipt Note {$note->receipt_note_no} created as draft.";

        return redirect()->route('purchasing.receipt-notes.show', $note)->with('success', $msg);
    }

    public function show(ReceiptNote $receiptNote): View
    {
        $receiptNote->load([
            'supplier',
            'warehouse',
            'purchaseOrder',
            'items.product.baseUom',
            'items.uom',
            'items.serials',
            'returns.items.product',
            'returns.creator',
            'invoices',
            'creator',
            'confirmer',
            'canceller',
        ]);

        return view('purchasing.receipt-notes.show', [
            'receiptNote' => $receiptNote,
        ]);
    }

    public function edit(ReceiptNote $receiptNote): View|RedirectResponse
    {
        if (! $receiptNote->isDraft()) {
            return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
                ->with('error', "Only draft Receipt Notes can be edited. This note is {$receiptNote->status_label}.");
        }

        $receiptNote->load(['items.product', 'items.uom', 'supplier', 'warehouse', 'purchaseOrder']);

        $suppliers = Customer::whereIn('party_type', [Customer::PARTY_TYPE_SUNDRY_CREDITORS, 'supplier', Customer::PARTY_TYPE_BOTH])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('is_active', true)->orderBy('name')->get();
        $products = Product::where('is_active', true)
            ->where(function ($q) use ($receiptNote) {
                $q->where('is_hidden', false)
                    ->orWhereIn('id', $receiptNote->items->pluck('product_id'));
            })
            ->orderBy('name')
            ->get();

        return view('purchasing.receipt-notes.edit', [
            'receiptNote' => $receiptNote,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
        ]);
    }

    public function update(Request $request, ReceiptNote $receiptNote): RedirectResponse
    {
        if (! $receiptNote->isDraft()) {
            return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
                ->with('error', "Only draft Receipt Notes can be updated.");
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'receipt_date' => 'required|date',
            'supplier_challan_no' => 'nullable|string|max:100',
            'supplier_challan_date' => 'nullable|date',
            'received_by' => 'nullable|string|max:100',
            'reference_no' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'nullable|exists:uoms,id',
            'items.*.challan_qty' => 'nullable|numeric|min:0',
            'items.*.received_qty' => 'required|numeric|min:0.0001',
            'items.*.unit_cost' => 'nullable|numeric|min:0',
            'items.*.batch_no' => 'nullable|string|max:80',
            'items.*.expiry_date' => 'nullable|date',
            'items.*.remarks' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($receiptNote, $validated) {
            $receiptNote->update([
                'supplier_id' => $validated['supplier_id'],
                'warehouse_id' => $validated['warehouse_id'],
                'receipt_date' => $validated['receipt_date'],
                'supplier_challan_no' => $validated['supplier_challan_no'] ?? null,
                'supplier_challan_date' => $validated['supplier_challan_date'] ?? null,
                'received_by' => $validated['received_by'] ?? null,
                'reference_no' => $validated['reference_no'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
            ]);

            $receiptNote->items()->delete();

            foreach ($validated['items'] as $row) {
                $product = Product::findOrFail($row['product_id']);
                $receiptNote->items()->create([
                    'product_id' => $row['product_id'],
                    'uom_id' => $row['uom_id'] ?? $product->base_uom_id,
                    'ordered_qty' => 0,
                    'challan_qty' => (float) ($row['challan_qty'] ?? $row['received_qty'] ?? 0),
                    'received_qty' => (float) $row['received_qty'],
                    'invoiced_qty' => 0,
                    'returned_qty' => 0,
                    'unit_cost' => (float) ($row['unit_cost'] ?? $product->purchase_price ?? 0),
                    'batch_no' => $row['batch_no'] ?? null,
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'remarks' => $row['remarks'] ?? null,
                ]);
            }
        });

        return redirect()->route('purchasing.receipt-notes.show', $receiptNote)->with('success', 'Receipt Note updated successfully.');
    }

    public function destroy(ReceiptNote $receiptNote): RedirectResponse
    {
        if (! $receiptNote->isDraft()) {
            return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
                ->with('error', "Only draft Receipt Notes can be deleted.");
        }

        $receiptNote->items()->delete();
        $receiptNote->delete();

        return redirect()->route('purchasing.receipt-notes.index')->with('success', 'Draft Receipt Note deleted.');
    }

    public function confirm(Request $request, ReceiptNote $receiptNote): RedirectResponse
    {
        try {
            $this->receiptNoteService->confirmReceiptNote($receiptNote, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
            ->with('success', "Receipt Note {$receiptNote->receipt_note_no} confirmed. Physical stock added to warehouse.");
    }

    public function cancel(Request $request, ReceiptNote $receiptNote): RedirectResponse
    {
        try {
            $this->receiptNoteService->cancelReceiptNote($receiptNote, $request->user());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
            ->with('success', "Receipt Note {$receiptNote->receipt_note_no} cancelled.");
    }

    public function returnForm(ReceiptNote $receiptNote): View|RedirectResponse
    {
        if (! $receiptNote->canBeReturned()) {
            return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
                ->with('error', 'This Receipt Note cannot be returned (no returnable quantity or cancelled).');
        }

        $receiptNote->load(['items.product.baseUom', 'items.uom', 'supplier', 'warehouse']);

        return view('purchasing.receipt-notes.return', [
            'receiptNote' => $receiptNote,
        ]);
    }

    public function storeReturn(Request $request, ReceiptNote $receiptNote): RedirectResponse
    {
        $validated = $request->validate([
            'return_no' => 'nullable|string|max:60',
            'return_date' => 'required|date',
            'reference_no' => 'nullable|string|max:100',
            'reason' => 'nullable|string|max:255',
            'remarks' => 'nullable|string',
            'items' => 'required|array',
            'items.*.quantity' => 'nullable|numeric|min:0',
            'items.*.remarks' => 'nullable|string|max:255',
        ]);

        try {
            $return = $this->receiptNoteService->createReturn($receiptNote, $validated, $request->user());
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }

        return redirect()->route('purchasing.receipt-notes.show', $receiptNote)
            ->with('success', "Material return {$return->return_no} processed. Stock updated.");
    }

    public function purchaseOrderItems(PurchaseOrder $order): JsonResponse
    {
        $order->load(['items.product.baseUom', 'items.uom', 'supplier', 'warehouse']);

        $items = $order->items->map(function (PurchaseOrderItem $item) {
            $remaining = max(0, (float) $item->quantity - (float) $item->received_qty);
            return [
                'purchase_order_item_id' => $item->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->name ?? 'Product',
                'uom_id' => $item->uom_id,
                'uom_name' => $item->uom?->name ?? $item->product?->unit ?? 'PCS',
                'ordered_qty' => (float) $item->quantity,
                'received_qty' => (float) $item->received_qty,
                'remaining_qty' => $remaining,
                'unit_cost' => (float) $item->unit_cost,
                'batch_no' => $item->batch_no ?? '',
            ];
        })->filter(fn ($it) => $it['remaining_qty'] > 0)->values();

        return response()->json([
            'order' => [
                'id' => $order->id,
                'po_no' => $order->po_no,
                'supplier_id' => $order->supplier_id,
                'warehouse_id' => $order->warehouse_id,
            ],
            'items' => $items,
        ]);
    }

    public function preview(ReceiptNote $receiptNote): View
    {
        $receiptNote->load([
            'supplier',
            'warehouse',
            'purchaseOrder',
            'items.product.baseUom',
            'items.uom',
            'items.serials',
            'creator',
            'confirmer',
        ]);

        $company = $receiptNote->company ?? Company::resolveCurrent() ?? Company::first();

        return view('purchasing.receipt-notes.preview', [
            'receiptNote' => $receiptNote,
            'company' => $company,
            'isPdf' => false,
        ]);
    }

    public function export(ReceiptNote $receiptNote, string $format): Response
    {
        $format = strtolower($format);
        abort_unless(in_array($format, ['pdf', 'xlsx', 'excel', 'csv'], true), 404);

        $receiptNote->load([
            'supplier',
            'warehouse',
            'purchaseOrder',
            'items.product.baseUom',
            'items.uom',
            'items.serials',
            'creator',
            'confirmer',
        ]);

        $company = $receiptNote->company ?? Company::resolveCurrent() ?? Company::first();
        $filename = 'RN-' . $receiptNote->receipt_note_no . '.' . ($format === 'excel' ? 'xlsx' : $format);

        if ($format === 'pdf') {
            return DocumentExporter::exportPdf('purchasing.receipt-notes.preview', [
                'receiptNote' => $receiptNote,
                'company' => $company,
                'isPdf' => true,
            ], $filename);
        }

        $headers = ['Item', 'UOM', 'Ordered Qty', 'Challan Qty', 'Received Qty', 'Invoiced Qty', 'Returned Qty', 'Batch No', 'Remarks'];
        $rows = [];

        foreach ($receiptNote->items as $item) {
            $rows[] = [
                $item->product?->name,
                $item->uom?->short_name ?? $item->product?->unit ?? 'PCS',
                (float) $item->ordered_qty,
                (float) $item->challan_qty,
                (float) $item->received_qty,
                (float) $item->invoiced_qty,
                (float) $item->returned_qty,
                $item->batch_no ?? '',
                $item->remarks ?? '',
            ];
        }

        return DocumentExporter::exportTabular('RN-' . $receiptNote->receipt_note_no, "Receipt Note {$receiptNote->receipt_note_no}", $headers, $rows, $format);
    }

    protected function exportReceiptNotes(Request $request, $query): Response|StreamedResponse|null
    {
        $format = strtolower((string) ($request->input('export') ?: $request->input('format') ?: ''));
        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return null;
        }

        $records = (clone $query)
            ->with(['supplier', 'warehouse', 'purchaseOrder', 'creator', 'items.product', 'items.uom'])
            ->latest('receipt_date')
            ->latest('id')
            ->get();

        $headers = [
            'Receipt Note No', 'Receipt Date', 'Supplier Code', 'Supplier Name', 'Supplier Phone',
            'Warehouse', 'PO No', 'Supplier Challan No', 'Supplier Challan Date', 'Reference No',
            'Status', 'Remarks', 'Created At'
        ];

        $noteRows = $records->map(function (ReceiptNote $rn) {
            return [
                'Receipt Note No' => $rn->receipt_note_no,
                'Receipt Date' => optional($rn->receipt_date)->format('Y-m-d'),
                'Supplier Code' => $rn->supplier?->code ?? '',
                'Supplier Name' => $rn->supplier?->name ?? '',
                'Supplier Phone' => $rn->supplier?->phone ?? '',
                'Warehouse' => $rn->warehouse?->name ?? '',
                'PO No' => $rn->purchaseOrder?->po_no ?? '',
                'Supplier Challan No' => $rn->supplier_challan_no ?? '',
                'Supplier Challan Date' => optional($rn->supplier_challan_date)->format('Y-m-d'),
                'Reference No' => $rn->reference_no ?? '',
                'Status' => ucfirst(str_replace('_', ' ', (string) $rn->status)),
                'Remarks' => $rn->remarks ?? '',
                'Created At' => optional($rn->created_at)->format('Y-m-d H:i:s'),
            ];
        })->all();

        if ($format === 'xlsx') {
            $itemHeaders = [
                'Receipt Note No', 'Receipt Date', 'Supplier', 'Product Code', 'Product Name',
                'Ordered Qty', 'Challan Qty', 'Received Qty', 'Invoiced Qty', 'Returned Qty',
                'UOM', 'Unit Cost (₹)', 'Batch No', 'Expiry Date', 'Remarks'
            ];

            $itemRows = [];
            foreach ($records as $rn) {
                foreach ($rn->items as $item) {
                    $itemRows[] = [
                        'Receipt Note No' => $rn->receipt_note_no,
                        'Receipt Date' => optional($rn->receipt_date)->format('Y-m-d'),
                        'Supplier' => $rn->supplier?->name ?? '',
                        'Product Code' => $item->product?->code ?? $item->product?->sku ?? '',
                        'Product Name' => $item->product?->name ?? '',
                        'Ordered Qty' => (float) $item->ordered_qty,
                        'Challan Qty' => (float) $item->challan_qty,
                        'Received Qty' => (float) $item->received_qty,
                        'Invoiced Qty' => (float) $item->invoiced_qty,
                        'Returned Qty' => (float) $item->returned_qty,
                        'UOM' => $item->uom?->code ?? $item->uom?->name ?? '',
                        'Unit Cost (₹)' => number_format((float) $item->unit_cost, 2, '.', ''),
                        'Batch No' => $item->batch_no ?? '',
                        'Expiry Date' => optional($item->expiry_date)->format('Y-m-d'),
                        'Remarks' => $item->remarks ?? '',
                    ];
                }
            }

            return DocumentExporter::exportMultiSheetExcel([
                'Receipt Notes' => [
                    'columns' => array_combine($headers, $headers),
                    'rows' => $noteRows,
                ],
                'Receipt Note Items' => [
                    'columns' => array_combine($itemHeaders, $itemHeaders),
                    'rows' => $itemRows,
                ],
            ], 'receipt-notes-master-' . now()->format('Y-m-d'));
        }

        if ($format === 'csv') {
            return ReportExporter::csv($noteRows, $headers, 'receipt-notes-' . now()->format('Y-m-d') . '.csv');
        }

        return ReportExporter::pdf($noteRows, array_combine($headers, $headers), 'Receipt Notes Report', 'receipt-notes-' . now()->format('Y-m-d') . '.pdf');
    }
}
