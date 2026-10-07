<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class UomController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Uom::query()
            ->when($request->filled('status'), function ($q) use ($request) {
                if ($request->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($request->status === 'inactive') {
                    $q->where('is_active', false);
                }
            });

        $this->applySearch($query, $request->input('search'), ['name', 'code']);

        $sortData = $this->applySorting(
            $query,
            $request,
            ['name', 'code', 'is_active', 'created_at'],
            defaultSort: 'name',
            defaultDirection: 'asc'
        );

        $items = $query->paginate(15)->withQueryString();

        return view('masters.uoms.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.uoms.form', array_merge(['item' => new Uom], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Uom::create($data);

        return $this->flashSuccess('UOM created successfully.', 'masters.uoms.index');
    }

    public function show(Uom $uom): RedirectResponse
    {
        return redirect()->route('masters.uoms.edit', $uom);
    }

    public function edit(Uom $uom): View
    {
        return view('masters.uoms.form', array_merge(['item' => $uom], $this->formData()));
    }

    public function update(Request $request, Uom $uom): RedirectResponse
    {
        $data = $this->validated($request, $uom);
        $uom->update($data);

        return $this->flashSuccess('UOM updated successfully.', 'masters.uoms.index');
    }

    public function destroy(Uom $uom): RedirectResponse
    {
        $uom->delete();

        return $this->flashSuccess('UOM deleted successfully.', 'masters.uoms.index');
    }

    protected function validated(Request $request, ?Uom $uom = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:uoms,code', 'is_active' => 'boolean'];
        if ($uom) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:uoms,code,'.$uom->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$uom->id;
            }
        }
        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function formData(): array
    {
        return [];
    }
}