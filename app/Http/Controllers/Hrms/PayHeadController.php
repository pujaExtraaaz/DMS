<?php

namespace App\Http\Controllers\Hrms;

use App\Domains\Hrms\Models\PayHead;
use App\Domains\Organization\Models\Company;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PayHeadController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = PayHead::query();

        $this->applySearch($query, $request->input('search'), ['name', 'code']);

        $allowedSorts = [
            'name' => 'name',
            'code' => 'code',
            'type' => 'type',
            'sort_order' => 'sort_order',
            'is_active' => 'is_active',
        ];

        $sortData = $this->applySorting(
            $query,
            $request,
            $allowedSorts,
            defaultSort: 'sort_order',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(20)->withQueryString();

        return view('hrms.pay-heads.index', [
            'items' => $items,
            'filters' => $request->only(['search']),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        $defaultCompanyId = auth()->user()?->company_id ?? Company::defaultCompanyId();

        return view('hrms.pay-heads.form', [
            'item' => new PayHead([
                'company_id' => $defaultCompanyId,
                'type' => PayHead::TYPE_EARNING,
                'is_active' => true,
                'sort_order' => 100,
            ]),
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        PayHead::create($this->validated($request));

        return $this->flashSuccess('Pay head created.', 'hrms.pay-heads.index');
    }

    public function edit(PayHead $pay_head): View
    {
        return view('hrms.pay-heads.form', [
            'item' => $pay_head,
            'companies' => Company::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, PayHead $pay_head): RedirectResponse
    {
        $data = $this->validated($request, $pay_head);

        if ($pay_head->is_system) {
            // System heads keep code/type/system flag; name/active/sort can change.
            unset($data['code'], $data['type'], $data['is_system'], $data['company_id']);
        }

        $pay_head->update($data);

        return $this->flashSuccess('Pay head updated.', 'hrms.pay-heads.index');
    }

    public function destroy(PayHead $pay_head): RedirectResponse
    {
        if ($pay_head->is_system) {
            return $this->flashError('Standard pay heads cannot be deleted.', 'hrms.pay-heads.index');
        }

        if ($pay_head->structureLines()->exists()) {
            return $this->flashError('Pay head is used in salary structures and cannot be deleted.', 'hrms.pay-heads.index');
        }

        $pay_head->delete();

        return $this->flashSuccess('Pay head deleted.', 'hrms.pay-heads.index');
    }

    protected function validated(Request $request, ?PayHead $payHead = null): array
    {
        $companyId = $request->input('company_id') ?: (auth()->user()?->company_id);

        $data = $request->validate([
            'company_id' => 'nullable|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => [
                'nullable',
                'string',
                'max:40',
                Rule::unique('pay_heads', 'code')
                    ->where(fn ($q) => $q->where('company_id', $companyId))
                    ->ignore($payHead?->id),
            ],
            'type' => 'required|in:earning,deduction',
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_system'] = false;
        $data['company_id'] = $data['company_id'] ?? $companyId;
        $data['sort_order'] = $data['sort_order'] ?? 100;

        if (! empty($data['code'])) {
            $data['code'] = strtoupper(preg_replace('/\s+/', '_', $data['code']));
        }

        return $data;
    }
}
