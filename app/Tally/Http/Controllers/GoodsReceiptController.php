<?php

namespace Tally\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Tally\Context\WorkspaceContext;
use Tally\Invoicing\InvoiceKind;
use Tally\Invoicing\PartyDirectory;
use Tally\Models\GoodsReceipt;
use Tally\Models\PurchaseOrder;
use Tally\Purchasing\GoodsReceiptService;

class GoodsReceiptController extends Controller
{
    public function index(WorkspaceContext $context): View
    {
        $company = $context->company();
        if (! $company) {
            return view('tally::workspace.needs-company', ['title' => 'Goods receipts', 'message' => 'Select a company first.']);
        }

        return view('tally::goods-receipts.index', [
            'company' => $company,
            'receipts' => GoodsReceipt::query()
                ->with('supplier')
                ->where('company_id', $company->id)
                ->when($context->financialYear(), fn ($q) => $q->where('financial_year_id', $context->financialYear()->id))
                ->latest('receipt_date')
                ->paginate(25),
        ]);
    }

    public function create(WorkspaceContext $context, PartyDirectory $parties): View
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        return view('tally::goods-receipts.form', [
            'company' => $company,
            'receipt' => new GoodsReceipt(['receipt_date' => now()->toDateString(), 'status' => 'draft', 'receipt_kind' => 'goods']),
            'suppliers' => $parties->options($company, InvoiceKind::Purchase),
            'purchaseOrders' => PurchaseOrder::query()
                ->where('company_id', $company->id)
                ->orderByDesc('order_date')
                ->limit(100)
                ->get(),
        ]);
    }

    public function store(Request $request, WorkspaceContext $context, GoodsReceiptService $service): RedirectResponse
    {
        $company = $context->company();
        $branch = $context->branch();
        $year = $context->financialYear();
        abort_unless($company && $branch && $year, 404);

        $data = $request->validate([
            'receipt_date' => ['required', 'date'],
            'purchase_order_id' => ['nullable', 'integer'],
            'supplier_ledger_id' => ['nullable', 'integer'],
            'godown_id' => ['nullable', 'integer'],
            'receipt_kind' => ['nullable', Rule::in(['goods', 'service'])],
            'status' => ['nullable', Rule::in(['draft', 'posted', 'cancelled'])],
            'narration' => ['nullable', 'string', 'max:500'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_name' => ['required', 'string', 'max:200'],
            'lines.*.product_id' => ['nullable', 'integer'],
            'lines.*.ordered_qty' => ['nullable'],
            'lines.*.received_qty' => ['required'],
            'lines.*.rate' => ['nullable'],
        ]);

        $receipt = $service->save($company, $branch, $year, $request->user(), $data);

        return redirect()->to(tally_route('books.tally.goods-receipts.show', $receipt))
            ->with('status', 'Goods receipt saved.');
    }

    public function show(WorkspaceContext $context, GoodsReceipt $goodsReceipt): View
    {
        abort_unless($context->company() && $goodsReceipt->company_id === $context->company()->id, 404);
        $goodsReceipt->load(['lines', 'supplier', 'purchaseOrder', 'stockTransaction']);

        return view('tally::goods-receipts.show', [
            'company' => $context->company(),
            'receipt' => $goodsReceipt,
        ]);
    }

    public function createBill(Request $request, WorkspaceContext $context, GoodsReceipt $goodsReceipt, GoodsReceiptService $service): RedirectResponse
    {
        abort_unless($context->company() && $goodsReceipt->company_id === $context->company()->id, 404);

        $invoice = $service->createPurchaseBill($goodsReceipt, $request->user());

        return redirect()->to(tally_route('books.tally.invoices.purchase.show', $invoice))
            ->with('status', 'Purchase bill created from goods receipt.');
    }

    public function post(Request $request, WorkspaceContext $context, GoodsReceipt $goodsReceipt, GoodsReceiptService $service): RedirectResponse
    {
        abort_unless($context->company() && $goodsReceipt->company_id === $context->company()->id, 404);

        $service->post($goodsReceipt, $request->user());

        return redirect()->to(tally_route('books.tally.goods-receipts.show', $goodsReceipt))
            ->with('status', 'Goods receipt posted.');
    }
}
