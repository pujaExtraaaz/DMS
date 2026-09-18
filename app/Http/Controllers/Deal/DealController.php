<?php

namespace App\Http\Controllers\Deal;

use App\Domains\Deal\Models\Deal;
use App\Domains\Deal\Models\DealExpense;
use App\Domains\Deal\Models\ExpenseType;
use App\Domains\Deal\Services\DealService;
use App\Domains\Deal\Services\MarginService;
use App\Domains\Master\Models\Customer;
use App\Domains\Order\Models\Order;
use App\Domains\Sales\Models\Invoice;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

class DealController extends Controller
{
    public function __construct(
        protected DealService $dealService,
        protected MarginService $marginService,
    ) {}

    public function index(Request $request): View
    {
        $items = Deal::query()
            ->with(['customer', 'invoice'])
            ->when($request->filled('customer_id'), fn ($q) => $q->where('customer_id', $request->customer_id))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('deals.index', [
            'items' => $items,
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('deals.form', [
            'item' => new Deal(['status' => 'draft']),
            'customers' => Customer::where('is_active', true)->orderBy('name')->get(),
            'invoices' => Invoice::with('customer')->orderByDesc('invoice_date')->limit(200)->get(),
            'orders' => Order::with('customer')->orderByDesc('order_date')->limit(200)->get(),
            'expenseTypes' => ExpenseType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'reference' => 'nullable|string|max:40|unique:deals,reference',
            'customer_id' => 'required|exists:customers,id',
            'invoice_id' => 'nullable|exists:invoices,id',
            'order_id' => 'nullable|exists:orders,id',
            'site_name' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,active,closed,cancelled',
            'notes' => 'nullable|string',
            'expenses' => 'nullable|array',
            'expenses.*.expense_type_id' => 'required_with:expenses|exists:expense_types,id',
            'expenses.*.amount' => 'required_with:expenses|numeric|min:0',
            'expenses.*.party_name' => 'nullable|string|max:255',
            'expenses.*.notes' => 'nullable|string',
        ]);

        $deal = $this->dealService->create($validated, $request->user());

        return $this->flashSuccess('Deal created.', 'deals.show', ['deal' => $deal]);
    }

    public function show(Deal $deal): View
    {
        $deal->load(['customer', 'invoice', 'order', 'expenses.expenseType', 'expenses.approvals']);
        $margin = $this->marginService->forDeal($deal);

        return view('deals.show', [
            'item' => $deal,
            'margin' => $margin,
            'expenseTypes' => ExpenseType::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function addExpense(Request $request, Deal $deal): RedirectResponse
    {
        $validated = $request->validate([
            'expense_type_id' => 'required|exists:expense_types,id',
            'amount' => 'required|numeric|min:0',
            'party_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $this->dealService->addExpense($deal, $validated, $request->user());

        return $this->flashSuccess('Expense added for approval.');
    }

    public function approveExpense(Request $request, DealExpense $deal_expense): RedirectResponse
    {
        try {
            $this->dealService->approveExpense($deal_expense, $request->user(), $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Expense approved.');
    }

    public function rejectExpense(Request $request, DealExpense $deal_expense): RedirectResponse
    {
        try {
            $this->dealService->rejectExpense($deal_expense, $request->user(), $request->input('reason'));
        } catch (InvalidArgumentException $e) {
            return $this->flashError($e->getMessage());
        }

        return $this->flashSuccess('Expense rejected.');
    }

    public function refreshMargin(Deal $deal): RedirectResponse
    {
        $this->marginService->refreshDeal($deal);

        return $this->flashSuccess('Net margin refreshed.');
    }
}
