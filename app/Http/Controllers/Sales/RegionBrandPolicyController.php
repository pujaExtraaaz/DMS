<?php

namespace App\Http\Controllers\Sales;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Area;
use App\Domains\Sales\Models\RegionBrandPolicy;
use App\Domains\Sales\Services\RegionBrandPolicyService;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RegionBrandPolicyController extends Controller
{
    public function __construct(protected RegionBrandPolicyService $regionBrandPolicyService) {}

    public function index(Request $request): View
    {
        $items = RegionBrandPolicy::query()
            ->with(['area', 'brand'])
            ->when($request->filled('area_id'), fn ($q) => $q->where('area_id', $request->area_id))
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->brand_id))
            ->orderBy('area_id')
            ->paginate(20)
            ->withQueryString();

        return view('sales.region-policies.index', [
            'items' => $items,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('sales.region-policies.form', [
            'item' => new RegionBrandPolicy(['is_allowed' => true, 'is_active' => true]),
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->regionBrandPolicyService->upsert($this->validated($request));

        return $this->flashSuccess('Region brand policy saved.', 'region-policies.index');
    }

    public function edit(RegionBrandPolicy $region_policy): View
    {
        return view('sales.region-policies.form', [
            'item' => $region_policy,
            'areas' => Area::where('is_active', true)->orderBy('name')->get(),
            'brands' => Brand::where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, RegionBrandPolicy $region_policy): RedirectResponse
    {
        $data = $this->validated($request);
        $region_policy->update($data);

        return $this->flashSuccess('Region brand policy updated.', 'region-policies.index');
    }

    public function destroy(RegionBrandPolicy $region_policy): RedirectResponse
    {
        $region_policy->delete();

        return $this->flashSuccess('Region brand policy deleted.', 'region-policies.index');
    }

    protected function validated(Request $request): array
    {
        $data = $request->validate([
            'area_id' => 'required|exists:areas,id',
            'brand_id' => 'required|exists:brands,id',
            'is_allowed' => 'nullable|boolean',
            'max_discount_percent' => 'nullable|numeric|min:0|max:100',
            'requires_approval' => 'nullable|boolean',
            'notes' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_allowed'] = $request->boolean('is_allowed');
        $data['requires_approval'] = $request->boolean('requires_approval');
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
