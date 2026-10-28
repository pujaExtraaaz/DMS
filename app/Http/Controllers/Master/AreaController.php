<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\Area;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
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
        if ($blocked = $this->areaDeleteBlock($area)) {
            return back()->with('error', $blocked);
        }

        $area->delete();

        return $this->flashSuccess('Area deleted successfully.', 'masters.areas.index');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $name = $this->cleanName($request->input('name'));
        if ($error = $this->nameError($name, 'Area')) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $exists = Area::query()->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'An area with this name already exists.'], 422);
        }

        $area = Area::create([
            'name' => $name,
            'code' => $this->uniqueCode(Area::class, $name, 'AREA'),
            'is_active' => true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Area created successfully.',
            'item' => ['id' => $area->id, 'name' => $area->fresh()->name, 'code' => $area->code],
        ], 201);
    }

    public function quickUpdate(Request $request, Area $area): JsonResponse
    {
        $name = $this->cleanName($request->input('name'));
        if ($error = $this->nameError($name, 'Area')) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $exists = Area::query()
            ->where('id', '!=', $area->id)
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->exists();
        if ($exists) {
            return response()->json(['success' => false, 'message' => 'An area with this name already exists.'], 422);
        }

        $area->update(['name' => $name]);

        return response()->json([
            'success' => true,
            'message' => 'Area updated successfully.',
            'item' => ['id' => $area->id, 'name' => $area->fresh()->name, 'code' => $area->code],
        ]);
    }

    public function quickDestroy(Area $area): JsonResponse
    {
        if ($blocked = $this->areaDeleteBlock($area)) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        $area->delete();

        return response()->json([
            'success' => true,
            'message' => 'Area deleted successfully.',
        ]);
    }

    protected function areaDeleteBlock(Area $area): ?string
    {
        if (Schema::hasTable('region_brand_policies') && DB::table('region_brand_policies')->where('area_id', $area->id)->exists()) {
            return 'This area is used on a region brand policy and cannot be deleted.';
        }

        return null;
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

    protected function cleanName(mixed $value): string
    {
        return trim((string) $value);
    }

    protected function nameError(string $name, string $label): ?string
    {
        if ($name === '') {
            return $label.' name is required.';
        }

        if (mb_strlen($name) > 255) {
            return $label.' name cannot exceed 255 characters.';
        }

        return null;
    }

    protected function uniqueCode(string $modelClass, string $name, string $fallback): string
    {
        $baseCode = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $name) ?? '');
        if ($baseCode === '') {
            $baseCode = $fallback;
        }
        $baseCode = substr($baseCode, 0, 15);
        $code = $baseCode;
        $suffix = 1;
        while ($modelClass::query()->where('code', $code)->exists()) {
            $code = substr($baseCode, 0, 14).$suffix;
            $suffix++;
        }

        return $code;
    }
}