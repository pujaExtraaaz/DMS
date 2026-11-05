<?php

namespace App\Http\Controllers\Master;

use App\Domains\Master\Models\PriceMaster;
use App\Domains\Master\Models\Product;
use App\Domains\Master\Models\ProductUom;
use App\Domains\Master\Models\Uom;
use App\Http\Controllers\Controller;
use App\Support\Traits\SortableAndSearchable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

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
        if ($blocked = $this->uomDeleteBlock($uom)) {
            return back()->with('error', $blocked);
        }

        $uom->delete();

        return $this->flashSuccess('UOM deleted successfully.', 'masters.uoms.index');
    }

    public function quickStore(Request $request): JsonResponse
    {
        $name = trim((string) $request->input('name', ''));
        if ($name === '') {
            return response()->json(['success' => false, 'message' => 'UOM name is required.'], 422);
        }
        if (mb_strlen($name) > 255) {
            return response()->json(['success' => false, 'message' => 'UOM name cannot exceed 255 characters.'], 422);
        }

        $code = strtoupper(trim((string) $request->input('code', '')));
        if ($code === '') {
            $code = strtoupper(Str::slug($name, '_'));
            $code = $code !== '' ? mb_substr($code, 0, 20) : 'UOM';
        }
        if (mb_strlen($code) > 20) {
            return response()->json(['success' => false, 'message' => 'UOM code cannot exceed 20 characters.'], 422);
        }

        $existingByCode = Uom::query()->whereRaw('UPPER(code) = ?', [$code])->first();
        if ($existingByCode) {
            return response()->json([
                'success' => true,
                'message' => 'UOM already exists and has been selected.',
                'item' => [
                    'id' => (string) $existingByCode->id,
                    'name' => $existingByCode->name.' ('.$existingByCode->code.')',
                    'code' => (string) $existingByCode->code,
                ],
            ]);
        }

        $existingByName = Uom::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->first();
        if ($existingByName) {
            return response()->json([
                'success' => true,
                'message' => 'UOM already exists and has been selected.',
                'item' => [
                    'id' => (string) $existingByName->id,
                    'name' => $existingByName->name.' ('.$existingByName->code.')',
                    'code' => (string) $existingByName->code,
                ],
            ]);
        }

        $uom = Uom::create([
            'name' => $name,
            'code' => $code,
            'is_active' => $request->boolean('is_active', true),
        ]);

        $fresh = $uom->fresh();

        return response()->json([
            'success' => true,
            'message' => 'UOM created successfully.',
            'item' => [
                'id' => (string) $fresh->id,
                'name' => $fresh->name.' ('.$fresh->code.')',
                'code' => (string) $fresh->code,
            ],
        ], 201);
    }

    public function quickDestroy(Uom $uom): JsonResponse
    {
        if ($blocked = $this->uomDeleteBlock($uom)) {
            return response()->json(['success' => false, 'message' => $blocked], 422);
        }

        $uom->delete();

        return response()->json([
            'success' => true,
            'message' => 'UOM deleted successfully.',
        ]);
    }

    protected function uomDeleteBlock(Uom $uom): ?string
    {
        if (Product::query()->where('base_uom_id', $uom->id)->orWhere('alternate_uom_id', $uom->id)->exists()) {
            return 'This UOM is used on one or more products and cannot be deleted.';
        }

        if (Schema::hasTable('product_uoms') && ProductUom::query()->where('uom_id', $uom->id)->exists()) {
            return 'This UOM is used on product unit rows and cannot be deleted.';
        }

        if (Schema::hasTable('price_masters') && PriceMaster::query()->where('uom_id', $uom->id)->exists()) {
            return 'This UOM is used on the price list and cannot be deleted.';
        }

        return null;
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