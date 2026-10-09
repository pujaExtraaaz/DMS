<?php

namespace App\Http\Controllers\Delivery;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Inventory\Services\StockMovementService;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryController extends Controller
{
    use SortableAndSearchable;

    public function __construct(
        protected StockMovementService $stockMovementService,
    ) {}

    public function index(Request $request): View
    {
        $query = Delivery::query()
            ->with(['customer', 'invoice', 'loadSheet'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('load_sheet_id'), fn ($q) => $q->where('load_sheet_id', $request->load_sheet_id));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['status'],
            [
                'customer' => ['name'],
                'invoice' => ['invoice_no'],
                'loadSheet' => ['load_sheet_no'],
            ]
        );

        $allowedSorts = [
            'status' => 'status',
            'created_at' => 'created_at',
            'customer' => function ($q, $dir) {
                $q->join('customers', 'deliveries.customer_id', '=', 'customers.id')
                  ->orderBy('customers.name', $dir)
                  ->select('deliveries.*');
            },
            'invoice' => function ($q, $dir) {
                $q->join('invoices', 'deliveries.invoice_id', '=', 'invoices.id')
                  ->orderBy('invoices.invoice_no', $dir)
                  ->select('deliveries.*');
            },
            'loadSheet' => function ($q, $dir) {
                $q->join('load_sheets', 'deliveries.load_sheet_id', '=', 'load_sheets.id')
                  ->orderBy('load_sheets.load_sheet_no', $dir)
                  ->select('deliveries.*');
            },
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'created_at',
            defaultDirection: 'desc'
        );

        $deliveries = $query->paginate(15)->withQueryString();

        return view('deliveries.index', [
            'deliveries' => $deliveries,
            'filters' => $request->only(['search', 'status', 'load_sheet_id']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function show(Delivery $delivery): View
    {
        $delivery->load(['customer', 'invoice', 'loadSheet', 'items.product', 'items.uom']);

        return view('deliveries.show', compact('delivery'));
    }

    public function edit(Delivery $delivery): View
    {
        $delivery->load(['customer', 'invoice', 'items.product', 'items.uom']);

        return view('deliveries.edit', compact('delivery'));
    }

    public function update(Request $request, Delivery $delivery): RedirectResponse
    {
        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.id' => 'required|exists:delivery_items,id',
            'items.*.delivered_qty' => 'required|numeric|min:0',
            'items.*.short_qty' => 'nullable|numeric|min:0',
            'items.*.returned_qty' => 'nullable|numeric|min:0',
        ]);

        $delivery->load(['items.product', 'items.uom']);

        DB::transaction(function () use ($validated, $delivery) {
            $allDelivered = true;
            $hasPartial = false;

            foreach ($validated['items'] as $row) {
                $item = $delivery->items()->findOrFail($row['id']);
                $delivered = (float) $row['delivered_qty'];
                $short = (float) ($row['short_qty'] ?? 0);
                $returned = (float) ($row['returned_qty'] ?? 0);

                if ($delivered + $short + $returned > (float) $item->loaded_qty) {
                    throw new \InvalidArgumentException('Quantities exceed loaded amount for '.$item->product->name);
                }

                $item->update([
                    'delivered_qty' => $delivered,
                    'short_qty' => $short,
                    'returned_qty' => $returned,
                ]);

                if ($returned > 0) {
                    $this->stockMovementService->recordIn(
                        product: Product::find($item->product_id),
                        uom: Uom::find($item->uom_id),
                        quantity: $returned,
                        type: 'return',
                        reference: $delivery,
                        notes: "Return from delivery #{$delivery->id}",
                        user: auth()->user(),
                    );
                }

                if ($short > 0) {
                    $this->stockMovementService->recordIn(
                        product: Product::find($item->product_id),
                        uom: Uom::find($item->uom_id),
                        quantity: $short,
                        type: 'delivery_short',
                        reference: $delivery,
                        notes: "Short delivery #{$delivery->id}",
                        user: auth()->user(),
                    );
                }

                if ($delivered < (float) $item->loaded_qty) {
                    $allDelivered = false;
                    $hasPartial = $delivered > 0;
                }
            }

            $status = $allDelivered ? 'delivered' : ($hasPartial ? 'partial' : 'returned');
            $delivery->update(['status' => $status]);
        });

        return $this->flashSuccess('Delivery quantities updated.', 'deliveries.show', ['delivery' => $delivery]);
    }
}
