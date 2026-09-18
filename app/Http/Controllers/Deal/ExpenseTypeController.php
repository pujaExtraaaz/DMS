<?php

namespace App\Http\Controllers\Deal;

use App\Domains\Deal\Models\ExpenseType;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseTypeController extends Controller
{
    public function index(Request $request): View
    {
        $items = ExpenseType::query()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $q->where('name', 'like', '%'.$request->search.'%')
                    ->orWhere('code', 'like', '%'.$request->search.'%');
            }))
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('expense-types.index', ['items' => $items, 'search' => $request->string('search')]);
    }

    public function create(): View
    {
        return view('expense-types.form', [
            'item' => new ExpenseType(['accounting_treatment' => 'deal_expense', 'is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        ExpenseType::create($this->validated($request));

        return $this->flashSuccess('Expense type created.', 'expense-types.index');
    }

    public function edit(ExpenseType $expense_type): View
    {
        return view('expense-types.form', ['item' => $expense_type]);
    }

    public function update(Request $request, ExpenseType $expense_type): RedirectResponse
    {
        $expense_type->update($this->validated($request, $expense_type));

        return $this->flashSuccess('Expense type updated.', 'expense-types.index');
    }

    protected function validated(Request $request, ?ExpenseType $item = null): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:30|unique:expense_types,code'.($item ? ','.$item->id : ''),
            'accounting_treatment' => 'required|in:trade_discount,deal_expense,landed_cost',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
