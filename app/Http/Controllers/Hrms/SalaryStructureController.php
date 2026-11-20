<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\Employee;
use App\Domains\Hrms\Models\PayHead;
use App\Domains\Hrms\Models\SalaryStructure;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalaryStructureController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = SalaryStructure::query()
            ->with(['employee.department', 'lines.payHead'])
            ->when($request->filled('employee_id'), fn ($q) => $q->where('employee_id', $request->employee_id))
            ->when($request->filled('is_active'), fn ($q) => $q->where('is_active', $request->boolean('is_active')));

        $this->applySearch(
            $query,
            $request->input('search'),
            ['name', 'notes'],
            ['employee' => ['name', 'employee_code']]
        );

        $allowedSorts = [
            'employee' => 'employee_id',
            'effective_from' => 'effective_from',
            'basic' => 'basic',
            'is_active' => 'is_active',
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'effective_from',
            defaultDirection: 'desc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('hrms.salary-structures.index', [
            'items' => $items,
            'employees' => Employee::orderBy('name')->get(['id', 'name', 'employee_code']),
            'filters' => $request->only(['search', 'employee_id', 'is_active']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(Request $request): View
    {
        $payHeads = PayHead::active()->ordered()->get();
        $defaultLines = $payHeads->map(fn (PayHead $head) => [
            'pay_head_id' => $head->id,
            'amount' => '0.00',
        ])->values()->all();

        return view('hrms.salary-structures.form', [
            'item' => new SalaryStructure([
                'employee_id' => $request->integer('employee_id') ?: null,
                'effective_from' => now()->toDateString(),
                'is_active' => true,
            ]),
            'employees' => Employee::where('status', 'active')->orderBy('name')->get(['id', 'name', 'employee_code']),
            'payHeads' => $payHeads,
            'lines' => old('lines', $defaultLines),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $structure = DB::transaction(function () use ($data) {
            if (! empty($data['is_active'])) {
                $from = $data['effective_from'] ?? now()->toDateString();
                SalaryStructure::where('employee_id', $data['employee_id'])
                    ->where('is_active', true)
                    ->get()
                    ->each(function (SalaryStructure $old) use ($from) {
                        $old->update([
                            'is_active' => false,
                            'effective_to' => $old->effective_to ?: Carbon::parse($from)->subDay()->toDateString(),
                        ]);
                    });
            }

            $structure = SalaryStructure::create(collect($data)->except('lines')->all());
            $this->syncLines($structure, $data['lines']);
            $structure->load('lines.payHead');
            $structure->syncLegacyTotalsFromLines();

            return $structure;
        });

        return $this->flashSuccess('Salary structure created.', 'hrms.salary-structures.history', ['employee' => $structure->employee_id]);
    }

    public function edit(SalaryStructure $salary_structure): View
    {
        $salary_structure->load('lines.payHead');
        $payHeads = PayHead::active()->ordered()->get();

        $lines = old('lines');
        if ($lines === null) {
            $lines = $salary_structure->lines->map(fn ($line) => [
                'pay_head_id' => $line->pay_head_id,
                'amount' => (string) $line->amount,
            ])->values()->all();

            // Ensure every active pay head appears (missing ones at 0).
            $existingIds = collect($lines)->pluck('pay_head_id')->all();
            foreach ($payHeads as $head) {
                if (! in_array($head->id, $existingIds, true)) {
                    $lines[] = ['pay_head_id' => $head->id, 'amount' => '0.00'];
                }
            }
        }

        return view('hrms.salary-structures.form', [
            'item' => $salary_structure,
            'employees' => Employee::orderBy('name')->get(['id', 'name', 'employee_code']),
            'payHeads' => $payHeads,
            'lines' => $lines,
        ]);
    }

    public function update(Request $request, SalaryStructure $salary_structure): RedirectResponse
    {
        $data = $this->validated($request, $salary_structure);

        DB::transaction(function () use ($data, $salary_structure) {
            if (! empty($data['is_active'])) {
                SalaryStructure::where('employee_id', $data['employee_id'])
                    ->where('id', '!=', $salary_structure->id)
                    ->where('is_active', true)
                    ->update(['is_active' => false]);
            }

            $salary_structure->update(collect($data)->except('lines')->all());
            $this->syncLines($salary_structure, $data['lines']);
            $salary_structure->load('lines.payHead');
            $salary_structure->syncLegacyTotalsFromLines();
        });

        return $this->flashSuccess('Salary structure updated.', 'hrms.salary-structures.index');
    }

    public function destroy(SalaryStructure $salary_structure): RedirectResponse
    {
        $salary_structure->delete();

        return $this->flashSuccess('Salary structure deleted.', 'hrms.salary-structures.index');
    }

    public function history(Employee $employee): View
    {
        $revisions = SalaryStructure::query()
            ->with('lines.payHead')
            ->where('employee_id', $employee->id)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->get();

        return view('hrms.salary-structures.history', [
            'employee' => $employee,
            'revisions' => $revisions,
        ]);
    }

    protected function validated(Request $request, ?SalaryStructure $structure = null): array
    {
        $data = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'name' => 'nullable|string|max:255',
            'effective_from' => 'nullable|date',
            'effective_to' => 'nullable|date|after_or_equal:effective_from',
            'is_active' => 'boolean',
            'notes' => 'nullable|string|max:2000',
            'lines' => 'required|array|min:1',
            'lines.*.pay_head_id' => 'required|exists:pay_heads,id|distinct',
            'lines.*.amount' => 'required|numeric|min:0',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['basic'] = 0;
        $data['hra'] = 0;
        $data['allowances'] = 0;
        $data['deductions'] = 0;

        return $data;
    }

    /**
     * @param  list<array{pay_head_id: mixed, amount: mixed}>  $lines
     */
    protected function syncLines(SalaryStructure $structure, array $lines): void
    {
        $structure->lines()->delete();

        foreach ($lines as $line) {
            $structure->lines()->create([
                'pay_head_id' => (int) $line['pay_head_id'],
                'amount' => (float) ($line['amount'] ?? 0),
            ]);
        }
    }
}
