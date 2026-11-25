<?php

namespace App\Http\Controllers\Inventory;

use App\Domains\Inventory\Models\StockAdjustment;
use App\Domains\Inventory\Models\StockAdjustmentItem;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Domains\Inventory\Models\StockMovement;
use App\Domains\Organization\Models\Warehouse;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use App\Support\DocumentExporter;
use App\Support\ReportExporter;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockAdjustmentController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected StockMovementService $stockMovementService,
        protected DocumentNumberService $documentNumberService,
    ) {}

    public function index(Request $request): View|Response|StreamedResponse
    {
        $query = StockAdjustment::query()
            ->with(['warehouse', 'creator'])
            ->when($request->filled('warehouse_id'), fn ($q) => $q->where('warehouse_id', $request->warehouse_id))
            ->when($request->filled('reason'), fn ($q) => $q->where('reason', $request->reason))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('from_date'), fn ($q) => $q->whereDate('adjustment_date', '>=', $request->from_date))
            ->when($request->filled('to_date'), fn ($q) => $q->whereDate('adjustment_date', '<=', $request->to_date));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['adjustment_no', 'reason', 'status', 'notes'],
            ['warehouse' => ['name']]
        );

        $allowedSorts = [
            'adjustment_no' => 'adjustment_no',
            'adjustment_date' => 'adjustment_date',
            'reason' => 'reason',
            'status' => 'status',
            'warehouse' => function ($q, $dir) {
                $q->leftJoin('warehouses', 'stock_adjustments.warehouse_id', '=', 'warehouses.id')
                  ->orderBy('warehouses.name', $dir)
                  ->select('stock_adjustments.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'adjustment_date',
            defaultDirection: 'desc'
        );

        if ($export = $this->exportAdjustments($request, $query)) {
            return $export;
        }

        $adjustments = $query->paginate(15)->withQueryString();

        return view('inventory.adjustments.index', [
            'adjustments' => $adjustments,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'filters' => $request->only(['search', 'warehouse_id', 'reason', 'status', 'from_date', 'to_date']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('inventory.adjustments.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)->where('is_hidden', false)->orderBy('name')->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'adjustment_date' => 'required|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'reason' => 'required|in:opening,damage,shrinkage,found,recount,other',
            'notes' => 'nullable|string',
            'action' => 'nullable|in:draft,post',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|not_in:0',
            'items.*.notes' => 'nullable|string',
        ]);

        $status = $request->input('action') === 'draft' ? 'draft' : 'posted';

        try {
            $adjustment = DB::transaction(function () use ($validated, $request, $status) {
                $adjustment = StockAdjustment::create([
                    'adjustment_no' => $this->documentNumberService->nextDocument('ADJ'),
                    'adjustment_date' => $validated['adjustment_date'],
                    'warehouse_id' => $validated['warehouse_id'] ?? null,
                    'reason' => $validated['reason'],
                    'status' => $status,
                    'notes' => $validated['notes'] ?? null,
                    'created_by' => $request->user()->id,
                ]);

                foreach ($validated['items'] as $item) {
                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);

                    if ($status === 'posted') {
                        $product = Product::findOrFail($item['product_id']);
                        $uom = Uom::findOrFail($item['uom_id']);
                        $qty = (float) $item['quantity'];
                        $type = $validated['reason'] === 'opening' ? 'opening' : 'adjustment';

                        if ($qty > 0) {
                            $this->stockMovementService->recordIn(
                                product: $product,
                                uom: $uom,
                                quantity: $qty,
                                type: $type,
                                reference: $adjustment,
                                notes: "Adjustment {$adjustment->adjustment_no}",
                                user: $request->user(),
                                warehouseId: $validated['warehouse_id'] ?? null,
                            );
                        } else {
                            $this->stockMovementService->recordOut(
                                product: $product,
                                uom: $uom,
                                quantity: abs($qty),
                                type: $type,
                                reference: $adjustment,
                                notes: "Adjustment {$adjustment->adjustment_no}",
                                user: $request->user(),
                                warehouseId: $validated['warehouse_id'] ?? null,
                            );
                        }
                    }
                }

                return $adjustment;
            });
        } catch (\Throwable $e) {
            return $this->flashError($e->getMessage());
        }

        $msg = $status === 'draft' ? 'Stock adjustment saved as draft.' : 'Stock adjustment posted.';
        return $this->flashSuccess($msg, 'inventory.adjustments.show', ['adjustment' => $adjustment]);
    }

    public function show(StockAdjustment $adjustment): View
    {
        $adjustment->load(['items.product', 'items.uom', 'warehouse', 'creator']);

        return view('inventory.adjustments.show', compact('adjustment'));
    }

    public function edit(StockAdjustment $adjustment): View|RedirectResponse
    {
        if ($adjustment->status === 'posted') {
            return redirect()->route('inventory.adjustments.show', $adjustment)
                ->with('error', "Cannot edit stock adjustment '{$adjustment->adjustment_no}' because it has already been posted. Posted adjustments are finalized in the inventory ledger and cannot be modified.");
        }

        if ($adjustment->status === 'cancelled') {
            return redirect()->route('inventory.adjustments.show', $adjustment)
                ->with('error', "Cannot edit stock adjustment '{$adjustment->adjustment_no}' because it has been cancelled.");
        }

        $adjustment->load(['items.product', 'items.uom', 'warehouse']);

        return view('inventory.adjustments.edit', [
            'adjustment' => $adjustment,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('name')->get(),
            'products' => Product::where('is_active', true)
                ->where(function ($q) use ($adjustment) {
                    $q->where('is_hidden', false)
                        ->orWhereIn('id', $adjustment->items->pluck('product_id'));
                })
                ->orderBy('name')
                ->get(),
            'uoms' => Uom::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, StockAdjustment $adjustment): RedirectResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.edit') &&
            ! $user->can('stock.edit')) {
            return back()->with('error', 'Unauthorized: You do not have permission to edit stock adjustments.');
        }

        if ($adjustment->status === 'posted') {
            return redirect()->route('inventory.adjustments.show', $adjustment)
                ->with('error', "Cannot update stock adjustment '{$adjustment->adjustment_no}' because it has already been posted. Posted adjustments are finalized in the inventory ledger and cannot be modified.");
        }

        if ($adjustment->status === 'cancelled') {
            return redirect()->route('inventory.adjustments.show', $adjustment)
                ->with('error', "Cannot update stock adjustment '{$adjustment->adjustment_no}' because it has been cancelled.");
        }

        $validated = $request->validate([
            'adjustment_date' => 'required|date',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'reason' => 'required|in:opening,damage,shrinkage,found,recount,other',
            'notes' => 'nullable|string',
            'action' => 'nullable|in:draft,post',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.quantity' => 'required|numeric|not_in:0',
            'items.*.notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($validated, $request, $adjustment) {
                $targetStatus = $request->input('action') === 'post' ? 'posted' : 'draft';

                $adjustment->update([
                    'adjustment_date' => $validated['adjustment_date'],
                    'warehouse_id' => $validated['warehouse_id'] ?? null,
                    'reason' => $validated['reason'],
                    'status' => $targetStatus,
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Replace items while preserving adjustment ID and adjustment_no
                $adjustment->items()->delete();
                foreach ($validated['items'] as $item) {
                    StockAdjustmentItem::create([
                        'stock_adjustment_id' => $adjustment->id,
                        'product_id' => $item['product_id'],
                        'uom_id' => $item['uom_id'],
                        'quantity' => $item['quantity'],
                        'notes' => $item['notes'] ?? null,
                    ]);

                    if ($targetStatus === 'posted') {
                        $product = Product::findOrFail($item['product_id']);
                        $uom = Uom::findOrFail($item['uom_id']);
                        $qty = (float) $item['quantity'];
                        $type = $validated['reason'] === 'opening' ? 'opening' : 'adjustment';

                        if ($qty > 0) {
                            $this->stockMovementService->recordIn(
                                product: $product,
                                uom: $uom,
                                quantity: $qty,
                                type: $type,
                                reference: $adjustment,
                                notes: "Adjustment {$adjustment->adjustment_no}",
                                user: $request->user(),
                                warehouseId: $validated['warehouse_id'] ?? null,
                            );
                        } else {
                            $this->stockMovementService->recordOut(
                                product: $product,
                                uom: $uom,
                                quantity: abs($qty),
                                type: $type,
                                reference: $adjustment,
                                notes: "Adjustment {$adjustment->adjustment_no}",
                                user: $request->user(),
                                warehouseId: $validated['warehouse_id'] ?? null,
                            );
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Failed to update stock adjustment: ' . $e->getMessage());
        }

        $msg = $request->input('action') === 'post' ? 'Stock adjustment posted successfully.' : 'Stock adjustment updated successfully.';
        return $this->flashSuccess($msg, 'inventory.adjustments.show', ['adjustment' => $adjustment]);
    }

    public function destroy(Request $request, StockAdjustment $adjustment): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('inventory.manage') &&
            ! $user->can('inventory.delete') &&
            ! $user->can('stock.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete stock adjustments.',
                ], 403);
            }
            return back()->with('error', 'Unauthorized: You do not have permission to delete stock adjustments.');
        }

        $adjustmentNo = $adjustment->adjustment_no;

        if ($adjustment->status === 'posted') {
            $message = "Cannot delete stock adjustment '{$adjustmentNo}' because it has already been posted. Posted adjustments cannot be deleted to preserve stock ledger integrity.";
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }
            return back()->with('error', $message);
        }

        try {
            DB::transaction(function () use ($adjustment) {
                $adjustment->items()->delete();

                StockMovement::where('reference_type', $adjustment->getMorphClass())
                    ->where('reference_id', $adjustment->id)
                    ->delete();

                DB::table('stock_cost_consumptions')
                    ->where('reference_type', $adjustment->getMorphClass())
                    ->where('reference_id', $adjustment->id)
                    ->delete();

                DB::table('stock_cost_layers')
                    ->where('source_type', $adjustment->getMorphClass())
                    ->where('source_id', $adjustment->id)
                    ->delete();

                $adjustment->delete();
            });

            $message = "Stock adjustment '{$adjustmentNo}' deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'inventory.adjustments.index');
        } catch (\Throwable $e) {
            report($e);
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete stock adjustment: ' . $e->getMessage(),
                ], 500);
            }
            return $this->flashError('Failed to delete stock adjustment: ' . $e->getMessage(), 'inventory.adjustments.index');
        }
    }

    protected function exportAdjustments(Request $request, $query): Response|StreamedResponse|null
    {
        $format = strtolower((string) ($request->input('export') ?: $request->input('format') ?: ''));
        if (! in_array($format, ['xlsx', 'csv', 'pdf'], true)) {
            return null;
        }

        $records = (clone $query)
            ->with(['warehouse', 'creator', 'items.product', 'items.uom'])
            ->get();

        $headers = [
            'Adjustment No', 'Adjustment Date', 'Warehouse', 'Reason', 'Status',
            'Total Items', 'Notes', 'Created By', 'Created At'
        ];

        $adjRows = $records->map(function (StockAdjustment $adj) {
            return [
                'Adjustment No' => $adj->adjustment_no,
                'Adjustment Date' => optional($adj->adjustment_date)->format('Y-m-d'),
                'Warehouse' => $adj->warehouse?->name ?? '',
                'Reason' => ucfirst((string) $adj->reason),
                'Status' => ucfirst((string) $adj->status),
                'Total Items' => $adj->items->count(),
                'Notes' => $adj->notes ?? '',
                'Created By' => $adj->creator?->name ?? '',
                'Created At' => optional($adj->created_at)->format('Y-m-d H:i:s'),
            ];
        })->all();

        if ($format === 'xlsx') {
            $itemHeaders = [
                'Adjustment No', 'Adjustment Date', 'Warehouse', 'Product Code', 'Product Name',
                'Quantity', 'UOM', 'Notes'
            ];

            $itemRows = [];
            foreach ($records as $adj) {
                foreach ($adj->items as $item) {
                    $itemRows[] = [
                        'Adjustment No' => $adj->adjustment_no,
                        'Adjustment Date' => optional($adj->adjustment_date)->format('Y-m-d'),
                        'Warehouse' => $adj->warehouse?->name ?? '',
                        'Product Code' => $item->product?->code ?? $item->product?->sku ?? '',
                        'Product Name' => $item->product?->name ?? '',
                        'Quantity' => (float) $item->quantity,
                        'UOM' => $item->uom?->code ?? $item->uom?->name ?? '',
                        'Notes' => $item->notes ?? '',
                    ];
                }
            }

            return DocumentExporter::exportMultiSheetExcel([
                'Stock Adjustments' => [
                    'columns' => array_combine($headers, $headers),
                    'rows' => $adjRows,
                ],
                'Adjustment Items' => [
                    'columns' => array_combine($itemHeaders, $itemHeaders),
                    'rows' => $itemRows,
                ],
            ], 'stock-adjustments-master-' . now()->format('Y-m-d'));
        }

        if ($format === 'csv') {
            return ReportExporter::csv($adjRows, $headers, 'stock-adjustments-' . now()->format('Y-m-d') . '.csv');
        }

        return ReportExporter::pdf($adjRows, array_combine($headers, $headers), 'Stock Adjustments Report', 'stock-adjustments-' . now()->format('Y-m-d') . '.pdf');
    }
}
