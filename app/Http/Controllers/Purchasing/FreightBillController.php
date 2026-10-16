<?php

namespace App\Http\Controllers\Purchasing;

use App\Domains\Purchasing\Models\FreightBill;
use App\Domains\Purchasing\Models\FreightBillAllocation;
use App\Domains\Purchasing\Models\PurchaseInvoice;
use App\Http\Controllers\Controller;
use App\Support\DocumentNumberService;
use Illuminate\Http\JsonResponse;
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

    public function edit(FreightBill $freightBill): View|RedirectResponse
    {
        $freightBill->load(['allocations', 'landedCosts']);

        // Check if already allocated to landed costs
        if ($freightBill->status === 'allocated' || $freightBill->landedCosts()->exists()) {
            return redirect()->route('purchasing.freight-bills.show', $freightBill)
                ->with('error', "Cannot edit freight bill '{$freightBill->freight_no}' because it has already been allocated to landed cost records. Reversing the landed cost allocation is required before modifying this bill.");
        }

        $linkedInvoiceId = $freightBill->allocations->first()?->allocatable_id;

        return view('purchasing.freight-bills.edit', [
            'freightBill' => $freightBill,
            'linkedInvoiceId' => $linkedInvoiceId,
            'invoices' => PurchaseInvoice::with('supplier')->whereIn('status', ['posted', 'draft'])->latest()->limit(100)->get(),
        ]);
    }

    public function update(Request $request, FreightBill $freightBill): RedirectResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('purchases.manage') &&
            ! $user->can('purchases.edit') &&
            ! $user->can('purchase-orders.manage') &&
            ! $user->can('purchase-orders.edit')) {
            return back()->with('error', 'Unauthorized: You do not have permission to edit freight bills.');
        }

        // Check if already allocated to landed costs
        if ($freightBill->status === 'allocated' || $freightBill->landedCosts()->exists()) {
            return redirect()->route('purchasing.freight-bills.show', $freightBill)
                ->with('error', "Cannot edit freight bill '{$freightBill->freight_no}' because it has already been allocated to landed costs.");
        }

        $validated = $request->validate([
            'freight_no' => 'required|string|max:40|unique:freight_bills,freight_no,' . $freightBill->id,
            'bill_date' => 'required|date',
            'transporter_name' => 'nullable|string|max:255',
            'vehicle_no' => 'nullable|string|max:40',
            'lr_no' => 'nullable|string|max:60',
            'amount' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'allocation_basis' => 'required|in:qty,value,weight,volume,equal,manual',
            'status' => 'required|in:draft,posted',
            'notes' => 'nullable|string',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
        ]);

        DB::transaction(function () use ($validated, $freightBill) {
            $amount = (float) $validated['amount'];
            $tax = (float) ($validated['tax_amount'] ?? 0);
            $total = $amount + $tax;

            $freightBill->update([
                'freight_no' => $validated['freight_no'],
                'bill_date' => $validated['bill_date'],
                'transporter_name' => $validated['transporter_name'] ?? null,
                'vehicle_no' => $validated['vehicle_no'] ?? null,
                'lr_no' => $validated['lr_no'] ?? null,
                'amount' => $amount,
                'tax_amount' => $tax,
                'total_amount' => $total,
                'allocation_basis' => $validated['allocation_basis'],
                'status' => $validated['status'],
                'notes' => $validated['notes'] ?? null,
            ]);

            if (! empty($validated['purchase_invoice_id'])) {
                $invoice = PurchaseInvoice::findOrFail($validated['purchase_invoice_id']);
                $allocation = $freightBill->allocations()->first();

                if ($allocation) {
                    $allocation->update([
                        'allocatable_type' => $invoice->getMorphClass(),
                        'allocatable_id' => $invoice->id,
                        'amount' => $total,
                    ]);
                } else {
                    FreightBillAllocation::create([
                        'freight_bill_id' => $freightBill->id,
                        'allocatable_type' => $invoice->getMorphClass(),
                        'allocatable_id' => $invoice->id,
                        'amount' => $total,
                    ]);
                }
            } else {
                $freightBill->allocations()->delete();
            }
        });

        return $this->flashSuccess("Freight bill '{$freightBill->freight_no}' updated successfully.", 'purchasing.freight-bills.show', ['freightBill' => $freightBill])
            ->with('success', "Freight bill '{$freightBill->freight_no}' updated successfully.");
    }

    public function destroy(Request $request, FreightBill $freightBill): RedirectResponse|JsonResponse
    {
        $user = $request->user() ?? auth()->user();

        // Enforce authorization
        if ($user && ! $user->hasAnyRole(['super-admin', 'owner', 'client-admin']) &&
            ! $user->can('purchases.manage') &&
            ! $user->can('purchases.delete') &&
            ! $user->can('purchase-orders.manage') &&
            ! $user->can('purchase-orders.delete')) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized: You do not have permission to delete freight bills.',
                ], 403);
            }

            return back()->with('error', 'Unauthorized: You do not have permission to delete freight bills.');
        }

        $freightBill->loadMissing(['allocations', 'landedCosts']);

        // Check if allocated
        if ($freightBill->status === 'allocated' || $freightBill->landedCosts()->exists()) {
            $message = "Cannot delete freight bill '{$freightBill->freight_no}' because it has already been allocated to landed costs. Reversing the landed cost allocation is required before deletion.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->with('error', $message);
        }

        $freightNo = $freightBill->freight_no;

        try {
            DB::transaction(function () use ($freightBill) {
                // Delete allocations
                $freightBill->allocations()->delete();
                // Delete freight bill
                $freightBill->delete();
            });

            $message = "Freight bill '{$freightNo}' deleted successfully.";

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                ]);
            }

            return $this->flashSuccess($message, 'purchasing.freight-bills.index')
                ->with('success', $message);
        } catch (\Throwable $e) {
            report($e);

            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete freight bill: ' . $e->getMessage(),
                ], 500);
            }

            return $this->flashError('Failed to delete freight bill: ' . $e->getMessage(), 'purchasing.freight-bills.index')
                ->with('error', 'Failed to delete freight bill: ' . $e->getMessage());
        }
    }
}
