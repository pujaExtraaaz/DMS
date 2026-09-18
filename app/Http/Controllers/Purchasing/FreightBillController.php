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

class FreightBillController extends Controller
{
    public function __construct(protected DocumentNumberService $documentNumberService) {}

    public function index(): View
    {
        $items = FreightBill::query()->with('creator')->latest('bill_date')->paginate(15);

        return view('purchasing.freight-bills.index', compact('items'));
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
