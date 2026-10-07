<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\FreightBillAllocation;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class FreightBillController extends Controller
{
    use SortableAndSearchable;

    public function __construct(protected DocumentNumberService $documentNumberService) {}

    public function index(Request $request): View
    {
        $query = FreightBill::query()
            ->with('creator')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('bill_date', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('bill_date', '<=', $request->date_to));

        $this->applySearch($query, $request->input('search'), ['freight_no', 'transporter_name', 'vehicle_no', 'lr_no']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['freight_no', 'bill_date', 'transporter_name', 'status', 'total_amount', 'created_at'],
            defaultSort: 'bill_date',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('purchasing.freight-bills.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('purchasing.freight-bills.create', [
            'invoices' => PurchaseInvoice::with('supplier')->where('status', 'posted')->latest()->limit(100)->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'bill_date' => 'required|date',
            'transporter_name' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:40',
            'lr_no' => 'nullable|string|max:60',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'allocation_basis' => 'required|in:qty,value,weight,volume,equal,manual',
            'notes' => 'nullable|string',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
        ]);

        $bill = DB::transaction(function () use ($validated, $request) {
            $tax = (float) ($validated['tax_amount'] ?? 0);
            $amount = (float) $validated['amount'];

            $bill = FreightBill::create([
                'freight_no' => $this->documentNumberService->next('FRT'),
                'bill_date' => $validated['bill_date'],
                'transporter_name' => $validated['transporter_name'] ?? null,
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'lr_no' => $validated['lr_no'] ?? null,
                'amount' => $amount,
                'tax_amount' => $tax,
                'total_amount' => $amount + $tax,
                'allocation_basis' => $validated['allocation_basis'],
                'status' => 'posted',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
            ]);

            if (! empty($validated['purchase_invoice_id'])) {
                $invoice = PurchaseInvoice::findOrFail($validated['purchase_invoice_id']);
                FreightBillAllocation::create([
                    'freight_bill_id' => $bill->id,
                    'allocatable_type' => $invoice->getMorphClass(),
                    'allocatable_id' => $invoice->id,
                    'amount' => $bill->total_amount,
                ]);
            }

            return $bill;
        });

        return $this->flashSuccess('Freight bill saved.', 'purchasing.freight-bills.show', ['freightBill' => $bill]);
    }

    public function show(FreightBill $freightBill): View
    {
        $freightBill->load(['allocations', 'creator', 'landedCosts']);

        return view('purchasing.freight-bills.show', compact('freightBill'));
    }
}
