<?php

namespace App\Domains\Sales\Services;

use App\Domains\Catalog\Models\Brand;
use App\Domains\Master\Models\Area;
use App\Domains\Master\Models\Customer;
use App\Domains\Master\Models\Product;
use App\Domains\Sales\Models\RegionBrandPolicy;
use App\Support\AuditLogService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class RegionBrandPolicyService
{
    public function __construct(protected AuditLogService $auditLogService) {}

    public function upsert(array $data): RegionBrandPolicy
    {
        return RegionBrandPolicy::query()->updateOrCreate(
            [
                'area_id' => $data['area_id'],
                'brand_id' => $data['brand_id'],
            ],
            [
                'is_allowed' => (bool) ($data['is_allowed'] ?? true),
                'max_discount_percent' => $data['max_discount_percent'] ?? null,
                'requires_approval' => (bool) ($data['requires_approval'] ?? false),
                'notes' => $data['notes'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]
        );
    }

    /**
     * @param  array<int, array{product_id: int}>  $items
     */
    public function assertItemsAllowed(Customer $customer, array $items, ?string $overrideReason = null): void
    {
        if (! $customer->area_id) {
            return;
        }

        $productIds = collect($items)->pluck('product_id')->map(fn ($id) => (int) $id)->unique()->values();
        $products = Product::query()->whereIn('id', $productIds)->get()->keyBy('id');

        $brandIds = $products->pluck('brand_id')->filter()->unique()->values();
        if ($brandIds->isEmpty()) {
            return;
        }

        /** @var Collection<int, RegionBrandPolicy> $policies */
        $policies = RegionBrandPolicy::query()
            ->where('area_id', $customer->area_id)
            ->whereIn('brand_id', $brandIds)
            ->where('is_active', true)
            ->get()
            ->keyBy('brand_id');

        $violations = [];

        foreach ($products as $product) {
            if (! $product->brand_id) {
                continue;
            }

            $policy = $policies->get($product->brand_id);
            if (! $policy) {
                continue;
            }

            if (! $policy->is_allowed) {
                $brand = Brand::find($product->brand_id);
                $area = Area::find($customer->area_id);
                $violations[] = sprintf(
                    '%s is restricted for region %s.',
                    $brand?->name ?? 'Brand',
                    $area?->name ?? 'selected'
                );
            }
        }

        if ($violations === []) {
            return;
        }

        if ($overrideReason && trim($overrideReason) !== '') {
            $this->auditLogService->approval(
                $customer,
                'region_brand_override',
                'approved',
                $overrideReason,
                ['violations' => $violations]
            );

            return;
        }

        throw ValidationException::withMessages([
            'region_brand_policy' => implode(' ', $violations),
        ]);
    }

    public function forArea(?int $areaId): Collection
    {
        return RegionBrandPolicy::query()
            ->with(['area', 'brand'])
            ->when($areaId, fn ($q) => $q->where('area_id', $areaId))
            ->orderBy('area_id')
            ->orderBy('brand_id')
            ->get();
    }
}
