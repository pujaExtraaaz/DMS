<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Area;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Support\Traits\SortableAndSearchable;

class AreaController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Area::query()
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

        return view('masters.areas.index', [
            'items' => $items,
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.areas.form', array_merge(['item' => new Area], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Area::create($data);

        return $this->flashSuccess('Area created successfully.', 'masters.areas.index');
    }

    public function show(Area $area): RedirectResponse
    {
        return redirect()->route('masters.areas.edit', $area);
    }

    public function edit(Area $area): View
    {
        return view('masters.areas.form', array_merge(['item' => $area], $this->formData()));
    }

    public function update(Request $request, Area $area): RedirectResponse
    {
        $data = $this->validated($request, $area);
        $area->update($data);

        return $this->flashSuccess('Area updated successfully.', 'masters.areas.index');
    }

    public function destroy(Area $area): RedirectResponse
    {
        $area->delete();

        return $this->flashSuccess('Area deleted successfully.', 'masters.areas.index');
    }

    protected function validated(Request $request, ?Area $area = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:areas,code', 'is_active' => 'boolean'];
        if ($area) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:areas,code,'.$area->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$area->id;
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