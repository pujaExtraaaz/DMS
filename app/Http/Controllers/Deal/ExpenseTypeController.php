<?php

namespace App\Http\Controllers\Deal;

use App\Domains\Deal\Models\ExpenseType;
use App\Support\Traits\SortableAndSearchable;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseTypeController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = ExpenseType::query();

        $this->applySearch(
            $query,
            $request->input('search'),
            ['name', 'code', 'accounting_treatment']
        );

        $allowedSorts = [
            'name' => 'name',
            'code' => 'code',
            'accounting_treatment' => 'accounting_treatment',
            'is_active' => 'is_active',
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(20)->withQueryString();

        return view('expense-types.index', [
            'items' => $items,
            'filters' => $request->only(['search']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
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
