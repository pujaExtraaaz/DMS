<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Route;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Domains\Master\Models\Area;
use App\Support\Traits\SortableAndSearchable;

class RouteController extends Controller
{
    use SortableAndSearchable;

    public function index(Request $request): View
    {
        $query = Route::query()->with(['area'])
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
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

        return view('masters.routes.index', [
            'items' => $items,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'search' => $request->string('search'),
            'status' => $request->string('status'),
            'areaId' => $request->input('area_id'),
            'sort' => $sortData['sort'],
            'direction' => $sortData['direction'],
        ]);
    }

    public function create(): View
    {
        return view('masters.routes.form', array_merge(['item' => new Route], $this->formData()));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        Route::create($data);

        return $this->flashSuccess('Route created successfully.', 'masters.routes.index');
    }

    public function show(Route $route): RedirectResponse
    {
        return redirect()->route('masters.routes.edit', $route);
    }

    public function edit(Route $route): View
    {
        return view('masters.routes.form', array_merge(['item' => $route], $this->formData()));
    }

    public function update(Request $request, Route $route): RedirectResponse
    {
        $data = $this->validated($request, $route);
        $route->update($data);

        return $this->flashSuccess('Route updated successfully.', 'masters.routes.index');
    }

    public function destroy(Route $route): RedirectResponse
    {
        $route->delete();

        return $this->flashSuccess('Route deleted successfully.', 'masters.routes.index');
    }

    protected function validated(Request $request, ?Route $route = null): array
    {
        $rules = ['name' => 'required|string|max:255', 'code' => 'required|string|max:20|unique:routes,code', 'area_id' => 'nullable|exists:areas,id', 'is_active' => 'boolean'];
        if ($route) {
            if (isset($rules['code'])) {
                $rules['code'] = 'required|string|max:20|unique:routes,code,'.$route->id;
            }
            if (isset($rules['registration_no'])) {
                $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,'.$route->id;
            }
        }
        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    protected function formData(): array
    {
        $areas = \App\Domains\Master\Models\Area::where('is_active', true)->orderBy('name')->get();

        return ['areas' => $areas];
    }
}