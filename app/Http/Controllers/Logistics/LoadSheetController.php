<?php

namespace App\Http\Controllers\Logistics;

use App\Domains\Delivery\Models\Delivery;
use App\Domains\Delivery\Models\DeliveryItem;
use App\Domains\Logistics\Models\LoadSheet;
use App\Domains\Logistics\Models\LoadSheetItem;
use App\Domains\Master\Models\DeliveryPerson;
use App\Domains\Master\Models\Driver;
use App\Domains\Master\Models\Route;
use App\Domains\Master\Models\Vehicle;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class LoadSheetController extends Controller
{
    public function index(Request $request): View
    {
        $loadSheets = LoadSheet::query()
            ->with(['route', 'vehicle', 'driver', 'deliveryPerson'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('load_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('load_date', '<=', $request->date_to))
            ->latest('load_date')
            ->paginate(15)
            ->withQueryString();

        return view('logistics.load-sheets.index', compact('loadSheets'));
    }

    public function create(): View
    {
        return view('logistics.load-sheets.create', [
            'invoices' => Invoice::with('customer')
                ->whereIn('status', ['issued', 'partial'])
                ->orderByDesc('invoice_date')
                ->get(),
            'routes' => Route::where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::where('is_active', true)->orderBy('name')->get(),
            'drivers' => Driver::where('is_active', true)->orderBy('name')->get(),
            'deliveryPersons' => DeliveryPerson::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'load_date' => 'required|date',
            'route_id' => 'nullable|exists:routes,id',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'driver_id' => 'nullable|exists:drivers,id',
            'delivery_person_id' => 'nullable|exists:delivery_persons,id',
            'invoice_ids' => 'required|array|min:1',
            'invoice_ids.*' => 'exists:invoices,id',
        ]);

        $loadSheet = DB::transaction(function () use ($validated) {
            $invoices = Invoice::with('items')->whereIn('id', $validated['invoice_ids'])->get();

            $totalValue = $invoices->sum('grand_total');
            $totalQty = $invoices->flatMap->items->sum('quantity');

            $loadSheet = LoadSheet::create([
                'load_sheet_no' => $this->generateLoadSheetNo(),
                'load_date' => $validated['load_date'],
                'route_id' => $validated['route_id'] ?? null,
                'vehicle_id' => $validated['vehicle_id'] ?? null,
                'driver_id' => $validated['driver_id'] ?? null,
                'delivery_person_id' => $validated['delivery_person_id'] ?? null,
                'status' => 'draft',
                'total_value' => $totalValue,
                'total_quantity' => $totalQty,
                'created_by' => auth()->id(),
            ]);

            foreach ($invoices as $invoice) {
                LoadSheetItem::create([
                    'load_sheet_id' => $loadSheet->id,
                    'invoice_id' => $invoice->id,
                    'loaded_quantity' => $invoice->items->sum('quantity'),
                    'loaded_value' => $invoice->grand_total,
                ]);

                $delivery = Delivery::create([
                    'load_sheet_id' => $loadSheet->id,
                    'customer_id' => $invoice->customer_id,
                    'invoice_id' => $invoice->id,
                    'status' => 'pending',
                ]);

                foreach ($invoice->items as $item) {
                    DeliveryItem::create([
                        'delivery_id' => $delivery->id,
                        'product_id' => $item->product_id,
                        'uom_id' => $item->uom_id,
                        'loaded_qty' => $item->quantity,
                        'delivered_qty' => 0,
                        'short_qty' => 0,
                        'returned_qty' => 0,
                    ]);
                }
            }

            return $loadSheet;
        });

        return $this->flashSuccess('Load sheet created.', 'logistics.load-sheets.show', ['load_sheet' => $loadSheet]);
    }

    public function show(LoadSheet $loadSheet): View
    {
        $loadSheet->load(['route', 'vehicle', 'driver', 'deliveryPerson', 'items.invoice.customer', 'deliveries.customer', 'creator']);

        return view('logistics.load-sheets.show', compact('loadSheet'));
    }

    public function dispatch(LoadSheet $loadSheet): RedirectResponse
    {
        $loadSheet->update(['status' => 'dispatched']);
        $loadSheet->deliveries()->update(['status' => 'out_for_delivery']);

        return $this->flashSuccess('Load sheet dispatched.');
    }

    protected function generateLoadSheetNo(): string
    {
        $date = now()->format('Ymd');
        $pattern = "LS-{$date}-%";
        $last = LoadSheet::where('load_sheet_no', 'like', $pattern)->orderByDesc('load_sheet_no')->value('load_sheet_no');
        $sequence = $last ? (int) Str::afterLast($last, '-') + 1 : 1;

        return sprintf('LS-%s-%04d', $date, $sequence);
    }
}
